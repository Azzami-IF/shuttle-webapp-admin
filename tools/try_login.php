<?php
// Simple cURL script to GET login page and POST credentials using cookie jar
$base = 'http://127.0.0.1:8000';
$loginUrl = $base . '/login';
$cookieFile = __DIR__ . '/cookies.txt';
$email = 'admin@example.com';
$password = 'password';

// GET login page
$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$response = curl_exec($ch);
if ($response === false) {
    echo "ERROR: curl GET failed: " . curl_error($ch) . PHP_EOL;
    exit(1);
}
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "GET /login returned HTTP {$httpCode}\n";
    echo $headers . PHP_EOL;
    exit(1);
}

// extract CSRF token
if (preg_match('/_token" value="([^"]+)"/', $body, $m)) {
    $token = $m[1];
    echo "CSRF: {$token}\n";
} else {
    echo "ERROR: CSRF token not found\n";
    exit(1);
}

// POST credentials
$postFields = http_build_query([
    '_token' => $token,
    'email' => $email,
    'password' => $password,
]);

$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$response = curl_exec($ch);
if ($response === false) {
    echo "ERROR: curl POST failed: " . curl_error($ch) . PHP_EOL;
    exit(1);
}
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "POST response HTTP: {$httpCode}\n";
// print Location header if present
if (preg_match('/Location:\s*(.*)\r\n/i', $headers, $m)) {
    echo "LOCATION: " . trim($m[1]) . PHP_EOL;
}
$snippet = substr(trim($body), 0, 400);
echo "BODY_SNIPPET:\n" . $snippet . PHP_EOL;

return 0;
