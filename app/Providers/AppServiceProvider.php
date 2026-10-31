<?php

namespace App\Providers;

use App\Models\SystemSetting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $email = mb_strtolower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        View::composer('*', function ($view): void {
            $settings = Schema::hasTable('system_settings') ? SystemSetting::current() : new SystemSetting([
                'name' => config('app.name', 'HubMarchand'),
                'primary_color' => '#0891B2',
                'secondary_color' => '#0F172A',
                'accent_color' => '#F59E0B',
            ]);

            $view->with([
                'systemSettings' => $settings,
                'brandStyles' => sprintf(
                    '--brand-primary: %s; --brand-secondary: %s; --brand-accent: %s;',
                    $settings->primary_color,
                    $settings->secondary_color,
                    $settings->accent_color,
                ),
            ]);
        });
    }
}
