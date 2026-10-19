const $ = id => document.getElementById(id);
let movements = [];

async function load() {
  try {
    const qs = new URLSearchParams({
      action: 'movements',
      q: $('search').value.trim(),
      type: $('typeFilter').value,
      from: $('from').value,
      to: $('to').value
    });
    movements = (await api('inventory/api.php?' + qs)).movements;
    $('rows').innerHTML = movements.length ? movements.map(m => {
      const qNum = parseInt(m.quantity, 10);
      const isPositive = qNum > 0;
      const badgeCls = isPositive ? 'badge-ok' : (m.type === 'sold' ? 'badge-info' : 'badge-danger');

      return `<tr>
        <td>${m.id}</td>
        <td>${esc(m.created_at)}</td>
        <td><strong>${esc(m.product_name)}</strong></td>
        <td><span class="badge ${badgeCls}">${esc(m.type.toUpperCase())}</span></td>
        <td class="num" style="font-weight:700; color: ${isPositive ? 'var(--ok)' : 'var(--danger)'}">
          ${isPositive ? '+' + qNum : qNum}
        </td>
        <td>${esc(m.reason || '-')}</td>
        <td>${esc(m.username)}</td>
      </tr>`;
    }).join('')
    : '<tr><td colspan="7" class="empty">No inventory movement history found.</td></tr>';
  } catch (e) { toastError(e); }
}

(async () => {
  if (!await initPage('movements')) return;
  $('search').addEventListener('input', debounce(load));
  $('typeFilter').addEventListener('change', load);
  $('from').addEventListener('change', load);
  $('to').addEventListener('change', load);
  $('resetBtn').onclick = () => {
    $('search').value = '';
    $('typeFilter').value = '';
    $('from').value = '';
    $('to').value = '';
    load();
  };
  load();
})();
