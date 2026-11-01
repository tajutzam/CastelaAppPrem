<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\Product;
use App\Models\ProductOffering;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ChatGPTService;
use App\Services\ClaudeService;
use App\Services\OrganizationAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AdminCatalogController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $paidTransactions = Transaction::query()->where('status', 'paid');
        $monthTransactions = (clone $paidTransactions)->whereBetween('paid_at', [$monthStart, $monthEnd]);
        $financialTransactions = (clone $paidTransactions)->with('productOffering')->where('paid_at', '>=', now()->subMonths(5)->startOfMonth())->get();
        $financialChart = collect(range(5, 0))->map(function (int $monthsAgo) use ($financialTransactions): array {
            $date = now()->subMonths($monthsAgo)->startOfMonth();
            $rows = $financialTransactions->filter(fn (Transaction $transaction): bool => $transaction->paid_at?->isSameMonth($date));
            $revenue = (float) $rows->sum('total_amount');
            $capital = (float) $rows->sum(fn (Transaction $transaction): float => (float) ($transaction->productOffering?->price ?? 0));

            return ['label' => $date->format('M'), 'revenue' => $revenue, 'capital' => $capital, 'profit' => $revenue - $capital];
        })->values();
        $monthRevenue = (float) $monthTransactions->sum('total_amount');
        $monthCapital = (float) $monthTransactions->with('productOffering')->get()->sum(fn (Transaction $transaction): float => (float) ($transaction->productOffering?->price ?? 0));

        return view('admin.catalog.index', [
            'metrics' => [
                'monthRevenue' => $monthRevenue,
                'monthCapital' => $monthCapital,
                'monthProfit' => $monthRevenue - $monthCapital,
                'monthTransactions' => (clone $monthTransactions)->count(),
                'activeSubscriptions' => Subscription::query()->where('status', 'active')->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count(),
                'activeCustomers' => User::query()->where('role', 'client')->whereHas('subscriptions', fn ($query) => $query->where('status', 'active')->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now())))->count(),
                'activeOfferings' => ProductOffering::query()->where('status', true)->count(),
                'pendingTransactions' => Transaction::query()->where('status', 'pending')->count(),
            ],
            'recentTransactions' => Transaction::query()->with(['user', 'cashier', 'productOffering'])->latest('paid_at')->limit(8)->get(),
            'expiringSubscriptions' => Subscription::query()->with(['user', 'productOffering.product'])->where('status', 'active')->whereBetween('expires_at', [now(), now()->addDays(7)])->orderBy('expires_at')->limit(6)->get(),
            'topOfferings' => ProductOffering::query()->with('product')->withCount(['transactions' => fn ($query) => $query->where('status', 'paid')])->orderByDesc('transactions_count')->limit(5)->get(),
            'financialChart' => $financialChart,
        ]);
    }

    public function offerings(): View
    {
        return view('admin.master.offerings', [
            'offerings' => ProductOffering::with('product')->withCount('subscriptions')->latest()->paginate(10, ['*'], 'offerings_page')->withQueryString(),
            'productOptions' => Product::orderBy('name')->get(),
            'accountTypeOptions' => AccountType::orderBy('name')->get(),
            'userOptions' => User::where('role', 'client')->orderBy('name')->get(),
        ]);
    }

    public function offeringDetail(ProductOffering $productOffering): View
    {
        $productOffering->load(['product', 'accountType', 'organization', 'subscriptions.user'])->loadCount('subscriptions');
        $productKey = Str::lower(($productOffering->product->slug ?? '').' '.($productOffering->product->name ?? ''));
        $providerLabel = Str::contains($productKey, ['chatgpt', 'openai'])
            ? 'ChatGPT / OpenAI'
            : (Str::contains($productKey, ['claude', 'anthropic']) ? 'Claude / Anthropic' : 'Provider tidak didukung');

        return view('admin.master.offering-detail', [
            'offering' => $productOffering,
            'providerLabel' => $providerLabel,
            'providerKey' => $providerLabel === 'Provider tidak didukung' ? null : Str::before($providerLabel, ' /'),
            'connectionError' => null,
            'subscriptions' => $productOffering->subscriptions->sortByDesc('expires_at')->values(),
        ]);
    }

    public function inviteTransactionUser(Request $request, ProductOffering $productOffering): \Illuminate\Http\JsonResponse
    {
        $productOffering->load(['product', 'organization']);
        $productKey = Str::lower(($productOffering->product->slug ?? '').' '.($productOffering->product->name ?? ''));
        $service = Str::contains($productKey, ['chatgpt', 'openai'])
            ? app(ChatGPTService::class)
            : (Str::contains($productKey, ['claude', 'anthropic']) ? app(ClaudeService::class) : null);

        if (! $service) {
            return response()->json(['message' => 'Provider untuk product ini belum didukung.'], 422);
        }

        if (! $productOffering->organization) {
            return response()->json(['message' => 'Offering ini belum memiliki organization.'], 422);
        }

        $data = $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'client'),
                Rule::exists('transactions', 'user_id')->where(fn ($query) => $query
                    ->where('product_offering_id', $productOffering->id)
                    ->where('status', 'paid')),
            ],
            'role' => ['required', Rule::in($service->roles())],
            'project_id' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'string', 'max:255'],
        ]);
        $user = User::query()->where('role', 'client')->findOrFail($data['user_id']);

        try {
            if ($service instanceof ChatGPTService && filled($data['organization_id'] ?? null)) {
                $service->selectOrganization($data['organization_id']);
                $productOffering->organization()->update(['external_id' => $data['organization_id']]);
            }

            if ($service->findByEmail($productOffering, 'users', $user->email)) {
                return response()->json(['message' => 'User ini sudah menjadi member di provider.'], 409);
            }

            $existingInvite = $service->findByEmail($productOffering, 'invites', $user->email);
            if ($existingInvite && in_array($existingInvite['status'] ?? 'pending', ['pending', 'accepted'], true)) {
                return response()->json(['message' => 'User ini sudah pernah diundang ke provider.'], 409);
            }

            return response()->json([
                'message' => 'Undangan berhasil dikirim.',
                'data' => $service->invite($productOffering, $user->email, $data['role'], $data['project_id'] ?? null),
            ], 201);
        } catch (Throwable $exception) {
            $status = $exception instanceof \RuntimeException && $exception->getCode() >= 400 && $exception->getCode() < 500
                ? $exception->getCode()
                : 422;

            return response()->json(['message' => $exception->getMessage()], $status);
        }
    }

    public function products(): View
    {
        return view('admin.master.products', [
            'products' => Product::withCount('offerings')->latest()->paginate(10, ['*'], 'products_page')->withQueryString(),
            'productStats' => [
                'total' => Product::count(),
                'active' => Product::where('status', true)->count(),
                'inactive' => Product::where('status', false)->count(),
            ],
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'alpha_dash', 'max:100', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
        ]);

        Product::create([...$data, 'status' => $request->boolean('status')]);

        return back()->with('success', 'Product berhasil ditambahkan.');
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'alpha_dash', 'max:100', Rule::unique('products', 'slug')->ignore($product->id)],
            'description' => ['nullable', 'string'],
        ]);
        $data['status'] = $request->boolean('status');

        $product->update($data);

        return back()->with('success', 'Data produk berhasil diperbarui.');
    }

    public function destroyProduct(Product $product): RedirectResponse
    {
        if ($product->offerings()->exists()) {
            return back()->with('error', 'Produk masih memiliki penawaran. Hapus penawarannya terlebih dahulu.');
        }

        $product->delete();

        return back()->with('success', 'Produk berhasil dihapus.');
    }

    public function storeAccountType(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:50', 'unique:account_types,code'],
            'name' => ['required', 'string', 'max:100'],
            'max_users' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        AccountType::create([...$data, 'status' => $request->boolean('status')]);

        return back()->with('success', 'Account type berhasil ditambahkan.');
    }

    public function storeOffering(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'account_type_id' => ['required', 'exists:account_types,id'],
            'email' => ['required', 'email', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ]);

        $offering = ProductOffering::updateOrCreate(
            ['product_id' => $data['product_id'], 'account_type_id' => $data['account_type_id']],
            [...$data, 'status' => $request->boolean('status')],
        );
        $offering->organization()->updateOrCreate([], [
            'email' => $data['email'],
            'expires_at' => $data['expires_at'],
        ]);

        return back()->with('success', 'Offering berhasil disimpan.');
    }

    public function updateOffering(Request $request, ProductOffering $productOffering): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'account_type_id' => [
                'required',
                'exists:account_types,id',
                Rule::unique('product_offerings', 'account_type_id')
                    ->where(fn ($query) => $query->where('product_id', $request->input('product_id')))
                    ->ignore($productOffering->id),
            ],
            'email' => ['required', 'email', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ]);
        $data['status'] = $request->boolean('status');

        $productOffering->update($data);
        $productOffering->organization()->updateOrCreate([], [
            'email' => $data['email'],
            'expires_at' => $data['expires_at'],
        ]);

        return back()->with('success', 'Offering berhasil diperbarui.');
    }

    public function addOfferingUser(Request $request, ProductOffering $productOffering): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('role', 'client')],
        ]);

        if (! $productOffering->email) {
            return back()->with('error', 'Tambahkan email pada offering sebelum menambahkan user.');
        }

        $organization = $productOffering->organization()->firstOrCreate([], [
            'email' => $productOffering->email,
            'expires_at' => $productOffering->expires_at,
        ]);

        if ($productOffering->subscriptions()->where('user_id', $data['user_id'])->exists()) {
            return back()->with('error', 'User tersebut sudah ditambahkan ke offering ini.');
        }

        Subscription::create([
            'user_id' => $data['user_id'],
            'organization_id' => $organization->id,
            'product_offering_id' => $productOffering->id,
            'status' => 'active',
            'starts_at' => $productOffering->starts_at,
            'expires_at' => $productOffering->expires_at,
        ]);

        return back()->with('success', 'User berhasil ditambahkan ke offering.');
    }

    public function destroyOffering(ProductOffering $productOffering): RedirectResponse
    {
        DB::transaction(function () use ($productOffering): void {
            $productOffering->subscriptions()->delete();
            $productOffering->organization?->delete();
            $productOffering->delete();
        });

        return back()->with('success', 'Offering dan seluruh akses user terkait berhasil dihapus.');
    }

    public function toggleProduct(Product $product): RedirectResponse
    {
        $product->update(['status' => ! $product->status]);

        return back();
    }

    public function toggleAccountType(AccountType $accountType): RedirectResponse
    {
        $accountType->update(['status' => ! $accountType->status]);

        return back();
    }
}
