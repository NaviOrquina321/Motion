(async () => {
  if (!await initPage('dashboard')) return;
  try {
    const s = await api('dashboard/stats.php');
    document.getElementById('cards').innerHTML = [
      ['Total Products', s.total_products, ''],
      ['Total Sales', money(s.total_sales_amount), `${s.total_sales_count} transactions`],
      ['Total Customers', s.total_customers, ''],
      ['Low Stock Products', s.low_stock_count, 'at or below reorder level', s.low_stock_count > 0 ? 'warn' : ''],
      ["Today's Sales", money(s.today_amount), `${s.today_count} transactions`],
    ].map(([l, v, sub, cls]) => `<div class="stat ${cls || ''}"><div class="label">${l}</div><div class="value">${v}</div><div class="sub">${sub}</div></div>`).join('');

    document.getElementById('lowStock').innerHTML = s.low_stock.length === 0
      ? '<div class="empty">All products are sufficiently stocked. ✅</div>'
      : `<table><thead><tr><th>ID</th><th>Product</th><th>Category</th><th class="num">Stock</th><th class="num">Reorder Level</th></tr></thead><tbody>` +
        s.low_stock.map(p => `<tr><td>${p.id}</td><td>${esc(p.name)}</td><td>${esc(p.category_name)}</td>
          <td class="num"><span class="badge badge-low">${p.stock_quantity}</span></td><td class="num">${p.reorder_level}</td></tr>`).join('') + '</tbody></table>';
  } catch (e) { toastError(e); }
})();
