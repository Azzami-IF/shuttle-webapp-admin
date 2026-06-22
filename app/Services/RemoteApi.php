<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Services\ApiResponseNormalizer;

class RemoteApi
{
    protected string $base;
    protected ?string $token;

    public function __construct()
    {
        $this->base = rtrim(config('services.remote_api.base', env('REMOTE_API_BASE', '')) , '/');
        // Prefer session token for logged-in admin, fall back to configured token
        $this->token = session('admin_api_token') ?: config('services.remote_api.token', env('REMOTE_API_TOKEN', null));
    }

    protected function client()
    {
        // Ensure base URL ends with a trailing slash so relative paths
        // concatenated by the HTTP client resolve correctly.
        $base = rtrim($this->base, '/') . '/';
        $client = Http::baseUrl($base)->acceptJson();
        if ($this->token) {
            $client = $client->withToken($this->token);
        }
        return $client;
    }

    public function get(string $path, array $query = [])
    {
        return $this->client()->get(ltrim($path, '/'), $query);
    }

    /**
     * Perform GET and return normalized JSON (objects/collections)
     */
    public function getJsonNormalized(string $path, array $query = [])
    {
        $r = $this->get($path, $query);
        $json = $r->json();
        return ApiResponseNormalizer::normalize($json);
    }

    public function post(string $path, array $data = [])
    {
        return $this->client()->post(ltrim($path, '/'), $data);
    }

    /**
     * Perform POST and return normalized JSON
     */
    public function postJsonNormalized(string $path, array $data = [])
    {
        $r = $this->post($path, $data);
        return ApiResponseNormalizer::normalize($r->json());
    }

    public function put(string $path, array $data = [])
    {
        return $this->client()->put(ltrim($path, '/'), $data);
    }

    /**
     * Perform PUT and return normalized JSON
     */
    public function putJsonNormalized(string $path, array $data = [])
    {
        $r = $this->put($path, $data);
        return ApiResponseNormalizer::normalize($r->json());
    }

    public function delete(string $path)
    {
        return $this->client()->delete(ltrim($path, '/'));
    }
}
