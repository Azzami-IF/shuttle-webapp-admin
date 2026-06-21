<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ob_start();
set_error_handler(function($errno, $errstr, $errfile, $errline){
    echo "ERROR: $errstr in $errfile:$errline\n";
});
set_exception_handler(function($e){
    echo "EXCEPTION: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
});
register_shutdown_function(function(){
    $err = error_get_last();
    if($err) {
        echo "SHUTDOWN ERROR: ";
        print_r($err);
    }
    $content = ob_get_clean();
    if($content) echo "BUFFER:\n".$content."\n";
});

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

echo "basePath=" . $app->basePath() . "\n";
echo "configPath=" . $app->configPath() . "\n";
echo "cachedConfigPath=" . $app->getCachedConfigPath() . "\n";

// Ensure a basic 'files' binding exists so early provider checks don't fail
if (! $app->bound('files')) {
    $app->instance('files', new Illuminate\Filesystem\Filesystem());
    echo "NOTE: bound basic 'files' filesystem instance for diagnostics\n";
}

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
try {
    $kernel->bootstrap();
    echo "BOOTSTRAP_OK\n";
} catch (Throwable $e) {
    echo "BOOTSTRAP_EXCEPTION: " . get_class($e) . ': ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "done\n";
