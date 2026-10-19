const $ = id => document.getElementById(id);
let list = [], me;
async function load() {
  try {
    list = (await api('users/api.php?action=list')).users;
    $('rows').innerHTML = list.map(u => `<tr><td>${u.id}</td><td>${esc(u.username)}</td><td>${esc(u.full_name)}</td><td>${u.role}</td>
      <td><span class="badge ${u.is_active == 1 ? 'badge-ok' : 'badge-low'}">${u.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
      <td class="actions"><button class="btn btn-sm btn-light" onclick="edit(${u.id})">Edit</button>
      ${u.id == me.id ? '' : `<button class="btn btn-sm btn-danger" onclick="del(${u.id})">Delete</button>`}</td></tr>`).join('');
  } catch (e) { toastError(e); }
}
function openForm(u) {
  $('formTitle').textContent = u ? 'Edit User' : 'Add User';
  $('id').value = u ? u.id : ''; $('username').value = u ? u.username : ''; $('full_name').value = u ? u.full_name : '';
  $('role').value = u ? u.role : 'cashier'; $('is_active').value = u ? u.is_active : 1; $('password').value = '';
  $('pwLabel').textContent = u ? 'New Password (leave blank to keep current)' : 'Password * (min 6 characters)';
  $('formError').textContent = ''; openModal('formModal');
}
window.edit = id => openForm(list.find(u => u.id == id));
window.del = async id => {
  if (!confirm('Delete this user?')) return;
  try { toast((await api('users/api.php?action=delete', { json: { id } })).message); load(); } catch (e) { toastError(e); }
};
$('form').addEventListener('submit', async e => {
  e.preventDefault();
  const err = m => { $('formError').textContent = m; };
  if (!/^[A-Za-z0-9_]{3,50}$/.test($('username').value.trim())) return err('Username: 3-50 letters, numbers or underscore.');
  if (!$('full_name').value.trim()) return err('Full name is required.');
  if (!$('id').value && $('password').value.length < 6) return err('Password must be at least 6 characters.');
  try {
    const d = await api('users/api.php?action=save', { json: {
      id: $('id').value, username: $('username').value, full_name: $('full_name').value,
      role: $('role').value, is_active: $('is_active').value === '1', password: $('password').value } });
    closeModal('formModal'); toast(d.message); load();
  } catch (ex) { err(ex.message); }
});
(async () => { me = await initPage('users'); if (!me) return; $('addBtn').onclick = () => openForm(null); load(); })();
