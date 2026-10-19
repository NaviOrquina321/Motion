const $ = id => document.getElementById(id);
let poList = [], suppliers = [], products = [];
let currentPo = null;

async function loadDependencies() {
  try {
    const [sRes, pRes] = await Promise.all([
      api('suppliers/api.php?action=list'),
      api('products/api.php?action=list')
    ]);
    suppliers = sRes.suppliers || [];
    products = pRes.products || [];

    $('po_supplier_id').innerHTML = '<option value="">-- Choose Supplier --</option>' +
      suppliers.map(s => `<option value="${s.id}">${esc(s.name)}</option>`).join('');
  } catch (e) { toastError(e); }
}

async function load() {
  try {
    const qs = new URLSearchParams({
      action: 'list',
      q: $('search').value.trim(),
      status: $('statusFilter').value
    });
    poList = (await api('purchase_orders/api.php?' + qs)).purchase_orders;

    $('rows').innerHTML = poList.length ? poList.map(po => {
      let badgeCls = 'badge-info';
      if (po.status === 'Completed') badgeCls = 'badge-ok';
      if (po.status === 'Cancelled') badgeCls = 'badge-danger';
      if (po.status === 'Partially Received') badgeCls = 'badge-low';

      return `<tr>
        <td><strong>${esc(po.po_number)}</strong></td>
        <td>${esc(po.created_at)}</td>
        <td>${esc(po.supplier_name)}</td>
        <td><span class="badge ${badgeCls}">${esc(po.status)}</span></td>
        <td class="num"><strong>${money(po.total_cost)}</strong></td>
        <td>${esc(po.username)}</td>
        <td class="actions">
          <button class="btn btn-sm btn-light" onclick="viewPo(${po.id})">View / Receive</button>
          ${['Pending', 'Ordered'].includes(po.status) ? `<button class="btn btn-sm btn-danger" onclick="cancelPo(${po.id})">Cancel</button>` : ''}
        </td>
      </tr>`;
    }).join('')
    : '<tr><td colspan="7" class="empty">No purchase orders found.</td></tr>';
  } catch (e) { toastError(e); }
}

function openCreateModal() {
  $('poForm').reset();
  $('poItemsRows').innerHTML = '';
  addPoItemRow();
  $('poFormError').textContent = '';
  openModal('createPoModal');
}

function addPoItemRow() {
  const rowId = 'poRow_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
  const tr = document.createElement('tr');
  tr.id = rowId;
  tr.innerHTML = `
    <td>
      <select class="po-prod-select" style="width:100%" required>
        <option value="">-- Choose Product --</option>
        ${products.map(p => `<option value="${p.id}" data-cost="${p.cost_price || 0}">${esc(p.name)} (Stock: ${p.stock_quantity})</option>`).join('')}
      </select>
    </td>
    <td class="num">
      <input type="number" class="po-cost-input" min="0" step="0.01" value="0.00" style="width:100px; text-align:right" required>
    </td>
    <td class="num">
      <input type="number" class="po-qty-input" min="1" step="1" value="1" style="width:80px; text-align:right" required>
    </td>
    <td class="num">
      <strong class="po-line-subtotal">₱0.00</strong>
    </td>
    <td style="text-align:center">
      <button type="button" class="btn btn-sm btn-danger" onclick="removePoRow('${rowId}')">✕</button>
    </td>
  `;
  $('poItemsRows').appendChild(tr);

  const prodSel = tr.querySelector('.po-prod-select');
  const costInp = tr.querySelector('.po-cost-input');
  const qtyInp = tr.querySelector('.po-qty-input');

  prodSel.onchange = () => {
    const selectedOpt = prodSel.options[prodSel.selectedIndex];
    if (selectedOpt && selectedOpt.dataset.cost) {
      costInp.value = parseFloat(selectedOpt.dataset.cost).toFixed(2);
    }
    recalcPoTotals();
  };

  costInp.oninput = recalcPoTotals;
  qtyInp.oninput = recalcPoTotals;

  recalcPoTotals();
}

window.removePoRow = rowId => {
  const tr = document.getElementById(rowId);
  if (tr) tr.remove();
  recalcPoTotals();
};

function recalcPoTotals() {
  let grand = 0.0;
  document.querySelectorAll('#poItemsRows tr').forEach(tr => {
    const cost = parseFloat(tr.querySelector('.po-cost-input').value) || 0;
    const qty = parseInt(tr.querySelector('.po-qty-input').value, 10) || 0;
    const lineSub = cost * qty;
    grand += lineSub;
    tr.querySelector('.po-line-subtotal').textContent = money(lineSub);
  });
  $('poGrandTotal').textContent = money(grand);
}

$('poForm').addEventListener('submit', async e => {
  e.preventDefault();
  $('poFormError').textContent = '';

  const supplierId = $('po_supplier_id').value;
  if (!supplierId) return ($('poFormError').textContent = 'Please select a supplier.');

  const items = [];
  document.querySelectorAll('#poItemsRows tr').forEach(tr => {
    const pid = tr.querySelector('.po-prod-select').value;
    const cost = parseFloat(tr.querySelector('.po-cost-input').value);
    const qty = parseInt(tr.querySelector('.po-qty-input').value, 10);

    if (pid && !isNaN(cost) && cost >= 0 && !isNaN(qty) && qty > 0) {
      items.push({ product_id: pid, unit_cost: cost, quantity: qty });
    }
  });

  if (items.length === 0) {
    return ($('poFormError').textContent = 'Please add at least one valid product item.');
  }

  try {
    const res = await api('purchase_orders/api.php?action=create', {
      json: {
        supplier_id: supplierId,
        notes: $('po_notes').value.trim(),
        items
      }
    });

    closeModal('createPoModal');
    toast(res.message);
    load();
  } catch (ex) {
    $('poFormError').textContent = ex.message;
  }
});

window.viewPo = async id => {
  try {
    const res = await api(`purchase_orders/api.php?action=get&id=${id}`);
    currentPo = res.purchase_order;

    $('viewPoTitle').textContent = `Purchase Order ${currentPo.po_number} (${currentPo.status})`;

    const canReceive = ['Ordered', 'Partially Received'].includes(currentPo.status);

    $('viewPoBody').innerHTML = `
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; font-size:13px">
        <div>
          <div><strong>Supplier:</strong> ${esc(currentPo.supplier_name)}</div>
          <div><strong>Phone:</strong> ${esc(currentPo.supplier_phone || '-')}</div>
          <div><strong>Email:</strong> ${esc(currentPo.supplier_email || '-')}</div>
        </div>
        <div>
          <div><strong>Order Date:</strong> ${esc(currentPo.created_at)}</div>
          <div><strong>Created By:</strong> ${esc(currentPo.username)}</div>
          <div><strong>Total Cost:</strong> ${money(currentPo.total_cost)}</div>
        </div>
      </div>
      ${currentPo.notes ? `<div style="margin-bottom:16px; font-style:italic"><strong>Notes:</strong> ${esc(currentPo.notes)}</div>` : ''}

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Product</th>
              <th class="num">Unit Cost</th>
              <th class="num">Ordered</th>
              <th class="num">Rec'd</th>
              <th class="num">Outstanding</th>
              ${canReceive ? '<th class="num" style="width:110px">Receive Now</th>' : ''}
            </tr>
          </thead>
          <tbody>
            ${currentPo.items.map(i => `<tr>
              <td><strong>${esc(i.product_name)}</strong></td>
              <td class="num">${money(i.unit_cost)}</td>
              <td class="num">${i.ordered_quantity}</td>
              <td class="num">${i.received_quantity}</td>
              <td class="num"><strong>${i.outstanding_quantity}</strong></td>
              ${canReceive ? `<td class="num">
                <input type="number" class="po-rec-input" data-item-id="${i.id}" min="0" max="${i.outstanding_quantity}"
                       value="${i.outstanding_quantity}" ${i.outstanding_quantity <= 0 ? 'disabled' : ''} style="width:80px; text-align:right">
              </td>` : ''}
            </tr>`).join('')}
          </tbody>
        </table>
      </div>
    `;

    $('receiveError').textContent = '';

    $('viewPoActions').innerHTML = `
      <button class="btn btn-light" data-close="viewPoModal">Close</button>
      ${canReceive ? `<button class="btn btn-ok" onclick="submitReceivePo()">Confirm & Receive Items into Stock</button>` : ''}
    `;

    openModal('viewPoModal');
  } catch (e) { toastError(e); }
};

window.submitReceivePo = async () => {
  $('receiveError').textContent = '';
  if (!currentPo) return;

  const receiveItems = [];
  document.querySelectorAll('.po-rec-input').forEach(input => {
    const poiId = parseInt(input.dataset.itemId, 10);
    const recQty = parseInt(input.value, 10);
    if (!isNaN(recQty) && recQty > 0) {
      receiveItems.push({ item_id: poiId, receive_quantity: recQty });
    }
  });

  if (receiveItems.length === 0) {
    return ($('receiveError').textContent = 'Please enter a receive quantity greater than zero for at least one item.');
  }

  try {
    const res = await api('purchase_orders/api.php?action=receive', {
      json: {
        po_id: currentPo.id,
        items: receiveItems
      }
    });

    closeModal('viewPoModal');
    toast(res.message);
    load();
  } catch (ex) {
    $('receiveError').textContent = ex.message;
  }
};

window.cancelPo = async id => {
  if (!confirm('Cancel this Purchase Order?')) return;
  try {
    toast((await api('purchase_orders/api.php?action=cancel', { json: { id } })).message);
    load();
  } catch (e) { toastError(e); }
};

async function showRestockNeeds() {
  try {
    const res = await api('purchase_orders/api.php?action=restocking_needs');
    const items = res.restock_items || [];

    $('restockRows').innerHTML = items.length ? items.map(p => `<tr>
      <td><strong>${esc(p.name)}</strong></td>
      <td>${esc(p.category_name)}</td>
      <td class="num"><span class="badge badge-low">${p.stock_quantity}</span></td>
      <td class="num">${p.reorder_level}</td>
      <td class="num">${money(p.cost_price)}</td>
    </tr>`).join('')
    : '<tr><td colspan="5" class="empty">All products are sufficiently stocked. ✅</td></tr>';

    openModal('restockModal');
  } catch (e) { toastError(e); }
}

(async () => {
  if (!await initPage('purchase_orders')) return;

  await loadDependencies();

  $('createPoBtn').onclick = openCreateModal;
  $('addItemRowBtn').onclick = addPoItemRow;
  $('restockListBtn').onclick = showRestockNeeds;

  $('search').addEventListener('input', debounce(load));
  $('statusFilter').addEventListener('change', load);

  load();
})();
