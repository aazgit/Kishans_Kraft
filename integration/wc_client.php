<?php

declare(strict_types=1);

final class WooCommerceClient
{
    private string $apiUrl;
    private string $consumerKey;
    private string $consumerSecret;

    public function __construct(array $config)
    {
        $this->apiUrl = rtrim($config['api_url'], '/') . '/';
        $this->consumerKey = $config['consumer_key'];
        $this->consumerSecret = $config['consumer_secret'];
    }

    public static function fromConfigFile(string $path): self
    {
        $config = kk_wc_load_config($path);
        return new self($config);
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, $query);
    }

    public function post(string $endpoint, array $payload): array
    {
        return $this->request('POST', $endpoint, [], $payload);
    }

    public function put(string $endpoint, array $payload): array
    {
        return $this->request('PUT', $endpoint, [], $payload);
    }

    public function delete(string $endpoint, array $payload = []): array
    {
        return $this->request('DELETE', $endpoint, [], $payload);
    }

    private function request(string $method, string $endpoint, array $query = [], array $payload = []): array
    {
        $url = $this->apiUrl . ltrim($endpoint, '/');
        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init($url);
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERPWD => $this->consumerKey . ':' . $this->consumerSecret,
        ]);

        if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
        }

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($errno) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('cURL error: ' . $error, $errno);
        }

        curl_close($ch);

        if ($response === false || $response === '') {
            throw new RuntimeException('Empty response from WooCommerce API.');
        }

        $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if ($statusCode >= 400) {
            $message = is_array($decoded) && isset($decoded['message']) ? (string) $decoded['message'] : 'WooCommerce API error.';
            throw new RuntimeException($message, $statusCode);
        }

        return $decoded;
    }

    public function getWpBaseUrl(): string
    {
        return preg_replace('#/wc/v3/?$#', '/', $this->apiUrl) ?? $this->apiUrl;
    }
}

function kk_wc_load_config(string $path): array
{
    if (!file_exists($path)) {
        throw new RuntimeException('Configuration file not found at ' . $path);
    }
    $config = json_decode(file_get_contents($path) ?: '', true, 512, JSON_THROW_ON_ERROR);
    foreach (['api_url', 'consumer_key', 'consumer_secret'] as $key) {
        if (empty($config[$key])) {
            throw new RuntimeException('Configuration key missing: ' . $key);
        }
    }
    return $config;
}
