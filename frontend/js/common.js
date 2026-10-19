/* Quick Buy Retail - Shared UI & API helpers */
const API = '../backend/php/';
let CSRF_TOKEN = '';

async function api(path, opts = {}) {
  const init = { method: 'GET', credentials: 'same-origin', headers: {} };

  if (opts.json || opts.form || opts.method === 'POST') {
    init.method = 'POST';
    if (CSRF_TOKEN) {
      init.headers['X-CSRF-Token'] = CSRF_TOKEN;
    }
  }

  if (opts.json) {
    init.headers['Content-Type'] = 'application/json';
    if (CSRF_TOKEN && typeof opts.json === 'object') {
      opts.json.csrf_token = CSRF_TOKEN;
    }
    init.body = JSON.stringify(opts.json);
  }
  if (opts.form) {
    init.body = opts.form;
  }

  let res, data;
  try { res = await fetch(API + path, init); } catch (e) { throw new Error('Cannot reach the server.'); }
  try { data = await res.json(); } catch (e) { throw new Error('Unexpected server response.'); }
  if (res.status === 401 && !location.pathname.endsWith('index.html')) { location.href = 'index.html'; }
  if (!res.ok || data.success === false) throw new Error(data.message || 'Request failed.');
  return data;
}

const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const money = n => '₱' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const imgUrl = name => name ? API + 'uploads/' + encodeURIComponent(name) : '';
const debounce = (fn, ms = 300) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

function toast(msg, type = 'ok') {
  let box = document.getElementById('toast');
  if (!box) { box = document.createElement('div'); box.id = 'toast'; document.body.appendChild(box); }
  const t = document.createElement('div');
  t.className = 'toast' + (type === 'error' ? ' error' : '');
  t.textContent = msg;
  box.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}
const toastError = e => toast(e.message || String(e), 'error');

const openModal = id => {
  const el = document.getElementById(id);
  if (el) el.classList.add('open');
};
const closeModal = id => {
  const el = document.getElementById(id);
  if (el) el.classList.remove('open');
};

document.addEventListener('click', e => {
  if (e.target.classList.contains('modal')) e.target.classList.remove('open');
  if (e.target.dataset && e.target.dataset.close) closeModal(e.target.dataset.close);
});

// Sidebar grouped like Loyverse Back Office
const NAV_GROUPS = [
  {
    group: 'Sales',
    items: [
      { key: 'dashboard', label: 'Dashboard', href: 'dashboard.html', icon: '🏠', roles: ['admin', 'cashier'] },
      { key: 'pos', label: 'POS Terminal', href: 'pos.html', icon: '🛒', roles: ['admin', 'cashier'] },
      { key: 'sales', label: 'Sales History', href: 'sales.html', icon: '🧾', roles: ['admin', 'cashier'] },
      { key: 'returns', label: 'Returns & Refunds', href: 'returns.html', icon: '🔄', roles: ['admin', 'cashier'] }
    ]
  },
  {
    group: 'Items',
    items: [
      { key: 'products', label: 'Products', href: 'products.html', icon: '📦', roles: ['admin'] },
      { key: 'categories', label: 'Categories', href: 'categories.html', icon: '🏷️', roles: ['admin'] },
      { key: 'discounts', label: 'Discounts', href: 'discounts.html', icon: '🏷️', roles: ['admin'] }
    ]
  },
  {
    group: 'Inventory',
    items: [
      { key: 'stock_overview', label: 'Stock Overview', href: 'stock_overview.html', icon: '📊', roles: ['admin'] },
      { key: 'stock_adjustments', label: 'Stock Adjustments', href: 'stock_adjustments.html', icon: '✏️', roles: ['admin'] },
      { key: 'suppliers', label: 'Suppliers', href: 'suppliers.html', icon: '🚚', roles: ['admin'] },
      { key: 'purchase_orders', label: 'Purchase Orders', href: 'purchase_orders.html', icon: '📋', roles: ['admin'] },
      { key: 'movements', label: 'Movement History', href: 'movements.html', icon: '📜', roles: ['admin'] }
    ]
  },
  {
    group: 'People',
    items: [
      { key: 'customers', label: 'Customers', href: 'customers.html', icon: '👥', roles: ['admin', 'cashier'] },
      { key: 'users', label: 'Users', href: 'users.html', icon: '🔑', roles: ['admin'] },
      { key: 'activity', label: 'Activity Log', href: 'activity.html', icon: '📜', roles: ['admin'] }
    ]
  },
  {
    group: 'Analytics & Config',
    items: [
      { key: 'reports', label: 'Reports', href: 'reports.html', icon: '📈', roles: ['admin'] },
      { key: 'settings', label: 'Settings', href: 'settings.html', icon: '⚙️', roles: ['admin'] }
    ]
  }
];

/* Call at the top of every protected page. Checks session, builds Loyverse sidebar. */
async function initPage(activeKey) {
  let user;
  try {
    const res = await api('auth/session.php');
    user = res.user;
    if (res.csrf_token) CSRF_TOKEN = res.csrf_token;
  } catch (e) {
    location.href = 'index.html';
    return null;
  }

  // Check access permissions for current key
  let allowed = false;
  let currentItem = null;
  for (const g of NAV_GROUPS) {
    for (const item of g.items) {
      if (item.key === activeKey) {
        currentItem = item;
        if (item.roles.includes(user.role)) allowed = true;
      }
    }
  }

  if (currentItem && !allowed) {
    location.href = 'pos.html';
    return null;
  }

  // Build Sidebar HTML
  let html = `<div class="brand">🛍️ Quick Buy</div><div class="sidebar-nav">`;
  for (const g of NAV_GROUPS) {
    const visibleItems = g.items.filter(item => item.roles.includes(user.role));
    if (visibleItems.length > 0) {
      html += `<div class="nav-group-label">${g.group}</div>`;
      for (const item of visibleItems) {
        const activeClass = item.key === activeKey ? 'active' : '';
        html += `<a href="${item.href}" class="${activeClass}"><span>${item.icon}</span>${item.label}</a>`;
      }
    }
  }
  html += `</div><div class="sidebar-footer"><a href="#" id="logoutLink"><span>🚪</span>Logout</a></div>`;

  const sb = document.getElementById('sidebar');
  if (sb) sb.innerHTML = html;

  const ui = document.getElementById('userInfo');
  if (ui) ui.textContent = `${user.full_name} (${user.role.toUpperCase()})`;

  const mb = document.getElementById('menuBtn');
  if (mb) mb.onclick = () => sb.classList.toggle('open');

  const lo = document.getElementById('logoutLink');
  if (lo) {
    lo.onclick = async e => {
      e.preventDefault();
      try { await api('auth/logout.php'); } finally { location.href = 'index.html'; }
    };
  }

  return user;
}

/* Receipt (shared by POS and Sales History). */
function receiptHTML(r) {
  const items = r.items || [];
  const rows = items.map(i =>
    `<div>${esc(i.product_name || i.name)}</div>
     <div class="r"><span>${i.quantity} x ${money(i.unit_price || i.price)}</span><span>${money(i.subtotal)}</span></div>`
  ).join('');

  return `<div class="receipt">
    <h3>QUICK BUY RETAIL</h3>
    <div class="c">Official Receipt</div>
    <hr>
    <div class="r"><span>Txn #${r.id}</span><span>${esc(r.created_at || '')}</span></div>
    <div>Cashier: ${esc(r.cashier || r.cashier_name || 'Staff')}</div>
    <div>Customer: ${esc(r.customer || r.customer_name || 'Walk-in Customer')}</div>
    <div>Payment Method: ${esc(r.payment_method || 'Cash')}</div>
    <hr>
    ${rows}
    <hr>
    ${r.discount_amount ? `<div class="r"><span>Discount</span><span>-${money(r.discount_amount)}</span></div>` : ''}
    ${r.tax_amount ? `<div class="r"><span>Tax</span><span>${money(r.tax_amount)}</span></div>` : ''}
    <div class="r"><strong>TOTAL</strong><strong>${money(r.total_amount)}</strong></div>
    <div class="r"><span>Payment</span><span>${money(r.payment || r.payment_amount || r.total_amount)}</span></div>
    <div class="r"><span>Change</span><span>${money(r.change_amount || 0)}</span></div>
    <hr>
    <div class="c">Thank you for shopping!</div>
  </div>`;
}

function showReceipt(r) {
  let modal = document.getElementById('receiptModal');
  if (!modal) {
    const div = document.createElement('div');
    div.id = 'receiptModal';
    div.className = 'modal';
    div.innerHTML = `<div class="modal-box"><div id="receiptBody"></div>
      <div class="modal-actions" style="margin-top:14px">
        <button class="btn btn-light" data-close="receiptModal">Close</button>
        <button class="btn" onclick="window.print()">Print</button>
      </div></div>`;
    document.body.appendChild(div);
  }
  document.getElementById('receiptBody').innerHTML = receiptHTML(r);
  openModal('receiptModal');
}
