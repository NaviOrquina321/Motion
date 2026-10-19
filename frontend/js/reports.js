const $ = id => document.getElementById(id);

async function loadReport() {
  try {
    const daysVal = $('daysSelect').value;
    let url = 'reports/summary.php?';

    if (daysVal === 'custom') {
      const from = $('from').value;
      const to = $('to').value;
      if (!from || !to) return;
      url += `from=${from}&to=${to}`;
    } else {
      url += `days=${daysVal}`;
    }

    const res = await api(url);
    const r = res.report;

    // Stat Cards
    $('cards').innerHTML = [
      ['Total Gross Revenue', money(r.total_revenue), `${r.transactions} transactions`],
      ['Est. Gross Profit', money(r.estimated_profit), `Revenue minus cost of goods`],
      ['Total Discounts', money(r.total_discounts), `Promotions & discounts`],
      ['Total Tax / VAT', money(r.total_taxes), `Collected tax`],
      ['Items Sold', r.items_sold + ' units', `Average sale: ${money(r.average_sale)}`],
    ].map(([l, v, sub]) => `<div class="stat"><div class="label">${l}</div><div class="value">${v}</div><div class="sub">${sub}</div></div>`).join('');

    // Daily Bar Chart
    const maxRev = Math.max(...r.daily.map(d => d.revenue), 1);
    $('bars').innerHTML = r.daily.map(d => {
      const pct = Math.round((d.revenue / maxRev) * 100);
      return `<div class="bar-wrap" title="${d.day}: ${money(d.revenue)} (${d.transactions} txns)">
        <div class="bar" style="height: ${Math.max(pct, 2)}%"></div>
        <div class="bar-label">${d.day.slice(5)}</div>
      </div>`;
    }).join('');

    // Top Products
    $('top').innerHTML = r.top_products.length ? `<table>
      <thead><tr><th>Product</th><th>Category</th><th class="num">Qty</th><th class="num">Revenue</th><th class="num">Est. Profit</th></tr></thead>
      <tbody>${r.top_products.map(p => `<tr>
        <td><strong>${esc(p.name)}</strong></td>
        <td>${esc(p.category)}</td>
        <td class="num">${p.quantity}</td>
        <td class="num">${money(p.revenue)}</td>
        <td class="num" style="color:var(--ok); font-weight:700">${money(p.estimated_profit)}</td>
      </tr>`).join('')}</tbody></table>`
    : '<div class="empty">No product sales in selected period.</div>';

    // Slow Movers
    $('slow').innerHTML = r.slow_movers.length ? `<table>
      <thead><tr><th>Product</th><th>Category</th><th class="num">Qty</th><th class="num">Revenue</th></tr></thead>
      <tbody>${r.slow_movers.map(p => `<tr>
        <td><strong>${esc(p.name)}</strong></td>
        <td>${esc(p.category)}</td>
        <td class="num">${p.quantity}</td>
        <td class="num">${money(p.revenue)}</td>
      </tr>`).join('')}</tbody></table>`
    : '<div class="empty">No sales recorded.</div>';

    // Category
    $('cat').innerHTML = r.by_category.length ? `<table>
      <thead><tr><th>Category</th><th class="num">Revenue</th></tr></thead>
      <tbody>${r.by_category.map(c => `<tr>
        <td><strong>${esc(c.category)}</strong></td>
        <td class="num">${money(c.revenue)}</td>
      </tr>`).join('')}</tbody></table>`
    : '<div class="empty">No category data.</div>';

    // Payment Methods
    $('payments').innerHTML = r.by_payment_method.length ? `<table>
      <thead><tr><th>Payment Method</th><th class="num">Total Collected</th></tr></thead>
      <tbody>${r.by_payment_method.map(pm => `<tr>
        <td><span class="badge badge-info">${esc(pm.method)}</span></td>
        <td class="num"><strong>${money(pm.amount)}</strong></td>
      </tr>`).join('')}</tbody></table>`
    : '<div class="empty">No payment data.</div>';

    // Inventory Valuation
    const val = r.stock_valuation;
    $('valuation').innerHTML = `
      <div style="font-size:14px; line-height:1.8">
        <div><strong>Total Stocked Units:</strong> ${val.total_items} items</div>
        <div><strong>Stock Valuation (Cost Price):</strong> ${money(val.cost_value)}</div>
        <div><strong>Stock Valuation (Retail Price):</strong> <span style="color:var(--primary); font-weight:700">${money(val.retail_value)}</span></div>
        <div><strong>Potential Gross Margin:</strong> ${money(val.retail_value - val.cost_value)}</div>
      </div>
    `;

    // Returns & PO Summary
    const ret = r.returns_summary;
    const po = r.po_summary;
    $('otherSummary').innerHTML = `
      <div style="font-size:14px; line-height:1.8">
        <div><strong>Processed Returns:</strong> ${ret.count} transaction(s)</div>
        <div><strong>Total Refunded:</strong> <span style="color:var(--danger); font-weight:700">${money(ret.total_refunded)}</span></div>
        <hr style="border:0; border-top:1px dashed var(--border); margin:8px 0">
        <div><strong>Purchase Orders:</strong> ${po.count} order(s)</div>
        <div><strong>Total PO Inventory Spent:</strong> ${money(po.total_spent)}</div>
      </div>
    `;

  } catch (e) { toastError(e); }
}

$('daysSelect').addEventListener('change', e => {
  if (e.target.value === 'custom') {
    $('dateRangeBox').classList.remove('hidden');
    $('dateRangeBox').style.display = 'inline-flex';
  } else {
    $('dateRangeBox').classList.add('hidden');
    $('dateRangeBox').style.display = 'none';
    loadReport();
  }
});

$('applyDatesBtn').onclick = loadReport;

$('exportSalesCsvBtn').onclick = () => {
  let url = '../backend/php/reports/export.php?type=sales';
  if ($('daysSelect').value === 'custom' && $('from').value && $('to').value) {
    url += `&from=${$('from').value}&to=${$('to').value}`;
  }
  window.location.href = url;
};

$('exportReturnsCsvBtn').onclick = () => {
  let url = '../backend/php/reports/export.php?type=returns';
  if ($('daysSelect').value === 'custom' && $('from').value && $('to').value) {
    url += `&from=${$('from').value}&to=${$('to').value}`;
  }
  window.location.href = url;
};

(async () => {
  if (!await initPage('reports')) return;
  loadReport();
})();
