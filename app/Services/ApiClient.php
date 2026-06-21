<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ApiClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('api.base_url', env('API_URL', 'http://localhost:8000/api')), '/');
    }

    /**
     * Simple GET request to the given path (absolute or relative).
     * Returns array with status and body (decoded JSON when possible).
     */
    public function get(string $path = '/') : array
    {
        $uri = $this->buildUri($path);
        try {
            $resp = Http::timeout(10)->get($uri);
            $status = $resp->status();
            $body = $resp->body();
            $decoded = null;
            if ($resp->header('Content-Type') && str_contains($resp->header('Content-Type'), 'application/json')) {
                $decoded = $resp->json();
            } else {
                $maybe = json_decode($body, true);
                if ($maybe !== null) {
                    $decoded = $maybe;
                }
            }
            return [
                'status' => $status,
                'body' => $decoded === null ? $body : $decoded,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 0,
                'body' => $e->getMessage(),
            ];
        }
    }

    protected function buildUri(string $path): string
    {
        $p = ltrim($path, '/');
        return $this->baseUrl . ($p === '' ? '' : '/' . $p);
    }
}
