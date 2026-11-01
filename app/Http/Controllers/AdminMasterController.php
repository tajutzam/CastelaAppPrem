<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\AppSetting;
use App\Models\ProductOffering;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminMasterController extends Controller
{
    public function settings(): View
    {
        $settings = AppSetting::query()->pluck('value', 'key');

        return view('admin.settings.index', [
            'settings' => $settings,
            'defaults' => [
                'app_name' => 'CassieRess',
                'support_email' => '',
                'support_phone' => '',
                'timezone' => 'Asia/Jakarta',
                'currency' => 'IDR',
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'timezone' => ['required', Rule::in(['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura', 'Asia/Singapore', 'UTC'])],
            'currency' => ['required', Rule::in(['IDR', 'USD'])],
        ]);

        DB::transaction(function () use ($data): void {
            foreach ($data as $key => $value) {
                AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        Cache::forget('admin.app_settings');

        return to_route('admin.settings.index')->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function accountTypes(): View
    {
        $accountTypes = AccountType::withCount('offerings')->latest()->paginate(10, ['*'], 'account_types_page')->withQueryString();

        return view('admin.master.account-types', [
            'accountTypes' => $accountTypes,
            'accountTypeStats' => [
                'total' => AccountType::count(),
                'active' => AccountType::where('status', true)->count(),
                'offerings' => ProductOffering::count(),
            ],
        ]);
    }

    public function users(): View
    {
        $roleCounts = User::query()
            ->selectRaw('role, COUNT(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        return view('admin.master.users', [
            'users' => User::query()->latest()->paginate(10, ['*'], 'users_page')->withQueryString(),
            'userStats' => [
                'total' => User::count(),
                'admin' => $roleCounts->get('admin', 0),
                'owner' => $roleCounts->get('owner', 0),
                'client' => $roleCounts->get('client', 0),
            ],
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,owner,client'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        unset($data['password_confirmation']);

        User::create($data);

        return to_route('admin.users.index')->with('success', 'User berhasil ditambahkan.');
    }
}
