const $ = id => document.getElementById(id);
async function load() {
  try {
    const qs = new URLSearchParams({ action: 'history', q: $('search').value.trim(), from: $('from').value, to: $('to').value });
    const sales = (await api('sales/api.php?' + qs)).sales;
    $('rows').innerHTML = sales.length ? sales.map(s => `<tr><td>#${s.id}</td><td>${esc(s.created_at)}</td><td>${esc(s.cashier)}</td>
      <td>${esc(s.customer)}</td><td class="num">${money(s.total_amount)}</td><td class="num">${money(s.payment)}</td>
      <td class="num">${money(s.change_amount)}</td><td><button class="btn btn-sm btn-light" onclick="view(${s.id})">Receipt</button></td></tr>`).join('')
      : '<tr><td colspan="8" class="empty">No transactions found.</td></tr>';
  } catch (e) { toastError(e); }
}
window.view = async id => {
  try { showReceipt((await api('sales/api.php?action=receipt&id=' + id)).receipt); } catch (e) { toastError(e); }
};
(async () => {
  if (!await initPage('sales')) return;
  $('modalHost').innerHTML = RECEIPT_MODAL;
  $('search').addEventListener('input', debounce(load));
  $('from').addEventListener('change', load); $('to').addEventListener('change', load);
  $('resetBtn').onclick = () => { $('search').value = $('from').value = $('to').value = ''; load(); };
  load();
})();
