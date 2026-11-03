<?php

namespace App\Http\Controllers;

use App\Exports\StockWorkbookExport;
use App\Imports\StockWorkbookImport;
use App\Models\AccountType;
use App\Models\Product;
use App\Models\ProductOffering;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AdminCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $selectedMonth = $request->date('month', 'Y-m')?->startOfMonth() ?? now()->startOfMonth();
        $now = $selectedMonth->copy();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $chartStart = $now->copy()->subMonths(5)->startOfMonth();

        // Paid transactions
        $paidTransactions = Transaction::query()
            ->where('status', 'paid');

        $monthTransactions = (clone $paidTransactions)
            ->whereBetween('paid_at', [$monthStart, $monthEnd]);

        // Financial transactions (last 6 months)
        $financialTransactions = (clone $paidTransactions)
            ->where('paid_at', '>=', $chartStart)
            ->get();

        // Offerings (last 6 months)
        // Modal mengikuti tanggal mulai premium
        $financialOfferings = ProductOffering::query()
            ->where('starts_at', '>=', $chartStart)
            ->get();

        // Financial chart
        $financialChart = collect(range(5, 0))
            ->map(function (int $monthsAgo) use (
                $financialTransactions,
                $financialOfferings,
                $now
            ): array {
                $date = $now->copy()
                    ->subMonths($monthsAgo)
                    ->startOfMonth();

                // Revenue dari paid transactions
                $transactions = $financialTransactions->filter(
                    fn (Transaction $transaction): bool => $transaction->paid_at?->isSameMonth($date)
                );

                $revenue = (float) $transactions->sum('total_amount');

                // Capital langsung dari product offerings
                $offerings = $financialOfferings->filter(
                    fn (ProductOffering $offering): bool => $offering->created_at?->isSameMonth($date)
                );

                $capital = (float) $offerings->sum('price');

                return [
                    'label' => $date->format('M'),
                    'revenue' => $revenue,
                    'capital' => $capital,
                    'profit' => $revenue - $capital,
                ];
            })
            ->values();

        // Monthly revenue
        $monthRevenue = (float) (clone $monthTransactions)
            ->sum('total_amount');

        // Monthly capital langsung dari offerings
        $monthCapital = (float) ProductOffering::query()
            ->whereBetween('starts_at', [$monthStart, $monthEnd])
            ->sum('price');

        // Product chart
        $productChart = Transaction::query()
            ->selectRaw('
            product_name,
            COUNT(*) as transaction_count,
            SUM(total_amount) as revenue
        ')
            ->where('status', 'paid')
            ->whereNotNull('product_name')
            ->groupBy('product_name')
            ->orderByDesc('transaction_count')
            ->get();

        return view('admin.catalog.index', [
            'metrics' => [
                'monthRevenue' => $monthRevenue,
                'monthCapital' => $monthCapital,
                'monthProfit' => $monthRevenue - $monthCapital,

                'monthTransactions' => (clone $monthTransactions)
                    ->count(),

                'activeSubscriptions' => Subscription::query()
                    ->where('status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->count(),

                'activeCustomers' => User::query()
                    ->where('role', 'client')
                    ->whereHas('subscriptions', function ($query) {
                        $query->where('status', 'active')
                            ->where(function ($query) {
                                $query->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', now());
                            });
                    })
                    ->count(),

                'activeOfferings' => ProductOffering::query()
                    ->where('status', 'active')
                    ->count(),

                'pendingTransactions' => Transaction::query()
                    ->where('status', 'pending')
                    ->count(),
            ],

            'recentTransactions' => Transaction::query()
                ->with(['user', 'cashier', 'productOffering'])
                ->latest('paid_at')
                ->limit(8)
                ->get(),

            'expiringSubscriptions' => Subscription::query()
                ->with(['user', 'productOffering.product'])
                ->where('status', 'active')
                ->whereBetween('expires_at', [
                    now(),
                    now()->addDays(7),
                ])
                ->orderBy('expires_at')
                ->limit(6)
                ->get(),

            'topOfferings' => ProductOffering::query()
                ->with('product')
                ->withCount([
                    'transactions' => fn ($query) => $query->where('status', 'paid'),
                ])
                ->orderByDesc('transactions_count')
                ->limit(5)
                ->get(),

            'financialChart' => $financialChart,
            'selectedMonth' => $selectedMonth->format('Y-m'),
            'productChart' => $productChart,
        ]);
    }

    public function exportOfferings()
    {
        return Excel::download(new StockWorkbookExport, 'castela-stock-akun-'.now()->format('Y-m-d').'.xlsx');
    }

    public function importOfferings(Request $request): RedirectResponse
    {
        set_time_limit(0);
        $request->validate(['stock_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);
        Excel::import(new StockWorkbookImport, $request->file('stock_file'));

        return back()->with('success', 'Stock akun berhasil diimport dan di-upsert ke offering.');
    }

    public function offerings(Request $request): View
    {
        $sortColumns = ['product_id', 'account_type_id', 'email', 'starts_at', 'expires_at', 'price', 'status', 'created_at'];
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->lower()->toString() === 'asc' ? 'asc' : 'desc';
        $sort = in_array($sort, $sortColumns, true) ? $sort : 'created_at';
        $search = $request->string('search')->trim()->toString();

        return view('admin.master.offerings', [
            'offerings' => ProductOffering::with(['product', 'accountType'])->withCount('subscriptions')->when($search, fn ($query) => $query->where(fn ($query) => $query->where('email', 'ilike', "%{$search}%")->orWhereHas('product', fn ($product) => $product->where('name', 'ilike', "%{$search}%"))->orWhereHas('accountType', fn ($type) => $type->where('name', 'ilike', "%{$search}%"))))->orderBy($sort, $direction)->paginate(10, ['*'], 'offerings_page')->withQueryString(),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
            'productOptions' => Product::orderBy('name')->get(),
            'accountTypeOptions' => AccountType::orderBy('name')->get(),
            'userOptions' => User::where('role', 'client')->orderBy('name')->get(),
        ]);
    }

    public function offeringDetail(ProductOffering $productOffering): View
    {
        $productOffering->load(['product', 'accountType', 'subscriptions.user', 'transactions.user', 'transactions.subscription'])->loadCount('subscriptions');

        return view('admin.master.offering-detail', [
            'offering' => $productOffering,
            'transactions' => $productOffering->transactions->sortByDesc('paid_at')->values(),
        ]);
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
            'notes' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ]);

        $offering = ProductOffering::updateOrCreate(
            ['product_id' => $data['product_id'], 'account_type_id' => $data['account_type_id']],
            [...$data, 'status' => 'active'],
        );

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
            'notes' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ]);
        $productOffering->update($data);

        return back()->with('success', 'Offering berhasil diperbarui.');
    }

    public function updateOfferingStatus(Request $request, ProductOffering $productOffering): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'suspended', 'deactivated'])]]);
        $productOffering->update($data);

        return back()->with('success', 'Status offering berhasil diperbarui.');
    }

    public function addOfferingUser(Request $request, ProductOffering $productOffering): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('role', 'client')],
        ]);

        if (! $productOffering->email) {
            return back()->with('error', 'Tambahkan email pada offering sebelum menambahkan user.');
        }

        if ($productOffering->subscriptions()->where('user_id', $data['user_id'])->exists()) {
            return back()->with('error', 'User tersebut sudah ditambahkan ke offering ini.');
        }

        Subscription::create([
            'user_id' => $data['user_id'],
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
