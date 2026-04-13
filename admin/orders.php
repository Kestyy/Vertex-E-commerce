<?php
require_once 'auth_check.php';
require_once '../assets/php/db.php';

// ── Fetch orders with user info ──
$statusFilter = $_GET['status'] ?? '';
$searchQuery  = $_GET['search'] ?? '';

$query = "
    SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.avatar as customer_avatar
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE 1=1
";
$params = [];
$types  = '';

if ($statusFilter) {
    $query .= " AND o.status = ?";
    $params[] = $statusFilter;
    $types   .= 's';
}

if ($searchQuery) {
    $query .= " AND (o.id LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types   .= 'sss';
}

$query .= " ORDER BY o.order_date DESC";

$stmt = mysqli_prepare($conn, $query);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// ── Fetch stats ──
$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count
    FROM orders
"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Vertex Admin — Orders</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --navy:      #0f172a;
      --border-dk: rgba(255,255,255,0.07);
      --accent:    #3b82f6;
      --border:    #e2e8f0;
      --bg:        #f8fafc;
      --card:      #ffffff;
      --text:      #0f172a;
      --text-muted:#94a3b8;
      --sidebar-w: 240px;
      --danger:    #ef4444;
      --success:   #22c55e;
      --warning:   #f59e0b;
    }

    body { font-family: 'Poppins', sans-serif; background: #f1f5f9; color: var(--text); display: flex; min-height: 100vh; }

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

    .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
    .topbar { background: var(--card); border-bottom: 1px solid var(--border); padding: 0 36px; height: 85px; display: flex; align-items: center; position: sticky; top: 0; z-index: 50; }
    .topbar-title { font-size: 1.4rem; font-weight: 700; color: var(--text); }
    .customer-cell { display: flex; align-items: center; gap: 12px; }
    .customer-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent); flex-shrink: 0; }
    .customer-avatar-initials { width: 40px; height: 40px; border-radius: 50%; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; flex-shrink: 0; }

    .content { padding: 36px; flex: 1; }

    /* Stats */
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; }
    .stat-value { font-size: 1.8rem; font-weight: 700; color: var(--text); margin-bottom: 4px; }
    .stat-label { font-size: 0.95rem; color: var(--text-muted); }

    /* Filters */
    .filter-bar { display: flex; gap: 12px; padding: 16px 20px; background: var(--card); border: 1px solid var(--border); border-radius: 16px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
    .filter-search { display: flex; align-items: center; gap: 8px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; padding: 8px 14px; min-width: 240px; }
    .filter-search input { border: none; outline: none; background: transparent; font-family: 'Poppins', sans-serif; font-size: 15px; color: var(--text); flex: 1; }
    .filter-select { border: 1px solid var(--border); background: var(--bg); border-radius: 10px; padding: 9px 32px 9px 12px; font-family: 'Poppins', sans-serif; font-size: 15px; color: var(--text); cursor: pointer; outline: none; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; }

    /* Table */
    .table-wrap { background: var(--card); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    thead th { padding: 12px 16px; font-size: 14px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; text-align: left; background: var(--bg); border-bottom: 1px solid var(--border); }
    tbody td { padding: 14px 16px; font-size: 16px; border-bottom: 1px solid var(--border); color: var(--text); }
    tbody tr:hover { background: #f8fafc; }
    tbody tr:last-child td { border-bottom: none; }

    .order-id { font-weight: 600; color: var(--accent); }
    .customer-cell { display: flex; flex-direction: column; gap: 2px; }
    .customer-name { font-weight: 500; }
    .customer-email { font-size: 14px; color: var(--text-muted); }

    .badge { display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 99px; font-size: 14px; font-weight: 600; }
    .badge-pending    { background: #fef9c3; color: #ca8a04; }
    .badge-processing { background: #dbeafe; color: #2563eb; }
    .badge-shipped    { background: #e0e7ff; color: #4f46e5; }
    .badge-delivered  { background: #dcfce7; color: #16a34a; }

    .action-btn { width: 30px; height: 30px; border: 1px solid var(--border); background: transparent; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-muted); transition: all 0.15s; }
    .action-btn:hover { border-color: var(--accent); color: var(--accent); background: rgba(59,130,246,0.1); }
    .action-btn i { font-size: 0.75rem; }

    .empty-state { text-align: center; padding: 48px 20px; color: var(--text-muted); }
    .empty-state i { font-size: 2rem; margin-bottom: 10px; display: block; }

    /* Modal */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: none; align-items: center; justify-content: center; z-index: 200; padding: 20px; }
    .modal-overlay.open { display: flex; }
    .modal { background: var(--card); border-radius: 16px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; border: 1px solid var(--border); }
    .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .modal-title { font-size: 18px; font-weight: 600; }
    .modal-close { width: 28px; height: 28px; border: 1px solid var(--border); background: transparent; border-radius: 7px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-muted); }
    .modal-close:hover { border-color: var(--danger); color: var(--danger); }
    .modal-body { padding: 20px 24px; }
    .modal-footer { padding: 16px 24px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 8px; }

    .order-detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 15px; }
    .order-detail-row:last-child { border-bottom: none; }
    .order-detail-label { color: var(--text-muted); }
    .order-detail-value { font-weight: 500; }

    .status-select { border: 1px solid var(--border); border-radius: 8px; padding: 6px 10px; font-family: 'Poppins', sans-serif; font-size: 14px; background: var(--bg); }
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
      <li><a href="orders.php" class="active"><i class="fas fa-shopping-bag"></i> Orders</a></li>
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

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-title">Orders</div>
      <div class="topbar-sub">Track and manage customer orders</div>
    </div>
  </div>

  <div class="content">
    
    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['total_orders'] ?? 0); ?></div>
        <div class="stat-label">Total Orders</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">₱<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
        <div class="stat-label">Total Revenue</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $stats['pending_count'] ?? 0; ?></div>
        <div class="stat-label">Pending Orders</div>
      </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="filter-bar">
      <div class="filter-search">
        <i class="fas fa-search" style="color:var(--text-muted);font-size:0.75rem;"></i>
        <input type="text" name="search" placeholder="Search by Order ID, customer name or email…" value="<?php echo htmlspecialchars($searchQuery); ?>"/>
      </div>
      <select class="filter-select" name="status" onchange="this.form.submit()">
        <option value="">All Status</option>
        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
        <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
        <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
        <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
      </select>
      <button type="button" class="action-btn" onclick="window.location.href='orders.php'" title="Clear filters">
        <i class="fas fa-times"></i>
      </button>
    </form>

    <!-- Orders Table -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Date</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($orders) > 0): ?>
            <?php while ($order = mysqli_fetch_assoc($orders)): ?>
            <tr>
              <td><span class="order-id">#<?php echo $order['id']; ?></span></td>
              <td>
                <div class="customer-cell">
                  <?php 
                    $customer_name = htmlspecialchars($order['customer_name'] ?? 'Guest');
                    $avatar = $order['customer_avatar'] ?? null;
                  ?>
                  <?php if ($avatar): ?>
                    <img src="../images/avatars/<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo $customer_name; ?>" class="customer-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                  <?php endif; ?>
                  <div class="customer-avatar-initials" <?php echo $avatar ? 'style="display:none;"' : ''; ?>> 
                    <?php echo strtoupper(implode('', array_map(fn($part) => $part[0], explode(' ', $customer_name)))); ?>
                  </div>
                  <div>
                    <span class="customer-name"><?php echo $customer_name; ?></span>
                    <span class="customer-email"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></span>
                  </div>
                </div>
              </td>
              <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
              <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
              <td><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></td>
              <td>
                <select class="status-select" data-order-id="<?php echo $order['id']; ?>" onchange="updateStatus(this)">
                  <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                  <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                  <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                  <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                </select>
              </td>
              <td>
                <button class="action-btn" onclick="viewOrder(<?php echo $order['id']; ?>)" title="View details">
                  <i class="fas fa-eye"></i>
                </button>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <p>No orders found.</p>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- Order Detail Modal -->
<div class="modal-overlay" id="orderModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Order #<span id="modalOrderNumber"></span></div>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body" id="modalBody">
      <!-- Content loaded via JS -->
    </div>
    <div class="modal-footer">
      <button class="action-btn" onclick="closeModal()">Close</button>
    </div>
  </div>
</div>

<script>
// ── Update order status via AJAX ──
function updateStatus(select) {
    const orderId = select.dataset.orderId;
    const newStatus = select.value;
    
    fetch('assets/php/update_order_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=${orderId}&status=${newStatus}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            select.style.borderColor = '#22c55e';
            setTimeout(() => select.style.borderColor = '', 1500);
        } else {
            alert('Failed to update status: ' + data.error);
            location.reload();
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Connection error. Please try again.');
        location.reload();
    });
}

// ── View order details ──
function viewOrder(orderId) {
    fetch(`api/get_order_details.php?id=${orderId}`)
    .then(res => res.text())
    .then(html => {
        document.getElementById('modalOrderNumber').textContent = orderId;
        document.getElementById('modalBody').innerHTML = html;
        document.getElementById('orderModal').classList.add('open');
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Failed to load order details.');
    });
}

function closeModal() {
    document.getElementById('orderModal').classList.remove('open');
}

document.getElementById('orderModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

</body>
</html>