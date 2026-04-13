// ═══════════════════════════════════════════════════════════════
// admin/assets/js/categories.js
// Handles: Add Category modal, image upload, save/delete via API
// ═══════════════════════════════════════════════════════════════

(function () {
  'use strict';

  // ✅ FIXED: Base API path for admin directory
  const API_BASE = 'assets/php/';

  // ── State ────────────────────────────────────────────────────
  let catImageFile = null;
  let pendingDelId = null;

  // ── DOM refs ─────────────────────────────────────────────────
  let modal, overlay, btnOpen, btnClose, btnDone;
  let nameInput, nameError, imgError;
  let fileInput, imgPlaceholder, imgPreviewWrap, imgPreviewImg, btnRemoveImg;
  let btnSave;
  let listScroll, listEmpty, catCount;
  let delModal, delCatNameSpan, btnCancelDel, btnConfirmDel, btnCloseDel;
  let filterCategorySelect, productCategorySelect;

  // ── Init ─────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    modal = document.getElementById('categoryModal');
    btnOpen = document.getElementById('btnOpenCategoryModal');
    btnClose = document.getElementById('btnCloseCategoryModal');
    btnDone = document.getElementById('btnDoneCategory');
    nameInput = document.getElementById('catNameInput');
    nameError = document.getElementById('catNameError');
    imgError = document.getElementById('catImgError');
    fileInput = document.getElementById('catImageFile');
    imgPlaceholder = document.getElementById('catImgPlaceholder');
    imgPreviewWrap = document.getElementById('catImgPreviewWrap');
    imgPreviewImg = document.getElementById('catImgPreviewImg');
    btnRemoveImg = document.getElementById('btnRemoveCatImg');
    btnSave = document.getElementById('btnSaveCategory');
    listScroll = document.getElementById('catListScroll');
    listEmpty = document.getElementById('catListEmpty');
    catCount = document.getElementById('catCount');
    delModal = document.getElementById('deleteCatModal');
    delCatNameSpan = document.getElementById('deleteCatName');
    btnCancelDel = document.getElementById('btnCancelDeleteCat');
    btnConfirmDel = document.getElementById('btnConfirmDeleteCat');
    btnCloseDel = document.getElementById('btnCloseDeleteCat');
    filterCategorySelect = document.getElementById('filterCategory');
    productCategorySelect = document.getElementById('productCategory');

    if (!modal) return;

    bindEvents();
    loadCategoryDropdowns();
  });

  // ── Event bindings ────────────────────────────────────────────
  function bindEvents() {
    if (btnOpen) btnOpen.addEventListener('click', openModal);
    btnClose.addEventListener('click', closeModal);
    btnDone.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    fileInput.addEventListener('change', onFileSelected);
    btnRemoveImg.addEventListener('click', clearImage);
    btnSave.addEventListener('click', saveCategory);
    nameInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') saveCategory(); });
    nameInput.addEventListener('input', () => hideError(nameError));

    btnCancelDel.addEventListener('click', closeDelModal);
    btnCloseDel.addEventListener('click', closeDelModal);
    btnConfirmDel.addEventListener('click', confirmDelete);
    delModal.addEventListener('click', (e) => { if (e.target === delModal) closeDelModal(); });
  }

  // ── Modal controls ────────────────────────────────────────────
  function openModal() {
    modal.classList.add('open');
    resetForm();
    loadCategoryList();
  }
  function closeModal() {
    modal.classList.remove('open');
    resetForm();
  }
  function resetForm() {
    nameInput.value = '';
    hideError(nameError);
    hideError(imgError);
    clearImage();
  }

  // ── Image handling ────────────────────────────────────────────
  function onFileSelected() {
    const file = fileInput.files[0];
    if (!file) return;

    const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!allowed.includes(file.type)) {
      showError(imgError, 'Only JPG, PNG, WEBP or GIF allowed.');
      fileInput.value = '';
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      showError(imgError, 'Image must be under 5 MB.');
      fileInput.value = '';
      return;
    }

    hideError(imgError);
    catImageFile = file;

    const reader = new FileReader();
    reader.onload = (e) => {
      imgPreviewImg.src = e.target.result;
      imgPlaceholder.style.display = 'none';
      imgPreviewWrap.style.display = 'block';
    };
    reader.readAsDataURL(file);
  }

  function clearImage() {
    catImageFile = null;
    fileInput.value = '';
    imgPreviewImg.src = '';
    imgPlaceholder.style.display = 'flex';
    imgPreviewWrap.style.display = 'none';
  }

  // ── Save category ─────────────────────────────────────────────
  async function saveCategory() {
    const name = nameInput.value.trim();
    let valid = true;

    if (!name) { showError(nameError, 'Category name is required.'); valid = false; }
    if (!catImageFile) { showError(imgError, 'Please upload a category image.'); valid = false; }
    if (!valid) return;

    btnSave.disabled = true;
    btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    try {
      const fd = new FormData();
      fd.append('action', 'add');
      fd.append('name', name);
      fd.append('image', catImageFile);

      // ✅ FIXED: Use API_BASE
      const res = await fetch(API_BASE + 'categories.php', { method: 'POST', body: fd });
      const data = await res.json();

      if (data.success) {
        resetForm();
        loadCategoryList();
        loadCategoryDropdowns();
        showToast('"' + name + '" added successfully');
      } else {
        showError(nameError, data.message || 'Failed to save category.');
      }
    } catch (err) {
      showError(nameError, 'Network error. Please try again.');
      console.error(err);
    } finally {
      btnSave.disabled = false;
      btnSave.innerHTML = '<i class="fas fa-plus"></i> Add Category';
    }
  }

  // ── Load category list ────────────────────────────────────────
  async function loadCategoryList() {
    try {
      // ✅ FIXED: Use API_BASE
      const res = await fetch(API_BASE + 'categories.php?action=list');
      const data = await res.json();
      if (!data.success) return;
      renderCategoryList(data.categories);
    } catch (err) {
      console.error('Failed to load categories:', err);
    }
  }

  function renderCategoryList(categories) {
    catCount.textContent = categories.length;
    const existing = listScroll.querySelectorAll('.cat-list-item');
    existing.forEach(el => el.remove());

    if (!categories.length) {
      listEmpty.style.display = 'block';
      return;
    }
    listEmpty.style.display = 'none';

    categories.forEach(cat => {
      const item = document.createElement('div');
      item.className = 'cat-list-item';
      item.dataset.id = cat.id;

      const thumbHTML = cat.image
        ? `<img class="cat-list-thumb" src="${cat.image}" alt="${escHtml(cat.name)}"/>`
        : `<div class="cat-list-thumb-placeholder"><i class="fas fa-image"></i></div>`;

      item.innerHTML = `
        ${thumbHTML}
        <span class="cat-list-name">${escHtml(cat.name)}</span>
        <button class="cat-list-del" data-id="${cat.id}" data-name="${escHtml(cat.name)}" title="Delete category">
          <i class="fas fa-trash-alt"></i>
        </button>
      `;

      item.querySelector('.cat-list-del').addEventListener('click', openDelModal);
      listScroll.appendChild(item);
    });
  }

  // ── Load categories into dropdowns ────────────────────────────
  async function loadCategoryDropdowns() {
    try {
      // ✅ FIXED: Use API_BASE
      const res = await fetch(API_BASE + 'categories.php?action=list');
      const data = await res.json();
      if (!data.success) return;

      syncDropdown(filterCategorySelect, data.categories, 'All Categories', false);
      syncDropdown(productCategorySelect, data.categories, '— Select category —', true);
    } catch (err) {
      console.error('Failed to load category dropdowns:', err);
    }
  }

  function syncDropdown(select, categories, placeholder, includeEmpty) {
    if (!select) return;
    const current = select.value;
    select.innerHTML = `<option value="">${placeholder}</option>`;
    categories.forEach(cat => {
      const opt = document.createElement('option');
      opt.value = cat.id;
      opt.textContent = cat.name;
      if (String(cat.id) === String(current)) opt.selected = true;
      select.appendChild(opt);
    });
  }

  // ── Delete flow ───────────────────────────────────────────────
  function openDelModal(e) {
    const btn = e.currentTarget;
    pendingDelId = btn.dataset.id;
    delCatNameSpan.textContent = btn.dataset.name;
    delModal.classList.add('open');
  }
  function closeDelModal() {
    delModal.classList.remove('open');
    pendingDelId = null;
  }

  async function confirmDelete() {
    if (!pendingDelId) return;

    btnConfirmDel.disabled = true;
    btnConfirmDel.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';

    try {
      const fd = new FormData();
      fd.append('action', 'delete');
      fd.append('id', pendingDelId);

      // ✅ FIXED: Use API_BASE
      const res = await fetch(API_BASE + 'categories.php', { method: 'POST', body: fd });
      const data = await res.json();

      if (data.success) {
        closeDelModal();
        loadCategoryList();
        loadCategoryDropdowns();
        showToast('Category deleted.');
      } else {
        showToast(data.message || 'Failed to delete.', true);
        closeDelModal();
      }
    } catch (err) {
      showToast('Network error.', true);
      closeDelModal();
      console.error(err);
    } finally {
      btnConfirmDel.disabled = false;
      btnConfirmDel.innerHTML = '<i class="fas fa-trash"></i> Delete';
    }
  }

  // ── Expose for other scripts ──────────────────────────────────
  window.refreshCategoryDropdowns = loadCategoryDropdowns;
  window.loadCategoryList = loadCategoryList;

  // ── Helpers ───────────────────────────────────────────────────
  function showError(el, msg) { el.textContent = msg; el.style.display = 'block'; }
  function hideError(el) { el.textContent = ''; el.style.display = 'none'; }
  function escHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function showToast(msg, isError = false) {
    const wrap = document.getElementById('toastWrap');
    const toast = document.createElement('div');
    toast.className = 'toast' + (isError ? ' error' : '');
    toast.textContent = msg;
    wrap.appendChild(toast);
    requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 300);
    }, 2800);
  }
})();