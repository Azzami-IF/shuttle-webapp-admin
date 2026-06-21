<?php
$url = 'https://api.ambatu.my.id/api/vehicles';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$resp = curl_exec($ch);
if ($resp === false) {
    echo "ERR: " . curl_error($ch) . PHP_EOL;
    exit(1);
}
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($resp, $headerSize);
echo "CODE: $code\n";
echo "LENGTH: " . strlen($body) . "\n";
echo "BODY_SNIPPET:\n" . substr($body, 0, 400) . PHP_EOL;
curl_close($ch);
