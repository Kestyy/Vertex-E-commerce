<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Vertex Admin — Products</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --navy:        #0f172a;
      --border-dk:   rgba(255,255,255,0.07);
      --accent:      #3b82f6;
      --accent-dark: #2563eb;
      --accent-glow: rgba(59,130,246,0.15);
      --border:      #e2e8f0;
      --bg:          #f8fafc;
      --card:        #ffffff;
      --text:        #0f172a;
      --text-2:      #64748b;
      --text-muted:  #94a3b8;
      --sidebar-w:   240px;
      --danger:      #ef4444;
    }

    body { font-family: 'Poppins', sans-serif; background: #f1f5f9; color: var(--text); display: flex; min-height: 100vh; }

    /* ── Sidebar ── */
    .sidebar { width: var(--sidebar-w); background: var(--navy); min-height: 100vh; position: fixed; left: 0; top: 0; bottom: 0; display: flex; flex-direction: column; border-right: 1px solid var(--border-dk); z-index: 100; }
    .sidebar-logo { padding: 24px 20px 20px; border-bottom: 1px solid var(--border-dk); }
    .sidebar-logo .logo-name { font-size: 1.4rem; font-weight: 700; color: #f1f5f9; letter-spacing: 0.06em; text-transform: uppercase; }
    .sidebar-logo .logo-sub  { font-size: 0.68rem; color: #475569; letter-spacing: 0.14em; text-transform: uppercase; margin-top: 3px; }
    .sidebar-section { padding: 22px 12px 6px; }
    .sidebar-label { font-size: 0.70rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #475569; padding: 0 8px; margin-bottom: 9px; }
    .sidebar-nav { list-style: none; }
    .sidebar-nav li a { display: flex; align-items: center; gap: 11px; padding: 12px 13px; border-radius: 8px; text-decoration: none; color: #94a3b8; font-size: 0.95rem; transition: all 0.15s; font-weight: 500; }
    .sidebar-nav li a i { width: 17px; text-align: center; font-size: 0.88rem; flex-shrink: 0; }
    .sidebar-nav li a:hover  { background: rgba(255,255,255,0.05); color: #f1f5f9; }
    .sidebar-nav li a.active { background: var(--accent); color: #fff; font-weight: 500; }
    .sidebar-bottom { margin-top: auto; padding: 20px 16px; border-top: 1px solid var(--border-dk); }
    .sidebar-logout-btn { display: flex; align-items: center; justify-content: center; gap: 9px; padding: 12px; border-radius: 8px; text-decoration: none; color: #94a3b8; font-size: 0.88rem; font-weight: 500; border: 1px solid rgba(255,255,255,0.08); transition: all 0.15s; }
    .sidebar-logout-btn:hover { background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.3); color: #f87171; }

    /* ── Main layout ── */
    .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
    .topbar { background: var(--card); border-bottom: 1px solid var(--border); padding: 0 36px; height: 85px; display: flex; align-items: center; position: sticky; top: 0; z-index: 50; }
    .topbar-title { font-size: 1.4rem; font-weight: 700; color: var(--text); }
    .topbar-sub { font-size: 1rem; color: var(--text-muted); margin-top: 2px; font-weight: 500; }
    .topbar-left { display: flex; flex-direction: column; }
    .content { padding: 36px; flex: 1; }

    /* ── Buttons ── */
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; }
    .btn i { font-size: 0.82rem; }
    .btn-primary   { background: var(--accent); color: #fff; box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
    .btn-primary:hover { background: var(--accent-dark); transform: translateY(-1px); }
    .btn-secondary { background: var(--bg); color: var(--text-2); border: 1px solid var(--border); }
    .btn-secondary:hover { border-color: var(--accent); color: var(--accent); }
    .btn-danger    { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }
    .btn-danger:hover { background: #dc2626; color: #fff; }

    /* ── Cards ── */
    .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }

    /* ── Filters ── */
    .filter-top { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; padding: 18px 22px; }
    .filter-group { display: flex; flex-direction: column; gap: 6px; }
    .filter-label { font-size: 14px; font-weight: 600; color: var(--text-2); letter-spacing: 0.02em; }
    .filter-select { border: 1px solid var(--border); background: var(--card); border-radius: 8px; padding: 10px 32px 10px 14px; font-family: 'Poppins', sans-serif; font-size: 15px; color: var(--text-2); cursor: pointer; outline: none; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; width: 100%; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
    .filter-select:focus { border-color: var(--accent); }

    .filter-bottom { display: flex; align-items: center; justify-content: space-between; padding: 14px 22px; border-bottom: 1px solid var(--border); background: var(--bg); }
    .filter-search { display: flex; align-items: center; gap: 8px; background: var(--card); border: 1px solid var(--border); border-radius: 9px; padding: 10px 16px; width: 300px; }
    .filter-search input { border: none; outline: none; background: transparent; font-family: 'Poppins', sans-serif; font-size: 15px; color: var(--text); flex: 1; }
    .filter-search input::placeholder { color: var(--text-muted); }

    /* ── Table ── */
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    thead th { padding: 13px 18px; font-size: 14px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; text-align: left; background: var(--bg); border-bottom: 1px solid var(--border); white-space: nowrap; }
    tbody td { padding: 15px 18px; font-size: 16px; border-bottom: 1px solid var(--border); color: var(--text-2); }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover { background: #f8fafc; }

    .product-cell { display: flex; align-items: center; gap: 12px; }
    .product-img  { width: 48px; height: 48px; border-radius: 10px; background: var(--bg); border: 1px solid var(--border); object-fit: cover; flex-shrink: 0; }
    .product-name { font-weight: 600; font-size: 16px; color: var(--text); }

    .status-dot { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 500; padding: 6px 12px; border-radius: 8px; }
    .status-dot::before { content: ''; width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .status-dot.active::before   { background: #22c55e; }
    .status-dot.active           { color: #16a34a; background: #dcfce7; }
    .status-dot.inactive::before { background: #cbd5e1; }
    .status-dot.inactive         { color: #94a3b8; background: #f1f5f9; }

    .action-wrap { display: flex; align-items: center; gap: 8px; }
    .btn-edit { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.15s; background: var(--accent); color: #fff; border: none; }
    .btn-edit:hover { background: var(--accent-dark); }
    .btn-edit i { font-size: 0.75rem; }
    .btn-del { display: inline-flex; align-items: center; justify-content: center; width: 35px; height: 35px; border-radius: 8px; cursor: pointer; transition: all 0.15s; background: #f1f5f9; color: var(--text-muted); border: 1px solid var(--border); }
    .btn-del:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
    .btn-del i { font-size: 0.78rem; }

    .empty-state { text-align: center; padding: 48px 20px; color: var(--text-muted); font-size: 15px; }
    .empty-state i { font-size: 2rem; margin-bottom: 10px; display: block; }

    /* ── Modals ── */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: none; align-items: center; justify-content: center; z-index: 200; padding: 20px; }
    .modal-overlay.open { display: flex; }
    .modal { background: var(--card); border-radius: 16px; width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto; border: 1px solid var(--border); }
    .modal-sm { max-width: 420px; }
    .modal-header { padding: 24px 28px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .modal-title    { font-size: 18px; font-weight: 600; color: var(--text); }
    .modal-subtitle { font-size: 15px; color: var(--text-muted); margin-top: 2px; }
    .modal-close { width: 32px; height: 32px; border: 1px solid var(--border); background: transparent; border-radius: 7px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-muted); transition: all 0.15s; }
    .modal-close:hover { border-color: var(--danger); color: var(--danger); }
    .modal-close i { font-size: 0.78rem; }
    .modal-body { padding: 24px 28px; }
    .modal-footer { padding: 18px 28px 24px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: flex-end; gap: 10px; }

    /* ── Product form ── */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-field { display: flex; flex-direction: column; gap: 6px; }
    .form-field.full { grid-column: 1 / -1; }
    .form-label { font-size: 15px; font-weight: 500; color: var(--text-2); }
    .form-input { padding: 11px 14px; border: 1px solid var(--border); border-radius: 9px; font-family: 'Poppins', sans-serif; font-size: 15px; color: var(--text); background: var(--bg); outline: none; transition: border-color 0.2s; }
    .form-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
    textarea.form-input { resize: none; }
    
    /* Remove number input spinners */
    .form-input[type="number"]::-webkit-outer-spin-button,
    .form-input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

    .img-upload-box { width: 120px; height: 120px; min-width: 120px; min-height: 120px; max-width: 120px; max-height: 120px; border: 2px dashed var(--border); border-radius: 12px; background: var(--bg); position: relative; cursor: pointer; flex-shrink: 0; overflow: hidden; transition: border-color 0.2s; }
    .img-upload-box:hover { border-color: var(--accent); }
    .img-upload-box input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; z-index: 2; }
    .img-upload-placeholder { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; pointer-events: none; text-align: center; }
    .img-upload-placeholder i    { font-size: 1.2rem; color: var(--text-muted); }
    .img-upload-placeholder span { font-size: 12px; color: var(--text-muted); line-height: 1.3; }
    .img-upload-preview { position: absolute; inset: 0; display: none; }
    .img-upload-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .img-remove { position: absolute; top: 5px; right: 5px; width: 20px; height: 20px; border-radius: 50%; background: rgba(0,0,0,0.55); color: #fff; border: none; cursor: pointer; z-index: 3; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; transition: background 0.15s; }
    .img-remove:hover { background: var(--danger); }

    /* ── Delete modal ── */
    .delete-icon { width: 52px; height: 52px; border-radius: 14px; background: #fee2e2; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
    .delete-icon i { font-size: 1.3rem; color: #dc2626; }
    .modal-desc { text-align: center; font-size: 15px; color: var(--text-2); }

    /* ══════════════════════════════════════
       CATEGORY MODAL STYLES
    ══════════════════════════════════════ */
    .cat-modal { max-width: 540px; }

    /* Top split: form left, list right */
    .cat-modal-body { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

    /* Left: add form */
    .cat-form-side { display: flex; flex-direction: column; gap: 14px; }

    /* Image upload — square, bigger */
    .cat-img-upload { width: 100%; aspect-ratio: 1; border: 2px dashed var(--border); border-radius: 14px; background: var(--bg); position: relative; cursor: pointer; overflow: hidden; transition: border-color 0.2s; }
    .cat-img-upload:hover { border-color: var(--accent); }
    .cat-img-upload input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; z-index: 2; }
    .cat-img-placeholder { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; pointer-events: none; }
    .cat-img-placeholder i    { font-size: 1.6rem; color: var(--text-muted); }
    .cat-img-placeholder span { font-size: 13px; color: var(--text-muted); text-align: center; line-height: 1.4; }
    .cat-img-preview { position: absolute; inset: 0; display: none; }
    .cat-img-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .cat-img-remove { position: absolute; top: 6px; right: 6px; width: 22px; height: 22px; border-radius: 50%; background: rgba(0,0,0,0.55); color: #fff; border: none; cursor: pointer; z-index: 3; display: flex; align-items: center; justify-content: center; font-size: 0.62rem; transition: background 0.15s; }
    .cat-img-remove:hover { background: var(--danger); }

    /* Right: category list */
    .cat-list-side { display: flex; flex-direction: column; gap: 8px; }
    .cat-list-label { font-size: 13px; font-weight: 600; color: var(--text-2); letter-spacing: 0.03em; margin-bottom: 2px; }
    .cat-list-scroll { flex: 1; max-height: 280px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; padding-right: 2px; }
    .cat-list-scroll::-webkit-scrollbar { width: 4px; }
    .cat-list-scroll::-webkit-scrollbar-track { background: transparent; }
    .cat-list-scroll::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

    /* Individual category row in the list */
    .cat-list-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; transition: border-color 0.15s; }
    .cat-list-item:hover { border-color: #cbd5e1; }
    .cat-list-thumb { width: 36px; height: 36px; border-radius: 8px; object-fit: cover; background: #e2e8f0; flex-shrink: 0; border: 1px solid var(--border); }
    .cat-list-thumb-placeholder { width: 36px; height: 36px; border-radius: 8px; background: #e2e8f0; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .cat-list-thumb-placeholder i { font-size: 0.85rem; color: var(--text-muted); }
    .cat-list-name { flex: 1; font-size: 14px; font-weight: 500; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cat-list-del { width: 26px; height: 26px; border-radius: 7px; background: transparent; border: 1px solid transparent; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; transition: all 0.15s; flex-shrink: 0; }
    .cat-list-del:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

    .cat-list-empty { text-align: center; padding: 28px 10px; color: var(--text-muted); font-size: 14px; }
    .cat-list-empty i { font-size: 1.4rem; margin-bottom: 8px; display: block; }

    .cat-error { font-size: 13px; color: var(--danger); margin-top: 4px; display: none; }

    /* ── Toast ── */
    .toast-wrap { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); z-index: 999; display: flex; flex-direction: column; gap: 8px; pointer-events: none; }
    .toast { background: #1e293b; color: #f1f5f9; font-family: 'Poppins', sans-serif; font-size: 14px; padding: 10px 18px; border-radius: 10px; white-space: nowrap; opacity: 0; transform: translateY(8px); transition: all 0.25s; }
    .toast.show { opacity: 1; transform: translateY(0); }
    .toast.error { background: #7f1d1d; }
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-name">Vertex</div>
    <div class="logo-sub">Admin Panel</div>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Main Menu</div>
    <ul class="sidebar-nav">
      <li><a href="dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
      <li><a href="products.php" class="active"><i class="fas fa-box"></i> Products</a></li>
      <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a></li>
      <li><a href="customers.php"><i class="fas fa-users"></i> Customers</a></li>
      <li><a href="support.php"><i class="fas fa-envelope"></i> Support</a></li>
      <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
    </ul>
  </div>
  <div class="sidebar-bottom">
    <a href="logout.php" class="sidebar-logout-btn">
      <i class="fas fa-sign-out-alt"></i> Log Out
    </a>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-title">Products</div>
      <div class="topbar-sub">Manage your product catalog</div>
    </div>
  </div>

  <div class="content">

    <div class="card" style="margin-bottom:16px;">
      <div class="filter-top">
        <div class="filter-group">
          <span class="filter-label">Category</span>
          <select class="filter-select" id="filterCategory"><option value="">All Categories</option></select>
        </div>
        <div class="filter-group">
          <span class="filter-label">Status</span>
          <select class="filter-select" id="filterStatus">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="filter-group">
          <span class="filter-label">Stock</span>
          <select class="filter-select" id="filterStock">
            <option value="">All Stock</option>
            <option value="in">In Stock</option>
            <option value="low">Low Stock (<10)</option>
            <option value="out">Out of Stock</option>
          </select>
        </div>
        <div class="filter-group">
          <span class="filter-label">Price</span>
          <select class="filter-select" id="filterPrice">
            <option value="">All Prices</option>
            <option value="0-500">₱0 – ₱500</option>
            <option value="500-1000">₱500 – ₱1,000</option>
            <option value="1000-5000">₱1,000 – ₱5,000</option>
            <option value="5000+">₱5,000+</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="filter-bottom">
        <div class="filter-search">
          <i class="fas fa-search" style="color:var(--text-muted);font-size:0.72rem;"></i>
          <input type="text" id="filterInput" placeholder="Search products…"/>
        </div>
        <div id="filterActions" style="display:flex;align-items:center;gap:10px;"></div>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Product Name</th>
              <th>Price</th>
              <th>Category</th>
              <th>Stock</th>
              <th>Status</th>
              <th>Last Modified</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="productTableBody"></tbody>
        </table>
        <div id="emptyState" class="empty-state" style="display:none;">
          <i class="fas fa-box-open"></i>
          <p>No products found.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     CATEGORY MODAL
═══════════════════════════════════════ -->
<div class="modal-overlay" id="categoryModal">
  <div class="modal cat-modal">
    <div class="modal-header">
      <div>
        <div class="modal-title">Manage Categories</div>
        <div class="modal-subtitle">Add categories — they appear in Shop by Category on the storefront</div>
      </div>
      <button class="modal-close" id="btnCloseCategoryModal"><i class="fas fa-times"></i></button>
    </div>

    <div class="modal-body">
      <div class="cat-modal-body">

        <!-- LEFT: Add form -->
        <div class="cat-form-side">
          <div class="form-field">
            <label class="form-label">Category Name *</label>
            <input class="form-input" id="catNameInput" type="text" placeholder="e.g. Headphones" maxlength="50"/>
            <span class="cat-error" id="catNameError"></span>
          </div>

          <div class="form-field">
            <label class="form-label">Category Image *</label>
            <div class="cat-img-upload" id="catImgUploadWrap">
              <input type="file" id="catImageFile" accept="image/*"/>
              <div class="cat-img-placeholder" id="catImgPlaceholder">
                <i class="fas fa-cloud-upload-alt"></i>
                <span>Click to upload<br/>JPG, PNG, WEBP</span>
              </div>
              <div class="cat-img-preview" id="catImgPreviewWrap">
                <img id="catImgPreviewImg" src="" alt="Preview"/>
                <button type="button" class="cat-img-remove" id="btnRemoveCatImg">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
            <span class="cat-error" id="catImgError"></span>
          </div>

          <button class="btn btn-primary" id="btnSaveCategory" style="width:100%;justify-content:center;">
            <i class="fas fa-plus"></i> Add Category
          </button>
        </div>

        <!-- RIGHT: Category list -->
        <div class="cat-list-side">
          <div class="cat-list-label">ALL CATEGORIES (<span id="catCount">0</span>)</div>
          <div class="cat-list-scroll" id="catListScroll">
            <div class="cat-list-empty" id="catListEmpty">
              <i class="fas fa-tags"></i>
              <p>No categories yet.</p>
            </div>
          </div>
        </div>

      </div>
    </div>

    <div class="modal-footer">
      <button class="btn btn-secondary" id="btnDoneCategory">Done</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     PRODUCT MODAL
═══════════════════════════════════════ -->
<div class="modal-overlay" id="productModal">
  <div class="modal">
    <div class="modal-header">
      <div><div class="modal-title" id="modalTitle">Add Product</div></div>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <form id="productForm" enctype="multipart/form-data">

        <div style="display:flex;gap:16px;margin-bottom:14px;align-items:flex-start;">
          <div style="flex-shrink:0;width:120px;">
            <label class="form-label" style="display:block;margin-bottom:6px;">Image</label>
            <div class="img-upload-box" id="imageUploadWrap">
              <input type="file" id="productImageFile" accept="image/*"/>
              <div class="img-upload-placeholder" id="imagePlaceholder">
                <i class="fas fa-cloud-upload-alt"></i>
                <span>Click to upload</span>
              </div>
              <div class="img-upload-preview" id="imagePreviewWrap">
                <img id="imagePreviewImg" src="" alt="Preview"/>
                <button type="button" class="img-remove" id="btnRemoveImage">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
            <input type="hidden" id="productImageExisting"/>
          </div>

          <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:10px;">
            <div class="form-field">
              <label class="form-label">Product Name *</label>
              <input class="form-input" id="productName" type="text" required/>
            </div>
            <div class="form-field">
              <label class="form-label">Description</label>
              <textarea class="form-input" id="productDescription" style="height:150px;resize:none;"></textarea>
            </div>
          </div>
        </div>

          <div class="form-grid">
            <div class="form-field">
              <label class="form-label">Price (₱) *</label>
              <input class="form-input" id="productPrice" type="number" step="0.01" min="0" required/>
            </div>
            <div class="form-field">
              <label class="form-label">Category</label>
              <select class="form-input" id="productCategory">
                <option value="">— Select category —</option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label">Stock Quantity</label>
              <input class="form-input" id="productStock" type="number" min="0" value="0"/>
            </div>
            <div class="form-field">
              <label class="form-label">Status</label>
              <select class="form-input" id="productStatus">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>

        <p id="formError" style="color:var(--danger);font-size:12px;margin-top:10px;display:none;"></p>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" id="btnCancelProduct">Cancel</button>
      <button class="btn btn-primary" id="btnSaveProduct">
        <i class="fas fa-check"></i> Save Product
      </button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     DELETE PRODUCT MODAL
═══════════════════════════════════════ -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div>
        <div class="modal-title">Delete Product</div>
        <div class="modal-subtitle">This action cannot be undone.</div>
      </div>
      <button class="modal-close" onclick="document.getElementById('deleteModal').classList.remove('open')">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="modal-body">
      <div class="delete-icon"><i class="fas fa-trash"></i></div>
      <p class="modal-desc">Are you sure you want to delete "<span id="deleteProductName"></span>"?</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" id="btnCancelDelete">Cancel</button>
      <button class="btn btn-danger" id="btnConfirmDelete">
        <i class="fas fa-trash"></i> Delete
      </button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     DELETE CATEGORY CONFIRM MODAL
═══════════════════════════════════════ -->
<div class="modal-overlay" id="deleteCatModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div>
        <div class="modal-title">Delete Category</div>
        <div class="modal-subtitle">This action cannot be undone.</div>
      </div>
      <button class="modal-close" id="btnCloseDeleteCat"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="delete-icon"><i class="fas fa-tags"></i></div>
      <p class="modal-desc">Delete "<span id="deleteCatName"></span>"? Products assigned to it will become uncategorised.</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" id="btnCancelDeleteCat">Cancel</button>
      <button class="btn btn-danger" id="btnConfirmDeleteCat">
        <i class="fas fa-trash"></i> Delete
      </button>
    </div>
  </div>
</div>

<!-- Toast container -->
<div class="toast-wrap" id="toastWrap"></div>

<script src="assets/js/categories.js"></script>
<script src="assets/js/products.js"></script>
</body>
</html>