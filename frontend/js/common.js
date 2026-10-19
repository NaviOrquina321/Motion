/* Shared helpers used by every page: API calls, sidebar, toast, modal, receipt. */
const API = '../backend/php/';

async function api(path, opts = {}) {
  const init = { method: 'GET', credentials: 'same-origin', headers: {} };
  if (opts.json) { init.method = 'POST'; init.headers['Content-Type'] = 'application/json'; init.body = JSON.stringify(opts.json); }
  if (opts.form) { init.method = 'POST'; init.body = opts.form; }
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

const openModal = id => document.getElementById(id).classList.add('open');
const closeModal = id => document.getElementById(id).classList.remove('open');
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal')) e.target.classList.remove('open');      // click on backdrop
  if (e.target.dataset && e.target.dataset.close) closeModal(e.target.dataset.close); // [data-close="modalId"]
});

const NAV = [
  { key: 'dashboard',  label: 'Dashboard',   href: 'dashboard.html',  icon: '🏠', roles: ['admin', 'cashier'] },
  { key: 'products',   label: 'Products',    href: 'products.html',   icon: '📦', roles: ['admin'] },
  { key: 'categories', label: 'Categories',  href: 'categories.html', icon: '🏷️', roles: ['admin'] },
  { key: 'pos',        label: 'Sales / POS', href: 'pos.html',        icon: '🛒', roles: ['admin', 'cashier'] },
  { key: 'sales',      label: 'Sales History', href: 'sales.html',    icon: '🧾', roles: ['admin', 'cashier'] },
  { key: 'customers',  label: 'Customers',   href: 'customers.html',  icon: '👥', roles: ['admin', 'cashier'] },
  { key: 'reports',    label: 'Reports',     href: 'reports.html',    icon: '📊', roles: ['admin'] },
  { key: 'users',      label: 'Users',       href: 'users.html',      icon: '🔑', roles: ['admin'] },
];

/* Call at the top of every protected page. Checks the session, builds the sidebar. */
async function initPage(activeKey) {
  let user;
  try { user = (await api('auth/session.php')).user; } catch (e) { location.href = 'index.html'; return null; }
  const item = NAV.find(n => n.key === activeKey);
  if (item && !item.roles.includes(user.role)) { location.href = 'pos.html'; return null; }

  const links = NAV.filter(n => n.roles.includes(user.role))
    .map(n => `<a href="${n.href}" class="${n.key === activeKey ? 'active' : ''}"><span>${n.icon}</span>${n.label}</a>`).join('');
  document.getElementById('sidebar').innerHTML =
    `<div class="brand">🛍️ Quick Buy</div>${links}<div class="spacer"></div><a href="#" id="logoutLink"><span>🚪</span>Logout</a>`;
  document.getElementById('userInfo').textContent = `${user.full_name} (${user.role})`;
  document.getElementById('menuBtn').onclick = () => document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('logoutLink').onclick = async e => {
    e.preventDefault();
    try { await api('auth/logout.php'); } finally { location.href = 'index.html'; }
  };
  return user;
}

/* Receipt (shared by POS and Sales History). Page needs the #receiptModal markup. */
function receiptHTML(r) {
  const rows = r.items.map(i =>
    `<div>${esc(i.product_name)}</div><div class="r"><span>${i.quantity} x ${money(i.unit_price)}</span><span>${money(i.subtotal)}</span></div>`).join('');
  return `<div class="receipt">
    <h3>QUICK BUY RETAIL</h3><div class="c">Official Receipt</div><hr>
    <div class="r"><span>Txn #${r.id}</span><span>${esc(r.created_at)}</span></div>
    <div>Cashier: ${esc(r.cashier)}</div><div>Customer: ${esc(r.customer)}</div><hr>
    ${rows}<hr>
    <div class="r"><strong>TOTAL</strong><strong>${money(r.total_amount)}</strong></div>
    <div class="r"><span>Payment</span><span>${money(r.payment)}</span></div>
    <div class="r"><span>Change</span><span>${money(r.change_amount)}</span></div><hr>
    <div class="c">Thank you for shopping!</div></div>`;
}
function showReceipt(r) {
  document.getElementById('receiptBody').innerHTML = receiptHTML(r);
  openModal('receiptModal');
}
const RECEIPT_MODAL = `<div class="modal" id="receiptModal"><div class="modal-box"><div id="receiptBody"></div>
  <div class="modal-actions" style="margin-top:14px"><button class="btn btn-light" data-close="receiptModal">Close</button>
  <button class="btn" onclick="window.print()">Print</button></div></div></div>`;
