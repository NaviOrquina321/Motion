const $ = id => document.getElementById(id);
let products = [], cart = [], categories = [], discounts = [];
let activePaymentMethod = 'Cash';
let currentCartToken = bin2hex();
let storeSettings = { tax_enabled: '0', tax_rate: '0.00' };

function bin2hex() {
  return Array.from(crypto.getRandomValues(new Uint8Array(16)))
    .map(b => b.toString(16).padStart(2, '0')).join('');
}

async function loadSettingsAndDiscounts() {
  try {
    const [sRes, dRes] = await Promise.all([
      api('settings/api.php?action=get'),
      api('discounts/api.php?action=list')
    ]);
    storeSettings = sRes.settings || { tax_enabled: '0', tax_rate: '0.00' };
    discounts = (dRes.discounts || []).filter(d => d.is_active == 1);

    const dSelect = $('discount');
    dSelect.innerHTML = '<option value="">No Discount</option>' +
      discounts.map(d => `<option value="${d.id}">${esc(d.name)} (${d.type === 'percent' ? d.value + '%' : '₱' + d.value})</option>`).join('');
  } catch (e) { toastError(e); }
}

async function loadCategories() {
  try {
    const res = await api('categories/api.php?action=list');
    categories = res.categories || [];
    const tabs = $('catTabs');
    tabs.innerHTML = `<button class="cat-tab active" data-id="">All Categories</button>` +
      categories.map(c => `<button class="cat-tab" data-id="${c.id}">${esc(c.name)}</button>`).join('');

    tabs.querySelectorAll('.cat-tab').forEach(btn => {
      btn.onclick = () => {
        tabs.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        loadProducts();
      };
    });
  } catch (e) { toastError(e); }
}

async function loadProducts() {
  try {
    const activeTab = document.querySelector('.cat-tab.active');
    const catId = activeTab ? activeTab.dataset.id : '';
    const query = $('search').value.trim();
    const qs = new URLSearchParams({ action: 'list', q: query, category_id: catId });
    products = (await api('products/api.php?' + qs)).products;

    $('grid').innerHTML = products.length ? products.map(p => `
      <button class="pcard ${p.stock_quantity <= 0 ? 'out' : ''}" onclick="addToCart(${p.id})">
        ${p.image ? `<img src="${imgUrl(p.image)}" alt="">` : '<div class="ph"></div>'}
        <div>
          <div class="n">${esc(p.name)}</div>
          <div class="p">${money(p.price)}</div>
        </div>
        <div class="s">${p.stock_quantity > 0 ? p.stock_quantity + ' in stock' : 'Out of stock'}</div>
      </button>`).join('')
      : '<div class="empty">No products found.</div>';
  } catch (e) { toastError(e); }
}

window.addToCart = id => {
  const p = products.find(x => x.id == id);
  if (!p || p.stock_quantity <= 0) return;
  const line = cart.find(c => c.id == id);
  if (line) {
    if (line.qty >= p.stock_quantity) return toast('No more stock available.', 'error');
    line.qty++;
  } else {
    cart.push({ id: p.id, name: p.name, price: +p.price, stock: +p.stock_quantity, qty: 1 });
  }
  render();
};

window.changeQty = (id, d) => {
  const line = cart.find(c => c.id == id);
  if (!line) return;
  if (d > 0 && line.qty >= line.stock) return toast('No more stock available.', 'error');
  line.qty += d;
  if (line.qty <= 0) cart = cart.filter(c => c.id != id);
  render();
};

window.removeItem = id => { cart = cart.filter(c => c.id != id); render(); };

function calculateTotals() {
  const subtotal = Math.round(cart.reduce((s, c) => s + c.price * c.qty, 0) * 100) / 100;

  // Discount calculation
  let discountAmount = 0.0;
  const selectedDiscId = $('discount').value;
  if (selectedDiscId) {
    const disc = discounts.find(d => d.id == selectedDiscId);
    if (disc) {
      if (disc.type === 'percent') {
        discountAmount = Math.round((subtotal * parseFloat(disc.value)) / 100 * 100) / 100;
      } else {
        discountAmount = Math.min(subtotal, parseFloat(disc.value));
      }
    }
  }

  const afterDiscount = Math.max(0, subtotal - discountAmount);

  // Tax calculation
  let taxAmount = 0.0;
  const taxRate = parseFloat(storeSettings.tax_rate || 0);
  if (storeSettings.tax_enabled === '1' && taxRate > 0) {
    taxAmount = Math.round((afterDiscount * taxRate) / 100 * 100) / 100;
  }

  const grandTotal = Math.round((afterDiscount + taxAmount) * 100) / 100;

  return { subtotal, discountAmount, taxAmount, taxRate, grandTotal };
}

function render() {
  $('cartItems').innerHTML = cart.length ? cart.map(c => `<div class="cart-item">
    <div class="info">
      <div class="name">${esc(c.name)}</div>
      <div class="price">${money(c.price)} × ${c.qty} = <strong>${money(c.price * c.qty)}</strong></div>
    </div>
    <div class="qty-controls">
      <button class="qty-btn" onclick="changeQty(${c.id},-1)">−</button>
      <span>${c.qty}</span>
      <button class="qty-btn" onclick="changeQty(${c.id},1)">+</button>
    </div>
    <button class="btn btn-sm btn-danger" onclick="removeItem(${c.id})" style="margin-left:4px" aria-label="Remove">✕</button>
  </div>`).join('')
    : '<div class="empty">Ticket is empty.<br>Select a product to add.</div>';

  const { subtotal, discountAmount, taxAmount, taxRate, grandTotal } = calculateTotals();

  $('subtotal').textContent = money(subtotal);

  if (discountAmount > 0) {
    $('discRow').classList.remove('hidden');
    $('discAmt').textContent = '-' + money(discountAmount);
  } else {
    $('discRow').classList.add('hidden');
  }

  if (storeSettings.tax_enabled === '1' && taxAmount > 0) {
    $('taxRow').classList.remove('hidden');
    $('taxRateLabel').textContent = taxRate;
    $('taxAmt').textContent = money(taxAmount);
  } else {
    $('taxRow').classList.add('hidden');
  }

  $('total').textContent = money(grandTotal);

  if (activePaymentMethod === 'Cash') {
    const pay = parseFloat($('payment').value);
    $('change').textContent = money(!isNaN(pay) && pay >= grandTotal ? pay - grandTotal : 0);
  } else {
    $('payment').value = grandTotal.toFixed(2);
    $('change').textContent = money(0);
  }
}

async function checkout() {
  $('payError').textContent = '';
  const { grandTotal } = calculateTotals();
  const pay = parseFloat($('payment').value);
  const refNum = $('refNum').value.trim();

  if (!cart.length) return ($('payError').textContent = 'Add at least one product.');
  if (activePaymentMethod === 'Cash' && (isNaN(pay) || pay < grandTotal)) {
    return ($('payError').textContent = 'Payment amount is less than total amount.');
  }
  if ((activePaymentMethod === 'GCash' || activePaymentMethod === 'Maya') && !refNum) {
    return ($('payError').textContent = 'Please enter reference number for digital payment.');
  }

  // Prevent duplicate checkout
  $('payBtn').disabled = true;

  try {
    const d = await api('sales/api.php?action=checkout', {
      json: {
        customer_id: $('customer').value,
        discount_id: $('discount').value,
        payment: activePaymentMethod === 'Cash' ? pay : grandTotal,
        payment_method: activePaymentMethod,
        reference_number: refNum,
        cart_token: currentCartToken,
        items: cart.map(c => ({ product_id: c.id, quantity: c.qty }))
      }
    });

    toast(d.message);
    showReceipt(d.receipt);

    // Reset cart and generate new cart token
    cart = [];
    $('payment').value = '';
    $('refNum').value = '';
    $('discount').value = '';
    currentCartToken = bin2hex();

    render();
    loadProducts();
  } catch (e) {
    $('payError').textContent = e.message;
    loadProducts();
  } finally {
    $('payBtn').disabled = false;
  }
}

// Payment method toggle buttons
document.querySelectorAll('.pay-method-btn').forEach(btn => {
  btn.onclick = () => {
    document.querySelectorAll('.pay-method-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activePaymentMethod = btn.dataset.method;

    if (activePaymentMethod === 'Cash') {
      $('refField').style.display = 'none';
      $('payField').style.display = 'block';
      $('changeRow').style.display = 'flex';
      $('payment').value = '';
    } else {
      $('refField').style.display = 'block';
      $('payField').style.display = 'block';
      $('changeRow').style.display = 'none';
    }
    render();
  };
});

// Barcode scanner logic on Enter key
$('search').addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    e.preventDefault();
    const query = $('search').value.trim();
    if (!query) return;

    // Check if query matches barcode or product ID directly
    const exact = products.find(p => p.barcode === query || p.id == query);
    if (exact) {
      addToCart(exact.id);
      $('search').value = '';
      loadProducts();
    }
  }
});

(async () => {
  if (!await initPage('pos')) return;
  $('modalHost').innerHTML = RECEIPT_MODAL;

  try {
    const cu = await api('customers/api.php?action=list');
    $('customer').innerHTML += cu.customers.map(x => `<option value="${x.id}">${esc(x.full_name)}</option>`).join('');
  } catch (e) { toastError(e); }

  $('search').addEventListener('input', debounce(loadProducts));
  $('payment').addEventListener('input', render);
  $('discount').addEventListener('change', render);

  $('clearBtn').onclick = () => {
    cart = [];
    $('payment').value = '';
    $('refNum').value = '';
    $('discount').value = '';
    currentCartToken = bin2hex();
    render();
  };

  $('payBtn').onclick = checkout;

  await loadSettingsAndDiscounts();
  await loadCategories();
  await loadProducts();
  render();
})();
