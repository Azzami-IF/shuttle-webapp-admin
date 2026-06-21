<?php

namespace App\Providers;

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
        // Avoid calling session() during early boot (artisan commands) or
        // when session services are not registered yet.
        if (app()->runningInConsole() || ! function_exists('app') || ! app()->bound('session')) {
            return;
        }

        if (function_exists('request') && request() && request()->has('lang')) {
            session(['locale' => request('lang')]);
        }

        if (function_exists('session') && session() && session()->has('locale')) {
            app()->setLocale(session('locale'));
        }
    }
}
