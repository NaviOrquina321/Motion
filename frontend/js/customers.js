const $ = id => document.getElementById(id);
const FIELDS = ['id', 'full_name', 'contact_number', 'email', 'address'];
let list = [];
async function load() {
  try {
    list = (await api('customers/api.php?action=list&q=' + encodeURIComponent($('search').value.trim()))).customers;
    $('rows').innerHTML = list.length ? list.map(c => `<tr><td>${c.id}</td><td>${esc(c.full_name)}</td><td>${esc(c.contact_number)}</td>
      <td>${esc(c.email)}</td><td>${esc(c.address)}</td><td class="actions">
      <button class="btn btn-sm btn-light" onclick="edit(${c.id})">Edit</button>
      <button class="btn btn-sm btn-danger" onclick="del(${c.id})">Delete</button></td></tr>`).join('')
      : '<tr><td colspan="6" class="empty">No customers found.</td></tr>';
  } catch (e) { toastError(e); }
}
function openForm(c) {
  $('formTitle').textContent = c ? 'Edit Customer' : 'Add Customer';
  FIELDS.forEach(k => { $(k).value = c ? (c[k] ?? '') : ''; });
  $('formError').textContent = ''; openModal('formModal');
}
window.edit = id => openForm(list.find(c => c.id == id));
window.del = async id => {
  if (!confirm('Delete this customer? Their past sales will stay in the history as Walk-in.')) return;
  try { toast((await api('customers/api.php?action=delete', { json: { id } })).message); load(); } catch (e) { toastError(e); }
};
$('form').addEventListener('submit', async e => {
  e.preventDefault();
  const err = m => { $('formError').textContent = m; };
  if (!$('full_name').value.trim()) return err('Full name is required.');
  const tel = $('contact_number').value.trim(), mail = $('email').value.trim();
  if (tel && !/^[0-9+\-\s()]{7,20}$/.test(tel)) return err('Enter a valid contact number.');
  if (mail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(mail)) return err('Enter a valid email address.');
  const payload = {}; FIELDS.forEach(k => payload[k] = $(k).value);
  try {
    const d = await api('customers/api.php?action=save', { json: payload });
    closeModal('formModal'); toast(d.message); load();
  } catch (ex) { err(ex.message); }
});
(async () => {
  if (!await initPage('customers')) return;
  $('addBtn').onclick = () => openForm(null);
  $('search').addEventListener('input', debounce(load));
  load();
})();
