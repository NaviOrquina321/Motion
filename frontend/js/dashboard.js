(async () => {
  if (!await initPage('dashboard')) return;
  try {
    const s = await api('dashboard/stats.php');
    document.getElementById('cards').innerHTML = [
      ["Today's Sales", money(s.today_amount), `${s.today_count} transactions`],
      ['This Week', money(s.week_amount), `${s.week_count} transactions`],
      ['This Month', money(s.month_amount), `${s.month_count} transactions`],
      ['Average Sale', money(s.average_sale), 'per transaction'],
      ['Total Products', s.total_products, 'active items'],
      ['Low Stock Alert', s.low_stock_count, 'at or below reorder level', s.low_stock_count > 0 ? 'warn' : ''],
    ].map(([l, v, sub, cls]) => `<div class="stat ${cls || ''}"><div class="label">${l}</div><div class="value">${v}</div><div class="sub">${sub}</div></div>`).join('');

    // Render Daily Sales SVG Chart
    const series = s.daily_series || [];
    const maxRev = Math.max(...series.map(d => d.revenue), 10);
    const chartHtml = `
      <div style="display:flex; align-items:flex-end; gap:12px; height:140px; padding-bottom:10px; border-bottom:1px solid var(--border)">
        ${series.map(d => {
          const pct = Math.round((d.revenue / maxRev) * 100);
          return `<div style="flex:1; display:flex; flex-direction:column; align-items:center; height:100%; justify-content:flex-end" title="${d.day}: ${money(d.revenue)} (${d.count} orders)">
            <div style="width:100%; max-width:40px; background:var(--primary); height:${Math.max(pct, 4)}%; border-radius:4px 4px 0 0; transition:height 0.3s"></div>
            <div style="font-size:10px; color:var(--muted); margin-top:6px">${d.day.slice(5)}</div>
          </div>`;
        }).join('')}
      </div>
    `;
    document.getElementById('chartWrap').innerHTML = chartHtml;

    // Low stock list with links
    document.getElementById('lowStock').innerHTML = s.low_stock.length === 0
      ? '<div class="empty">All products are sufficiently stocked. ✅</div>'
      : `<table><thead><tr><th>Product</th><th>Category</th><th class="num">Stock</th><th class="num">Reorder</th><th>Action</th></tr></thead><tbody>` +
        s.low_stock.map(p => `<tr>
          <td><strong>${esc(p.name)}</strong></td>
          <td>${esc(p.category_name)}</td>
          <td class="num"><span class="badge badge-low">${p.stock_quantity}</span></td>
          <td class="num">${p.reorder_level}</td>
          <td><a href="products.html?q=${encodeURIComponent(p.name)}" class="btn btn-sm btn-light">View Item</a></td>
        </tr>`).join('') + '</tbody></table>';

    // Top products
    document.getElementById('topProducts').innerHTML = !s.top_products || s.top_products.length === 0
      ? '<div class="empty">No product sales yet.</div>'
      : `<table><thead><tr><th>Product</th><th class="num">Qty Sold</th><th class="num">Revenue</th></tr></thead><tbody>` +
        s.top_products.map(p => `<tr><td><strong>${esc(p.name)}</strong></td><td class="num">${p.total_qty}</td><td class="num">${money(p.total_revenue)}</td></tr>`).join('') + '</tbody></table>';

    // Recent sales
    document.getElementById('recentSales').innerHTML = !s.recent_sales || s.recent_sales.length === 0
      ? '<div class="empty">No recent transactions recorded.</div>'
      : `<table><thead><tr><th>Txn #</th><th>Date</th><th>Customer</th><th>Cashier</th><th class="num">Total</th></tr></thead><tbody>` +
        s.recent_sales.map(r => `<tr><td>#${r.id}</td><td>${esc(r.created_at)}</td><td>${esc(r.customer_name)}</td><td>${esc(r.cashier_name)}</td><td class="num"><strong>${money(r.total_amount)}</strong></td></tr>`).join('') + '</tbody></table>';

  } catch (e) { toastError(e); }
})();
