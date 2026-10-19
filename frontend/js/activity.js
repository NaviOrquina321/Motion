const $ = id => document.getElementById(id);
let logs = [];

async function load() {
  try {
    const qs = new URLSearchParams({
      q: $('search').value.trim(),
      from: $('from').value,
      to: $('to').value
    });
    logs = (await api('activity/api.php?' + qs)).logs;
    $('rows').innerHTML = logs.length ? logs.map(l => `<tr>
      <td>${l.id}</td>
      <td>${esc(l.created_at)}</td>
      <td><strong>${esc(l.username)}</strong></td>
      <td><span class="badge badge-info">${esc(l.action)}</span></td>
      <td style="max-width:400px; white-space:normal; word-break:break-word">${esc(l.details)}</td>
    </tr>`).join('')
    : '<tr><td colspan="5" class="empty">No activity records found.</td></tr>';
  } catch (e) { toastError(e); }
}

(async () => {
  if (!await initPage('activity')) return;
  $('search').addEventListener('input', debounce(load));
  $('from').addEventListener('change', load);
  $('to').addEventListener('change', load);
  $('resetBtn').onclick = () => {
    $('search').value = '';
    $('from').value = '';
    $('to').value = '';
    load();
  };
  load();
})();
