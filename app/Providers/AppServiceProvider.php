<?php

namespace App\Providers;

use App\Models\AppSetting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.admin', function ($view): void {

            $settingsData = Cache::rememberForever(
                'admin.app_settings',
                function (): array {
                    if (! Schema::hasTable('app_settings')) {
                        return [];
                    }

                    return AppSetting::query()
                        ->pluck('value', 'key')
                        ->toArray();
                }
            );

            $settings = collect($settingsData);

            config([
                'app.name' => $settings->get(
                    'app_name',
                    config('app.name')
                ),
                'app.currency' => $settings->get(
                    'currency',
                    'IDR'
                ),
            ]);

            date_default_timezone_set(
                $settings->get(
                    'timezone',
                    config('app.timezone')
                )
            );

            $expiringTransactions = Schema::hasTable('transactions')
                ? Transaction::query()
                    ->with('user')
                    ->where('status', 'paid')
                    ->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now(), now()->addDays(5)])
                    ->orderBy('expires_at')
                    ->limit(8)
                    ->get()
                : collect();

            $view->with('appSettings', $settings);
            $view->with('expiringTransactions', $expiringTransactions);
            $view->with('formatRemainingTime', function ($expiresAt): string {
                $minutes = max(0, now()->diffInMinutes($expiresAt, false));
                $days = intdiv($minutes, 1440);
                $hours = intdiv($minutes % 1440, 60);
                $remainingMinutes = $minutes % 60;

                return sprintf('%d hari %d jam %d menit lagi', $days, $hours, $remainingMinutes);
            });
        });
    }
}
