const $ = id => document.getElementById(id);
let products = [];

async function loadCategories() {
  try {
    const res = await api('categories/api.php?action=list');
    const select = $('catFilter');
    select.innerHTML = '<option value="">All categories</option>' +
      res.categories.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
  } catch (e) { toastError(e); }
}

async function load() {
  try {
    const qs = new URLSearchParams({
      action: 'overview',
      q: $('search').value.trim(),
      category_id: $('catFilter').value,
      status: $('statusFilter').value
    });
    const res = await api('inventory/api.php?' + qs);
    products = res.products;
    const summary = res.summary;

    $('summaryCards').innerHTML = [
      ['Total Stock Quantity', summary.total_items + ' items', 'across all products'],
      ['Inventory Retail Value', money(summary.total_value_retail), 'potential sales value'],
    ].map(([l, v, sub]) => `<div class="stat"><div class="label">${l}</div><div class="value">${v}</div><div class="sub">${sub}</div></div>`).join('');

    $('rows').innerHTML = products.length ? products.map(p => {
      let badge = '<span class="badge badge-ok">In Stock</span>';
      if (p.stock_quantity <= 0) badge = '<span class="badge badge-danger">Out of Stock</span>';
      else if (p.stock_quantity <= p.reorder_level) badge = '<span class="badge badge-low">Low Stock</span>';

      return `<tr>
        <td>${p.id}</td>
        <td><strong>${esc(p.name)}</strong></td>
        <td>${esc(p.category_name)}</td>
        <td class="num">${p.stock_quantity}</td>
        <td class="num">${p.reorder_level}</td>
        <td>${badge}</td>
        <td class="num">${money(p.price)}</td>
        <td class="num"><strong>${money(p.stock_value_retail)}</strong></td>
      </tr>`;
    }).join('')
    : '<tr><td colspan="8" class="empty">No product stock records found.</td></tr>';
  } catch (e) { toastError(e); }
}

(async () => {
  if (!await initPage('stock_overview')) return;
  await loadCategories();
  $('search').addEventListener('input', debounce(load));
  $('catFilter').addEventListener('change', load);
  $('statusFilter').addEventListener('change', load);
  load();
})();
