const $ = id => document.getElementById(id);
const FIELDS = ['id', 'name', 'contact_person', 'phone', 'email', 'address'];
let suppliersList = [];

async function load() {
  try {
    suppliersList = (await api('suppliers/api.php?action=list&q=' + encodeURIComponent($('search').value.trim()))).suppliers;
    $('rows').innerHTML = suppliersList.length ? suppliersList.map(s => `<tr>
      <td>${s.id}</td>
      <td><strong>${esc(s.name)}</strong></td>
      <td>${esc(s.contact_person || '-')}</td>
      <td>${esc(s.phone || '-')}</td>
      <td>${esc(s.email || '-')}</td>
      <td>${esc(s.address || '-')}</td>
      <td class="num">${s.po_count || 0}</td>
      <td class="actions">
        <button class="btn btn-sm btn-light" onclick="edit(${s.id})">Edit</button>
        <button class="btn btn-sm btn-danger" onclick="del(${s.id})">Delete</button>
      </td>
    </tr>`).join('')
    : '<tr><td colspan="8" class="empty">No suppliers found.</td></tr>';
  } catch (e) { toastError(e); }
}

function openForm(s) {
  $('formTitle').textContent = s ? 'Edit Supplier' : 'Add Supplier';
  FIELDS.forEach(k => { $(k).value = s ? (s[k] ?? '') : ''; });
  $('formError').textContent = '';
  openModal('formModal');
}

window.edit = id => openForm(suppliersList.find(s => s.id == id));
window.del = async id => {
  if (!confirm('Delete this supplier?')) return;
  try {
    toast((await api('suppliers/api.php?action=delete', { json: { id } })).message);
    load();
  } catch (e) { toastError(e); }
};

$('form').addEventListener('submit', async e => {
  e.preventDefault();
  $('formError').textContent = '';
  const name = $('name').value.trim();
  const mail = $('email').value.trim();

  if (!name) return ($('formError').textContent = 'Supplier name is required.');
  if (mail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(mail)) return ($('formError').textContent = 'Enter a valid email address.');

  const payload = {};
  FIELDS.forEach(k => payload[k] = $(k).value);

  try {
    const res = await api('suppliers/api.php?action=save', { json: payload });
    closeModal('formModal');
    toast(res.message);
    load();
  } catch (ex) {
    $('formError').textContent = ex.message;
  }
});

(async () => {
  if (!await initPage('suppliers')) return;
  $('addBtn').onclick = () => openForm(null);
  $('search').addEventListener('input', debounce(load));
  load();
})();
