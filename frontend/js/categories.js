const $ = id => document.getElementById(id);
let list = [];
async function load() {
  try {
    list = (await api('categories/api.php?action=list')).categories;
    $('rows').innerHTML = list.length ? list.map(c => `<tr><td>${c.id}</td><td><strong>${esc(c.name)}</strong></td><td>${esc(c.description || '-')}</td>
      <td class="num">${c.product_count}</td><td class="actions">
      <button class="btn btn-sm btn-light" onclick="edit(${c.id})">Edit</button>
      <button class="btn btn-sm btn-danger" onclick="del(${c.id})">Delete</button></td></tr>`).join('')
      : '<tr><td colspan="5" class="empty">No categories yet. Add your first category.</td></tr>';
  } catch (e) { toastError(e); }
}
function openForm(c) {
  $('formTitle').textContent = c ? 'Edit Category' : 'Add Category';
  $('id').value = c ? c.id : ''; $('name').value = c ? c.name : ''; $('description').value = c ? (c.description || '') : '';
  $('formError').textContent = ''; openModal('formModal'); $('name').focus();
}
window.edit = id => openForm(list.find(c => c.id == id));
window.del = id => {
  confirmModal('Delete Category', 'Are you sure you want to delete this category?', async () => {
    try { toast((await api('categories/api.php?action=delete', { json: { id } })).message); load(); } catch (e) { toastError(e); }
  });
};
$('form').addEventListener('submit', async e => {
  e.preventDefault();
  if (!$('name').value.trim()) { $('formError').textContent = 'Category name is required.'; return; }
  try {
    const d = await api('categories/api.php?action=save', { json: { id: $('id').value, name: $('name').value, description: $('description').value } });
    closeModal('formModal'); toast(d.message); load();
  } catch (err) { $('formError').textContent = err.message; }
});
(async () => { if (!await initPage('categories')) return; $('addBtn').onclick = () => openForm(null); load(); })();
