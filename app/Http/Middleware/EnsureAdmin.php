<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // When running in the console (artisan), skip runtime auth checks
        if (app()->runningInConsole()) {
            return $next($request);
        }

        try {
            if (!Auth::check()) {
                return redirect()->route('admin.login');
            }

            $user = Auth::user();
            if (! $user || ($user->role ?? '') !== 'admin') {
                return redirect()->route('admin.login');
            }

            return $next($request);
        } catch (\Throwable $e) {
            return redirect()->route('admin.login');
        }
    }
}
