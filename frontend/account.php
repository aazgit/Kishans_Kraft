<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../integration/wc_client.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$customer = $_SESSION['customer'] ?? null;
$orders = [];
$error = null;

if ($customer) {
    try {
        $client = WooCommerceClient::fromConfigFile(__DIR__ . '/../config.json');
        $orders = $client->get('orders', [
            'customer' => (int) $customer['id'],
            'per_page' => 20,
            'orderby' => 'date',
            'order' => 'desc',
        ]);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

kk_render_header('My Account – KishansKraft', 'page-account');
?>
<section class="kk-section kk-account" data-scroll>
    <div class="kk-container">
        <div class="kk-account__header">
            <h1>My Account</h1>
            <?php if ($customer): ?>
                <p>Hello, <strong><?php echo htmlspecialchars($customer['first_name'] ?? $customer['email'], ENT_QUOTES, 'UTF-8'); ?></strong> 👋</p>
                <form method="post" action="/integration/wc_api.php?action=logout" class="kk-inline-form">
                    <button type="submit" class="kk-btn kk-btn--ghost">Logout</button>
                </form>
            <?php else: ?>
                <p><a href="/frontend/login.php">Login</a> to access your orders.</p>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="kk-alert kk-alert--warning">Unable to load orders at the moment. Please refresh.</div>
        <?php endif; ?>

        <?php if ($customer): ?>
            <div class="kk-orders" data-orders-list>
                <?php if ($orders): ?>
                    <?php foreach ($orders as $order): ?>
                        <article class="kk-order-card">
                            <header>
                                <h3>Order #<?php echo htmlspecialchars((string) $order['id'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <span class="kk-status kk-status--<?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </header>
                            <p class="kk-order-card__meta">Placed on <?php echo date('d M Y', strtotime($order['date_created'] ?? 'now')); ?></p>
                            <ul class="kk-order-card__items">
                                <?php foreach ($order['line_items'] as $item): ?>
                                    <li>
                                        <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        × <?php echo (int) $item['quantity']; ?>
                                        <span>₹<?php echo number_format((float) $item['total'], 2); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <footer>
                                <strong>Total: ₹<?php echo number_format((float) $order['total'], 2); ?></strong>
                            </footer>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No orders yet. <a href="/frontend/index.php#checkout">Order now</a> to get started!</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="kk-card kk-card--center">
                <p>Sign in to see your order history and sync POS purchases.</p>
                <a href="/frontend/login.php" class="kk-btn kk-btn--primary">Login / Sign up</a>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php kk_render_footer(); ?>
