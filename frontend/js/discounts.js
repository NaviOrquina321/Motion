const $ = id => document.getElementById(id);
let discountsList = [];

async function load() {
  try {
    discountsList = (await api('discounts/api.php?action=list')).discounts;
    $('rows').innerHTML = discountsList.length ? discountsList.map(d => `<tr>
      <td>${d.id}</td>
      <td><strong>${esc(d.name)}</strong></td>
      <td><span class="badge badge-info">${esc(d.type.toUpperCase())}</span></td>
      <td class="num">${d.type === 'percent' ? d.value + '%' : money(d.value)}</td>
      <td>${d.is_active == 1 ? '<span class="badge badge-ok">Active</span>' : '<span class="badge badge-danger">Inactive</span>'}</td>
      <td class="actions">
        <button class="btn btn-sm btn-light" onclick="edit(${d.id})">Edit</button>
        <button class="btn btn-sm btn-danger" onclick="del(${d.id})">Delete</button>
      </td>
    </tr>`).join('')
    : '<tr><td colspan="6" class="empty">No discounts configured yet. Add your first discount.</td></tr>';
  } catch (e) { toastError(e); }
}

function openForm(d) {
  $('formTitle').textContent = d ? 'Edit Discount' : 'Add Discount';
  $('id').value = d ? d.id : '';
  $('name').value = d ? d.name : '';
  $('type').value = d ? d.type : 'percent';
  $('value').value = d ? d.value : '';
  $('is_active').value = d ? d.is_active : '1';
  $('formError').textContent = '';
  openModal('formModal');
}

window.edit = id => openForm(discountsList.find(d => d.id == id));
window.del = id => {
  confirmModal('Delete Discount', 'Are you sure you want to delete this discount?', async () => {
    try {
      toast((await api('discounts/api.php?action=delete', { json: { id } })).message);
      load();
    } catch (e) { toastError(e); }
  });
};

$('form').addEventListener('submit', async e => {
  e.preventDefault();
  $('formError').textContent = '';
  const name = $('name').value.trim();
  const type = $('type').value;
  const val = parseFloat($('value').value);

  if (!name) return ($('formError').textContent = 'Discount name is required.');
  if (isNaN(val) || val < 0) return ($('formError').textContent = 'Enter a valid discount value.');
  if (type === 'percent' && val > 100) return ($('formError').textContent = 'Percentage value cannot exceed 100%.');

  try {
    const payload = {
      id: $('id').value,
      name, type, value: val,
      is_active: $('is_active').value
    };
    const res = await api('discounts/api.php?action=save', { json: payload });
    closeModal('formModal');
    toast(res.message);
    load();
  } catch (ex) {
    $('formError').textContent = ex.message;
  }
});

(async () => {
  if (!await initPage('discounts')) return;
  $('addBtn').onclick = () => openForm(null);
  load();
})();
