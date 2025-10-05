<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../integration/wc_client.php';

$product = null;
$error = null;
$slug = 'kishanskraft-cold-pressed-mustard-oil';

try {
    $client = WooCommerceClient::fromConfigFile(__DIR__ . '/../config.json');
    $response = $client->get('products', ['slug' => $slug]);
    if (!$response) {
        $response = $client->get('products', ['search' => 'KishansKraft']);
    }
    $product = $response[0] ?? null;
} catch (Throwable $e) {
    $error = $e->getMessage();
}

kk_render_header('KishansKraft Mustard Oil', 'page-home');
?>
<section class="kk-hero" id="hero" data-scroll>
    <div class="kk-container kk-hero__grid">
        <div class="kk-hero__copy">
            <p class="kk-eyebrow">Cold-Pressed Goodness</p>
            <h1>Pure. Fresh. Chemical-Free.<br>From the fields of Bihar.</h1>
            <p class="kk-lead">Experience the golden richness of KishansKraft Cold-Pressed Mustard Oil. Crafted in small batches, sealed with care, delivered to your kitchen.</p>
            <div class="kk-hero__cta">
                <a class="kk-btn kk-btn--primary" href="#checkout">Buy Now</a>
                <a class="kk-btn kk-btn--ghost" href="#product">Discover Product</a>
            </div>
            <?php if ($error): ?>
                <p class="kk-alert kk-alert--warning">Unable to fetch live product data right now. Trying again shortly…</p>
            <?php endif; ?>
        </div>
        <div class="kk-hero__image">
            <div class="kk-bottle-frame">
                <img src="https://images.unsplash.com/photo-1514996937319-344454492b37?auto=format&fit=crop&w=900&q=80" alt="KishansKraft Cold-Pressed Mustard Oil bottle" loading="lazy">
                <div class="kk-price-tag" id="kk-hero-price">
                    <span class="kk-price-tag__label">Only</span>
                    <span class="kk-price-tag__value" data-product-price>
                        <?php echo $product['price'] ?? '—'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="kk-section kk-product" id="product" data-scroll data-product
    data-product-id="<?php echo $product['id'] ?? ''; ?>"
    data-product-name="<?php echo htmlspecialchars($product['name'] ?? 'KishansKraft Cold-Pressed Mustard Oil', ENT_QUOTES, 'UTF-8'); ?>"
    data-product-price="<?php echo $product['price'] ?? ''; ?>"
    data-product-sku="<?php echo htmlspecialchars($product['sku'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <div class="kk-container kk-product__grid">
        <div>
            <h2><?php echo htmlspecialchars($product['name'] ?? 'KishansKraft Cold-Pressed Mustard Oil', ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="kk-product__description" data-product-description>
                <?php
                if ($product && !empty($product['short_description'])) {
                    echo $product['short_description'];
                } else {
                    echo 'Sustainably harvested mustard seeds are cold-pressed within 12 hours of collection, ensuring every drop retains its natural antioxidants, aroma, and flavor.';
                }
                ?>
            </p>
            <ul class="kk-feature-list">
                <li>✔️ Cold-pressed within 12 hours of harvest</li>
                <li>✔️ Lab-tested for purity and free from argemone</li>
                <li>✔️ Rich in omega-3 and natural antioxidants</li>
                <li>✔️ Traditional Kachi Ghani process for authentic taste</li>
            </ul>
            <div class="kk-product__purchase">
                <div class="kk-price" data-product-price>
                    ₹<?php echo $product['price'] ?? '—'; ?>
                    <span class="kk-price__unit">/ 1L bottle</span>
                </div>
                <button class="kk-btn kk-btn--primary" data-action="add-to-cart">Add to Cart</button>
                <button class="kk-btn kk-btn--ghost" data-action="refresh-product">Refresh Details</button>
            </div>
            <p class="kk-stock" data-product-stock>
                <?php
                if ($product && isset($product['stock_status'])) {
                    echo $product['stock_status'] === 'instock' ? 'In Stock & ships within 24 hours.' : 'Currently out of stock. Check back soon!';
                } else {
                    echo 'Checking stock availability…';
                }
                ?>
            </p>
        </div>
        <aside>
            <div class="kk-card kk-card--benefits" id="benefits">
                <h3>Why Farmers Trust KishansKraft</h3>
                <ul>
                    <li><strong>Farm-to-Bottle:</strong> Traceable supply chain from Bihar farms.</li>
                    <li><strong>Zero Additives:</strong> No solvents, preservatives, or chemicals.</li>
                    <li><strong>Balanced Nutrition:</strong> Rich in natural MUFA & PUFA.</li>
                    <li><strong>Smoky Aroma:</strong> Perfect for tadka, pickles, and wellness rituals.</li>
                </ul>
            </div>
        </aside>
    </div>
</section>
<section class="kk-section kk-checkout" id="checkout" data-scroll>
    <div class="kk-container">
        <h2>Swift Checkout</h2>
        <p>Ready to bring purity home? Complete the checkout form and we’ll handle the rest.</p>
        <a class="kk-btn kk-btn--accent" href="/frontend/checkout.php">Proceed to Checkout</a>
    </div>
</section>
<section class="kk-section kk-testimonials" data-scroll>
    <div class="kk-container kk-testimonials__grid">
        <article class="kk-quote">
            <p>“The aroma reminds me of my grandmother’s pickles. KishansKraft is now a staple in my kitchen.”</p>
            <h4>– Swati Sharma, Patna</h4>
        </article>
        <article class="kk-quote">
            <p>“I recommend KishansKraft oil to all my wellness clients for its authentic flavor and purity.”</p>
            <h4>– Chef Arjun, Mumbai</h4>
        </article>
        <article class="kk-quote">
            <p>“Fast delivery, beautiful packaging, and most importantly, chemical-free cold-pressed oil.”</p>
            <h4>– Nisha Verma, Delhi</h4>
        </article>
    </div>
</section>
<section class="kk-section kk-faq" data-scroll>
    <div class="kk-container">
        <h2>Frequently Asked Questions</h2>
        <div class="kk-accordion">
            <details open>
                <summary>Is the oil refined?</summary>
                <p>No, KishansKraft oil is 100% cold-pressed using Kachi Ghani. Nothing is added or removed.</p>
            </details>
            <details>
                <summary>Do you deliver across India?</summary>
                <p>Yes, we ship pan-India within 5-7 working days via trusted logistics partners.</p>
            </details>
            <details>
                <summary>What payment methods do you support?</summary>
                <p>Cash on Delivery, UPI, NetBanking, and Cards are processed securely via WooCommerce.</p>
            </details>
        </div>
    </div>
</section>
<?php kk_render_footer(); ?>
