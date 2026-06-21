<?php
// Lightweight web/CLI script to test configured API_URL and a specific path.
// Usage (CLI): php public/api_test.php [/path]
// Usage (HTTP): http://localhost/public/api_test.php?path=/ping

error_reporting(E_ALL);
ini_set('display_errors', '1');

$path = '/';
if (php_sapi_name() === 'cli') {
    $path = $argv[1] ?? '/';
} else {
    $path = $_GET['path'] ?? '/';
}

$envFile = __DIR__ . '/../.env';
$apiUrl = getenv('API_URL') ?: null;
if (! $apiUrl && file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), 'API_URL=')) {
            $apiUrl = substr(trim($line), strlen('API_URL='));
            $apiUrl = trim($apiUrl, "\"' ");
            break;
        }
    }
}

if (! $apiUrl) {
    $out = ['error' => 'API_URL not set in environment or .env'];
    header('Content-Type: application/json');
    echo json_encode($out, JSON_PRETTY_PRINT);
    exit(2);
}

$apiUrl = rtrim($apiUrl, '/');
$uri = $apiUrl . (ltrim($path, '/') === '' ? '' : '/' . ltrim($path, '/'));

$ch = curl_init($uri);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$resp = curl_exec($ch);
if ($resp === false) {
    $err = curl_error($ch);
    curl_close($ch);
    $out = ['error' => $err];
    header('Content-Type: application/json');
    echo json_encode($out, JSON_PRETTY_PRINT);
    exit(3);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($resp, 0, $headerSize);
$body = substr($resp, $headerSize);
curl_close($ch);

$out = [
    'requested' => $uri,
    'status' => $httpCode,
    'headers' => $header,
    'body_snippet' => mb_substr($body, 0, 200),
];

header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
