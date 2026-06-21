<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;

$email = 'admin@local.test';
$password = 'Password123';

$ok = Auth::attempt(['email' => $email, 'password' => $password]);

if ($ok) {
    echo "AUTH_OK\n";
    Auth::logout();
    exit(0);
}

echo "AUTH_FAILED\n";
