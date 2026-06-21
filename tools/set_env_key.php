<?php
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath) && file_exists(__DIR__ . '/../.env.example')) {
    copy(__DIR__ . '/../.env.example', $envPath);
}
$key = 'APP_KEY=base64:' . base64_encode(random_bytes(32));
$content = file_exists($envPath) ? file_get_contents($envPath) : '';
if (preg_match('/^APP_KEY=/m', $content)) {
    $content = preg_replace('/^APP_KEY=.*/m', $key, $content);
} else {
    $content = rtrim($content, "\n") . "\n" . $key . "\n";
}
file_put_contents($envPath, $content);
echo $key . PHP_EOL;
