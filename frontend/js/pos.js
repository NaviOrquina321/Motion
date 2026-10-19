const $ = id => document.getElementById(id);
let products = [], cart = [];   // cart item: {id, name, price, stock, qty}

async function loadProducts() {
  try {
    const qs = new URLSearchParams({ action: 'list', q: $('search').value.trim(), category_id: $('catFilter').value });
    products = (await api('products/api.php?' + qs)).products;
    $('grid').innerHTML = products.length ? products.map(p => `
      <button class="pcard ${p.stock_quantity <= 0 ? 'out' : ''}" onclick="addToCart(${p.id})">
        ${p.image ? `<img src="${imgUrl(p.image)}" alt="">` : '<div class="ph"></div>'}
        <div class="n">${esc(p.name)}</div><div class="p">${money(p.price)}</div>
        <div class="s">${p.stock_quantity > 0 ? p.stock_quantity + ' in stock' : 'Out of stock'}</div></button>`).join('')
      : '<div class="empty">No products found.</div>';
  } catch (e) { toastError(e); }
}

window.addToCart = id => {
  const p = products.find(x => x.id == id);
  if (!p || p.stock_quantity <= 0) return;
  const line = cart.find(c => c.id == id);
  if (line) { if (line.qty >= p.stock_quantity) return toast('No more stock available.', 'error'); line.qty++; }
  else cart.push({ id: p.id, name: p.name, price: +p.price, stock: +p.stock_quantity, qty: 1 });
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

const total = () => Math.round(cart.reduce((s, c) => s + c.price * c.qty, 0) * 100) / 100;

function render() {
  $('cartItems').innerHTML = cart.length ? cart.map(c => `<div class="cart-item">
    <div class="info"><div>${esc(c.name)}</div><div class="s" style="color:var(--muted);font-size:12px">${money(c.price)} each</div></div>
    <div class="qty"><button onclick="changeQty(${c.id},-1)">−</button><span>${c.qty}</span><button onclick="changeQty(${c.id},1)">+</button></div>
    <div style="width:70px;text-align:right">${money(c.price * c.qty)}</div>
    <button class="btn btn-sm btn-danger" onclick="removeItem(${c.id})" aria-label="Remove">✕</button></div>`).join('')
    : '<div class="empty">Cart is empty. Click a product to add it.</div>';
  const t = total();
  $('subtotal').textContent = $('total').textContent = money(t);
  const pay = parseFloat($('payment').value);
  $('change').textContent = money(!isNaN(pay) && pay >= t ? pay - t : 0);
}

async function checkout() {
  $('payError').textContent = '';
  const t = total(), pay = parseFloat($('payment').value);
  if (!cart.length) return ($('payError').textContent = 'Add at least one product.');
  if (isNaN(pay)) return ($('payError').textContent = 'Enter the payment amount.');
  if (pay < t) return ($('payError').textContent = 'Payment is less than the total.');
  $('payBtn').disabled = true;
  try {
    const d = await api('sales/api.php?action=checkout', {
      json: { customer_id: $('customer').value, payment: pay, items: cart.map(c => ({ product_id: c.id, quantity: c.qty })) } });
    toast(d.message); showReceipt(d.receipt);
    cart = []; $('payment').value = ''; render(); loadProducts();   // reload to show new stock levels
  } catch (e) { $('payError').textContent = e.message; loadProducts(); }
  finally { $('payBtn').disabled = false; }
}

(async () => {
  if (!await initPage('pos')) return;
  $('modalHost').innerHTML = RECEIPT_MODAL;
  try {
    const [c, cu] = await Promise.all([api('categories/api.php?action=list'), api('customers/api.php?action=list')]);
    $('catFilter').innerHTML += c.categories.map(x => `<option value="${x.id}">${esc(x.name)}</option>`).join('');
    $('customer').innerHTML += cu.customers.map(x => `<option value="${x.id}">${esc(x.full_name)}</option>`).join('');
  } catch (e) { toastError(e); }
  $('search').addEventListener('input', debounce(loadProducts));
  $('catFilter').addEventListener('change', loadProducts);
  $('payment').addEventListener('input', render);
  $('clearBtn').onclick = () => { cart = []; $('payment').value = ''; render(); };
  $('payBtn').onclick = checkout;
  render(); loadProducts();
})();
