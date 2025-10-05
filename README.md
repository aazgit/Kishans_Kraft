# KishansKraft Oil – Single Product Store

Custom PHP frontend integrated with WooCommerce for the KishansKraft Cold-Pressed Mustard Oil flagship product.

## Features
- Dynamic single-product landing page that pulls pricing, description, and stock directly from WooCommerce.
- Checkout workflow that posts orders to `/wc/v3/orders` with live billing and shipping details.
- Customer authentication (signup/login) backed by WordPress JWT token endpoint and WooCommerce customers API.
- Account dashboard with order history feed for logged-in shoppers.
- Offline-ready POS screen powered by IndexedDB with background sync to WooCommerce when connectivity returns.
- API playground for quickly testing any WooCommerce REST endpoint using the stored credentials.

## Project Structure
```
config.json                  # WooCommerce API credentials and base URL
docs/architecture.md         # Integration map and assumptions
integration/
	wc_client.php              # Shared WooCommerce REST client
	wc_api.php                 # JSON bridge for frontend AJAX calls
frontend/
	includes/layout.php        # Shared header/footer helpers
	index.php                  # Hero, product overview, testimonials
	checkout.php               # Checkout form and success state
	login.php                  # Login/signup forms
	account.php                # My orders dashboard
	pos.php                    # Offline POS experience
	api-playground.php         # WooCommerce API tester UI
assets/
	css/style.css              # KishansKraft look & feel
	js/app.js                  # Frontend logic, caching, POS sync
```

## Configuration
Edit `config.json` with the live WooCommerce site details:
```json
{
	"api_url": "https://oil.kishanskraft.com/oil_wp/wp-json/wc/v3/",
	"consumer_key": "<ck_...>",
	"consumer_secret": "<cs_...>"
}
```
No `.env` file is used—keep this JSON outside of version control if needed.

## Deployment
1. Upload the repository contents to your Plesk hosting (e.g., `/httpdocs`).
2. Ensure the `integration` and `frontend` directories are web-accessible (`/frontend/index.php` is the homepage).
3. Confirm PHP has cURL and JSON extensions enabled (standard on most Plesk builds).
4. Update the WordPress site to expose the JWT auth endpoint at `/wp-json/jwt-auth/v1/token` for customer login.

## Usage
- Visit `/frontend/index.php` for the storefront. Prices and stock auto-refresh via the REST API with caching.
- `/frontend/checkout.php` posts orders via `integration/wc_api.php?action=create_order` and displays the WooCommerce order ID.
- `/frontend/login.php` and `/frontend/account.php` share the PHP session set through `integration/wc_api.php?action=login`.
- `/frontend/pos.php` works offline and syncs queued orders with WooCommerce when the browser reports `navigator.onLine === true`.
- `/frontend/api-playground.php` lets you send authorized requests to any WooCommerce endpoint through the `proxy` bridge.

## Offline POS Notes
- Orders are stored in IndexedDB (`kk_pos_db` → `orders` store). Browsers without IndexedDB fall back to `localStorage`.
- Use the **Sync Now** button or simply reconnect to the internet to push pending orders.
- The **Export JSON** action downloads all locally stored POS orders for manual reconciliation.

## Quality Checks
- PHP syntax validated with `php -l` across all PHP files.
- Frontend JavaScript implements granular error handling with toasts and retry hooks.

## Next Steps
- Configure SSL for the WooCommerce domain to keep credentials secure.
- If JWT auth is unavailable, consider adding an alternative customer login flow (custom plugin or OAuth token endpoint).