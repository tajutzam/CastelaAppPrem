<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

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
                    if (!Schema::hasTable('app_settings')) {
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

            $view->with('appSettings', $settings);
        });
    }
}