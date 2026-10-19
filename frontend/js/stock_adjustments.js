const $ = id => document.getElementById(id);

async function loadProducts() {
  try {
    const res = await api('products/api.php?action=list');
    const select = $('product_id');
    select.innerHTML = '<option value="">-- Choose Product --</option>' +
      res.products.map(p => `<option value="${p.id}">${esc(p.name)} (Current: ${p.stock_quantity})</option>`).join('');
  } catch (e) { toastError(e); }
}

$('adjForm').addEventListener('submit', async e => {
  e.preventDefault();
  $('formError').textContent = '';

  const pid = $('product_id').value;
  const type = $('type').value;
  const qty = parseInt($('quantity').value, 10);
  const reason = $('reason').value.trim();

  if (!pid) return ($('formError').textContent = 'Please choose a product.');
  if (isNaN(qty) || qty <= 0) return ($('formError').textContent = 'Enter a valid quantity greater than zero.');
  if (!reason) return ($('formError').textContent = 'Please provide a reason for the adjustment.');

  try {
    const d = await api('inventory/api.php?action=adjust', {
      json: { product_id: pid, type, quantity: qty, reason }
    });
    toast(d.message);
    $('adjForm').reset();
    loadProducts();
  } catch (ex) {
    $('formError').textContent = ex.message;
  }
});

(async () => {
  if (!await initPage('stock_adjustments')) return;
  loadProducts();
})();
