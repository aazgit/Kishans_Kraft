# KishansKraft Oil Single-Product Store – Integration Overview

## WooCommerce API Usage
- **Base URL:** configured via `/config.json` (`api_url`). All requests appending relative paths (e.g., `products`, `orders`).
- **Auth:** HTTP Basic Auth using `consumer_key` and `consumer_secret`.
- **Headers:** `Content-Type: application/json` for POST/PUT/DELETE.

### Product Data
- **Endpoint:** `GET products?slug=kishanskraft-cold-pressed-mustard-oil`
- **Fallback:** `GET products` then filter first matching name/ID.
- **Caching:** store response in `localStorage` with timestamp; refresh after 10 minutes or when `force` flag true.

### Cart Operations
- We maintain a lightweight client-side cart (`localStorage.cart`).
- Checkout directly creates an order; WooCommerce handles payment method captured in order `payment_method` (`cod`, `upi`, etc.).

### Order Creation
- **Endpoint:** `POST orders`
- **Payload:**
  ```json
  {
    "payment_method": "cod",
    "payment_method_title": "Cash on Delivery",
    "set_paid": false,
    "billing": { ... },
    "shipping": { ... },
    "line_items": [
      {
        "product_id": 123,
        "quantity": 1
      }
    ],
    "customer_id": 45 // optional if logged in
  }
  ```
- On success store order in session storage for confirmation display.

### Customers
- **Signup:** `POST customers`
- **Login:** `GET customers?email=...` filtered by password via JWT-auth? WooCommerce REST does not support password check directly; use `POST /wc/v3/login` plugin? → We will proxy using `POST /customers/auth` custom? Since only REST available, use WooCommerce REST `POST /customers` for signup and JWT authentication via `POST /jwt-auth/v1/token`. Because vanilla WooCommerce lacks login endpoint, we will implement a PHP session-based login by calling WordPress `wp-json/jwt-auth/v1/token` (assumed enabled). If unavailable, fallback to basic auth using stored credentials (document assumption). Store JWT token in PHP session + `localStorage`.
- **Orders for customer:** `GET orders?customer={id}`.

### POS Offline Flow
1. Operator adds order with customer details (optional) and quantity.
2. Order stored in IndexedDB `kk_pos_orders` with schema `id`, `payload`, `status` (`pending`, `synced`), `created_at`.
3. When navigator online event fires, iterate pending orders and POST to WooCommerce `orders` endpoint. On success mark as `synced` and record WooCommerce order ID.
4. Provide manual "Sync Now" button.

### API Playground
- Use fetch wrapper to call arbitrary endpoints relative to `api_url`.
- Form sets method, endpoint path, request body. Response displayed with syntax highlighted JSON.

## Security & Storage
- Credentials read server-side in PHP (`config.json`) to sign requests; never exposed directly client-side except for public product fetch proxied via PHP endpoints.
- PHP endpoints under `/integration/wc_api.php` expose JSON REST wrappers for the frontend via AJAX (prevent CORS and credential leakage).
- Sessions handled via PHP `$_SESSION` storing `customer` info and JWT token when available.

## Modules
- `integration/wc_api.php`: defines `WooCommerceClient` class (GET/POST/PUT/DELETE) and helper functions for login, product fetch, order creation, customer operations.
- AJAX entry points implemented via query parameters, e.g., `wc_api.php?action=get_product` returning JSON. Frontend calls with `fetch('/integration/wc_api.php?action=...')`.

## Error Handling
- All API responses normalized to `{ success: bool, data: mixed, error: { message, code } }`.
- Frontend displays toast notifications and retries (POS sync exponential backoff on failure).

## Offline Storage Summary
- Product cache: `localStorage.productCache` (expires 600s).
- Session: `sessionStorage.currentOrder` for success page.
- POS: IndexedDB `kk_pos_orders` using simple key-value via `idb` utility implemented manually with `indexedDB.open`.

## Assumptions
- WordPress site has JWT auth plugin enabled at `/wp-json/jwt-auth/v1/token`.
- Only one flagship product; ID stored in cache once fetched to reuse for POS sync.
- Hosting supports PHP 8+; JSON extension enabled.
