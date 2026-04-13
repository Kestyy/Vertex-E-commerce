// ═══════════════════════════════════════
// PRODUCTS CRUD — products.js
// ═══════════════════════════════════════

const API = 'assets/php/products_api.php';

let products   = [];
let categories = [];
let editingId  = null;
let deletingId = null;

function formatMoney(n) {
  return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function statusDot(s) {
  return s === 'active'
    ? '<span class="status-dot active">Active</span>'
    : '<span class="status-dot inactive">Inactive</span>';
}

function formatDate(d) {
  if (!d) return '—';
  const dt = new Date(d);
  return dt.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
    + ' ' + dt.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
}

// ── Single init call ───────────────────────────────────
async function initPage() {
  try {
    const res  = await fetch(`${API}?action=init`);
    const data = await res.json();
    if (data.success) {
      products   = data.products;
      categories = data.categories;

      const sel = document.getElementById('filterCategory');
      sel.innerHTML = '<option value="">All Categories</option>';
      categories.forEach(c => sel.innerHTML += `<option value="${c.id}">${c.name}</option>`);

      renderTable();
    } else {
      await loadCategories();
      await loadProducts();
    }
  } catch (err) {
    showToast('danger', 'Cannot reach products_api.php — check the file exists.');
  }
}

async function loadProducts() {
  try {
    const res  = await fetch(`${API}?action=list`);
    const data = await res.json();
    if (data.success) {
      products = data.data;
      renderTable();
    } else {
      showToast('danger', 'Failed to load products: ' + (data.message || ''));
    }
  } catch (err) {
    showToast('danger', 'Cannot reach products_api.php.');
  }
}

async function loadCategories() {
  try {
    const res  = await fetch(`${API}?action=categories`);
    const data = await res.json();
    if (data.success) {
      categories = data.data;
      const sel  = document.getElementById('filterCategory');
      sel.innerHTML = '<option value="">All Categories</option>';
      categories.forEach(c => sel.innerHTML += `<option value="${c.id}">${c.name}</option>`);
    }
  } catch (err) { console.error('Error loading categories'); }
}

// ── Filter ─────────────────────────────────────────────
function getFiltered() {
  const q     = (document.getElementById('filterInput')?.value || '').toLowerCase();
  const cat   = document.getElementById('filterCategory')?.value || '';
  const stat  = document.getElementById('filterStatus')?.value || '';
  const stk   = document.getElementById('filterStock')?.value || '';
  const price = document.getElementById('filterPrice')?.value || '';

  return products.filter(p => {
    const matchQ = !q   || p.name.toLowerCase().includes(q)
                        || (p.sku || '').toLowerCase().includes(q)
                        || (p.description || '').toLowerCase().includes(q);
    const matchC = !cat  || String(p.category_id) === String(cat);
    const matchS = !stat || p.status === stat;

    let matchStk = true;
    if      (stk === 'out') matchStk = p.stock_quantity == 0;
    else if (stk === 'low') matchStk = p.stock_quantity > 0 && p.stock_quantity < 10;
    else if (stk === 'in')  matchStk = p.stock_quantity >= 10;

    let matchP = true;
    if      (price === '0-500')     matchP = p.price >= 0    && p.price < 500;
    else if (price === '500-1000')  matchP = p.price >= 500  && p.price < 1000;
    else if (price === '1000-5000') matchP = p.price >= 1000 && p.price < 5000;
    else if (price === '5000+')     matchP = p.price >= 5000;

    return matchQ && matchC && matchS && matchStk && matchP;
  });
}

// ── Render table ────────────────────────────────────────
function renderTable() {
  const filtered = getFiltered();
  const tbody    = document.getElementById('productTableBody');
  const empty    = document.getElementById('emptyState');

  if (!filtered.length) {
    tbody.innerHTML = '';
    empty.style.display = 'block';
    return;
  }

  empty.style.display = 'none';
  tbody.innerHTML = filtered.map(p => {
    const stockColor = p.stock_quantity == 0
      ? 'color:#dc2626;font-weight:600;'
      : p.stock_quantity < 10
        ? 'color:#ca8a04;font-weight:600;'
        : 'color:#16a34a;font-weight:600;';

    const imgSrc = p.image
      ? `../images/products/${p.image}`
      : `../images/products/default.jpg`;

    const modifiedAt = formatDate(p.updated_at || p.created_at);

    return `
      <tr id="row-${p.id}">
        <td>
          <div class="product-cell">
            <img src="${imgSrc}" alt="${p.name}" class="product-img"
                 onerror="this.src='../images/products/default.jpg'"/>
            <div>
              <div class="product-name">${p.name}</div>
            </div>
          </div>
        </td>
        <td style="font-weight:600;color:#0f172a;font-size:15px;">${formatMoney(p.price)}</td>
        <td>${p.category_name || 'Uncategorized'}</td>
        <td><span style="${stockColor}">${p.stock_quantity}</span></td>
        <td>${statusDot(p.status)}</td>
        <td style="font-size:15px;color:var(--text-muted);">${modifiedAt}</td>
        <td>
          <div class="action-wrap">
            <button class="btn-edit" onclick="openEdit(${p.id})">
              <i class="fas fa-pen"></i> Edit
            </button>
            <button class="btn-del" onclick="openDelete(${p.id})" title="Delete">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

// ── Category select helper ──────────────────────────────
function populateCategorySelect(selectedId = '') {
  const sel = document.getElementById('productCategory');
  sel.innerHTML = '<option value="">Select Category</option>';
  categories.forEach(c => {
    sel.innerHTML += `<option value="${c.id}" ${String(c.id) === String(selectedId) ? 'selected' : ''}>${c.name}</option>`;
  });
}

// ── Reset image UI ──────────────────────────────────────
function resetImageUI() {
  const fileInput = document.getElementById('productImageFile');
  if (fileInput) fileInput.value = '';
  const prevImg = document.getElementById('imagePreviewImg');
  if (prevImg) prevImg.src = '';
  const existing = document.getElementById('productImageExisting');
  if (existing) existing.value = '';
  const placeholder = document.getElementById('imagePlaceholder');
  const preview     = document.getElementById('imagePreviewWrap');
  if (placeholder) placeholder.style.display = 'flex';
  if (preview)     preview.style.display     = 'none';
}

// ── Setup price field ─────────────────────────────────
function setupPriceField() {
  const priceField = document.getElementById('productPrice');
  if (!priceField) return;
  
  // Prevent typing 'e' and non-numeric characters
  priceField.addEventListener('keypress', function(e) {
    const char = String.fromCharCode(e.which);
    if (!/^[0-9.]$/.test(char)) {
      e.preventDefault();
    }
  });
  
  // Format with commas and .00 on blur
  priceField.addEventListener('blur', function() {
    let value = this.value.replace(/,/g, '');
    if (value && !isNaN(value)) {
      const parts = value.split('.');
      const integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      const decimalPart = parts[1] ? parts[1].padEnd(2, '0').substring(0, 2) : '00';
      this.value = integerPart + '.' + decimalPart;
    }
  });
}

// ── Open Add ───────────────────────────────────────────
function openAdd() {
  editingId = null;
  document.getElementById('modalTitle').textContent = 'Add Product';
  document.getElementById('productForm').reset();
  document.getElementById('formError').style.display = 'none';
  resetImageUI();
  populateCategorySelect();
  setupPriceField();
  document.getElementById('productModal').classList.add('open');
}

// ── Open Edit ──────────────────────────────────────────
function openEdit(id) {
  const p = products.find(x => x.id == id);
  if (!p) return;
  editingId = id;

  document.getElementById('modalTitle').textContent     = 'Edit Product';
  document.getElementById('productName').value          = p.name;
  document.getElementById('productDescription').value   = p.description || '';
  document.getElementById('productPrice').value         = p.price;
  document.getElementById('productStock').value         = p.stock_quantity;
  document.getElementById('productStatus').value        = p.status;
  document.getElementById('formError').style.display    = 'none';
  document.getElementById('productImageExisting').value = p.image || '';

  resetImageUI();
  if (p.image) {
    const prevImg     = document.getElementById('imagePreviewImg');
    const placeholder = document.getElementById('imagePlaceholder');
    const preview     = document.getElementById('imagePreviewWrap');
    if (prevImg)     prevImg.src               = `../images/products/${p.image}`;
    if (placeholder) placeholder.style.display = 'none';
    if (preview)     preview.style.display     = 'block';
  }

  populateCategorySelect(p.category_id);
  setupPriceField();
  document.getElementById('productModal').classList.add('open');
}

// ── Close modal ────────────────────────────────────────
function closeModal() {
  document.getElementById('productModal').classList.remove('open');
  editingId = null;
}

// ── Open Delete ────────────────────────────────────────
function openDelete(id) {
  deletingId = id;
  const p = products.find(x => x.id == id);
  document.getElementById('deleteProductName').textContent = p ? p.name : '';
  document.getElementById('deleteModal').classList.add('open');
}

// ── Save product ───────────────────────────────────────
async function saveProduct() {
  const name  = document.getElementById('productName').value.trim();
  const price = parseFloat(document.getElementById('productPrice').value.replace(/,/g, ''));
  const errEl = document.getElementById('formError');

  if (!name || isNaN(price) || price < 0) {
    errEl.textContent   = 'Product name and a valid price are required.';
    errEl.style.display = 'block';
    return;
  }

  errEl.style.display = 'none';

  const fd = new FormData();
  fd.append('name',           name);
  fd.append('description',    document.getElementById('productDescription').value.trim());
  fd.append('price',          price);
  fd.append('category_id',    document.getElementById('productCategory').value || 0);
  fd.append('stock_quantity', document.getElementById('productStock').value || 0);
  fd.append('status',         document.getElementById('productStatus').value);

  const fileInput = document.getElementById('productImageFile');
  if (fileInput.files[0]) {
    fd.append('image', fileInput.files[0]);
  }

  if (editingId) fd.append('id', editingId);

  const action = editingId ? 'edit' : 'add';

  try {
    const res    = await fetch(`${API}?action=${action}`, { method: 'POST', body: fd });
    const result = await res.json();
    if (result.success) {
      showToast('success', result.message || 'Product saved.');
      closeModal();
      await loadProducts();
    } else {
      errEl.textContent   = result.message || 'Failed to save.';
      errEl.style.display = 'block';
    }
  } catch (err) {
    errEl.textContent   = 'Server error — check products_api.php.';
    errEl.style.display = 'block';
  }
}

// ── Delete product ─────────────────────────────────────
async function deleteProduct() {
  if (!deletingId) return;
  try {
    const res    = await fetch(`${API}?action=delete`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ id: deletingId }),
    });
    const result = await res.json();
    if (result.success) {
      showToast('success', result.message || 'Product deleted.');
      document.getElementById('deleteModal').classList.remove('open');
      deletingId = null;
      await loadProducts();
    } else {
      showToast('danger', result.message || 'Failed to delete.');
    }
  } catch (err) {
    showToast('danger', 'Server error.');
  }
}

// ── Toast ──────────────────────────────────────────────
function showToast(type, message) {
  document.querySelectorAll('.toast-popup').forEach(t => t.remove());
  const t = document.createElement('div');
  t.className = 'toast-popup';
  t.style.cssText = `
    position:fixed;bottom:24px;right:24px;z-index:999;
    background:${type === 'success' ? '#dcfce7' : '#fee2e2'};
    color:${type === 'success' ? '#16a34a' : '#dc2626'};
    border:1px solid ${type === 'success' ? '#bbf7d0' : '#fca5a5'};
    border-radius:10px;padding:12px 18px;
    font-family:'Poppins',sans-serif;font-size:13px;font-weight:500;
    display:flex;align-items:center;gap:10px;
    box-shadow:0 4px 16px rgba(0,0,0,0.1);
  `;
  t.innerHTML = `
    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
    <span>${message}</span>
    <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-size:1rem;color:inherit;margin-left:8px;">×</button>
  `;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 5000);
}

// ── Init ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  await initPage();

  // Create action buttons in filter row
  const filterActions = document.getElementById('filterActions');
  if (filterActions) {
    filterActions.innerHTML = `
      <button class="btn btn-secondary" id="btnOpenCategoryModal">
        <i class="fas fa-tags"></i> Add Category
      </button>
      <button class="btn btn-primary" id="btnAddProduct">
        <i class="fas fa-plus"></i> Add Product
      </button>
    `;
  }

  ['filterInput','filterCategory','filterStatus','filterStock','filterPrice'].forEach(id => {
    document.getElementById(id)?.addEventListener('input',  renderTable);
    document.getElementById(id)?.addEventListener('change', renderTable);
  });

  document.getElementById('btnOpenCategoryModal')?.addEventListener('click', () => {
    const modal = document.getElementById('categoryModal');
    if (modal) {
      modal.classList.add('open');
      // Reset form when opening
      const nameInput = document.getElementById('catNameInput');
      if (nameInput) nameInput.value = '';
      // Load categories into the list
      window.loadCategoryList?.();
    }
    window.refreshCategoryDropdowns?.();
  });
  document.getElementById('btnAddProduct')   ?.addEventListener('click', openAdd);
  document.getElementById('btnSaveProduct')  ?.addEventListener('click', saveProduct);
  document.getElementById('btnCancelProduct')?.addEventListener('click', closeModal);
  document.getElementById('btnConfirmDelete')?.addEventListener('click', deleteProduct);
  document.getElementById('btnCancelDelete') ?.addEventListener('click', () => {
    document.getElementById('deleteModal').classList.remove('open');
    deletingId = null;
  });

  document.getElementById('productImageFile')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('imagePreviewImg').src             = e.target.result;
      document.getElementById('imagePlaceholder').style.display  = 'none';
      document.getElementById('imagePreviewWrap').style.display  = 'block';
    };
    reader.readAsDataURL(file);
  });

  document.getElementById('btnRemoveImage')?.addEventListener('click', function (e) {
    e.stopPropagation();
    resetImageUI();
  });

  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });
});