<?php

declare(strict_types=1);

function kk_render_header(string $pageTitle = 'KishansKraft Oil', string $bodyClass = ''): void
{
    $title = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
    $bodyClassAttr = $bodyClass ? ' class="' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '"' : '';
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="/assets/js/app.js"></script>
</head>
<body{$bodyClassAttr}>
    <header class="kk-header">
        <div class="kk-container kk-header__inner">
            <a class="kk-logo" href="/frontend/index.php">
                <span class="kk-logo__icon">🟡</span>
                <span class="kk-logo__text">KishansKraft Oil</span>
            </a>
            <nav class="kk-nav">
                <a href="/frontend/index.php#product">Product</a>
                <a href="/frontend/index.php#benefits">Benefits</a>
                <a href="/frontend/index.php#checkout" class="kk-btn kk-btn--primary kk-nav__cta">Buy Now</a>
                <a href="/frontend/account.php" class="kk-nav__link">My Account</a>
                <a href="/frontend/pos.php" class="kk-nav__link">POS</a>
                <a href="/frontend/api-playground.php" class="kk-nav__link">API Playground</a>
            </nav>
        </div>
    </header>
    <main class="kk-main" id="top">
HTML;
}

function kk_render_footer(): void
{
    $year = date('Y');
    echo <<<HTML
    </main>
    <footer class="kk-footer">
        <div class="kk-container kk-footer__grid">
            <div>
                <h3>KishansKraft Oil</h3>
                <p>Pure cold-pressed oils crafted in Bihar.</p>
            </div>
            <div>
                <h4>Contact</h4>
                <p>Village Rampur, Purnia, Bihar 854301</p>
                <p>+91 98765 43210</p>
                <p>hello@kishanskraft.com</p>
            </div>
            <div>
                <h4>Quick Links</h4>
                <a href="/frontend/index.php#checkout">Buy Now</a>
                <a href="/frontend/account.php">My Orders</a>
                <a href="/frontend/api-playground.php">API Playground</a>
            </div>
        </div>
        <p class="kk-footer__bottom">© {$year} KishansKraft Agro Foods Pvt. Ltd. All rights reserved.</p>
    </footer>
    <div class="kk-toast-container" id="kk-toast-container"></div>
</body>
</html>
HTML;
}
