<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * Create and return the application instance. Bind a minimal Filesystem
 * early to avoid provider checks failing during local diagnostic bootstraps.
 */
$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

// Bind a minimal Filesystem early to prevent providers from resolving 'files'
// before the framework's FilesystemServiceProvider runs. This is conservative
// and avoids registering other manual providers.
if (! $app->bound('files')) {
    $app->instance('files', new Illuminate\Filesystem\Filesystem());
}

// Provide a minimal MaintenanceMode binding for diagnostics to avoid
// BindingResolutionException during HTTP bootstrap when some providers
// haven't been fully registered in this extracted environment.
if (! $app->bound(Illuminate\Contracts\Foundation\MaintenanceMode::class)) {
    $app->instance(
        Illuminate\Contracts\Foundation\MaintenanceMode::class,
        new Illuminate\Foundation\ArrayMaintenanceMode()
    );
}

return $app;
