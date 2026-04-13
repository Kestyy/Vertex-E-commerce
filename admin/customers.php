<?php
require_once 'auth_check.php';
require_once '../assets/php/db.php';

// Fetch stats
$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(spent) as total_revenue,
        SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active_count
    FROM users 
    WHERE role = 'customer'
"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Vertex Admin — Customers</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --navy:        #0f172a;
      --navy-2:      #1e293b;
      --border-dk:   rgba(255,255,255,0.07);
      --accent:      #3b82f6;
      --accent-dark: #2563eb;
      --accent-glow: rgba(59,130,246,0.15);
      --red:         #ef4444;
      --border:      #e2e8f0;
      --bg:          #f8fafc;
      --card:        #ffffff;
      --text:        #0f172a;
      --text-2:      #64748b;
      --text-muted:  #94a3b8;
      --sidebar-w:   240px;
      --danger:      #ef4444;
      --success:     #22c55e;
      --warning:     #f59e0b;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: #f1f5f9;
      color: var(--text);
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: var(--sidebar-w);
      background: var(--navy);
      min-height: 100vh;
      position: fixed;
      left: 0; top: 0; bottom: 0;
      display: flex;
      flex-direction: column;
      border-right: 1px solid var(--border-dk);
      z-index: 100;
    }

    .sidebar-logo {
      padding: 24px 20px 20px;
      border-bottom: 1px solid var(--border-dk);
    }

    .sidebar-logo .logo-name {
      font-size: 1.4rem;
      font-weight: 700;
      color: #f1f5f9;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .sidebar-logo .logo-sub {
      font-size: 0.68rem;
      color: #475569;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      margin-top: 3px;
    }

    .sidebar-section { padding: 22px 12px 6px; }

    .sidebar-label {
      font-size: 0.70rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #475569;
      padding: 0 8px;
      margin-bottom: 9px;
    }

    .sidebar-nav { list-style: none; }

    .sidebar-nav li a {
      display: flex;
      align-items: center;
      gap: 11px;
      padding: 12px 13px;
      border-radius: 8px;
      text-decoration: none;
      color: #94a3b8;
      font-size: 0.95rem;
      transition: all 0.15s;
      font-weight: 500;
    }

    .sidebar-nav li a i {
      width: 17px;
      text-align: center;
      font-size: 0.88rem;
      flex-shrink: 0;
    }

    .sidebar-nav li a:hover {
      background: rgba(255,255,255,0.05);
      color: #f1f5f9;
    }

    .sidebar-nav li a.active {
      background: var(--accent);
      color: #fff;
      font-weight: 500;
    }

    .sidebar-bottom {
      margin-top: auto;
      padding: 20px 16px;
      border-top: 1px solid var(--border-dk);
    }

    .sidebar-logout-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      padding: 12px;
      border-radius: 8px;
      text-decoration: none;
      color: #94a3b8;
      font-size: 0.88rem;
      font-weight: 500;
      border: 1px solid rgba(255,255,255,0.08);
      transition: all 0.15s;
    }

    .sidebar-logout-btn:hover {
      background: rgba(239,68,68,0.12);
      border-color: rgba(239,68,68,0.3);
      color: #f87171;
    }

    .main {
      margin-left: var(--sidebar-w);
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .topbar {
      background: var(--card);
      border-bottom: 1px solid var(--border);
      padding: 0 36px;
      height: 85px;
      display: flex;
      align-items: center;
      gap: 16px;
      position: sticky;
      top: 0;
      z-index: 50;
    }

    .topbar-title { font-size: 1.4rem; font-weight: 700; color: var(--text); }
    .topbar-sub { font-size: 1rem; color: var(--text-muted); margin-top: 2px; font-weight: 500; }
    .topbar-left { display: flex; flex-direction: column; }

    .content { padding: 36px; flex: 1; }

    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
    }

    .page-header h1 { font-size: 1.2rem; font-weight: 700; }
    .page-header p  { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 9px 18px;
      border-radius: 10px;
      font-family: 'Poppins', sans-serif;
      font-size: 12.5px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
      text-decoration: none;
    }

    .btn i { font-size: 0.75rem; }
    .btn-primary { background: var(--accent); color: #fff; box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
    .btn-primary:hover { background: var(--accent-dark); transform: translateY(-1px); }
    .btn-outline { background: transparent; color: var(--text-2); border: 1px solid var(--border); }
    .btn-outline:hover { border-color: var(--accent); color: var(--accent); }
    .btn-danger { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }
    .btn-danger:hover { background: #dc2626; color: #fff; }

    .summary-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-top: 24px;
      margin-bottom: 24px;
    }

    .summary-mini {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 28px;
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .s-icon {
      width: 58px; height: 58px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }

    .s-icon i { font-size: 1.4rem; }
    .s-val { font-size: 1.8rem; font-weight: 700; color: var(--text); }
    .s-lbl { font-size: 0.95rem; color: var(--text-muted); }

    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
    }

    .filter-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 14px 20px;
      border-bottom: 1px solid var(--border);
      margin-bottom: 20px;
    }

    .filter-search {
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 9px;
      padding: 8px 12px;
      flex: 1;
      max-width: 280px;
    }

    .filter-search input {
      border: none; outline: none; background: transparent;
      font-family: 'Poppins', sans-serif;
      font-size: 15px; color: var(--text); flex: 1;
    }

    .filter-search input::placeholder { color: var(--text-muted); }

    .filter-select {
      border: 1px solid var(--border);
      background: var(--bg);
      border-radius: 9px;
      padding: 8px 12px;
      font-family: 'Poppins', sans-serif;
      font-size: 15px;
      color: var(--text-2);
      cursor: pointer;
      outline: none;
    }

    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }

    thead th {
      padding: 12px 16px;
      font-size: 14px;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      text-align: center;
      background: var(--bg);
      border-bottom: 1px solid var(--border);
    }

    thead th:nth-child(2),
    thead th:nth-child(3) {
      text-align: left;
    }

    tbody td {
      padding: 13px 16px;
      font-size: 16px;
      border-bottom: 1px solid var(--border);
      color: var(--text-2);
      text-align: center;
    }

    tbody td:nth-child(2),
    tbody td:nth-child(3) {
      text-align: left;
    }

    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover { background: #f8fafc; }
    tbody tr.highlight { background: #fef9c3; transition: background 1.5s ease; }

    .badge {
      display: inline-flex;
      align-items: center;
      padding: 3px 10px;
      border-radius: 99px;
      font-size: 14px;
      font-weight: 600;
    }

    .badge-success { background: #dcfce7; color: #16a34a; }
    .badge-warning  { background: #fef9c3; color: #ca8a04; }
    .badge-danger   { background: #fee2e2; color: #dc2626; }
    .badge-info     { background: #dbeafe; color: #2563eb; }
    .badge-gray     { background: #f1f5f9; color: #64748b; }

    .action-btn {
      width: 28px; height: 28px;
      border: 1px solid var(--border);
      background: transparent;
      border-radius: 7px;
      display: inline-flex; align-items: center; justify-content: center;
      cursor: pointer;
      color: var(--text-muted);
      transition: all 0.15s;
    }

    .action-btn i { font-size: 0.72rem; }
    .action-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-glow); }
    .action-btn.danger:hover { border-color: var(--danger); color: var(--danger); background: #fee2e2; }

    .avatar-sm {
      width: 30px; height: 30px;
      border-radius: 50%;
      background: var(--accent-glow);
      border: 2px solid var(--accent);
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 700;
      color: var(--accent);
      flex-shrink: 0;
      overflow: hidden;
      object-fit: cover;
    }

    .customer-cell { display: flex; align-items: center; gap: 10px; }
    .customer-name { font-weight: 500; font-size: 13px; color: var(--text); }
    .customer-email { font-size: 11px; color: var(--text-muted); }

    .empty-state {
      text-align: center;
      padding: 48px 20px;
      color: var(--text-muted);
      font-size: 13px;
    }

    .empty-state i { font-size: 2rem; margin-bottom: 10px; display: block; }

    .pagination {
      display: flex;
      align-items: center;
      gap: 4px;
      padding: 16px 20px;
      border-top: 1px solid var(--border);
      justify-content: flex-end;
    }

    .page-btn {
      min-width: 32px; height: 32px;
      border: 1px solid var(--border);
      background: var(--card);
      border-radius: 7px;
      display: inline-flex; align-items: center; justify-content: center;
      cursor: pointer;
      font-size: 12px;
      transition: all 0.15s;
    }

    .page-btn:hover { border-color: var(--accent); background: var(--accent-glow); }
    .page-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }

    .modal-overlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.4);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 200;
      padding: 20px;
    }

    .modal-overlay.open { display: flex; }

    .modal {
      background: var(--card);
      border-radius: 16px;
      width: 100%;
      max-width: 560px;
      max-height: 90vh;
      overflow-y: auto;
      border: 1px solid var(--border);
    }

    .modal-sm { max-width: 420px; }

    .modal-header {
      padding: 20px 24px 16px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .modal-title    { font-size: 15px; font-weight: 600; color: var(--text); }
    .modal-subtitle { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

    .modal-close {
      width: 28px; height: 28px;
      border: 1px solid var(--border);
      background: transparent;
      border-radius: 7px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      color: var(--text-muted);
      transition: all 0.15s;
    }

    .modal-close:hover { border-color: var(--danger); color: var(--danger); }
    .modal-close i { font-size: 0.72rem; }

    .modal-body { padding: 20px 24px; }
    .modal-body p { font-size: 13px; color: var(--text-2); line-height: 1.6; }

    .modal-footer {
      padding: 16px 24px 20px;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 8px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .form-field { display: flex; flex-direction: column; gap: 5px; }
    .form-field.full { grid-column: 1 / -1; }
    .form-label { font-size: 11.5px; font-weight: 500; color: var(--text-2); }

    .form-input {
      padding: 9px 12px;
      border: 1px solid var(--border);
      border-radius: 9px;
      font-family: 'Poppins', sans-serif;
      font-size: 12.5px;
      color: var(--text);
      background: var(--bg);
      outline: none;
      transition: border-color 0.2s;
    }

    .form-input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--accent-glow);
    }

    .view-avatar {
      width: 64px; height: 64px;
      border-radius: 50%;
      background: var(--accent);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem; font-weight: 700; color: #fff;
      margin: 0 auto 12px;
      overflow: hidden;
      object-fit: cover;
    }

    .view-name {
      text-align: center;
      font-size: 1rem;
      font-weight: 600;
      color: var(--text);
      margin-bottom: 8px;
    }

    .view-badge-wrap { text-align: center; margin-bottom: 20px; }

    .view-stats {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 20px;
    }

    .view-stat {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 12px;
      text-align: center;
    }

    .view-stat-val { font-size: 1.1rem; font-weight: 700; color: var(--text); }
    .view-stat-lbl { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

    .view-detail-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 0;
      border-bottom: 1px solid var(--border);
      font-size: 13px;
    }

    .view-detail-row:last-child { border-bottom: none; }
    .view-detail-row i { color: var(--text-muted); font-size: 0.85rem; width: 16px; text-align: center; }
    .view-detail-label { color: var(--text-muted); min-width: 60px; }
    .view-detail-val { color: var(--text); font-weight: 500; margin-left: auto; }

    .delete-modal { max-width: 400px; }

    .delete-icon {
      width: 52px; height: 52px;
      border-radius: 14px;
      background: #fee2e2;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 16px;
    }

    .delete-icon i { font-size: 1.3rem; color: #dc2626; }
    .modal-desc { text-align: center; font-size: 13px; color: var(--text-2); }

    .toast-wrap {
      position: fixed;
      bottom: 24px; right: 24px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      z-index: 999;
    }

    .toast {
      padding: 12px 18px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 500;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      animation: slideIn 0.3s ease;
    }

    .toast.success { background: #dcfce7; color: #16a34a; border: 1px solid #86efac; }
    .toast.danger  { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }
    .toast.warning { background: #fef9c3; color: #ca8a04; border: 1px solid #fde047; }

    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to   { transform: translateX(0); opacity: 1; }
    }

    .flex-center { display: flex; align-items: center; }
    .gap-8 { gap: 8px; }
    .gap-6 { gap: 6px; }
    .fw-500 { font-weight: 500; }
    .fw-600 { font-weight: 600; }
    .text-muted { color: var(--text-muted); }
    .cursor-pointer { cursor: pointer; }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-name">Vertex</div>
    <div class="logo-sub">Admin Panel</div>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-label">Main Menu</div>
    <ul class="sidebar-nav">
      <li><a href="dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
      <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
      <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a></li>
      <li><a href="customers.php" class="active"><i class="fas fa-users"></i> Customers</a></li>
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

<!-- MAIN -->
<div class="main">

  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-title">Customers</div>
      <div class="topbar-sub">Manage your customer base</div>
    </div>
  </div>

  <div class="content">

    <div class="summary-row">
      <div class="summary-mini">
        <div class="s-icon" style="background:#dbeafe;">
          <i class="fas fa-users" style="color:#2563eb;"></i>
        </div>
        <div><div class="s-val" id="statTotal"><?php echo (int)$stats['total']; ?></div><div class="s-lbl">Total Customers</div></div>
      </div>
      <div class="summary-mini">
        <div class="s-icon" style="background:#dcfce7;">
          <i class="fas fa-peso-sign" style="color:#16a34a;"></i>
        </div>
        <div><div class="s-val" id="statRevenue">₱<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div><div class="s-lbl">Total Spent</div></div>
      </div>
      <div class="summary-mini">
        <div class="s-icon" style="background:#faf5ff;">
          <i class="fas fa-user-check" style="color:#7c3aed;"></i>
        </div>
        <div><div class="s-val" id="statActive"><?php echo (int)$stats['active_count']; ?></div><div class="s-lbl">Active Customers</div></div>
      </div>
    </div>

    <div class="card">
      <div class="filter-bar">
        <div class="filter-search">
          <i class="fas fa-search" style="color:var(--text-muted);font-size:0.72rem;"></i>
          <input type="text" id="searchInput" placeholder="Search by name, email or phone…"/>
        </div>
        <button class="btn btn-primary" id="btnAddCustomer" style="margin-left:auto;">
          <i class="fas fa-plus"></i> Add Customer
        </button>
        <select class="filter-select" id="filterType">
          <option value="">All Types</option>
          <option value="New">New</option>
          <option value="First-time buyers">First-time buyers</option>
          <option value="Regular">Regular</option>
          <option value="Loyal">Loyal</option>
          <option value="Bulk">Bulk</option>
          <option value="Inactive">Inactive</option>
          <option value="At-Risk">At-Risk</option>
        </select>
        <span style="font-size:12px;color:var(--text-muted);margin-left:auto;" id="resultCount">Loading…</span>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Orders</th>
              <th>Total Spent</th>
              <th>Type</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="customerTableBody">
            <tr><td colspan="9" class="empty-state"><i class="fas fa-spinner fa-spin"></i> Loading customers…</td></tr>
          </tbody>
        </table>
        <div id="emptyState" class="empty-state" style="display:none;">
          <i class="fas fa-users"></i>
          <p>No customers found.</p>
        </div>
      </div>

      <div class="pagination" id="paginationWrap">
        <span style="font-size:12px;margin-right:auto;" class="text-muted" id="paginationInfo"></span>
      </div>
    </div>

  </div>
</div>

<!-- ADD / EDIT MODAL -->
<div class="modal-overlay" id="modalForm">
  <div class="modal">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="formModalTitle">Add Customer</div>
        <div class="modal-subtitle" id="formModalSubtitle">Fill in the customer details</div>
      </div>
      <button class="modal-close" onclick="closeModal('modalForm')">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-field">
          <label class="form-label">First Name *</label>
          <input class="form-input" id="fieldFirstName" type="text" placeholder="Juan"/>
        </div>
        <div class="form-field">
          <label class="form-label">Last Name *</label>
          <input class="form-input" id="fieldLastName" type="text" placeholder="Dela Cruz"/>
        </div>
        <div class="form-field full">
          <label class="form-label">Email Address *</label>
          <input class="form-input" id="fieldEmail" type="email" placeholder="juan@email.com"/>
        </div>
        <div class="form-field full">
          <label class="form-label">Phone Number</label>
          <input class="form-input" id="fieldPhone" type="tel" placeholder="+63 912 345 6789"/>
        </div>
        <div class="form-field">
          <label class="form-label">Total Orders</label>
          <input class="form-input" id="fieldOrders" type="number" placeholder="0" min="0"/>
        </div>
        <div class="form-field">
          <label class="form-label">Total Spent (₱)</label>
          <input class="form-input" id="fieldSpent" type="number" placeholder="0" min="0" step="0.01"/>
        </div>
        <div class="form-field">
          <label class="form-label">Customer Type</label>
          <select class="form-input" id="fieldType">
            <option value="New">New</option>
            <option value="First-time buyers">First-time buyers</option>
            <option value="Regular">Regular</option>
            <option value="Loyal">Loyal</option>
            <option value="Bulk">Bulk</option>
            <option value="Inactive">Inactive</option>
            <option value="At-Risk">At-Risk</option>
          </select>
        </div>
        <div class="form-field">
          <label class="form-label">Status</label>
          <select class="form-input" id="fieldActive">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
      </div>
      <p id="formError" style="color:var(--danger);font-size:12px;margin-top:10px;display:none;"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalForm')">Cancel</button>
      <button class="btn btn-primary" id="btnFormSave" onclick="saveCustomer()">
        <i class="fas fa-check"></i> Save Customer
      </button>
    </div>
  </div>
</div>

<!-- VIEW MODAL -->
<div class="modal-overlay" id="modalView">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div>
        <div class="modal-title">Customer Profile</div>
        <div class="modal-subtitle">Full customer details</div>
      </div>
      <button class="modal-close" onclick="closeModal('modalView')">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="modal-body">
      <div class="view-avatar" id="viewAvatar">—</div>
      <div class="view-name" id="viewName">—</div>
      <div class="view-badge-wrap"><span class="badge" id="viewBadge">—</span></div>
      <div class="view-stats">
        <div class="view-stat">
          <div class="view-stat-val" id="viewOrders">—</div>
          <div class="view-stat-lbl">Total Orders</div>
        </div>
        <div class="view-stat">
          <div class="view-stat-val" id="viewSpent">—</div>
          <div class="view-stat-lbl">Total Spent</div>
        </div>
      </div>
      <div class="view-detail-row">
        <i class="fas fa-envelope"></i>
        <span class="view-detail-label">Email</span>
        <span class="view-detail-val" id="viewEmail">—</span>
      </div>
      <div class="view-detail-row">
        <i class="fas fa-phone"></i>
        <span class="view-detail-label">Phone</span>
        <span class="view-detail-val" id="viewPhone">—</span>
      </div>
      <div class="view-detail-row">
        <i class="fas fa-calendar"></i>
        <span class="view-detail-label">Joined</span>
        <span class="view-detail-val" id="viewJoined">—</span>
      </div>
      <div class="view-detail-row">
        <i class="fas fa-check-circle"></i>
        <span class="view-detail-label">Status</span>
        <span class="view-detail-val" id="viewStatus">—</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalView')">Close</button>
      <button class="btn btn-primary" onclick="editFromView()">
        <i class="fas fa-pen"></i> Edit Customer
      </button>
    </div>
  </div>
</div>

<!-- DEACTIVATE MODAL -->
<div class="modal-overlay" id="modalDeactivate">
  <div class="modal delete-modal">
    <div class="modal-body" style="text-align:center;padding:28px 24px;">
      <div class="delete-icon" id="deactivateIcon">
        <i class="fas fa-ban"></i>
      </div>
      <div class="modal-title" id="deactivateModalTitle" style="margin-bottom:8px;">Deactivate Customer</div>
      <p class="modal-desc" id="deactivateModalDesc">Are you sure you want to deactivate <strong id="deactivateCustomerName"></strong>?</p>
    </div>
    <div class="modal-footer" style="justify-content:center;gap:12px;">
      <button class="btn btn-outline" style="min-width:100px;" onclick="closeModal('modalDeactivate')">Cancel</button>
      <button class="btn btn-danger" id="deactivateConfirmBtn" style="min-width:100px;" onclick="confirmDeactivate()">Deactivate</button>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="toast-wrap" id="toastWrap"></div>

<script>
const API = 'assets/php/customers_api.php';

let customers     = [];
let editingId     = null;
let deactivatingId = null;
let viewingId     = null;
let currentPage   = 1;
const perPage     = 8;

// Helpers
function getInitials(fullName) {
  if (!fullName) return '?';
  const parts = fullName.trim().split(' ').filter(p => p);
  if (parts.length === 0) return '?';
  return (parts[0]?.[0] || '?') + (parts[1]?.[0] || '').toUpperCase();
}

function formatMoney(n) {
  return '₱' + Number(n || 0).toLocaleString('en-PH', {minimumFractionDigits: 2});
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return '—';
  const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return `${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
}

function badgeClass(type) {
  switch(type) {
    case 'New': return 'badge-gray';
    case 'First-time buyers': return 'badge-gray';
    case 'Regular': return 'badge-info';
    case 'Loyal': return 'badge-success';
    case 'Bulk': return 'badge-warning';
    case 'At-Risk': return 'badge-danger';
    case 'Inactive': return 'badge-gray';
    default: return 'badge-gray';
  }
}

// Load customers
async function loadCustomers() {
  try {
    const res = await fetch(`${API}?action=list`);
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

// Filter
function getFiltered() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  const typ = document.getElementById('filterType').value;
  return customers.filter(c => {
    const matchQ = !q || (c.full_name || '').toLowerCase().includes(q) || 
                   (c.email || '').toLowerCase().includes(q) || 
                   (c.phone || '').includes(q);
    const matchT = !typ || c.type === typ;
    return matchQ && matchT;
  });
}

// Render table
function renderTable() {
  const filtered = getFiltered();
  const start = (currentPage - 1) * perPage;
  const paged = filtered.slice(start, start + perPage);
  const tbody = document.getElementById('customerTableBody');
  const empty = document.getElementById('emptyState');

  if (filtered.length === 0) {
    tbody.innerHTML = '';
    empty.style.display = 'block';
  } else {
    empty.style.display = 'none';
    tbody.innerHTML = paged.map(c => {
      const customerId = `#C-${String(1000 + (c.id || 0)).padStart(4, '0')}`;
      return `
      <tr id="row-${c.id}" ${!c.active ? 'style="opacity:0.5"' : ''}>
        <td>
          <span class="text-muted cursor-pointer" 
            style="font-family:'Poppins', sans-serif;font-size:15px;font-weight:600;"
            onclick="navigator.clipboard.writeText('${customerId}'); showToast('success', 'ID copied!')"
            title="Click to copy">
            ${customerId}
          </span>
        </td>
        <td>
          <div class="flex-center gap-8">
            ${c.avatar ? 
              `<img src="../images/avatars/${c.avatar}" alt="${c.full_name}" 
                class="avatar-sm" 
                style="width:30px;height:30px;border-radius:50%;object-fit:cover;border:2px solid var(--accent);flex-shrink:0;" 
                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"/>` 
              : ''}
            <div class="avatar-sm" style="${c.avatar ? 'display:none;' : ''}">${getInitials(c.full_name)}</div>
            <div>
              <div class="fw-500">${c.full_name || '—'}</div>
              ${!c.active ? '<span style="font-size:10px;color:var(--danger);font-weight:600">Inactive</span>' : ''}
            </div>
          </div>
        </td>
        <td class="text-muted">${c.email || '—'}</td>
        <td class="text-muted">${c.phone || '—'}</td>
        <td>${c.orders || 0}</td>
        <td class="fw-600">${formatMoney(c.spent)}</td>
        <td><span class="badge ${badgeClass(c.type)}">${c.type || 'New'}</span></td>
        <td class="text-muted">${formatDate(c.created_at)}</td>
        <td>
          <div class="flex-center gap-6">
            <button class="action-btn" title="View" onclick="openView(${c.id})"><i class="fas fa-eye"></i></button>
            <button class="action-btn" title="Edit" onclick="openEdit(${c.id})"><i class="fas fa-pen"></i></button>
            <button class="action-btn ${c.active ? 'danger' : ''}" 
              title="${c.active ? 'Deactivate' : 'Activate'}"
              onclick="openDeactivate(${c.id})">
              <i class="fas ${c.active ? 'fa-ban' : 'fa-check'}"></i>
            </button>
          </div>
        </td>
      </tr>
    `}).join('');
  }

  renderPagination(filtered.length);
  document.getElementById('resultCount').textContent = 
    `Showing ${filtered.length} customer${filtered.length !== 1 ? 's' : ''}`;
}

// Update stats
function updateStats() {
  const total = customers.length;
  const totalSpent = customers.reduce((sum, c) => sum + parseFloat(c.spent || 0), 0);
  const active = customers.filter(c => c.active == 1).length;
  
  document.getElementById('statTotal').textContent = total;
  document.getElementById('statRevenue').textContent = formatMoney(totalSpent);
  document.getElementById('statActive').textContent = active;
}

// Pagination
function renderPagination(total) {
  const pages = Math.ceil(total / perPage);
  const wrap = document.getElementById('paginationWrap');
  
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

// Modal helpers
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Clear form
function clearForm() {
  ['fieldFirstName','fieldLastName','fieldEmail','fieldPhone','fieldOrders','fieldSpent'].forEach(id => {
    document.getElementById(id).value = '';
  });
  document.getElementById('fieldType').value = 'New';
  document.getElementById('fieldActive').value = '1';
  document.getElementById('formError').style.display = 'none';
}

// Add
function openAdd() {
  editingId = null;
  document.getElementById('formModalTitle').textContent = 'Add Customer';
  document.getElementById('formModalSubtitle').textContent = 'Fill in the customer details';
  document.getElementById('btnFormSave').innerHTML = '<i class="fas fa-check"></i> Save Customer';
  clearForm();
  openModal('modalForm');
}

// Edit
function openEdit(id) {
  editingId = id;
  const c = customers.find(x => x.id === id);
  if (!c) return;
  
  const nameParts = (c.full_name || '').trim().split(/\s+/).filter(p => p);
  const firstName = nameParts[0] || '';
  const lastName = nameParts.slice(1).join(' ') || '';
  
  document.getElementById('formModalTitle').textContent = 'Edit Customer';
  document.getElementById('formModalSubtitle').textContent = `Editing ${c.full_name}`;
  document.getElementById('btnFormSave').innerHTML = '<i class="fas fa-check"></i> Update Customer';
  document.getElementById('fieldFirstName').value = firstName;
  document.getElementById('fieldLastName').value = lastName;
  document.getElementById('fieldEmail').value = c.email || '';
  document.getElementById('fieldPhone').value = c.phone || '';
  document.getElementById('fieldOrders').value = c.orders || 0;
  document.getElementById('fieldSpent').value = c.spent || 0;
  document.getElementById('fieldType').value = c.type || 'New';
  document.getElementById('fieldActive').value = c.active != null ? c.active : 1;
  document.getElementById('formError').style.display = 'none';
  closeModal('modalView');
  openModal('modalForm');
}

function editFromView() {
  closeModal('modalView');
  openEdit(viewingId);
}

// Save
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

// View
function openView(id) {
  viewingId = id;
  const c = customers.find(x => x.id === id);
  if (!c) return;
  
  const avatarEl = document.getElementById('viewAvatar');
  avatarEl.innerHTML = '';
  if (c.avatar) {
    const img = document.createElement('img');
    img.src = `../images/avatars/${c.avatar}`;
    img.alt = c.full_name;
    img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
    img.onerror = () => { 
      img.style.display = 'none'; 
      avatarEl.textContent = getInitials(c.full_name); 
    };
    avatarEl.appendChild(img);
  } else {
    avatarEl.textContent = getInitials(c.full_name);
  }
  document.getElementById('viewName').textContent = c.full_name || '—';
  document.getElementById('viewEmail').textContent = c.email || '—';
  document.getElementById('viewPhone').textContent = c.phone || '—';
  document.getElementById('viewOrders').textContent = c.orders || 0;
  document.getElementById('viewSpent').textContent = formatMoney(c.spent);
  document.getElementById('viewJoined').textContent = formatDate(c.created_at);
  document.getElementById('viewStatus').textContent = c.active == 1 ? 'Active' : 'Inactive';
  document.getElementById('viewStatus').style.color = c.active == 1 ? 'var(--success)' : 'var(--danger)';
  
  const badge = document.getElementById('viewBadge');
  badge.className = `badge ${badgeClass(c.type)}`;
  badge.textContent = c.type || 'New';
  
  openModal('modalView');
}

// Deactivate/Activate
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

// Highlight row
function highlightRow(id) {
  const row = document.getElementById(`row-${id}`);
  if (row) {
    row.classList.add('highlight');
    setTimeout(() => row.classList.remove('highlight'), 1500);
  }
}

// Toast
function showToast(type, message) {
  const wrap = document.getElementById('toastWrap');
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

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('btnAddCustomer').addEventListener('click', openAdd);

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
</script>

</body>
</html>