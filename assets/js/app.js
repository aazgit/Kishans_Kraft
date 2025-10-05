(function () {
  const API_BASE = '/integration/wc_api.php';
  const PRODUCT_CACHE_KEY = 'kk.product.cache.v1';
  const CART_KEY = 'kk.cart.v1';
  const POS_DB_NAME = 'kk_pos_db';
  const POS_STORE = 'orders';
  const POS_LOCAL_FALLBACK = 'kk.pos.orders';
  const PLACEHOLDER_RETRY_DELAY = 60000;

  const toastContainer = document.getElementById('kk-toast-container');

  function uuid() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
      return window.crypto.randomUUID();
    }
    return `pos-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  }

  function showToast(message, type = 'info') {
    if (!toastContainer) return;
    const toast = document.createElement('div');
    toast.className = `kk-toast kk-toast--${type}`;
    toast.innerHTML = `<strong>${type === 'error' ? 'Oops' : 'Info'}</strong><div>${message}</div>`;
    toastContainer.appendChild(toast);
    setTimeout(() => {
      toast.classList.add('is-leaving');
      toast.addEventListener('transitionend', () => toast.remove(), { once: true });
      toast.remove();
    }, 4200);
  }

  async function api(action, { method = 'GET', data = null, query = null } = {}) {
    const url = new URL(API_BASE, window.location.origin);
    url.searchParams.set('action', action);
    if (query && method === 'GET') {
      Object.entries(query).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          url.searchParams.set(key, value);
        }
      });
    }

    const options = {
      method,
      headers: {
        Accept: 'application/json',
      },
      credentials: 'same-origin',
    };

    if (method !== 'GET') {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(data ?? {});
    }

    const response = await fetch(url.toString(), options);
    const payload = await response.json();
    if (!payload.success) {
      const errorMsg = payload?.error?.message || 'Request failed';
      throw new Error(errorMsg);
    }
    return payload.data;
  }

  function intersectObserverInit() {
    const elements = document.querySelectorAll('[data-scroll]');
    if (!('IntersectionObserver' in window) || !elements.length) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.2 });
    elements.forEach((el) => observer.observe(el));
  }

  function readCart() {
    try {
      const raw = localStorage.getItem(CART_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (err) {
      console.error(err);
      return null;
    }
  }

  function writeCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
  }

  function setProductCache(product) {
    localStorage.setItem(PRODUCT_CACHE_KEY, JSON.stringify({
      product,
      timestamp: Date.now(),
    }));
  }

  function getProductCache() {
    try {
      const raw = localStorage.getItem(PRODUCT_CACHE_KEY);
      if (!raw) return null;
      const parsed = JSON.parse(raw);
      if (!parsed?.timestamp || Date.now() - parsed.timestamp > 600_000) {
        return null;
      }
      return parsed.product;
    } catch (err) {
      console.error(err);
      return null;
    }
  }

  function updateProductUI(product) {
    if (!product) return;
    const productSection = document.querySelector('[data-product]');
    const isPlaceholder = Boolean(product.kk_placeholder);
    if (productSection) {
      const productIdValue = isPlaceholder ? '' : product.id;
      productSection.dataset.productId = productIdValue;
      productSection.dataset.productName = product.name;
      productSection.dataset.productPrice = product.price;
      productSection.dataset.productSku = product.sku || '';
      const nameEl = productSection.querySelector('[data-product-name]');
      if (nameEl) nameEl.textContent = product.name;
      const priceEls = productSection.querySelectorAll('[data-product-price]');
      priceEls.forEach((el) => {
        const priceValue = Number(product.price || 0);
        el.textContent = priceValue
          ? `₹${priceValue.toLocaleString('en-IN')}`
          : '₹—';
      });
      const descriptionEl = productSection.querySelector('[data-product-description]');
      if (descriptionEl && product.short_description) {
        descriptionEl.innerHTML = product.short_description;
      }
      const stockEl = productSection.querySelector('[data-product-stock]');
      if (stockEl) {
        const inStock = product.stock_status === 'instock';
        stockEl.textContent = inStock
          ? 'In Stock & ships within 24 hours.'
          : 'Currently out of stock. Check back soon!';
      }
      const addButton = productSection.querySelector('[data-action="add-to-cart"]');
      if (addButton) {
        if (!addButton.dataset.defaultLabel) {
          addButton.dataset.defaultLabel = addButton.textContent;
        }
        addButton.disabled = isPlaceholder;
        addButton.textContent = isPlaceholder
          ? 'Connect to store to buy'
          : addButton.dataset.defaultLabel;
      }
      if (isPlaceholder) {
        if (!productSection.dataset.placeholderActive) {
          showToast('Showing fallback product info. Connect to WooCommerce for live pricing.', 'error');
          productSection.dataset.placeholderActive = '1';
        }
      } else {
        delete productSection.dataset.placeholderActive;
      }
    }

    const heroPrice = document.querySelector('#kk-hero-price [data-product-price]');
    if (heroPrice) {
      const priceValue = Number(product.price || 0);
      heroPrice.textContent = priceValue
        ? `₹${priceValue.toLocaleString('en-IN')}`
        : '₹—';
    }

    const cart = readCart() || { quantity: 1 };
    cart.product = {
      id: isPlaceholder ? null : product.id,
      price: Number(product.price || 0),
      name: product.name,
    };
    cart.placeholder = isPlaceholder;
    writeCart(cart);
    updateOrderSummary();
  }

  async function loadProduct(force = false) {
    try {
      if (!force) {
        const cached = getProductCache();
        if (cached) {
          updateProductUI(cached);
          return;
        }
      }
      const product = await api('get_product');
      if (product && !product.kk_placeholder) {
        setProductCache(product);
      } else {
        localStorage.removeItem(PRODUCT_CACHE_KEY);
      }
      updateProductUI(product);
      if (product?.kk_placeholder) {
        setTimeout(() => loadProduct(true), PLACEHOLDER_RETRY_DELAY);
      }
    } catch (err) {
      console.error(err);
      showToast('Unable to fetch fresh product details. Retrying soon.', 'error');
      setTimeout(() => loadProduct(false), 15000);
    }
  }

  function updateOrderSummary() {
    const summaryPrice = document.querySelector('[data-summary-price]');
    const summaryTotal = document.querySelector('[data-summary-total]');
    const summaryName = document.querySelector('[data-summary-name]');
    const cart = readCart();
    if (!summaryPrice || !summaryTotal || !summaryName || !cart?.product) return;
    const quantity = cart.quantity ?? 1;
    const unitPrice = Number(cart.product.price || 0);
    const total = quantity * unitPrice;
    summaryName.textContent = cart.product.name;
    summaryPrice.textContent = unitPrice
      ? `₹${unitPrice.toLocaleString('en-IN')}`
      : '₹—';
    summaryTotal.textContent = total
      ? `₹${total.toLocaleString('en-IN')}`
      : '₹—';
    if (cart.placeholder) {
      summaryTotal.textContent = 'Connect for live checkout';
    }
  }

  function splitName(fullName) {
    const parts = (fullName || '').trim().split(/\s+/);
    if (parts.length === 0) return { first_name: '', last_name: '' };
    if (parts.length === 1) return { first_name: parts[0], last_name: parts[0] };
    const first_name = parts.shift();
    const last_name = parts.join(' ');
    return { first_name, last_name };
  }

  function handleAddToCart() {
    const productSection = document.querySelector('[data-product]');
    if (!productSection) return;
    const addButton = productSection.querySelector('[data-action="add-to-cart"]');
    if (!addButton) return;

    addButton.addEventListener('click', () => {
      const cachedCart = readCart();
      const id = Number(productSection.dataset.productId);
      const price = Number(productSection.dataset.productPrice);
      const name = productSection.dataset.productName;
      if (!id || cachedCart?.placeholder) {
        showToast('Live product data unavailable. Connect to the store before ordering.', 'error');
        return;
      }
      const cart = {
        product: { id, price, name },
        quantity: 1,
        placeholder: false,
      };
      writeCart(cart);
      updateOrderSummary();
      showToast('Added to cart. Ready for checkout!', 'info');
    });

    const refreshButton = productSection.querySelector('[data-action="refresh-product"]');
    if (refreshButton) {
      refreshButton.addEventListener('click', () => loadProduct(true));
    }
  }

  function handleCheckoutForm() {
    const form = document.querySelector('[data-checkout-form]');
    if (!form) return;
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(form);
      const fullName = formData.get('first_name');
      const { first_name, last_name } = splitName(fullName);
      const cart = readCart();
      const productSection = document.querySelector('[data-product]');
      const productId = cart?.product?.id || Number(productSection?.dataset.productId || 0);
      const quantity = cart?.quantity || 1;
      if (!productId || cart?.placeholder) {
        showToast('Live product data unavailable. Connect to the store before ordering.', 'error');
        return;
      }
      const paymentMethod = formData.get('payment_method') || 'cod';
      const paymentTitles = {
        cod: 'Cash on Delivery',
        upi: 'UPI Payment',
        netbanking: 'Net Banking',
      };
      const payload = {
        payment_method: paymentMethod,
        payment_method_title: paymentTitles[paymentMethod] || 'Online Payment',
        set_paid: paymentMethod !== 'cod',
        billing: {
          first_name,
          last_name,
          address_1: formData.get('address_1'),
          address_2: formData.get('address_2'),
          city: formData.get('city'),
          state: formData.get('state'),
          postcode: formData.get('postcode'),
          country: 'IN',
          email: formData.get('email'),
          phone: formData.get('phone'),
        },
        shipping: {
          first_name,
          last_name,
          address_1: formData.get('address_1'),
          address_2: formData.get('address_2'),
          city: formData.get('city'),
          state: formData.get('state'),
          postcode: formData.get('postcode'),
          country: 'IN',
          phone: formData.get('phone'),
        },
        line_items: [
          {
            product_id: productId,
            quantity,
          },
        ],
      };
      form.querySelector('button[type="submit"]').disabled = true;
      try {
        const order = await api('create_order', { method: 'POST', data: payload });
        showToast('Order placed successfully!', 'info');
        sessionStorage.setItem('kk.lastOrder', JSON.stringify(order));
        const successBox = document.getElementById('kk-checkout-success');
        const orderIdEl = successBox?.querySelector('[data-order-id]');
        if (orderIdEl) orderIdEl.textContent = order.id;
        if (successBox) successBox.classList.remove('kk-hidden');
        form.classList.add('kk-hidden');
      } catch (err) {
        showToast(err.message, 'error');
      } finally {
        form.querySelector('button[type="submit"]').disabled = false;
      }
    });
  }

  function handleAuthForms() {
    const loginForm = document.querySelector('[data-login-form]');
    if (loginForm) {
      loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(loginForm);
        const payload = {
          username: formData.get('username'),
          password: formData.get('password'),
        };
        loginForm.querySelector('button[type="submit"]').disabled = true;
        try {
          await api('login', { method: 'POST', data: payload });
          showToast('Logged in successfully!', 'info');
          setTimeout(() => {
            window.location.href = '/frontend/account.php';
          }, 800);
        } catch (err) {
          showToast(err.message, 'error');
        } finally {
          loginForm.querySelector('button[type="submit"]').disabled = false;
        }
      });
    }

    const signupForm = document.querySelector('[data-signup-form]');
    if (signupForm) {
      signupForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(signupForm);
        const { first_name, last_name } = splitName(formData.get('first_name'));
        const payload = {
          email: formData.get('email'),
          first_name,
          last_name,
          username: formData.get('email'),
          password: formData.get('password'),
          billing: {
            first_name,
            last_name,
            email: formData.get('email'),
            phone: formData.get('phone'),
            country: 'IN',
          },
        };
        signupForm.querySelector('button[type="submit"]').disabled = true;
        try {
          await api('signup', { method: 'POST', data: payload });
          showToast('Account created! You are now logged in.', 'info');
          setTimeout(() => {
            window.location.href = '/frontend/account.php';
          }, 900);
        } catch (err) {
          showToast(err.message, 'error');
        } finally {
          signupForm.querySelector('button[type="submit"]').disabled = false;
        }
      });
    }

    document.querySelectorAll('[data-switch-auth]').forEach((link) => {
      link.addEventListener('click', (event) => {
        event.preventDefault();
        const target = link.dataset.switchAuth;
        document.querySelector(`#kk-${target}-form`)?.scrollIntoView({ behavior: 'smooth' });
      });
    });
  }

  async function refreshOrders() {
    const ordersList = document.querySelector('[data-orders-list]');
    if (!ordersList) return;
    try {
      const orders = await api('get_orders');
      if (!orders.length) {
        ordersList.innerHTML = '<p>No orders yet. Start shopping!</p>';
        return;
      }
      ordersList.innerHTML = '';
      orders.forEach((order) => {
        const article = document.createElement('article');
        article.className = 'kk-order-card';
        article.innerHTML = `
          <header>
            <h3>Order #${order.id}</h3>
            <span class="kk-status kk-status--${order.status}">${order.status}</span>
          </header>
          <p class="kk-order-card__meta">Placed on ${new Date(order.date_created).toLocaleDateString('en-IN')}</p>
          <ul class="kk-order-card__items">
            ${order.line_items.map((item) => `
              <li>
                ${item.name} × ${item.quantity}
                <span>₹${Number(item.total).toLocaleString('en-IN')}</span>
              </li>
            `).join('')}
          </ul>
          <footer>
            <strong>Total: ₹${Number(order.total).toLocaleString('en-IN')}</strong>
          </footer>
        `;
        ordersList.appendChild(article);
      });
    } catch (err) {
      console.error(err);
      showToast('Unable to refresh orders right now.', 'error');
    }
  }

  function handleAccountAutoRefresh() {
    if (document.querySelector('[data-orders-list]')) {
      refreshOrders();
    }
  }

  function parseJsonSafe(input) {
    if (!input) return {};
    try {
      return JSON.parse(input);
    } catch (err) {
      showToast('Invalid JSON body. Please correct and retry.', 'error');
      throw err;
    }
  }

  function handleApiPlayground() {
    const form = document.querySelector('[data-api-form]');
    const responseEl = document.querySelector('[data-api-response] code');
    if (!form || !responseEl) return;

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const endpoint = form.endpoint.value.trim();
      const method = form.method.value;
      const bodyRaw = form.body.value.trim();
      try {
        const payload = {
          endpoint,
          method,
        };
        if (['POST', 'PUT', 'DELETE'].includes(method)) {
          payload.body = parseJsonSafe(bodyRaw || '{}');
        } else if (method === 'GET' && bodyRaw) {
          payload.query = parseJsonSafe(bodyRaw);
        }
        const data = await api('proxy', { method: 'POST', data: payload });
        responseEl.textContent = JSON.stringify(data, null, 2);
      } catch (err) {
        responseEl.textContent = `Error: ${err.message}`;
        showToast(err.message, 'error');
      }
    });

    form.querySelector('[data-action="api-clear"]').addEventListener('click', () => {
      form.body.value = '';
      responseEl.textContent = '// Response will appear here';
    });

    document.querySelectorAll('[data-example]').forEach((button) => {
      button.addEventListener('click', () => {
        const config = JSON.parse(button.dataset.example);
        form.method.value = config.method;
        form.endpoint.value = config.endpoint;
        form.body.value = config.body;
      });
    });
  }

  function idbAvailable() {
    return 'indexedDB' in window;
  }

  function openPosDb() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(POS_DB_NAME, 1);
      request.onupgradeneeded = (event) => {
        const db = event.target.result;
        if (!db.objectStoreNames.contains(POS_STORE)) {
          const store = db.createObjectStore(POS_STORE, { keyPath: 'id' });
          store.createIndex('status', 'status', { unique: false });
        }
      };
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async function dbPut(order) {
    if (!idbAvailable()) {
      const existing = JSON.parse(localStorage.getItem(POS_LOCAL_FALLBACK) || '[]');
      const filtered = existing.filter((item) => item.id !== order.id);
      filtered.push(order);
      localStorage.setItem(POS_LOCAL_FALLBACK, JSON.stringify(filtered));
      return;
    }
    const db = await openPosDb();
    await new Promise((resolve, reject) => {
      const tx = db.transaction(POS_STORE, 'readwrite');
      tx.onerror = () => reject(tx.error);
      tx.oncomplete = () => resolve();
      tx.objectStore(POS_STORE).put(order);
    });
  }

  async function dbGetAll() {
    if (!idbAvailable()) {
      return JSON.parse(localStorage.getItem(POS_LOCAL_FALLBACK) || '[]');
    }
    const db = await openPosDb();
    return new Promise((resolve, reject) => {
      const tx = db.transaction(POS_STORE, 'readonly');
      const request = tx.objectStore(POS_STORE).getAll();
      request.onsuccess = () => resolve(request.result || []);
      request.onerror = () => reject(request.error);
    });
  }

  async function dbDelete(id) {
    if (!idbAvailable()) {
      const existing = JSON.parse(localStorage.getItem(POS_LOCAL_FALLBACK) || '[]');
      const filtered = existing.filter((item) => item.id !== id);
      localStorage.setItem(POS_LOCAL_FALLBACK, JSON.stringify(filtered));
      return;
    }
    const db = await openPosDb();
    await new Promise((resolve, reject) => {
      const tx = db.transaction(POS_STORE, 'readwrite');
      const request = tx.objectStore(POS_STORE).delete(id);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  function downloadJson(filename, data) {
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  }

  function handlePosModule() {
    const posRoot = document.querySelector('[data-pos]');
    if (!posRoot) return;

    const statusEl = document.querySelector('[data-pos-status]');
    const statusText = statusEl?.querySelector('[data-status-text]');
    const listEl = posRoot.querySelector('[data-pos-list]');
    const form = posRoot.querySelector('[data-pos-form]');
    const syncLog = document.querySelector('[data-sync-log]');

    const productId = Number(posRoot.dataset.productId || 0);
    const productName = posRoot.dataset.productName;
    const productPrice = Number(posRoot.dataset.productPrice || 0);

    const state = {
      orders: [],
      syncing: false,
    };

    function updateStatus() {
      const online = navigator.onLine;
      if (statusText) {
        statusText.textContent = online
          ? 'Online – new sales will sync automatically.'
          : 'Offline – storing sales locally.';
      }
      if (statusEl) {
        statusEl.classList.toggle('is-offline', !online);
      }
    }

    function renderList() {
      if (!listEl) return;
      if (!state.orders.length) {
        listEl.innerHTML = '<li class="kk-empty">No offline orders yet.</li>';
        return;
      }
      listEl.innerHTML = '';
      state.orders.sort((a, b) => b.createdAt - a.createdAt).forEach((order) => {
        const li = document.createElement('li');
        li.innerHTML = `
          <div class="kk-pos-item__header">
            <strong>${order.customer_name || 'Walk-in customer'}</strong>
            <span>${new Date(order.createdAt).toLocaleString('en-IN')}</span>
          </div>
          <div class="kk-pos-item__body">
            <span>${order.quantity} × ${productName}</span>
            <span>₹${(order.quantity * productPrice).toLocaleString('en-IN')}</span>
          </div>
          <div class="kk-pos-item__meta">
            <span>${order.payment_method.toUpperCase()}</span>
            <span>Status: ${order.status}</span>
            ${order.wc_order_id ? `<span>Woo Order #${order.wc_order_id}</span>` : ''}
          </div>
        `;
        listEl.appendChild(li);
      });
    }

    function appendLog(message, type = 'info') {
      if (!syncLog) return;
      const item = document.createElement('li');
      item.textContent = `${new Date().toLocaleTimeString('en-IN')} – ${message}`;
      item.className = `log-${type}`;
      if (syncLog.querySelector('.kk-empty')) {
        syncLog.innerHTML = '';
      }
      syncLog.prepend(item);
    }

    async function loadOrders() {
      state.orders = await dbGetAll();
      renderList();
    }

    form?.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!productId) {
        showToast('Product unavailable for POS. Please refresh.', 'error');
        return;
      }
      const formData = new FormData(form);
      const order = {
        id: uuid(),
        createdAt: Date.now(),
        customer_name: formData.get('customer_name'),
        customer_phone: formData.get('customer_phone'),
        quantity: Number(formData.get('quantity') || 1),
        payment_method: formData.get('payment_method'),
        notes: formData.get('notes'),
        status: 'pending',
        syncAttempts: 0,
      };
      order.payload = {
        payment_method: `pos_${order.payment_method}`,
        payment_method_title: `POS ${order.payment_method.toUpperCase()}`,
        set_paid: true,
        billing: {
          first_name: order.customer_name || 'POS',
          last_name: order.customer_name || 'Customer',
          phone: order.customer_phone,
          email: order.customer_phone ? `pos+${order.customer_phone}@kishanskraft.com` : `guest+${order.id}@kishanskraft.com`,
          country: 'IN',
        },
        line_items: [
          {
            product_id: productId,
            quantity: order.quantity,
          },
        ],
        meta_data: [
          { key: 'pos_notes', value: order.notes },
          { key: 'pos_origin', value: 'KishansKraft POS' },
        ],
      };
      await dbPut(order);
      appendLog(`Offline order saved (${order.quantity} units).`, 'info');
      showToast('Offline order saved locally.', 'info');
      form.reset();
      form.querySelector('[name="quantity"]').value = '1';
      await loadOrders();
      if (navigator.onLine) {
        await syncNow();
      }
    });

    async function syncNow() {
      if (state.syncing) return;
      const pending = state.orders.filter((order) => order.status !== 'synced');
      if (!pending.length) {
        appendLog('Nothing to sync.', 'info');
        return;
      }
      if (!navigator.onLine) {
        appendLog('Cannot sync while offline.', 'error');
        showToast('You appear to be offline. Try again later.', 'error');
        return;
      }
      state.syncing = true;
      appendLog(`Syncing ${pending.length} order(s)…`, 'info');
      try {
        const response = await api('pos_sync', {
          method: 'POST',
          data: { orders: pending.map((order) => order.payload) },
        });
        for (let i = 0; i < response.length; i += 1) {
          const result = response[i];
          const order = pending[i];
          if (result.success) {
            order.status = 'synced';
            order.wc_order_id = result.order.id;
            appendLog(`Order synced successfully (WooCommerce #${result.order.id}).`, 'info');
          } else {
            order.status = 'failed';
            order.last_error = result.error?.message;
            order.syncAttempts += 1;
            appendLog(`Order sync failed: ${order.last_error}`, 'error');
          }
          await dbPut(order);
        }
        state.orders = await dbGetAll();
        renderList();
      } catch (err) {
        console.error(err);
        appendLog('Sync failed due to network or API error.', 'error');
        showToast(err.message, 'error');
      } finally {
        state.syncing = false;
      }
    }

    posRoot.querySelector('[data-action="sync-now"]').addEventListener('click', syncNow);
    posRoot.querySelector('[data-action="export-json"]').addEventListener('click', async () => {
      const data = await dbGetAll();
      downloadJson(`kk-pos-orders-${Date.now()}.json`, data);
    });

    window.addEventListener('online', () => {
      updateStatus();
      syncNow();
    });
    window.addEventListener('offline', updateStatus);

    updateStatus();
    loadOrders();
  }

  function init() {
    intersectObserverInit();
    loadProduct(false);
    handleAddToCart();
    handleCheckoutForm();
    handleAuthForms();
    handleApiPlayground();
    handleAccountAutoRefresh();
    handlePosModule();
    updateOrderSummary();
  }

  document.addEventListener('DOMContentLoaded', init);
})();
