<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\ProductOffering;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        $customer = $request->integer('customer');
        $search = $request->string('search')->trim()->toString();
        $phoneSearch = preg_replace('/\D+/', '', $search);
        $logoutStatus = $request->string('logout_status')->toString();
        $accessStatus = $request->string('access_status')->toString();
        $sortColumns = ['number', 'product_name', 'total_amount', 'starts_at', 'expires_at', 'paid_at', 'status'];
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->lower()->toString() === 'asc' ? 'asc' : 'desc';
        $sort = in_array($sort, $sortColumns, true) ? $sort : 'paid_at';
        $query = Transaction::with(['user', 'cashier', 'subscription', 'productOffering'])
            ->when($customer, fn ($query) => $query->where('user_id', $customer))
            ->when($search, fn ($query) => $query->where(function ($query) use ($search, $phoneSearch): void {
                $query->where('number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->when($phoneSearch !== '', fn ($user) => $user->orWhere('phone_number', 'like', "%{$phoneSearch}%")));
            }))
            ->when($logoutStatus === 'logged_out', fn ($query) => $query->whereNotNull('logged_out_at'))
            ->when($logoutStatus === 'pending', fn ($query) => $query->whereNull('logged_out_at'))
            ->when($accessStatus === 'active', fn ($query) => $query->where('status', 'paid')->where('expires_at', '>', now()))
            ->when($accessStatus === 'expired', fn ($query) => $query->where(fn ($query) => $query->where('status', '!=', 'paid')->orWhereNull('expires_at')->orWhere('expires_at', '<=', now())))
            ->orderBy($sort, $direction);

        return view('admin.transactions.index', [
            'sort' => $sort,
            'direction' => $direction,
            'transactions' => $query->paginate(10, ['*'], 'transactions_page')->withQueryString(),
            'customer' => $customer,
            'search' => $search,
            'logoutStatus' => $logoutStatus,
            'accessStatus' => $accessStatus,
            'customers' => User::query()->where('role', 'client')->orderBy('name')->get(['id', 'name', 'email']),
            'cards' => [
                'total' => (clone $query)->count(),
                'paid' => (clone $query)->where('status', 'paid')->count(),
                'revenue' => (float) (clone $query)->where('status', 'paid')->sum('total_amount'),
            ],
        ]);
    }

    public function showTransaction(Transaction $transaction): View
    {
        return view('admin.transactions.show', [
            'transaction' => $transaction->load(['user', 'cashier', 'subscription', 'productOffering.product', 'productOffering.accountType']),
        ]);
    }

    public function markTransactionLoggedOut(Request $request, Transaction $transaction): RedirectResponse
    {
        DB::transaction(function () use ($request, $transaction): void {
            $transaction->update([
                'logged_out_at' => $transaction->logged_out_at ?? now(),
                'logged_out_by' => $transaction->logged_out_by ?? $request->user()->id,
            ]);

            $transaction->subscription?->update(['status' => 'cancelled']);
        });

        return back()->with('success', 'Status logout transaksi berhasil dicatat dan slot offering dikembalikan.');
    }

    public function markTransactionInactive(Transaction $transaction): RedirectResponse
    {
        $transaction->update(['status' => 'cancelled']);
        $transaction->subscription?->update(['status' => 'cancelled']);

        return back()->with('success', 'Transaksi ditandai tidak aktif.');
    }

    public function receipt(Transaction $transaction): View
    {
        return view('admin.transactions.receipt', [
            'transaction' => $transaction->load(['user', 'cashier', 'productOffering.product', 'productOffering.accountType']),
            'appSettings' => collect(AppSetting::query()->pluck('value', 'key')),
        ]);
    }

    public function renewTransaction(Request $request, Transaction $transaction): RedirectResponse
    {
        $data = $request->validate([
            'total_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);
        $transaction->load('productOffering');
        $startsAt = now();
        $expiresAt = $startsAt->copy()->addDays((int) $data['duration_days']);
        $subscription = Subscription::create(['user_id' => $transaction->user_id, 'product_offering_id' => $transaction->product_offering_id, 'status' => 'active', 'starts_at' => $startsAt, 'expires_at' => $expiresAt]);
        Transaction::create(['number' => 'TRX-'.Str::upper((string) Str::ulid()), 'user_id' => $transaction->user_id, 'cashier_id' => $request->user()->id, 'product_offering_id' => $transaction->product_offering_id, 'subscription_id' => $subscription->id, 'product_name' => $transaction->product_name, 'total_amount' => $data['total_amount'], 'duration_days' => $data['duration_days'], 'starts_at' => $startsAt, 'expires_at' => $expiresAt, 'status' => 'paid', 'paid_at' => $startsAt]);

        return back()->with('success', 'Perpanjangan berhasil dibuat sebagai transaksi baru.');
    }

    public function updateTransaction(Request $request, Transaction $transaction): RedirectResponse
    {
        $data = $request->validate([
            'total_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'status' => ['required', Rule::in(['paid', 'pending', 'cancelled'])],
            'paid_at' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
        ]);

        $startsAt = filled($data['starts_at'] ?? null)
            ? now()->parse($data['starts_at'])
            : $transaction->starts_at;
        $data['starts_at'] = $startsAt;
        $data['expires_at'] = $startsAt?->copy()->addDays((int) $data['duration_days']);

        DB::transaction(function () use ($transaction, $data): void {
            $transaction->update($data);

            if ($transaction->subscription) {
                $transaction->subscription->update([
                    'status' => $data['status'] === 'paid' ? 'active' : $data['status'],
                    'starts_at' => $data['starts_at'],
                    'expires_at' => $data['expires_at'],
                ]);
            }
        });

        return back()->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroyTransaction(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return back()->with('success', 'Transaksi berhasil dihapus. Subscription tetap dipertahankan.');
    }

    public function reports(Request $request): View
    {
        $filters = $this->transactionFilters($request);
        $query = $this->transactionQuery($filters);
        $monthQuery = Transaction::query()->with('productOffering')->where('status', 'paid')->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()]);
        $monthlyReports = collect(range(11, 0))->map(function (int $monthsAgo): array {
            $start = now()->subMonths($monthsAgo)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $rows = Transaction::query()->where('status', 'paid')->whereBetween('paid_at', [$start, $end])->get();
            $sales = (float) $rows->sum('total_amount');
            $capital = (float) ProductOffering::query()->whereBetween('starts_at', [$start, $end])->sum('price');

            return ['label' => $start->translatedFormat('F Y'), 'count' => $rows->count(), 'sales' => $sales, 'capital' => $capital, 'profit' => $sales - $capital];
        });

        return view('admin.transactions.reports', [
            'monthlyReports' => $monthlyReports,
            'transactions' => $query->with('productOffering')->latest('paid_at')->paginate(15, ['*'], 'transactions_page')->withQueryString(),
            'transactionStats' => [
                'monthCount' => (clone $monthQuery)->count(),
                'monthCapital' => (float) ProductOffering::query()->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('price'),
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

    public function offerings(Request $request): JsonResponse
    {
        $term = $request->string('q')->trim()->toString();
        $offerings = ProductOffering::query()
            ->with(['product:id,name', 'accountType:id,name,max_users'])
            ->withCount(['subscriptions as active_subscriptions_count' => fn ($query) => $query
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))])
            ->where('status', 'active')
            ->whereHas('product', fn ($query) => $query->where('status', true))
            ->when($term, fn ($query) => $query->where(function ($query) use ($term): void {
                $query->whereHas('product', fn ($product) => $product->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('accountType', fn ($accountType) => $accountType->where('name', 'like', "%{$term}%"))
                    ->orWhere('email', 'like', "%{$term}%");
            }))
            ->whereRaw('(select count(*) from subscriptions where subscriptions.product_offering_id = product_offerings.id and subscriptions.status = ? and subscriptions.expires_at > ?) < (select max_users from account_types where account_types.id = product_offerings.account_type_id)', ['active', now()])
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
                'notes' => $offering->notes,
                'used_quota' => $offering->active_subscriptions_count,
                'quota' => max(0, $offering->accountType->max_users - $offering->active_subscriptions_count),
                'remaining_days' => $offering->expires_at?->isFuture() ? (int) floor(now()->diffInSeconds($offering->expires_at) / 86400) : 0,
                'expires_at' => $offering->expires_at?->format('d M Y H:i'),
            ]);

        return response()->json(['data' => $offerings]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_type' => ['required', Rule::in(['existing', 'new'])],
            'user_id' => ['required_if:customer_type,existing', 'nullable', Rule::exists('users', 'id')->where('role', 'client')],
            'name' => ['required_if:customer_type,new', 'nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
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

            abort_unless($offering->status === 'active', 422, 'Offering ini sedang tidak aktif.');

            $temporaryPassword = null;
            if ($data['customer_type'] === 'new') {
                $temporaryPassword = Str::random(16);
                $customer = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'] ?? null,
                    'phone_number' => $data['phone_number'] ?? null,
                    'password' => $temporaryPassword,
                    'role' => 'client',
                ]);
            } else {
                $customer = User::query()->where('role', 'client')->findOrFail($data['user_id']);
            }

            $now = now();
            $activeSeats = Subscription::query()
                ->where('product_offering_id', $offering->id)
                ->where('status', 'active')
                ->where('expires_at', '>', $now)
                ->count();

            if ($activeSeats >= $offering->accountType->max_users) {
                abort(422, 'Kapasitas user untuk offering ini sudah penuh.');
            }

            $startsAt = $now->copy();
            $expiresAt = $startsAt->copy()->addDays((int) $data['duration_days']);

            $subscription = Subscription::create([
                'user_id' => $customer->id,
                'product_offering_id' => $offering->id,
                'status' => 'active',
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
            ]);

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
                    'receipt_url' => route('admin.transactions.receipt', $result['transaction']),
                ],
                'temporary_password' => $result['temporary_password'],
            ],
        ], 201);
    }
}
