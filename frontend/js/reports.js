const $ = id => document.getElementById(id);
const table = (head, rows) => rows.length ? `<table><thead><tr>${head.map(h => `<th>${h}</th>`).join('')}</tr></thead><tbody>${rows.join('')}</tbody></table>` : '<div class="empty">No sales in this period.</div>';
async function load() {
  try {
    const r = (await api('reports/summary.php?days=' + $('days').value)).report;
    $('cards').innerHTML = [['Revenue', money(r.total_revenue)], ['Transactions', r.transactions], ['Average Sale', money(r.average_sale)], ['Items Sold', r.items_sold]]
      .map(([l, v]) => `<div class="stat"><div class="label">${l}</div><div class="value">${v}</div></div>`).join('');
    const max = Math.max(...r.daily.map(d => d.revenue), 1);
    $('bars').innerHTML = r.daily.map(d => `<div class="bar" style="height:${Math.max(d.revenue / max * 100, 2)}%" title="${d.day}: ${money(d.revenue)}"></div>`).join('');
    $('top').innerHTML = table(['Product', 'Qty', 'Revenue'], r.top_products.map(p => `<tr><td>${esc(p.name)}</td><td>${p.quantity}</td><td>${money(p.revenue)}</td></tr>`));
    $('cat').innerHTML = table(['Category', 'Revenue'], r.by_category.map(c => `<tr><td>${esc(c.category)}</td><td>${money(c.revenue)}</td></tr>`));
  } catch (e) { toastError(e); }
}
(async () => { if (!await initPage('reports')) return; $('days').onchange = load; load(); })();
