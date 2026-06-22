<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Services\RemoteApi;

$email = 'admin@local.test';
$password = 'Password123';

// Try remote API first
$api = new RemoteApi();
try {
    $r = $api->post('/admin/users', [
        'name' => 'Local Admin',
        'email' => $email,
        'role' => 'admin',
        'password' => $password,
    ]);
    if ($r->successful()) {
        echo "Created admin user remotely: {$email}\n";
        exit(0);
    }
} catch (\Exception $e) {
}

if (User::where('email', $email)->exists()) {
    echo "User already exists: {$email}\n";
    exit(0);
}

$user = User::create([
    'name' => 'Local Admin',
    'email' => $email,
    'password' => Hash::make($password),
    'role' => 'admin',
]);

if ($user) {
    echo "Created admin user: {$email} with password: {$password}\n";
    exit(0);
}

echo "Failed to create user\n";
