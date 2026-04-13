// ═══════════════════════════════════════
// CUSTOMERS CRUD — customers.js
// Connected to vertex_db via customers_api.php
// ═══════════════════════════════════════

const API = 'assets/php/customers_api.php';

let customers     = [];
let editingId     = null;
let deactivatingId = null;
let viewingId     = null;
let currentPage   = 1;
const perPage     = 8;

// ── Icons (Font Awesome) ──
const iconEye        = `<i class="fas fa-eye"></i>`;
const iconEdit       = `<i class="fas fa-pen"></i>`;
const iconDeactivate = `<i class="fas fa-ban"></i>`;
const iconActivate   = `<i class="fas fa-check"></i>`;
const iconCheck      = `<i class="fas fa-check"></i>`;

// ── Helpers ──
function getInitials(fullName) {
  const parts = (fullName || '').trim().split(' ');
  return (parts[0]?.[0] || '') + (parts[1]?.[0] || '').toUpperCase();
}

function formatMoney(n) {
  return '₱' + Number(n).toLocaleString('en-PH', {minimumFractionDigits: 2});
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return `${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
}

function badgeClass(type) {
  return type === 'Regular' ? 'badge-info' : 'badge-gray';
}

// Render avatar: image if exists, fallback to initials
function renderAvatar(customer, size = 'sm') {
  const initials = getInitials(customer.full_name);
  const sizeClass = size === 'lg' ? 'view-avatar' : 'avatar-sm';
  
  if (customer.avatar) {
    return `<div class="${sizeClass}">
      <img src="../images/avatars/${customer.avatar}" 
           alt="${customer.full_name}" 
           style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
    </div>`;
  }
  return `<div class="${sizeClass}">${initials}</div>`;
}

// ── Load from DB ──
async function loadCustomers() {
  try {
    const res  = await fetch(`${API}?action=list`);
    const data = await res.json();
    if (data.success) {
      customers = data.data;
      renderTable();
      updateStats();
    } else {
      showToast('danger', 'Failed to load customers.');
    }
  } catch (err) {
    console.error(err);
    showToast('danger', 'Connection error.');
  }
}

// ── Filter ──
function getFiltered() {
  const q   = document.getElementById('searchInput').value.toLowerCase();
  const typ = document.getElementById('filterType').value;
  return customers.filter(c => {
    const matchQ = !q || c.full_name.toLowerCase().includes(q) ||
                   c.email.toLowerCase().includes(q) || (c.phone || '').includes(q);
    const matchT = !typ || c.type === typ;
    return matchQ && matchT;
  });
}

// ── Render Table ──
function renderTable() {
  const filtered = getFiltered();
  const start    = (currentPage - 1) * perPage;
  const paged    = filtered.slice(start, start + perPage);
  const tbody    = document.getElementById('customerTableBody');
  const empty    = document.getElementById('emptyState');

  if (filtered.length === 0) {
    tbody.innerHTML = '';
    empty.style.display = 'block';
  } else {
    empty.style.display = 'none';
    tbody.innerHTML = paged.map(c => `
      <tr id="row-${c.id}" ${!c.active ? 'style="opacity:0.5"' : ''}>
        <td><input type="checkbox" class="row-check" data-id="${c.id}"/></td>
        <td>
          <div class="flex-center gap-8">
            ${renderAvatar(c, 'sm')}
            <div>
              <span class="fw-500">${c.full_name}</span>
              ${!c.active ? '<span style="display:block;font-size:10px;color:var(--danger);font-weight:600">Inactive</span>' : ''}
            </div>
          </div>
        </td>
        <td class="text-muted">${c.email}</td>
        <td class="text-muted">${c.phone || '—'}</td>
        <td>${c.orders || 0}</td>
        <td class="fw-600">${formatMoney(c.spent)}</td>
        <td><span class="badge ${badgeClass(c.type)}">${c.type}</span></td>
        <td class="text-muted">${formatDate(c.created_at)}</td>
        <td>
          <div class="flex-center gap-6">
            <button class="action-btn" title="View" onclick="openView(${c.id})">${iconEye}</button>
            <button class="action-btn" title="Edit" onclick="openEdit(${c.id})">${iconEdit}</button>
            <button class="action-btn ${c.active ? 'danger' : ''}"
              title="${c.active ? 'Deactivate' : 'Activate'}"
              onclick="openDeactivate(${c.id})">
              ${c.active ? iconDeactivate : iconActivate}
            </button>
          </div>
        </td>
      </tr>
    `).join('');
  }

  renderPagination(filtered.length);
  document.getElementById('resultCount').textContent =
    `Showing ${filtered.length} customer${filtered.length !== 1 ? 's' : ''}`;
}

// ── Update Stats ──
function updateStats() {
  const total = customers.length;
  const totalSpent = customers.reduce((sum, c) => sum + parseFloat(c.spent || 0), 0);
  const active = customers.filter(c => c.active == 1).length;
  
  document.getElementById('statTotal').textContent = total;
  document.getElementById('statRevenue').textContent = formatMoney(totalSpent);
  document.getElementById('statActive').textContent = active;
}

// ── Pagination ──
function renderPagination(total) {
  const pages = Math.ceil(total / perPage);
  const wrap  = document.getElementById('paginationWrap');
  
  if (pages <= 1) {
    wrap.innerHTML = '';
    return;
  }

  let html = `<span style="font-size:12px;margin-right:auto" class="text-muted">Page ${currentPage} of ${pages}</span>`;
  html += `<div class="page-btn" onclick="goPage(${currentPage - 1})"><i class="fas fa-chevron-left"></i></div>`;
  
  for (let i = 1; i <= pages; i++) {
    html += `<div class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goPage(${i})">${i}</div>`;
  }
  
  html += `<div class="page-btn" onclick="goPage(${currentPage + 1})"><i class="fas fa-chevron-right"></i></div>`;
  wrap.innerHTML = html;
}

function goPage(p) {
  const pages = Math.ceil(getFiltered().length / perPage);
  if (p < 1 || p > pages) return;
  currentPage = p;
  renderTable();
}

// ── Modal helpers ──
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// ── CLEAR FORM ──
function clearForm() {
  ['fieldFirstName','fieldLastName','fieldEmail','fieldPhone','fieldOrders','fieldSpent'].forEach(id => {
    document.getElementById(id).value = '';
  });
  document.getElementById('fieldType').value = 'New';
  document.getElementById('fieldActive').value = '1';
  document.getElementById('formError').style.display = 'none';
}

// ── ADD ──
function openAdd() {
  editingId = null;
  document.getElementById('formModalTitle').textContent    = 'Add Customer';
  document.getElementById('formModalSubtitle').textContent = 'Fill in the customer details';
  document.getElementById('btnFormSave').innerHTML = `<i class="fas fa-check"></i> Save Customer`;
  clearForm();
  openModal('modalForm');
}

// ── EDIT ──
function openEdit(id) {
  editingId = id;
  const c = customers.find(x => x.id === id);
  if (!c) return;
  
  // Split full_name into first/last for form
  const nameParts = c.full_name.split(' ');
  
  document.getElementById('formModalTitle').textContent    = 'Edit Customer';
  document.getElementById('formModalSubtitle').textContent = `Editing ${c.full_name}`;
  document.getElementById('btnFormSave').innerHTML = `<i class="fas fa-check"></i> Update Customer`;
  document.getElementById('fieldFirstName').value = nameParts[0] || '';
  document.getElementById('fieldLastName').value  = nameParts.slice(1).join(' ') || '';
  document.getElementById('fieldEmail').value     = c.email;
  document.getElementById('fieldPhone').value     = c.phone || '';
  document.getElementById('fieldOrders').value    = c.orders || 0;
  document.getElementById('fieldSpent').value     = c.spent || 0;
  document.getElementById('fieldType').value      = c.type;
  document.getElementById('fieldActive').value    = c.active;
  document.getElementById('formError').style.display = 'none';
  closeModal('modalView');
  openModal('modalForm');
}

function editFromView() {
  closeModal('modalView');
  openEdit(viewingId);
}

// ── SAVE ──
async function saveCustomer() {
  const fn = document.getElementById('fieldFirstName').value.trim();
  const ln = document.getElementById('fieldLastName').value.trim();
  const email = document.getElementById('fieldEmail').value.trim();
  const phone = document.getElementById('fieldPhone').value.trim();
  const orders = parseInt(document.getElementById('fieldOrders').value) || 0;
  const spent = parseFloat(document.getElementById('fieldSpent').value) || 0;
  const type = document.getElementById('fieldType').value;
  const active = parseInt(document.getElementById('fieldActive').value);
  const errEl = document.getElementById('formError');

  if (!fn || !ln || !email) {
    errEl.textContent = 'First name, last name and email are required.';
    errEl.style.display = 'block';
    return;
  }
  
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    errEl.textContent = 'Please enter a valid email address.';
    errEl.style.display = 'block';
    return;
  }

  const payload = { 
    firstName: fn, 
    lastName: ln, 
    email, 
    phone, 
    orders, 
    spent, 
    type, 
    active 
  };
  
  const action = editingId ? 'update' : 'add';
  if (editingId) payload.id = editingId;

  try {
    const res = await fetch(`${API}?action=${action}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      closeModal('modalForm');
      showToast('success', `${fn} ${ln} ${editingId ? 'updated' : 'added'} successfully.`);
      await loadCustomers();
      if (data.id) setTimeout(() => highlightRow(data.id), 100);
    } else {
      errEl.textContent = data.message || 'Save failed.';
      errEl.style.display = 'block';
    }
  } catch (err) {
    console.error(err);
    errEl.textContent = 'Connection error.';
    errEl.style.display = 'block';
  }
}

// ── VIEW ──
function openView(id) {
  viewingId = id;
  const c = customers.find(x => x.id === id);
  if (!c) return;
  
  // Set avatar with image or initials
  const viewAvatarEl = document.getElementById('viewAvatar');
  if (c.avatar) {
    viewAvatarEl.innerHTML = `<img src="../images/avatars/${c.avatar}" 
                                   alt="${c.full_name}" 
                                   style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
  } else {
    viewAvatarEl.textContent = getInitials(c.full_name);
  }
  
  document.getElementById('viewName').textContent = c.full_name;
  document.getElementById('viewEmail').textContent = c.email;
  document.getElementById('viewPhone').textContent = c.phone || '—';
  document.getElementById('viewOrders').textContent = c.orders || 0;
  document.getElementById('viewSpent').textContent = formatMoney(c.spent);
  document.getElementById('viewJoined').textContent = formatDate(c.created_at);
  document.getElementById('viewStatus').textContent = c.active == 1 ? 'Active' : 'Inactive';
  document.getElementById('viewStatus').style.color = c.active == 1 ? 'var(--success)' : 'var(--danger)';
  
  const badge = document.getElementById('viewBadge');
  badge.className = `badge ${badgeClass(c.type)}`;
  badge.textContent = c.type;
  
  openModal('modalView');
}

// ── DEACTIVATE / ACTIVATE ──
function openDeactivate(id) {
  deactivatingId = id;
  const c = customers.find(x => x.id === id);
  if (!c) return;
  
  const isActive = c.active == 1;
  document.getElementById('deactivateCustomerName').textContent = c.full_name;
  document.getElementById('deactivateModalTitle').textContent = isActive ? 'Deactivate Customer' : 'Activate Customer';
  document.getElementById('deactivateModalDesc').innerHTML = isActive 
    ? `Are you sure you want to deactivate <strong>${c.full_name}</strong>?`
    : `Are you sure you want to activate <strong>${c.full_name}</strong>?`;
  document.getElementById('deactivateConfirmBtn').textContent = isActive ? 'Deactivate' : 'Activate';
  document.getElementById('deactivateConfirmBtn').className = `btn ${isActive ? 'btn-danger' : 'btn-primary'}`;
  
  openModal('modalDeactivate');
}

async function confirmDeactivate() {
  const c = customers.find(x => x.id === deactivatingId);
  if (!c) return;
  
  const name = c.full_name;
  const newActive = c.active == 1 ? 0 : 1;

  try {
    const res = await fetch(`${API}?action=toggle`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: deactivatingId, active: newActive })
    });
    const data = await res.json();

    if (data.success) {
      closeModal('modalDeactivate');
      showToast(newActive ? 'success' : 'warning', `${name} has been ${newActive ? 'activated' : 'deactivated'}.`);
      await loadCustomers();
    } else {
      showToast('danger', 'Action failed.');
    }
  } catch (err) {
    console.error(err);
    showToast('danger', 'Connection error.');
  }
}

// ── HIGHLIGHT ROW ──
function highlightRow(id) {
  const row = document.getElementById(`row-${id}`);
  if (row) {
    row.classList.add('highlight');
    setTimeout(() => row.classList.remove('highlight'), 1500);
  }
}

// ── Toast ──
function showToast(type, message) {
  const wrap = document.getElementById('toastWrap');
  if (!wrap) return;
  
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = message;
  wrap.appendChild(toast);
  
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// ── EVENT LISTENERS ──
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('btnAddCustomer').addEventListener('click', openAdd);

  document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
  });

  document.getElementById('searchInput').addEventListener('input', () => { 
    currentPage = 1; 
    renderTable(); 
  });
  
  document.getElementById('filterType').addEventListener('change', () => { 
    currentPage = 1; 
    renderTable(); 
  });

  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
    }
  });

  loadCustomers();
});