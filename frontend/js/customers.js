let currentUserRole = 'cashier';
const $ = id => document.getElementById(id);
const FIELDS = ['id', 'full_name', 'contact_number', 'email', 'address'];
let list = [];

async function load() {
  try {
    list = (await api('customers/api.php?action=list&q=' + encodeURIComponent($('search').value.trim()))).customers;
    $('rows').innerHTML = list.length ? list.map(c => `<tr>
      <td>${c.id}</td>
      <td><strong>${esc(c.full_name)}</strong></td>
      <td>${esc(c.contact_number || '-')}</td>
      <td>${esc(c.email || '-')}</td>
      <td class="num">${c.total_orders || 0}</td>
      <td class="num"><strong>${money(c.total_spent || 0)}</strong></td>
      <td class="actions">
        <button class="btn btn-sm btn-light" onclick="viewHistory(${c.id})">History</button>
        <button class="btn btn-sm btn-light" onclick="edit(${c.id})">Edit</button>
        ${currentUserRole === 'admin' ? `<button class="btn btn-sm btn-danger" onclick="del(${c.id})">Delete</button>` : ''}
      </td></tr>`).join('')
      : '<tr><td colspan="7" class="empty">No customers found.</td></tr>';
  } catch (e) { toastError(e); }
}

function openForm(c) {
  $('formTitle').textContent = c ? 'Edit Customer' : 'Add Customer';
  FIELDS.forEach(k => { $(k).value = c ? (c[k] ?? '') : ''; });
  $('formError').textContent = ''; openModal('formModal');
}

window.edit = id => openForm(list.find(c => c.id == id));

window.viewHistory = async id => {
  try {
    const res = await api(`customers/api.php?action=sales_history&id=${id}`);
    const c = res.customer;
    const sales = res.sales || [];

    $('historyTitle').textContent = `Purchase History: ${esc(c.full_name)}`;

    $('historyBody').innerHTML = sales.length ? `
      <div class="table-wrap" style="max-height: 300px; overflow-y: auto;">
        <table>
          <thead>
            <tr>
              <th>Txn #</th>
              <th>Date</th>
              <th>Method</th>
              <th>Cashier</th>
              <th class="num">Amount</th>
            </tr>
          </thead>
          <tbody>
            ${sales.map(s => `<tr>
              <td>#${s.id}</td>
              <td>${esc(s.created_at)}</td>
              <td><span class="badge badge-info">${esc(s.payment_method)}</span></td>
              <td>${esc(s.cashier_name)}</td>
              <td class="num"><strong>${money(s.total_amount)}</strong></td>
            </tr>`).join('')}
          </tbody>
        </table>
      </div>`
    : '<div class="empty">This customer has not made any purchases yet.</div>';

    openModal('historyModal');
  } catch (e) { toastError(e); }
};

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
  const user = await initPage('customers');
  if (!user) return;
  currentUserRole = user.role;
  $('addBtn').onclick = () => openForm(null);
  $('search').addEventListener('input', debounce(load));
  load();
})();
