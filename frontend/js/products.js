const $ = id => document.getElementById(id);
let products = [], cats = [];
async function loadCats() {
  cats = (await api('categories/api.php?action=list')).categories;
  $('catFilter').innerHTML = '<option value="">All categories</option>' + cats.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
  $('category_id').innerHTML = cats.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
}
async function load() {
  try {
    const qs = new URLSearchParams({ action: 'list', q: $('search').value.trim(), category_id: $('catFilter').value });
    products = (await api('products/api.php?' + qs)).products;
    $('rows').innerHTML = products.length ? products.map(p => `<tr>
      <td>${p.image ? `<img class="thumb" src="${imgUrl(p.image)}" alt="">` : '<div class="thumb"></div>'}</td>
      <td>${p.id}</td>
      <td><code>${esc(p.barcode || '-')}</code></td>
      <td><strong>${esc(p.name)}</strong></td>
      <td>${esc(p.category_name)}</td>
      <td class="num">${money(p.cost_price || 0)}</td>
      <td class="num">${money(p.price)}</td>
      <td class="num">${p.stock_quantity <= p.reorder_level ? `<span class="badge badge-low">${p.stock_quantity} low</span>` : p.stock_quantity}</td>
      <td class="num">${p.reorder_level}</td>
      <td class="actions"><button class="btn btn-sm btn-light" onclick="edit(${p.id})">Edit</button>
      <button class="btn btn-sm btn-danger" onclick="del(${p.id})">Delete</button></td></tr>`).join('')
      : '<tr><td colspan="10" class="empty">No products found.</td></tr>';
  } catch (e) { toastError(e); }
}
function openForm(p) {
  if (!cats.length) { toast('Add a category first.', 'error'); return; }
  $('formTitle').textContent = p ? 'Edit Product' : 'Add Product';
  $('id').value = p ? p.id : '';
  $('name').value = p ? p.name : '';
  $('barcode').value = p ? (p.barcode || '') : '';
  $('category_id').value = p ? p.category_id : cats[0].id;
  $('cost_price').value = p ? (p.cost_price || 0) : '0.00';
  $('price').value = p ? p.price : '';
  $('stock_quantity').value = p ? p.stock_quantity : '';
  $('reorder_level').value = p ? p.reorder_level : 10;
  $('image').value = '';
  $('formError').textContent = ''; openModal('formModal');
}
window.edit = id => openForm(products.find(p => p.id == id));
window.del = async id => {
  if (!confirm('Delete this product? This cannot be undone.')) return;
  try { toast((await api('products/api.php?action=delete', { json: { id } })).message); load(); } catch (e) { toastError(e); }
};
$('form').addEventListener('submit', async e => {
  e.preventDefault();
  const err = m => { $('formError').textContent = m; };
  if (!$('name').value.trim()) return err('Product name is required.');
  if ($('price').value === '' || +$('price').value < 0) return err('Enter a valid selling price.');
  if ($('stock_quantity').value === '' || +$('stock_quantity').value < 0) return err('Enter a valid stock quantity.');
  if ($('reorder_level').value === '' || +$('reorder_level').value < 0) return err('Enter a valid reorder level.');
  const fd = new FormData();
  ['id', 'name', 'barcode', 'category_id', 'cost_price', 'price', 'stock_quantity', 'reorder_level'].forEach(k => fd.append(k, $(k).value));
  if ($('image').files[0]) fd.append('image', $('image').files[0]);
  try {
    const d = await api('products/api.php?action=save', { form: fd });
    closeModal('formModal'); toast(d.message); load();
  } catch (ex) { err(ex.message); }
});
(async () => {
  if (!await initPage('products')) return;
  try { await loadCats(); } catch (e) { toastError(e); }
  $('addBtn').onclick = () => openForm(null);
  $('search').addEventListener('input', debounce(load));
  $('catFilter').addEventListener('change', load);
  load();
})();
