<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'admin@local.test';
$password = 'Password123';

if (User::where('email', $email)->exists()) {
    echo "User already exists: {$email}\n";
    exit(0);
}

$user = User::create([
    'name' => 'Local Admin',
    'email' => $email,
    'password' => Hash::make($password),
]);

if ($user) {
    echo "Created admin user: {$email} with password: {$password}\n";
    exit(0);
}

echo "Failed to create user\n";
