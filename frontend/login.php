<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

kk_render_header('Login – KishansKraft Mustard Oil', 'page-login');
?>
<section class="kk-section kk-auth" data-scroll>
    <div class="kk-container kk-auth__grid">
        <div class="kk-auth__card">
            <h1>Welcome back</h1>
            <p>Log in to track your orders and reorder instantly.</p>
            <form class="kk-form" id="kk-login-form" data-login-form>
                <div class="kk-form__group">
                    <label for="login_email">Email</label>
                    <input type="email" id="login_email" name="username" placeholder="you@example.com" required>
                </div>
                <div class="kk-form__group">
                    <label for="login_password">Password</label>
                    <input type="password" id="login_password" name="password" required>
                </div>
                <button type="submit" class="kk-btn kk-btn--primary">Login</button>
            </form>
            <p class="kk-smallprint">New to KishansKraft? <a href="#signup" data-switch-auth="signup">Create an account</a>.</p>
        </div>
        <div class="kk-auth__card">
            <h2 id="signup">Create account</h2>
            <p>Build your profile for faster checkout and exclusive offers.</p>
            <form class="kk-form" id="kk-signup-form" data-signup-form>
                <div class="kk-form__group">
                    <label for="signup_first_name">Full Name</label>
                    <input type="text" id="signup_first_name" name="first_name" placeholder="Asha Kumari" required>
                </div>
                <div class="kk-form__group">
                    <label for="signup_email">Email</label>
                    <input type="email" id="signup_email" name="email" placeholder="asha@example.com" required>
                </div>
                <div class="kk-form__group">
                    <label for="signup_phone">Phone</label>
                    <input type="tel" id="signup_phone" name="phone" required>
                </div>
                <div class="kk-form__group">
                    <label for="signup_password">Password</label>
                    <input type="password" id="signup_password" name="password" required minlength="6">
                </div>
                <button type="submit" class="kk-btn kk-btn--accent">Create Account</button>
            </form>
            <p class="kk-smallprint">Already registered? <a href="#login" data-switch-auth="login">Sign in here</a>.</p>
        </div>
    </div>
</section>
<?php kk_render_footer(); ?>
