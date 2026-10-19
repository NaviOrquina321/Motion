const $ = id => document.getElementById(id);
let currentSale = null;

async function loadHistory() {
  try {
    const res = await api('returns/api.php?action=history');
    const returnsList = res.returns || [];
    $('returnsHistoryRows').innerHTML = returnsList.length ? returnsList.map(r => `<tr>
      <td>#${r.id}</td>
      <td>${esc(r.created_at)}</td>
      <td>#${r.sale_id}</td>
      <td class="num" style="color:var(--danger); font-weight:700">${money(r.total_refund)}</td>
      <td><span class="badge badge-info">${esc(r.refund_method)}</span></td>
      <td>${esc(r.reason)}</td>
      <td>${esc(r.username)}</td>
    </tr>`).join('')
    : '<tr><td colspan="7" class="empty">No processed returns found.</td></tr>';
  } catch (e) { toastError(e); }
}

async function lookupSale() {
  const saleId = parseInt($('saleSearchId').value, 10);
  if (isNaN(saleId) || saleId <= 0) return toast('Enter a valid Sale / Transaction ID.', 'error');

  try {
    const res = await api(`returns/api.php?action=search_sale&sale_id=${saleId}`);
    currentSale = res.sale;

    $('saleTitle').textContent = `Transaction #${currentSale.id} (${esc(currentSale.created_at)})`;
    $('saleCustomer').textContent = `Customer: ${esc(currentSale.customer)} | Cashier: ${esc(currentSale.cashier)}`;

    renderSaleItems();
    $('returnArea').classList.remove('hidden');
  } catch (e) {
    $('returnArea').classList.add('hidden');
    toastError(e);
  }
}

function renderSaleItems() {
  if (!currentSale || !currentSale.items) return;

  $('saleItemsRows').innerHTML = currentSale.items.map(i => {
    const isReturnable = i.returnable_quantity > 0;
    return `<tr>
      <td><strong>${esc(i.product_name)}</strong></td>
      <td class="num">${money(i.unit_price)}</td>
      <td class="num">${i.quantity}</td>
      <td class="num">${i.returned_quantity}</td>
      <td class="num">
        <input type="number" class="ret-qty-input" data-item-id="${i.sale_item_id}" data-price="${i.unit_price}"
               min="0" max="${i.returnable_quantity}" value="0" ${!isReturnable ? 'disabled' : ''} style="width:70px; text-align:right">
      </td>
      <td class="num"><strong class="line-refund-val" id="lineRefund_${i.sale_item_id}">₱0.00</strong></td>
      <td style="text-align:center">
        <input type="checkbox" class="ret-restock-chk" data-item-id="${i.sale_item_id}" ${!isReturnable ? 'disabled' : ''} checked>
      </td>
    </tr>`;
  }).join('');

  document.querySelectorAll('.ret-qty-input').forEach(input => {
    input.addEventListener('input', calculateTotalRefund);
  });

  calculateTotalRefund();
}

function calculateTotalRefund() {
  let grandRefund = 0.0;
  document.querySelectorAll('.ret-qty-input').forEach(input => {
    const siId = input.dataset.itemId;
    const price = parseFloat(input.dataset.price);
    let qty = parseInt(input.value, 10);
    const maxQty = parseInt(input.max, 10);

    if (isNaN(qty) || qty < 0) qty = 0;
    if (qty > maxQty) {
      qty = maxQty;
      input.value = maxQty;
    }

    const lineRefund = qty * price;
    grandRefund += lineRefund;

    const lineEl = $(`lineRefund_${siId}`);
    if (lineEl) lineEl.textContent = money(lineRefund);
  });

  $('totalRefundAmt').textContent = money(grandRefund);
}

$('processReturnForm').addEventListener('submit', async e => {
  e.preventDefault();
  $('returnError').textContent = '';

  if (!currentSale) return;

  const reason = $('reason').value.trim();
  const refundMethod = $('refund_method').value;

  if (!reason) return ($('returnError').textContent = 'Please provide a reason for the return.');

  const itemsToReturn = [];
  document.querySelectorAll('.ret-qty-input').forEach(input => {
    const siId = parseInt(input.dataset.itemId, 10);
    const qty = parseInt(input.value, 10);
    const restockChk = document.querySelector(`.ret-restock-chk[data-item-id="${siId}"]`);

    if (!isNaN(qty) && qty > 0) {
      itemsToReturn.push({
        sale_item_id: siId,
        quantity: qty,
        restock: restockChk ? restockChk.checked : false
      });
    }
  });

  if (itemsToReturn.length === 0) {
    return ($('returnError').textContent = 'Select at least one product quantity to return.');
  }

  $('submitReturnBtn').disabled = true;

  try {
    const res = await api('returns/api.php?action=process_return', {
      json: {
        sale_id: currentSale.id,
        reason,
        refund_method: refundMethod,
        items: itemsToReturn
      }
    });

    toast(res.message);
    $('processReturnForm').reset();
    $('returnArea').classList.add('hidden');
    currentSale = null;
    loadHistory();
  } catch (ex) {
    $('returnError').textContent = ex.message;
  } finally {
    $('submitReturnBtn').disabled = false;
  }
});

(async () => {
  if (!await initPage('returns')) return;

  $('searchSaleBtn').onclick = lookupSale;
  $('saleSearchId').addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      lookupSale();
    }
  });

  loadHistory();
})();
