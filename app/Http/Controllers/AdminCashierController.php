<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\ProductOffering;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCashierController extends Controller
{
    public function index(): View
    {
        return view('admin.cashier.index');
    }

    public function transactions(Request $request): View
    {
        $query = Transaction::with(['user', 'cashier'])->latest('paid_at');

        return view('admin.transactions.index', [
            'transactions' => $query->paginate(15, ['*'], 'transactions_page')->withQueryString(),
        ]);
    }

    public function reports(Request $request): View
    {
        $filters = $this->transactionFilters($request);
        $query = $this->transactionQuery($filters);
        $monthQuery = Transaction::query()->with('productOffering')->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()]);

        return view('admin.transactions.reports', [
            'transactions' => $query->with('productOffering')->latest('paid_at')->paginate(15, ['*'], 'transactions_page')->withQueryString(),
            'transactionStats' => [
                'monthCount' => (clone $monthQuery)->count(),
                'monthCapital' => (clone $monthQuery)->get()->sum(fn (Transaction $transaction): float => (float) ($transaction->productOffering?->price ?? 0)),
                'monthActive' => (clone $monthQuery)->where('status', 'paid')->where('expires_at', '>', now())->count(),
                'filteredCount' => (clone $query)->count(),
                'filteredRevenue' => (clone $query)->sum('total_amount'),
            ],
            'filters' => $filters,
            'products' => ProductOffering::query()->with(['product', 'accountType'])->orderBy('product_id')->get(),
            'cashiers' => User::query()->where('role', 'admin')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function exportTransactionsPdf(Request $request)
    {
        $filters = $this->transactionFilters($request);
        $transactions = $this->transactionQuery($filters)->with('productOffering')->latest('paid_at')->get();

        return Pdf::loadView('admin.transactions.pdf', [
            'transactions' => $transactions,
            'filters' => $filters,
            'total' => $transactions->sum(fn (Transaction $transaction): float => (float) ($transaction->productOffering?->price ?? 0)),
        ])->setPaper('a4', 'landscape')->download('laporan-transaksi-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function transactionFilters(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'customer' => ['nullable', 'string', 'max:255'],
            'product_offering_id' => ['nullable', 'integer', 'exists:product_offerings,id'],
            'status' => ['nullable', Rule::in(['paid', 'pending', 'cancelled'])],
            'cashier_id' => ['nullable', 'integer', 'exists:users,id'],
            'amount_min' => ['nullable', 'numeric', 'min:0'],
            'amount_max' => ['nullable', 'numeric', 'min:0', 'gte:amount_min'],
        ]);
    }

    private function transactionQuery(array $filters)
    {
        return Transaction::query()->with(['user', 'cashier'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('paid_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('paid_at', '<=', $date))
            ->when($filters['customer'] ?? null, fn ($query, $term) => $query->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->when($filters['product_offering_id'] ?? null, fn ($query, $id) => $query->where('product_offering_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['cashier_id'] ?? null, fn ($query, $id) => $query->where('cashier_id', $id))
            ->when($filters['amount_min'] ?? null, fn ($query, $amount) => $query->where('total_amount', '>=', $amount))
            ->when($filters['amount_max'] ?? null, fn ($query, $amount) => $query->where('total_amount', '<=', $amount));
    }

    public function users(Request $request): JsonResponse
    {
        $query = User::query()
            ->where('role', 'client')
            ->when($request->filled('q'), function ($builder) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';
                $builder->where(fn ($builder) => $builder
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone_number', 'like', $term));
            })
            ->orderBy('name');

        $users = $query->paginate(10)->withQueryString();

        return response()->json([
            'data' => $users->getCollection()->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
            ]),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function offerings(): JsonResponse
    {
        $offerings = ProductOffering::query()
            ->with(['product:id,name', 'accountType:id,name,max_users'])
            ->withCount(['subscriptions as active_subscriptions_count' => fn ($query) => $query
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))])
            ->where('status', true)
            ->orderBy('product_id')
            ->get()
            ->map(fn ($offering): array => [
                'id' => $offering->id,
                'label' => $offering->product->name.' - '.$offering->accountType->name,
                'product' => $offering->product->name,
                'account_type' => $offering->accountType->name,
                'price' => (float) $offering->price,
                'duration_days' => $offering->duration_days,
                'max_users' => $offering->accountType->max_users,
                'email' => $offering->email,
                'used_quota' => $offering->active_subscriptions_count,
                'quota' => max(0, $offering->accountType->max_users - $offering->active_subscriptions_count),
            ]);

        return response()->json(['data' => $offerings]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_type' => ['required', Rule::in(['existing', 'new'])],
            'user_id' => ['required_if:customer_type,existing', 'nullable', Rule::exists('users', 'id')->where('role', 'client')],
            'name' => ['required_if:customer_type,new', 'nullable', 'string', 'max:255'],
            'email' => ['required_if:customer_type,new', 'nullable', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'product_offering_id' => ['required', 'exists:product_offerings,id'],
            'total_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $result = DB::transaction(function () use ($data, $request): array {
            $offering = ProductOffering::query()
                ->with(['product', 'accountType'])
                ->lockForUpdate()
                ->findOrFail($data['product_offering_id']);

            abort_unless($offering->status, 422, 'Offering ini sedang tidak aktif.');

            $temporaryPassword = null;
            if ($data['customer_type'] === 'new') {
                $temporaryPassword = Str::random(16);
                $customer = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone_number' => $data['phone_number'] ?? null,
                    'password' => $temporaryPassword,
                    'role' => 'client',
                ]);
            } else {
                $customer = User::query()->where('role', 'client')->findOrFail($data['user_id']);
            }

            $now = now();
            $activeSubscription = Subscription::query()
                ->where('product_offering_id', $offering->id)
                ->where('user_id', $customer->id)
                ->where('status', 'active')
                ->where('expires_at', '>', $now)
                ->latest('expires_at')
                ->lockForUpdate()
                ->first();

            if (! $activeSubscription) {
                $activeSeats = Subscription::query()
                    ->where('product_offering_id', $offering->id)
                    ->where('status', 'active')
                    ->where('expires_at', '>', $now)
                    ->count();

                if ($activeSeats >= $offering->accountType->max_users) {
                    abort(422, 'Kapasitas user untuk offering ini sudah penuh.');
                }
            }

            $startsAt = $activeSubscription?->expires_at?->copy() ?? $now->copy();
            $expiresAt = $startsAt->copy()->addDays((int) $data['duration_days']);

            $organization = Organization::query()->firstOrCreate(
                ['product_offering_id' => $offering->id],
                ['email' => $offering->email ?: $customer->email, 'expires_at' => $expiresAt],
            );
            if ($organization->expires_at === null || $organization->expires_at->lt($expiresAt)) {
                $organization->update(['expires_at' => $expiresAt]);
            }

            if ($activeSubscription) {
                $subscription = $activeSubscription;
                $subscription->update(['expires_at' => $expiresAt]);
            } else {
                $subscription = Subscription::create([
                    'user_id' => $customer->id,
                    'organization_id' => $organization->id,
                    'product_offering_id' => $offering->id,
                    'status' => 'active',
                    'starts_at' => $startsAt,
                    'expires_at' => $expiresAt,
                ]);
            }

            $transaction = Transaction::create([
                'number' => 'TRX-'.Str::upper((string) Str::ulid()),
                'user_id' => $customer->id,
                'cashier_id' => $request->user()->id,
                'product_offering_id' => $offering->id,
                'subscription_id' => $subscription->id,
                'product_name' => $offering->product->name,
                'total_amount' => $data['total_amount'],
                'duration_days' => $data['duration_days'],
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'status' => 'paid',
                'paid_at' => $now,
            ]);

            return [
                'transaction' => $transaction->load(['user', 'cashier']),
                'temporary_password' => $temporaryPassword,
            ];
        });

        return response()->json([
            'message' => 'Transaksi berhasil disimpan.',
            'data' => [
                'transaction' => [
                    'id' => $result['transaction']->id,
                    'number' => $result['transaction']->number,
                    'customer' => $result['transaction']->user?->name,
                    'email' => $result['transaction']->user?->email,
                    'product' => $result['transaction']->product_name,
                    'amount' => (float) $result['transaction']->total_amount,
                    'duration_days' => $result['transaction']->duration_days,
                    'starts_at' => $result['transaction']->starts_at->format('d M Y'),
                    'expires_at' => $result['transaction']->expires_at->format('d M Y'),
                    'paid_at' => $result['transaction']->paid_at->format('d M Y H:i'),
                ],
                'temporary_password' => $result['temporary_password'],
            ],
        ], 201);
    }
}
