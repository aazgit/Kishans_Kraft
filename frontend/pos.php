<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../integration/wc_client.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$product = null;
$error = null;
$slug = 'kishanskraft-cold-pressed-mustard-oil';

try {
    $client = WooCommerceClient::fromConfigFile(__DIR__ . '/../config.json');
    $response = $client->get('products', ['slug' => $slug]);
    $product = $response[0] ?? null;
} catch (Throwable $e) {
    $error = $e->getMessage();
}

kk_render_header('POS – KishansKraft', 'page-pos');
?>
<section class="kk-section kk-pos" data-scroll>
    <div class="kk-container">
        <header class="kk-pos__header">
            <h1>Point of Sale (Offline)</h1>
            <p>Record walk-in purchases even without internet. We’ll sync to WooCommerce once you’re online.</p>
            <div class="kk-pos__status" data-pos-status>
                <span class="kk-dot"></span>
                <span data-status-text>Checking connection…</span>
            </div>
            <?php if ($error): ?>
                <p class="kk-alert kk-alert--warning">Unable to load product details for POS. Using cached data.</p>
            <?php endif; ?>
        </header>

        <div class="kk-pos__grid" data-pos
            data-product-id="<?php echo $product['id'] ?? ''; ?>"
            data-product-price="<?php echo $product['price'] ?? ''; ?>"
            data-product-name="<?php echo htmlspecialchars($product['name'] ?? 'KishansKraft Cold-Pressed Mustard Oil', ENT_QUOTES, 'UTF-8'); ?>">
            <form class="kk-card kk-pos__form" id="kk-pos-form" data-pos-form>
                <h2>New Sale</h2>
                <div class="kk-form__group">
                    <label for="pos_customer_name">Customer Name</label>
                    <input type="text" id="pos_customer_name" name="customer_name" placeholder="Walk-in customer">
                </div>
                <div class="kk-form__group">
                    <label for="pos_customer_phone">Phone</label>
                    <input type="tel" id="pos_customer_phone" name="customer_phone" placeholder="Optional">
                </div>
                <div class="kk-form__group">
                    <label for="pos_quantity">Quantity (bottles)</label>
                    <input type="number" id="pos_quantity" name="quantity" min="1" value="1" required>
                </div>
                <div class="kk-form__group">
                    <label for="pos_payment">Payment Method</label>
                    <select id="pos_payment" name="payment_method">
                        <option value="cash">Cash</option>
                        <option value="upi">UPI</option>
                        <option value="card">Card</option>
                    </select>
                </div>
                <div class="kk-form__group">
                    <label for="pos_notes">Notes</label>
                    <textarea id="pos_notes" name="notes" rows="3" placeholder="Any special instructions"></textarea>
                </div>
                <button type="submit" class="kk-btn kk-btn--primary">Save Offline Sale</button>
            </form>

            <div class="kk-card kk-pos__queue">
                <div class="kk-pos__queue-header">
                    <h2>Pending Orders</h2>
                    <div class="kk-pos__queue-actions">
                        <button class="kk-btn kk-btn--ghost" data-action="sync-now">Sync Now</button>
                        <button class="kk-btn kk-btn--ghost" data-action="export-json">Export JSON</button>
                    </div>
                </div>
                <ul class="kk-pos__list" data-pos-list>
                    <li class="kk-empty">No offline orders yet.</li>
                </ul>
            </div>
        </div>

        <section class="kk-card kk-pos__history">
            <h2>Sync History</h2>
            <ul data-sync-log>
                <li class="kk-empty">No sync activity recorded.</li>
            </ul>
        </section>
    </div>
</section>
<?php kk_render_footer(); ?>
