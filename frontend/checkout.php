<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

kk_render_header('Checkout – KishansKraft Mustard Oil', 'page-checkout');
?>
<section class="kk-section kk-checkout-page" data-scroll>
    <div class="kk-container kk-checkout-page__grid">
        <div>
            <h1>Checkout</h1>
            <p>Complete your details and we’ll place the order instantly.</p>
            <form class="kk-form" id="kk-checkout-form" data-checkout-form>
                <div class="kk-form__group">
                    <label for="first_name">Full Name</label>
                    <input type="text" id="first_name" name="first_name" placeholder="Rohit Kumar" required>
                </div>
                <div class="kk-form__group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="rohit@example.com" required>
                </div>
                <div class="kk-form__group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" placeholder="9876543210" required>
                </div>
                <div class="kk-form__group">
                    <label for="address_1">Address</label>
                    <input type="text" id="address_1" name="address_1" placeholder="House no, Street" required>
                </div>
                <div class="kk-form__group">
                    <label for="address_2">Landmark</label>
                    <input type="text" id="address_2" name="address_2" placeholder="Apartment, landmark (optional)">
                </div>
                <div class="kk-form__group kk-form__group--inline">
                    <div>
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" required>
                    </div>
                    <div>
                        <label for="state">State</label>
                        <input type="text" id="state" name="state" required>
                    </div>
                    <div>
                        <label for="postcode">PIN Code</label>
                        <input type="text" id="postcode" name="postcode" required pattern="[0-9]{6}">
                    </div>
                </div>
                <fieldset class="kk-form__fieldset">
                    <legend>Payment Method</legend>
                    <label class="kk-radio">
                        <input type="radio" name="payment_method" value="cod" checked>
                        <span>Cash on Delivery</span>
                    </label>
                    <label class="kk-radio">
                        <input type="radio" name="payment_method" value="upi">
                        <span>UPI</span>
                    </label>
                    <label class="kk-radio">
                        <input type="radio" name="payment_method" value="netbanking">
                        <span>Net Banking</span>
                    </label>
                </fieldset>
                <button type="submit" class="kk-btn kk-btn--primary">Place Order</button>
            </form>
            <div class="kk-alert kk-alert--success kk-hidden" id="kk-checkout-success">
                <h2>Thank you!</h2>
                <p>Your order has been placed successfully.</p>
                <p>Order ID: <strong data-order-id></strong></p>
                <a class="kk-btn kk-btn--ghost" href="/frontend/account.php">Go to My Orders</a>
            </div>
        </div>
        <aside class="kk-order-summary" id="kk-order-summary">
            <h2>Order Summary</h2>
            <div class="kk-card">
                <div class="kk-order-summary__item">
                    <div>
                        <h3 data-summary-name>KishansKraft Cold-Pressed Mustard Oil</h3>
                        <p>1L glass bottle</p>
                    </div>
                    <div class="kk-order-summary__price" data-summary-price>₹—</div>
                </div>
                <div class="kk-order-summary__footer">
                    <span>Total</span>
                    <strong data-summary-total>₹—</strong>
                </div>
            </div>
            <p class="kk-smallprint">Shipping is calculated automatically during order confirmation. COD charges may apply for select pin codes.</p>
        </aside>
    </div>
</section>
<?php kk_render_footer(); ?>
