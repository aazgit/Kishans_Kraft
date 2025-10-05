<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

kk_render_header('API Playground – KishansKraft', 'page-api-playground');
?>
<section class="kk-section kk-api-playground" data-scroll>
    <div class="kk-container">
        <header class="kk-api-playground__header">
            <h1>WooCommerce API Playground</h1>
            <p>Experiment with the live store API using your site credentials from <code>config.json</code>.</p>
        </header>
        <div class="kk-api-playground__grid">
            <form class="kk-card kk-api-form" id="kk-api-form" data-api-form>
                <div class="kk-form__group">
                    <label for="api_endpoint">Endpoint</label>
                    <input type="text" id="api_endpoint" name="endpoint" value="products" required>
                </div>
                <div class="kk-form__group">
                    <label for="api_method">Method</label>
                    <select id="api_method" name="method">
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="DELETE">DELETE</option>
                    </select>
                </div>
                <div class="kk-form__group">
                    <label for="api_body">Request Body (JSON)</label>
                    <textarea id="api_body" name="body" rows="8" placeholder='{"per_page": 10}'></textarea>
                </div>
                <button type="submit" class="kk-btn kk-btn--primary">Send Request</button>
                <button type="button" class="kk-btn kk-btn--ghost" data-action="api-clear">Clear</button>
            </form>
            <div class="kk-card kk-api-response">
                <h2>Response</h2>
                <pre id="api-response" data-api-response><code>// Response will appear here</code></pre>
            </div>
        </div>
        <section class="kk-card kk-api-examples">
            <h2>Quick Examples</h2>
            <ul>
                <li><button class="kk-link" data-example='{"method":"GET","endpoint":"products","body":""}'>List products</button></li>
                <li><button class="kk-link" data-example='{"method":"GET","endpoint":"orders?per_page=5","body":""}'>Last 5 orders</button></li>
                <li><button class="kk-link" data-example='{"method":"POST","endpoint":"customers","body":"{\"email\":\"demo@example.com\",\"first_name\":\"Demo\",\"last_name\":\"User\"}"}'>Create demo customer</button></li>
            </ul>
        </section>
    </div>
</section>
<?php kk_render_footer(); ?>
