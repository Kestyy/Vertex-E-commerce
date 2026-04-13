<?php
// Redirect to new location
header('Location: profile.php?tab=orders');
exit;
?>

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ── Fetch user ──
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$full_name  = trim($user['full_name'] ?? '');
$avatar_src = !empty($user['avatar'])
    ? 'images/avatars/' . htmlspecialchars($user['avatar'])
    : null;

// ── Filter ──
$filter = $_GET['filter'] ?? 'all';

// ── Fetch orders with items ──
$where = "WHERE o.user_id = ?";
$types = 'i';
$params = [$user_id];

if ($filter !== 'all') {
    $where .= " AND o.status = ?";
    $types .= 's';
    $params[] = $filter;
}

$sql = "SELECT o.id, o.total_amount, o.payment_method,
               o.status, o.order_date, o.updated_at
        FROM orders o
        $where
        ORDER BY o.order_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
$orders = mysqli_fetch_all($orders_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ── Fetch items for each order ──
foreach ($orders as &$order) {
    $oid = $order['id'];
    $istmt = mysqli_prepare($conn,
        "SELECT oi.id, oi.order_id, oi.product_id, oi.quantity, oi.price,
                p.name AS product_name, p.image AS product_image
         FROM order_items oi
         JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = ?");
    mysqli_stmt_bind_param($istmt, 'i', $oid);
    mysqli_stmt_execute($istmt);
    $order['items'] = mysqli_fetch_all(mysqli_stmt_get_result($istmt), MYSQLI_ASSOC);
    mysqli_stmt_close($istmt);
}
unset($order);

$order_count = count($orders);

// ── Status badge config ──
function statusBadge($status) {
    $map = [
        'pending'    => ['label' => 'Pending',    'color' => '#f59e0b', 'bg' => '#fffbeb', 'border' => '#fde68a'],
        'processing' => ['label' => 'Processing', 'color' => '#3b82f6', 'bg' => '#eff6ff', 'border' => '#bfdbfe'],
        'accepted'   => ['label' => 'Accepted',   'color' => '#f59e0b', 'bg' => '#fffbeb', 'border' => '#fde68a'],
        'shipped'    => ['label' => 'Shipped',    'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'border' => '#ddd6fe'],
        'delivered'  => ['label' => 'Delivered',  'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#bbf7d0'],
        'cancelled'  => ['label' => 'Cancelled',  'color' => '#ef4444', 'bg' => '#fef2f2', 'border' => '#fecaca'],
    ];
    return $map[strtolower($status)] ?? ['label' => ucfirst($status), 'color' => '#64748b', 'bg' => '#f8fafc', 'border' => '#e2e8f0'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Orders — Vertex</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/style.css"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; }

    body {
      background: #f1f5f9;
      font-family: 'Poppins', sans-serif;
      color: #1e293b;
    }

    /* ── Hero ── */
    .profile-hero {
      position: relative;
      background: linear-gradient(135deg, #e8f0fe 0%, #f0f4ff 40%, #e4eefb 100%);
      text-align: center;
      padding: 35px 25px;
      overflow: hidden;
      border-bottom: 1px solid #d6e4f7;
    }
    .profile-hero::before {
      content: '';
      position: absolute;
      width: 320px; height: 320px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(99,155,255,0.18) 0%, transparent 70%);
      top: -80px; left: -60px;
      pointer-events: none;
    }
    .profile-hero::after {
      content: '';
      position: absolute;
      width: 260px; height: 260px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(139,92,246,0.1) 0%, transparent 70%);
      bottom: -60px; right: -40px;
      pointer-events: none;
    }
    .profile-hero-title {
      font-size: 4rem; font-weight: 700;
      letter-spacing: -0.02em; color: #3b82f6;
      margin-bottom: 12px; position: relative; z-index: 1;
    }
    .profile-hero-sub {
      font-size: 14px; color: #6b7faa; font-weight: 400;
      margin-bottom: 20px; position: relative; z-index: 1;
    }
    .profile-hero-bc {
      display: inline-flex; align-items: center; justify-content: center;
      gap: 8px; background: rgba(255,255,255,0.6);
      border: 1px solid rgba(180,205,255,0.5); backdrop-filter: blur(6px);
      border-radius: 30px; padding: 6px 18px;
      font-size: 13px; color: #8aa0c8; position: relative; z-index: 1;
    }
    .profile-hero-bc a { color: #7a99cc; text-decoration: none; font-weight: 500; }
    .profile-hero-bc a:hover { color: #3b82f6; }
    .profile-hero-bc-sep { color: #b0c4e8; font-size: 12px; }

    /* ── Layout ── */
    .profile-wrap {
      max-width: 1100px; margin: 40px auto 80px;
      padding: 0 2rem;
      display: grid; grid-template-columns: 280px 1fr;
      gap: 40px; align-items: start;
    }
    @media (max-width: 768px) {
      .profile-wrap { grid-template-columns: 1fr; margin-top: 28px; gap: 20px; }
    }

    /* ── Sidebar ── */
    .sidebar-card {
      background: #fff; border-radius: 18px;
      border: 1px solid #e2e8f0; overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .sidebar-avatar-block {
      padding: 28px 24px 20px; text-align: center;
      border-bottom: 1px solid #f1f5f9;
    }
    .avatar-circle {
      width: 82px; height: 82px; border-radius: 50%;
      object-fit: cover; border: 3px solid #e2e8f0; display: block;
      margin: 0 auto 12px;
    }
    .avatar-default {
      width: 82px; height: 82px; border-radius: 50%;
      background: #aaa; display: flex; align-items: center;
      justify-content: center; border: 3px solid #e2e8f0;
      margin: 0 auto 12px; overflow: hidden;
    }
    .sidebar-name { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 3px; }
    .sidebar-email { font-size: 12px; color: #94a3b8; }
    .sidebar-nav { padding: 8px 0; }
    .sidebar-nav-item {
      display: flex; align-items: center; gap: 12px;
      padding: 13px 22px; font-size: 14px; font-weight: 500;
      color: #475569; cursor: pointer; text-decoration: none;
      transition: background .15s, color .15s;
      border-left: 3px solid transparent;
    }
    .sidebar-nav-item:hover { background: #f8fafc; color: #3b82f6; }
    .sidebar-nav-item.active {
      background: #eff6ff; color: #3b82f6;
      border-left-color: #3b82f6; font-weight: 600;
    }
    .sidebar-nav-item i { width: 18px; text-align: center; font-size: 13.5px; opacity: .75; }
    .sidebar-nav-item.active i { opacity: 1; }
    .sidebar-nav-divider { height: 1px; background: #f1f5f9; margin: 6px 0; }
    .sidebar-nav-item.logout { color: #ef4444; }
    .sidebar-nav-item.logout:hover {
      background: #fef2f2; color: #dc2626;
      border-left-color: #ef4444;
    }

    /* ── Main white container card (matches profile page) ── */
    .main-card {
      background: #fff;
      border-radius: 18px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
      overflow: hidden;
    }

    /* Card header — matches the "Manage Address" header style */
    .main-card-header {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 22px 28px;
      border-bottom: 1px solid #f1f5f9;
    }
    .main-card-header-icon {
      width: 38px; height: 38px;
      background: #eff6ff;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      color: #3b82f6; font-size: 15px; flex-shrink: 0;
    }
    .main-card-header-text { flex: 1; min-width: 0; }
    .main-card-header-title {
      font-size: 15px; font-weight: 700; color: #1e293b;
      margin-bottom: 2px;
    }
    .main-card-header-sub {
      font-size: 12px; color: #94a3b8; font-weight: 400;
    }

    /* Filter row inside card */
    .main-card-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 28px;
      border-bottom: 1px solid #f1f5f9;
      flex-wrap: wrap;
      gap: 10px;
    }
    .orders-count-pill {
      display: inline-flex; align-items: center; justify-content: center;
      background: #3b82f6; color: #fff;
      border-radius: 20px; padding: 2px 10px;
      font-size: 12px; font-weight: 600;
      margin-left: 8px;
    }
    .toolbar-label {
      font-size: 13px; font-weight: 600; color: #1e293b;
    }
    .filter-wrap {
      display: flex; align-items: center; gap: 8px;
      font-size: 13px; color: #64748b;
    }
    .filter-wrap select {
      border: 1.5px solid #e2e8f0; border-radius: 8px;
      padding: 6px 30px 6px 12px; font-size: 13px;
      font-family: 'Poppins', sans-serif;
      font-weight: 500; color: #1e293b;
      background: #fff;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2394a3b8' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 10px center;
      appearance: none; cursor: pointer; outline: none;
    }
    .filter-wrap select:focus { border-color: #3b82f6; }

    /* Orders body — scrollable content area */
    .main-card-body {
      padding: 24px 28px;
    }

    /* ── Order card ── */
    .order-card {
      background: #fff; border-radius: 14px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      margin-bottom: 20px; overflow: hidden;
      transition: box-shadow .2s;
    }
    .order-card:last-child { margin-bottom: 0; }
    .order-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.09); }

    /* Order header row */
    .order-card-header {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0;
      background: #fef9ec;
      border-bottom: 1px solid #fde68a;
      padding: 14px 20px;
    }
    @media (max-width: 600px) {
      .order-card-header { grid-template-columns: 1fr 1fr; gap: 10px; }
    }
    .order-header-cell { display: flex; flex-direction: column; gap: 3px; }
    .order-header-label {
      font-size: 11px; font-weight: 600; color: #92400e;
      text-transform: uppercase; letter-spacing: .04em;
    }
    .order-header-value {
      font-size: 13.5px; font-weight: 700; color: #1e293b;
    }
    .order-id-value { color: #d97706; }

    /* Items list */
    .order-items-list { padding: 0; }
    .order-item-row {
      display: flex; align-items: center; gap: 16px;
      padding: 14px 20px;
      border-bottom: 1px solid #f1f5f9;
      transition: background .15s;
    }
    .order-item-row:last-child { border-bottom: none; }
    .order-item-row:hover { background: #fafbfc; }

    .order-item-img {
      width: 60px; height: 60px; border-radius: 10px;
      object-fit: cover; border: 1px solid #e2e8f0;
      flex-shrink: 0; background: #f8fafc;
    }
    .order-item-img-placeholder {
      width: 60px; height: 60px; border-radius: 10px;
      background: #f1f5f9; border: 1px solid #e2e8f0;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; color: #cbd5e1; font-size: 20px;
    }
    .order-item-info { flex: 1; min-width: 0; }
    .order-item-name {
      font-size: 14px; font-weight: 600; color: #1e293b;
      margin-bottom: 4px;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .order-item-meta {
      font-size: 12px; color: #94a3b8; font-weight: 400;
    }

    /* Status + actions footer */
    .order-card-footer {
      padding: 12px 20px;
      border-top: 1px solid #f1f5f9;
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 12px;
      background: #fafbfc;
    }
    .order-status-wrap { display: flex; align-items: center; gap: 10px; }
    .status-badge {
      display: inline-flex; align-items: center;
      padding: 4px 12px; border-radius: 20px;
      font-size: 12px; font-weight: 600;
      border: 1px solid;
    }
    .order-status-msg {
      font-size: 13px; color: #64748b; font-weight: 400;
    }
    .order-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

    .btn-track {
      display: inline-flex; align-items: center; gap: 7px;
      background: #1e293b; color: #fff;
      border: none; border-radius: 9px;
      padding: 8px 18px;
      font-family: 'Poppins', sans-serif;
      font-size: 13px; font-weight: 600;
      cursor: pointer;
      transition: background .2s, transform .15s;
      text-decoration: none;
    }
    .btn-track:hover { background: #0f172a; transform: translateY(-1px); color: #fff; }

    .btn-invoice {
      display: inline-flex; align-items: center; gap: 7px;
      background: #fff; color: #1e293b;
      border: 1.5px solid #e2e8f0; border-radius: 9px;
      padding: 7px 18px;
      font-family: 'Poppins', sans-serif;
      font-size: 13px; font-weight: 600;
      cursor: pointer;
      transition: border-color .2s, background .2s;
      text-decoration: none;
    }
    .btn-invoice:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }

    .btn-review {
      display: inline-flex; align-items: center; gap: 7px;
      background: #1e293b; color: #fff;
      border: none; border-radius: 9px;
      padding: 8px 18px;
      font-family: 'Poppins', sans-serif;
      font-size: 13px; font-weight: 600;
      cursor: pointer;
      transition: background .2s; text-decoration: none;
    }
    .btn-review:hover { background: #0f172a; color: #fff; }

    .btn-cancel {
      background: none; border: none; padding: 0;
      font-size: 13px; font-weight: 600; color: #ef4444;
      cursor: pointer; font-family: 'Poppins', sans-serif;
      transition: color .2s; text-decoration: none;
    }
    .btn-cancel:hover { color: #dc2626; text-decoration: underline; }

    /* Empty state */
    .empty-orders {
      padding: 60px 30px; text-align: center;
    }
    .empty-orders-icon {
      font-size: 3rem; color: #cbd5e1; margin-bottom: 16px;
    }
    .empty-orders-title {
      font-size: 16px; font-weight: 600; color: #64748b; margin-bottom: 8px;
    }
    .empty-orders-sub { font-size: 13px; color: #94a3b8; }

    @media (max-width: 768px) {
      .profile-hero-title { font-size: 2.2rem; }
      .main-card-header { padding: 18px 20px; }
      .main-card-toolbar { padding: 14px 20px; }
      .main-card-body { padding: 16px 16px; }
      .order-card-header { grid-template-columns: 1fr 1fr; gap: 12px; padding: 14px 16px; }
      .order-item-row { padding: 12px 16px; }
      .order-card-footer { padding: 10px 16px; }
    }
  </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- ══ HERO ══ -->
<div class="profile-hero">
  <div class="profile-hero-title">My Account</div>
  <div class="profile-hero-sub">Manage your orders and account settings</div>
  <nav class="profile-hero-bc" aria-label="breadcrumb">
    <a href="index.php">Home</a>
    <span class="profile-hero-bc-sep">›</span>
    <a href="profile.php">My Account</a>
    <span class="profile-hero-bc-sep">›</span>
    <span>My Orders</span>
  </nav>
</div>

<div class="profile-wrap">

  <!-- ══ SIDEBAR ══ -->
  <aside>
    <div class="sidebar-card">
      <div class="sidebar-avatar-block">
        <?php if ($avatar_src): ?>
          <img src="<?= $avatar_src ?>" alt="Avatar" class="avatar-circle"/>
        <?php else: ?>
          <div class="avatar-default">
            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" width="48" height="48">
              <circle cx="50" cy="35" r="20" fill="#e0e0e0"/>
              <ellipse cx="50" cy="95" rx="40" ry="30" fill="#e0e0e0"/>
            </svg>
          </div>
        <?php endif; ?>
        <div class="sidebar-name"><?= htmlspecialchars($full_name ?: 'My Account') ?></div>
        <div class="sidebar-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
      </div>
      <nav class="sidebar-nav">
        <a href="profile.php?tab=info" class="sidebar-nav-item">
          <i class="fas fa-user"></i> Personal Information
        </a>
        <a href="my_orders.php" class="sidebar-nav-item active">
          <i class="fas fa-box"></i> My Orders
        </a>
        <a href="profile.php?tab=address" class="sidebar-nav-item">
          <i class="fas fa-map-marker-alt"></i> Manage Address
        </a>
        <a href="profile.php?tab=payment" class="sidebar-nav-item">
          <i class="fas fa-credit-card"></i> Payment Method
        </a>
        <a href="profile.php?tab=password" class="sidebar-nav-item">
          <i class="fas fa-lock"></i> Password Manager
        </a>
        <div class="sidebar-nav-divider"></div>
        <a href="#" class="sidebar-nav-item logout" id="sidebarLogout">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </nav>
    </div>
  </aside>

  <!-- ══ MAIN WHITE CARD ══ -->
  <main>
    <div class="main-card">

      <!-- Card header — matches profile page style -->
      <div class="main-card-header">
        <div class="main-card-header-icon">
          <i class="fas fa-box"></i>
        </div>
        <div class="main-card-header-text">
          <div class="main-card-header-title">My Orders</div>
          <div class="main-card-header-sub">Track and manage your order history</div>
        </div>
      </div>

      <!-- Toolbar: count + filter -->
      <div class="main-card-toolbar">
        <div class="toolbar-label">
          Orders
          <span class="orders-count-pill"><?= $order_count ?></span>
        </div>
        <div class="filter-wrap">
          <span>Sort by :</span>
          <form method="GET" id="filterForm">
            <select name="filter" onchange="document.getElementById('filterForm').submit()">
              <option value="all"        <?= $filter === 'all'        ? 'selected' : '' ?>>All</option>
              <option value="pending"    <?= $filter === 'pending'    ? 'selected' : '' ?>>Pending</option>
              <option value="processing" <?= $filter === 'processing' ? 'selected' : '' ?>>Processing</option>
              <option value="accepted"   <?= $filter === 'accepted'   ? 'selected' : '' ?>>Accepted</option>
              <option value="shipped"    <?= $filter === 'shipped'    ? 'selected' : '' ?>>Shipped</option>
              <option value="delivered"  <?= $filter === 'delivered'  ? 'selected' : '' ?>>Delivered</option>
              <option value="cancelled"  <?= $filter === 'cancelled'  ? 'selected' : '' ?>>Cancelled</option>
            </select>
          </form>
        </div>
      </div>

      <!-- Orders body -->
      <div class="main-card-body">

        <?php if (empty($orders)): ?>
        <div class="empty-orders">
          <div class="empty-orders-icon"><i class="fas fa-box-open"></i></div>
          <div class="empty-orders-title">No orders found</div>
          <div class="empty-orders-sub">
            <?= $filter !== 'all' ? 'No ' . htmlspecialchars($filter) . ' orders yet.' : 'You haven\'t placed any orders yet.' ?>
          </div>
        </div>

        <?php else: ?>
        <?php foreach ($orders as $order):
            $badge = statusBadge($order['status']);
            $status = strtolower($order['status']);

            $date_label = 'Order Date';
            $date_value = date('d F Y', strtotime($order['order_date']));

            $status_messages = [
                'pending'    => 'Your Order is Pending',
                'processing' => 'Your Order is Being Processed',
                'accepted'   => 'Your Order has been Accepted',
                'shipped'    => 'Your Order is On the Way',
                'delivered'  => 'Your Order has been Delivered',
                'cancelled'  => 'Your Order has been Cancelled',
            ];
            $status_msg = $status_messages[$status] ?? 'Order Status: ' . ucfirst($status);

            $order_num = '#ORD-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
        ?>
        <div class="order-card">

          <!-- Header row -->
          <div class="order-card-header">
            <div class="order-header-cell">
              <span class="order-header-label">Order ID</span>
              <span class="order-header-value order-id-value"><?= htmlspecialchars($order_num) ?></span>
            </div>
            <div class="order-header-cell">
              <span class="order-header-label">Total Payment</span>
              <span class="order-header-value">$<?= number_format($order['total_amount'], 2) ?></span>
            </div>
            <div class="order-header-cell">
              <span class="order-header-label">Payment Method</span>
              <span class="order-header-value"><?= htmlspecialchars(ucfirst($order['payment_method'] ?? 'N/A')) ?></span>
            </div>
            <div class="order-header-cell">
              <span class="order-header-label"><?= htmlspecialchars($date_label) ?></span>
              <span class="order-header-value"><?= htmlspecialchars($date_value) ?></span>
            </div>
          </div>

          <!-- Items -->
          <div class="order-items-list">
            <?php foreach ($order['items'] as $item): ?>
            <div class="order-item-row">
              <?php
                $img_path = '';
                if (!empty($item['product_image'])) {
                    $img_path = 'images/products/' . $item['product_image'];
                }
              ?>
              <?php if ($img_path): ?>
                <img src="<?= htmlspecialchars($img_path) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="order-item-img"/>
              <?php else: ?>
                <div class="order-item-img-placeholder"><i class="fas fa-image"></i></div>
              <?php endif; ?>
              <div class="order-item-info">
                <div class="order-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                <div class="order-item-meta">
                  <?php
                    $meta = [];
                    $meta[] = ($item['quantity'] ?? 1) . ' Qty.';
                    echo htmlspecialchars(implode(' | ', $meta));
                  ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Footer: status + actions -->
          <div class="order-card-footer">
            <div class="order-status-wrap">
              <span class="status-badge"
                    style="color:<?= $badge['color'] ?>;background:<?= $badge['bg'] ?>;border-color:<?= $badge['border'] ?>;">
                <?= htmlspecialchars($badge['label']) ?>
              </span>
              <span class="order-status-msg"><?= htmlspecialchars($status_msg) ?></span>
            </div>

            <div class="order-actions">
              <?php if ($status === 'delivered'): ?>
                <a href="review.php?order=<?= $order['id'] ?>" class="btn-review">
                  <i class="fas fa-star"></i> Add Review
                </a>
                <a href="invoice.php?order=<?= $order['id'] ?>" class="btn-invoice">
                  <i class="fas fa-file-invoice"></i> Invoice
                </a>

              <?php elseif ($status === 'cancelled'): ?>
                <a href="invoice.php?order=<?= $order['id'] ?>" class="btn-invoice">
                  <i class="fas fa-file-invoice"></i> Invoice
                </a>

              <?php else: ?>
                <a href="track_order.php?order=<?= $order['id'] ?>" class="btn-track">
                  <i class="fas fa-location-dot"></i> Track Order
                </a>
                <a href="invoice.php?order=<?= $order['id'] ?>" class="btn-invoice">
                  <i class="fas fa-file-invoice"></i> Invoice
                </a>
                <?php if (in_array($status, ['pending', 'processing'])): ?>
                  <a href="cancel_order.php?order=<?= $order['id'] ?>" class="btn-cancel"
                     onclick="return confirm('Are you sure you want to cancel this order?')">
                    Cancel Order
                  </a>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>

        </div>
        <?php endforeach; ?>
        <?php endif; ?>

      </div><!-- /.main-card-body -->
    </div><!-- /.main-card -->
  </main>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebarLogout = document.getElementById('sidebarLogout');
    const logoutModal   = document.getElementById('logoutModal');
    if (sidebarLogout && logoutModal) {
        sidebarLogout.addEventListener('click', function (e) {
            e.preventDefault();
            logoutModal.classList.add('active');
            document.body.classList.add('modal-open');
        });
    }
});
</script>
</body>
</html>
