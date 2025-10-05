<?php

declare(strict_types=1);

require_once __DIR__ . '/wc_client.php';

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$configPath = __DIR__ . '/../config.json';
try {
    $config = kk_wc_load_config($configPath);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'CONFIG_ERROR',
            'message' => $e->getMessage(),
        ],
    ], JSON_THROW_ON_ERROR);
    exit;
}

function respond(bool $success, $data = null, array $error = null, int $status = 200): void
{
    http_response_code($status);
    $payload = ['success' => $success];
    if ($success) {
        $payload['data'] = $data;
    } else {
        $payload['error'] = $error ?? ['message' => 'Unknown error'];
    }

    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

function getJsonInput(): array
{
    $contents = file_get_contents('php://input');
    if ($contents === false || $contents === '') {
        return [];
    }
    return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
}

function kk_placeholder_product(?string $reason = null): array
{
    if ($reason) {
        error_log('[KishansKraft] Using placeholder product: ' . $reason);
    }

    return [
        'id' => 0,
        'name' => 'KishansKraft Cold-Pressed Mustard Oil',
        'slug' => 'kishanskraft-cold-pressed-mustard-oil',
        'price' => '499',
        'regular_price' => '499',
        'sale_price' => '',
        'stock_status' => 'instock',
        'short_description' => '<p>Sustainably harvested mustard seeds are cold-pressed within 12 hours of collection, preserving aroma, antioxidants, and authentic flavour.</p>',
        'description' => '<p>This is offline fallback product information. Connect to the live WooCommerce store for real-time price and stock.</p>',
        'images' => [
            [
                'src' => 'https://images.unsplash.com/photo-1514996937319-344454492b37?auto=format&fit=crop&w=900&q=80',
            ],
        ],
        'sku' => 'KK-MUSTARD-1L',
        'kk_placeholder' => true,
        'kk_placeholder_reason' => $reason,
    ];
}

$client = new WooCommerceClient($config);
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'ping':
            respond(true, ['message' => 'OK']);

        case 'get_product':
            $slug = $_GET['slug'] ?? 'kishanskraft-cold-pressed-mustard-oil';
            $product = null;
            $reason = null;
            try {
                $products = $client->get('products', ['slug' => $slug]);
                if (!$products) {
                    $products = $client->get('products', ['search' => 'KishansKraft']);
                    if (!$products) {
                        $reason = 'WooCommerce product search returned empty.';
                    }
                }
                $product = $products[0] ?? null;
            } catch (Throwable $e) {
                $reason = $e->getMessage();
            }

            if (!$product) {
                $fallback = kk_placeholder_product($reason);
                $_SESSION['product_id'] = $fallback['id'];
                respond(true, $fallback);
            }

            $_SESSION['product_id'] = $product['id'];
            respond(true, $product);

        case 'create_order':
            $payload = getJsonInput();
            if (isset($_SESSION['customer']['id'])) {
                $payload['customer_id'] = (int) $_SESSION['customer']['id'];
            }
            $order = $client->post('orders', $payload);
            respond(true, $order, null, 201);

        case 'signup':
            $payload = getJsonInput();
            $customer = $client->post('customers', $payload);
            $_SESSION['customer'] = $customer;
            respond(true, $customer, null, 201);

        case 'login':
            $payload = getJsonInput();
            if (!isset($payload['username'], $payload['password'])) {
                respond(false, null, ['code' => 'INVALID_BODY', 'message' => 'Username and password are required.'], 400);
            }
            $wpBase = rtrim($client->getWpBaseUrl(), '/') . '/';
            $tokenUrl = $wpBase . 'jwt-auth/v1/token';

            $ch = curl_init($tokenUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
                CURLOPT_POSTFIELDS => json_encode([
                    'username' => $payload['username'],
                    'password' => $payload['password'],
                ], JSON_THROW_ON_ERROR),
                CURLOPT_TIMEOUT => 30,
            ]);

            $tokenResponse = curl_exec($ch);
            if ($tokenResponse === false) {
                throw new RuntimeException('Login request failed: ' . curl_error($ch));
            }
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $tokenData = json_decode($tokenResponse, true, 512, JSON_THROW_ON_ERROR);
            if ($statusCode >= 400) {
                $message = $tokenData['message'] ?? 'Login failed.';
                respond(false, null, ['code' => 'LOGIN_FAILED', 'message' => $message], $statusCode);
            }

            $customers = $client->get('customers', ['email' => $payload['username']]);
            $customer = $customers[0] ?? null;
            if (!$customer) {
                respond(false, null, ['code' => 'CUSTOMER_NOT_FOUND', 'message' => 'Customer account not found.'], 404);
            }
            $_SESSION['customer'] = $customer;
            $_SESSION['jwt'] = $tokenData;
            respond(true, ['customer' => $customer, 'token' => $tokenData]);

        case 'logout':
            unset($_SESSION['customer'], $_SESSION['jwt']);
            respond(true, ['message' => 'Logged out']);

        case 'get_session':
            $customer = $_SESSION['customer'] ?? null;
            respond(true, ['customer' => $customer]);

        case 'get_orders':
            $customerId = $_SESSION['customer']['id'] ?? null;
            if (!$customerId) {
                respond(false, null, ['code' => 'UNAUTHENTICATED', 'message' => 'Login required.'], 401);
            }
            $orders = $client->get('orders', ['customer' => (int) $customerId, 'per_page' => 20, 'orderby' => 'date', 'order' => 'desc']);
            respond(true, $orders);

        case 'pos_sync':
            $payload = getJsonInput();
            if (!isset($payload['orders']) || !is_array($payload['orders'])) {
                respond(false, null, ['code' => 'INVALID_BODY', 'message' => 'Orders payload missing.'], 400);
            }
            $results = [];
            foreach ($payload['orders'] as $orderPayload) {
                try {
                    if (isset($_SESSION['customer']['id']) && empty($orderPayload['customer_id'])) {
                        $orderPayload['customer_id'] = (int) $_SESSION['customer']['id'];
                    }
                    $results[] = [
                        'success' => true,
                        'order' => $client->post('orders', $orderPayload),
                    ];
                } catch (Throwable $e) {
                    $results[] = [
                        'success' => false,
                        'error' => [
                            'message' => $e->getMessage(),
                            'code' => $e->getCode(),
                        ],
                    ];
                }
            }
            respond(true, $results);

        case 'proxy':
            $payload = getJsonInput();
            $endpoint = $payload['endpoint'] ?? null;
            if (!$endpoint) {
                respond(false, null, ['code' => 'INVALID_ENDPOINT', 'message' => 'Endpoint is required.'], 400);
            }
            $method = strtoupper($payload['method'] ?? 'GET');
            $body = $payload['body'] ?? [];
            $query = $payload['query'] ?? [];
            try {
                switch ($method) {
                    case 'GET':
                        $result = $client->get($endpoint, is_array($body) ? $body : $query);
                        break;
                    case 'POST':
                        $payloadBody = is_array($body) ? $body : [];
                        $result = $client->post($endpoint, $payloadBody);
                        break;
                    case 'PUT':
                        $payloadBody = is_array($body) ? $body : [];
                        $result = $client->put($endpoint, $payloadBody);
                        break;
                    case 'DELETE':
                        $payloadBody = is_array($body) ? $body : [];
                        $result = $client->delete($endpoint, $payloadBody);
                        break;
                    default:
                        respond(false, null, ['code' => 'INVALID_METHOD', 'message' => 'Unsupported method.'], 400);
                }
            } catch (Throwable $e) {
                respond(false, null, [
                    'code' => $e->getCode() ?: 'API_ERROR',
                    'message' => $e->getMessage(),
                ], 500);
            }
            respond(true, $result);

        default:
            respond(false, null, ['code' => 'UNKNOWN_ACTION', 'message' => 'Unrecognized action.'], 400);
    }
} catch (Throwable $e) {
    respond(false, null, [
        'code' => $e->getCode() ?: 'UNCAUGHT',
        'message' => $e->getMessage(),
    ], 500);
}
