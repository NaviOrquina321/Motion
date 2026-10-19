const $ = id => document.getElementById(id);
let products = [], cats = [];
let currentView = 'grid'; // 'grid' or 'table'

async function loadCats() {
  cats = (await api('categories/api.php?action=list')).categories;
  $('catFilter').innerHTML = '<option value="">All categories</option>' + cats.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
  $('category_id').innerHTML = cats.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
}

async function load() {
  try {
    const qs = new URLSearchParams({ action: 'list', q: $('search').value.trim(), category_id: $('catFilter').value });
    products = (await api('products/api.php?' + qs)).products;
    renderViews();
  } catch (e) { toastError(e); }
}

function renderViews() {
  if (currentView === 'grid') {
    $('gridWrap').classList.remove('hidden');
    $('tableWrap').classList.add('hidden');
    renderGrid();
  } else {
    $('gridWrap').classList.add('hidden');
    $('tableWrap').classList.remove('hidden');
    renderTable();
  }
}

function renderGrid() {
  if (!products.length) {
    $('gridWrap').innerHTML = '<div class="empty" style="grid-column:1/-1">No products found.</div>';
    return;
  }

  $('gridWrap').innerHTML = products.map(p => {
    const isOut = p.stock_quantity <= 0;
    const isLow = !isOut && p.stock_quantity <= p.reorder_level;
    let badge = `<span class="badge badge-ok">In stock (${p.stock_quantity})</span>`;
    if (isOut) badge = `<span class="badge badge-danger">Out of stock</span>`;
    else if (isLow) badge = `<span class="badge badge-low">Low: ${p.stock_quantity} left</span>`;

    return `
      <div class="pcard ${isOut ? 'out' : ''}">
        ${renderProductSlotImage(p)}
        <div>
          <div class="n">${esc(p.name)}</div>
          <div class="p">${money(p.price)}</div>
          <div class="s">${badge}</div>
        </div>
        <div style="display:flex; gap:6px; margin-top:10px">
          <button class="btn btn-sm btn-light" style="flex:1" onclick="edit(${p.id})">Edit</button>
          <button class="btn btn-sm btn-danger" onclick="del(${p.id})">Delete</button>
        </div>
      </div>
    `;
  }).join('');
}

function renderTable() {
  $('rows').innerHTML = products.length ? products.map(p => `<tr>
    <td>${p.image ? `<img class="thumb" src="${imgUrl(p.image)}" alt="${esc(p.name)}">` : '<div class="thumb" style="display:flex;align-items:center;justify-content:center">📦</div>'}</td>
    <td>${p.id}</td>
    <td><code>${esc(p.barcode || '-')}</code></td>
    <td><strong>${esc(p.name)}</strong></td>
    <td>${esc(p.category_name)}</td>
    <td class="num">${money(p.cost_price || 0)}</td>
    <td class="num">${money(p.price)}</td>
    <td class="num">${p.stock_quantity <= p.reorder_level ? `<span class="badge badge-low">${p.stock_quantity} low</span>` : p.stock_quantity}</td>
    <td class="num">${p.reorder_level}</td>
    <td class="actions">
      <button class="btn btn-sm btn-light" onclick="edit(${p.id})">Edit</button>
      <button class="btn btn-sm btn-danger" onclick="del(${p.id})">Delete</button>
    </td></tr>`).join('')
    : '<tr><td colspan="10" class="empty">No products found. Add your first product.</td></tr>';
}

function openForm(p) {
  if (!cats.length) { toast('Add a category first.', 'error'); return; }
  $('formTitle').textContent = p ? 'Edit Product' : 'Add Product';
  $('id').value = p ? p.id : '';
  $('name').value = p ? p.name : '';
  $('barcode').value = p ? (p.barcode || '') : '';
  $('category_id').value = p ? p.category_id : cats[0].id;
  $('cost_price').value = p ? (p.cost_price || 0) : '0.00';
  $('price').value = p ? p.price : '';
  $('stock_quantity').value = p ? p.stock_quantity : '';
  $('reorder_level').value = p ? p.reorder_level : 10;
  $('image').value = '';
  $('remove_image_val').value = '0';

  if (p && p.image) {
    $('imagePreview').innerHTML = `<img src="${imgUrl(p.image)}" style="width:100%;height:100%;object-fit:cover">`;
    $('removeImageBtn').classList.remove('hidden');
  } else {
    $('imagePreview').innerHTML = '<span>📦</span>';
    $('removeImageBtn').classList.add('hidden');
  }

  $('formError').textContent = '';
  openModal('formModal');
}

window.edit = id => openForm(products.find(p => p.id == id));

window.del = id => {
  confirmModal('Delete Product', 'Are you sure you want to delete this product? This action cannot be undone.', async () => {
    try {
      toast((await api('products/api.php?action=delete', { json: { id } })).message);
      load();
    } catch (e) { toastError(e); }
  });
};

$('removeImageBtn').onclick = () => {
  $('image').value = '';
  $('remove_image_val').value = '1';
  $('imagePreview').innerHTML = '<span>📦</span>';
  $('removeImageBtn').classList.add('hidden');
};

$('image').addEventListener('change', e => {
  const file = e.target.files[0];
  if (file) {
    $('remove_image_val').value = '0';
    const reader = new FileReader();
    reader.onload = ev => {
      $('imagePreview').innerHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover">`;
      $('removeImageBtn').classList.remove('hidden');
    };
    reader.readAsDataURL(file);
  }
});

$('viewGridBtn').onclick = () => {
  currentView = 'grid';
  $('viewGridBtn').classList.add('active');
  $('viewTableBtn').classList.remove('active');
  renderViews();
};

$('viewTableBtn').onclick = () => {
  currentView = 'table';
  $('viewTableBtn').classList.add('active');
  $('viewGridBtn').classList.remove('active');
  renderViews();
};

$('form').addEventListener('submit', async e => {
  e.preventDefault();
  const err = m => { $('formError').textContent = m; };
  if (!$('name').value.trim()) return err('Product name is required.');
  if ($('price').value === '' || +$('price').value < 0) return err('Enter a valid selling price.');
  if ($('stock_quantity').value === '' || +$('stock_quantity').value < 0) return err('Enter a valid stock quantity.');
  if ($('reorder_level').value === '' || +$('reorder_level').value < 0) return err('Enter a valid reorder level.');

  const saveBtn = $('saveBtn');
  saveBtn.disabled = true;
  saveBtn.textContent = 'Saving...';

  const fd = new FormData();
  ['id', 'name', 'barcode', 'category_id', 'cost_price', 'price', 'stock_quantity', 'reorder_level'].forEach(k => fd.append(k, $(k).value));
  if ($('remove_image_val').value === '1') {
    fd.append('remove_image', '1');
  }
  if ($('image').files[0]) {
    fd.append('image', $('image').files[0]);
  }

  try {
    const d = await api('products/api.php?action=save', { form: fd });
    closeModal('formModal');
    toast(d.message);
    load();
  } catch (ex) {
    err(ex.message);
  } finally {
    saveBtn.disabled = false;
    saveBtn.textContent = 'Save Product';
  }
});

(async () => {
  if (!await initPage('products')) return;
  try { await loadCats(); } catch (e) { toastError(e); }
  $('addBtn').onclick = () => openForm(null);
  $('search').addEventListener('input', debounce(load));
  $('catFilter').addEventListener('change', load);
  load();
})();
