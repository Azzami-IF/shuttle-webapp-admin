<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_exception_handler(function($e){
    echo "EXCEPTION: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
});

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

echo "basePath=" . $app->basePath() . "\n";

try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create('/');
    $response = $kernel->handle($request);
    echo "STATUS=" . $response->getStatusCode() . "\n";
    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    echo "BOOTSTRAP_EXCEPTION: " . get_class($e) . ': ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "done\n";
