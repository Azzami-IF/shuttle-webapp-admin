<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

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
        // Ensure the request signature macros exist; some environments (cached routes,
        // or early filesystem serving) may call these before the Foundation provider
        // registers them. Register defensively here if they are missing.
        if (! Request::hasMacro('hasValidRelativeSignature')) {
            Request::macro('hasValidRelativeSignature', function () {
                return URL::hasValidSignature($this, false);
            });
        }

        if (! Request::hasMacro('hasValidRelativeSignatureWhileIgnoring')) {
            Request::macro('hasValidRelativeSignatureWhileIgnoring', function ($ignoreQuery = []) {
                return URL::hasValidSignature($this, false, $ignoreQuery);
            });
        }

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
