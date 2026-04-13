<?php require_once 'auth_check.php'; 
require_once '../assets/php/db.php';

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Fetch real data for dashboard
$total_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'"))['total'];
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status != 'cancelled'"))['total'];
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE status = 'active'"))['total'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];

// Recent orders
$recent_orders = mysqli_query($conn, "
    SELECT o.id, o.total_amount, o.status, o.order_date, u.full_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.order_date DESC 
    LIMIT 5
");

// Top Products (with image)
$top_products = mysqli_query($conn, "
    SELECT p.name, p.image, SUM(oi.quantity) as total_sales, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled' AND p.status = 'active'
    GROUP BY p.id, p.name, p.image
    ORDER BY total_sales DESC
    LIMIT 5
");

// Top Customers (with avatar) - Calculate from actual orders
$top_customers = mysqli_query($conn, "
    SELECT u.id, u.full_name, u.avatar, COUNT(o.id) as order_count, SUM(o.total_amount) as spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'cancelled'
    WHERE u.role = 'customer'
    GROUP BY u.id, u.full_name, u.avatar
    ORDER BY spent DESC
    LIMIT 5
");

// Sales by Category
$sales_by_category = mysqli_query($conn, "
    SELECT c.name, SUM(oi.quantity * oi.price) as total_sales
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY c.id, c.name
    ORDER BY total_sales DESC
    LIMIT 5
");

$category_labels = [];
$category_values = [];
$category_colors = ['#1e1b4b', '#3b82f6', '#ec4899', '#f59e0b', '#10b981'];

while ($cat = mysqli_fetch_assoc($sales_by_category)) {
    $category_labels[] = $cat['name'];
    $category_values[] = floatval($cat['total_sales']);
}

// Sales Chart Data
$period = $_GET['period'] ?? 'monthly';
$chart_labels = [];
$chart_values = [];
$chart_title = 'Sales Performance';
$chart_subtitle = 'You can see sales volume from here';

if ($period === 'weekly') {
    $sales_query = mysqli_query($conn, "
        SELECT DATE(order_date) as date, SUM(total_amount) as total
        FROM orders 
        WHERE status != 'cancelled' 
          AND order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(order_date)
        ORDER BY date ASC
    ");
    if ($sales_query) {
        while($row = mysqli_fetch_assoc($sales_query)) {
            $chart_labels[] = date('M j', strtotime($row['date']));
            $chart_values[] = floatval($row['total']);
        }
    }
    if (empty($chart_labels)) {
        for ($i = 6; $i >= 0; $i--) {
            $chart_labels[] = date('M j', strtotime("-{$i} days"));
            $chart_values[] = 0;
        }
    }
    $chart_title = 'Weekly Sales Performance';
    $chart_subtitle = 'Daily sales for the last 7 days';
} elseif ($period === 'yearly') {
    $sales_query = mysqli_query($conn, "
        SELECT YEAR(order_date) as year, SUM(total_amount) as total
        FROM orders 
        WHERE status != 'cancelled'
        GROUP BY YEAR(order_date)
        ORDER BY year ASC
    ");
    if ($sales_query) {
        while($row = mysqli_fetch_assoc($sales_query)) {
            $chart_labels[] = $row['year'];
            $chart_values[] = floatval($row['total']);
        }
    }
    $chart_title = 'Yearly Sales Performance';
    $chart_subtitle = 'Total sales per year';
} else {
    $sales_query = mysqli_query($conn, "
        SELECT DATE_FORMAT(order_date, '%Y-%m') as month, SUM(total_amount) as total
        FROM orders 
        WHERE status != 'cancelled'
          AND order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(order_date, '%Y-%m')
        ORDER BY month ASC
    ");
    if ($sales_query) {
        while($row = mysqli_fetch_assoc($sales_query)) {
            $chart_labels[] = date('M', strtotime($row['month'] . '-01'));
            $chart_values[] = floatval($row['total']);
        }
    }
    $chart_title = 'Monthly Sales Performance';
    $chart_subtitle = 'You can see monthly sales volume from here';
}

// Helper: get initials from full name
function getInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return $initials;
}

// Avatar color palette cycling
$avatar_colors = [
    ['bg' => '#E6F1FB', 'color' => '#185FA5'],
    ['bg' => '#EEEDFE', 'color' => '#534AB7'],
    ['bg' => '#E1F5EE', 'color' => '#0F6E56'],
    ['bg' => '#FAECE7', 'color' => '#993C1D'],
    ['bg' => '#FBEAF0', 'color' => '#993556'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Vertex Admin — Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        --success:     #10b981;
        --warning:     #f59e0b;
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
      .topbar { background: var(--card); border-bottom: 1px solid var(--border); padding: 0 36px; height: 85px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
      .topbar-title { font-size: 1.4rem; font-weight: 700; color: var(--text); }
      .topbar-sub { font-size: 1rem; color: var(--text-muted); margin-top: 2px; font-weight: 500; }
      .topbar-right { display: flex; align-items: center; gap: 18px; }
      .topbar-date { font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; font-weight: 500; }
      .topbar-clock { font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; font-weight: 500; }
      .topbar-badge { width: 40px; height: 40px; border-radius: 10px; background: var(--bg); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-2); font-size: 0.95rem; transition: all 0.15s; position: relative; }
      .topbar-badge:hover { background: var(--border); }
      .badge-dot { position: absolute; top: 6px; right: 6px; width: 7px; height: 7px; background: var(--danger); border-radius: 50%; border: 2px solid var(--card); }
      .content { padding: 36px; flex: 1; }

      .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
      .page-title { font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: 4px; }
      .page-sub { font-size: 0.85rem; color: var(--text-muted); }

      /* ── Stat Cards ── */
      .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 32px; }
      .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; transition: transform 0.2s, box-shadow 0.2s; position: relative; overflow: hidden; }
      .stat-card::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 4px; }
      .stat-card.blue::after { background: #3b82f6; }
      .stat-card.green::after { background: #22c55e; }
      .stat-card.amber::after { background: #f59e0b; }
      .stat-card.purple::after { background: #a855f7; }
      .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
      .stat-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; }
      .stat-main { display: flex; align-items: center; gap: 16px; margin-bottom: 12px; }
      .stat-icon { width: 58px; height: 58px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
      .stat-card.blue .stat-icon { background: #dbeafe; color: #1e40af; }
      .stat-card.green .stat-icon { background: #dcfce7; color: #166534; }
      .stat-card.amber .stat-icon { background: #fed7aa; color: #92400e; }
      .stat-card.purple .stat-icon { background: #e9d5ff; color: #6b21a8; }
      .stat-trend { display: flex; align-items: center; gap: 4px; font-size: 0.78rem; font-weight: 700; padding: 5px 10px; border-radius: 20px; flex-shrink: 0; }
      .stat-trend.up { background: #f0fdf4; color: #16a34a; }
      .stat-trend.down { background: #fef2f2; color: #dc2626; }
      .stat-value { font-size: 1.7rem; font-weight: 800; color: var(--text); line-height: 1; word-break: break-word; }
      .stat-label { font-size: 1rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; font-weight: 600; }

      /* ── Dashboard Grid ── */
      .dashboard-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 24px; }
      .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
      .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
      .card-title { font-size: 1rem; font-weight: 700; color: var(--text); }
      .card-action { font-size: 0.85rem; color: var(--accent); text-decoration: none; font-weight: 600; cursor: pointer; }
      .card-select { padding: 6px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.85rem; font-weight: 500; color: var(--text-2); background: var(--bg); cursor: pointer; outline: none; transition: all 0.2s; }
      .card-select:hover { border-color: var(--accent); }
      .card-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }

      /* Charts */
      .chart-container { position: relative; height: 340px; margin-top: 16px; }
      .category-chart-container { position: relative; height: 240px; display: flex; align-items: center; justify-content: center; }
      .category-legend { display: flex; flex-wrap: wrap; gap: 12px 20px; justify-content: center; }
      .category-legend-item { display: flex; align-items: center; gap: 6px; padding: 4px 12px; background: #f8fafc; border-radius: 20px; }
      .category-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
      .category-percentage { font-size: 0.85rem; font-weight: 700; color: var(--text); }
      .category-name { font-size: 0.85rem; font-weight: 500; color: var(--text-2); }

      /* Recent Orders Table */
      .orders-table { width: 100%; border-collapse: collapse; }
      .orders-table th { text-align: left; padding: 12px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); }
      .orders-table td { padding: 16px 12px; font-size: 0.9rem; border-bottom: 1px solid var(--border); }
      .orders-table tr:last-child td { border-bottom: none; }
      .order-id { font-weight: 600; color: var(--text); }
      .order-status { display: inline-flex; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
      .order-status.completed { background: #f0fdf4; color: #16a34a; }
      .order-status.pending { background: #fef3c7; color: #d97706; }
      .order-status.processing { background: #dbeafe; color: #2563eb; }

      /* ── NEW: Top Products Panel ── */
      .product-list { display: flex; flex-direction: column; gap: 0; }
      .product-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 0;
        border-bottom: 1px solid var(--border);
        flex: 1;
      }
      .product-item:last-child { border-bottom: none; padding-bottom: 16px; }
      .product-item:first-child { padding-top: 16px; }
      .product-thumb {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        object-fit: cover;
        flex-shrink: 0;
        background: var(--bg);
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
      }
      .product-thumb img { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }
      .product-thumb-placeholder { font-size: 1.2rem; }
      .product-info { flex: 1; min-width: 0; }
      .product-name { font-weight: 600; color: var(--text); font-size: 0.88rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
      .product-sales { font-size: 0.78rem; color: var(--text-muted); }
      .product-revenue { font-weight: 700; color: var(--success); font-size: 0.88rem; white-space: nowrap; flex-shrink: 0; }

      /* ── NEW: Top Customers Panel ── */
      .customer-list { display: flex; flex-direction: column; gap: 0; }
      .customer-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 0;
        border-bottom: 1px solid var(--border);
        flex: 1;
      }
      .customer-item:last-child { border-bottom: none; padding-bottom: 16px; }
      .customer-item:first-child { padding-top: 16px; }
      .customer-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        font-weight: 600;
        overflow: hidden;
      }
      .customer-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
      .customer-info { flex: 1; min-width: 0; }
      .customer-name { font-weight: 600; color: var(--text); font-size: 0.88rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
      .customer-orders { font-size: 0.78rem; color: var(--text-muted); }
      .customer-spent { font-weight: 700; color: var(--accent); font-size: 0.88rem; white-space: nowrap; flex-shrink: 0; }

      @media (max-width: 1200px) {
        .dashboard-grid { grid-template-columns: 1fr; }
        .stat-value { font-size: 1.75rem; }
      }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-name">Vertex</div>
        <div class="logo-sub">Admin</div>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-label">Main</div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="active"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-boxes"></i> Products</a></li>
            <li><a href="orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
            <li><a href="customers.php"><i class="fas fa-users"></i> Customers</a></li>
            <li><a href="support.php"><i class="fas fa-headset"></i> Support</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
        </ul>
    </div>
    <div class="sidebar-bottom">
        <a href="logout.php" class="sidebar-logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <div>
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-sub">Welcome back, <?php echo htmlspecialchars($admin_name); ?></div>
        </div>
        <div class="topbar-right">
            <div class="topbar-date">
                <i class="fas fa-calendar-alt"></i>
                <span id="today-date">--</span>
            </div>
            <div class="topbar-clock">
                <i class="fas fa-clock"></i>
                <span id="live-clock">--:-- --</span>
            </div>
            <div class="topbar-badge">
                <i class="fas fa-bell"></i>
                <span class="badge-dot"></span>
            </div>
            <div class="topbar-badge">
                <i class="fas fa-cog"></i>
            </div>
        </div>
    </div>

    <div class="content">

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-top">
                    <div></div>
                    <div class="stat-trend up"><i class="fas fa-arrow-up"></i> 12.4%</div>
                </div>
                <div class="stat-main">
                    <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                    <div>
                        <div class="stat-value">₱<?php echo number_format($total_sales, 0); ?></div>
                        <div class="stat-label">Total Sales</div>
                    </div>
                </div>
            </div>
            <div class="stat-card green">
                <div class="stat-top">
                    <div></div>
                    <div class="stat-trend up"><i class="fas fa-arrow-up"></i> 8.1%</div>
                </div>
                <div class="stat-main">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div>
                        <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                </div>
            </div>
            <div class="stat-card amber">
                <div class="stat-top">
                    <div></div>
                    <div class="stat-trend down"><i class="fas fa-arrow-down"></i> 2.3%</div>
                </div>
                <div class="stat-main">
                    <div class="stat-icon"><i class="fas fa-boxes-stacked"></i></div>
                    <div>
                        <div class="stat-value"><?php echo number_format($total_products); ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                </div>
            </div>
            <div class="stat-card purple">
                <div class="stat-top">
                    <div></div>
                    <div class="stat-trend up"><i class="fas fa-arrow-up"></i> 5.7%</div>
                </div>
                <div class="stat-main">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="stat-value"><?php echo number_format($total_users); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">

            <!-- Sales Chart -->
            <div class="card" style="grid-column: span 8;">
                <div class="card-header">
                    <div>
                        <h3 class="card-title"><?= htmlspecialchars($chart_title) ?></h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;"><?= htmlspecialchars($chart_subtitle) ?></p>
                    </div>
                    <select class="card-select" id="salesPeriod" onchange="changePeriod(this.value)">
                        <option value="weekly"  <?= $period === 'weekly'  ? 'selected' : '' ?>>Weekly</option>
                        <option value="monthly" <?= ($period === 'monthly' || $period === '') ? 'selected' : '' ?>>Monthly</option>
                        <option value="yearly"  <?= $period === 'yearly'  ? 'selected' : '' ?>>Yearly</option>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Sales by Category -->
            <div class="card" style="grid-column: span 4;">
                <div class="card-header">
                    <h3 class="card-title">Sales by Category</h3>
                    <a class="card-action">Details</a>
                </div>
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px 0;">
                    <div style="width: 240px; height: 240px; margin-bottom: 24px;">
                        <div class="category-chart-container" style="height: 240px;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                    <div class="category-legend" id="categoryLegend"></div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card" style="grid-column: span 6;">
                <div class="card-header">
                    <h3 class="card-title">Recent Orders</h3>
                    <a href="orders.php" class="card-action">View All</a>
                </div>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                            <tr>
                                <td class="order-id">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="order-status <?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                    No recent orders found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ── TOP PRODUCTS (image left, no rank number) ── -->
            <div class="card" style="grid-column: span 3;">
                <div class="card-header">
                    <h3 class="card-title">Top Products</h3>
                    <a class="card-action">See All</a>
                </div>
                <div class="product-list">
                    <?php if (mysqli_num_rows($top_products) > 0): ?>
                        <?php while($product = mysqli_fetch_assoc($top_products)): ?>
                        <div class="product-item">
                            <!-- Product image / placeholder -->
                            <div class="product-thumb">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="../images/products/<?php echo htmlspecialchars($product['image']); ?>"
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         onerror="this.src='../images/products/default.jpg'; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <span class="product-thumb-placeholder" style="display:none;">📦</span>
                                <?php else: ?>
                                    <span class="product-thumb-placeholder">📦</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-sales"><?php echo number_format($product['total_sales']); ?> sold</div>
                            </div>
                            <div class="product-revenue">
                                ₱<?php
                                    $rev = $product['revenue'];
                                    echo $rev >= 1000000
                                        ? number_format($rev / 1000000, 1) . 'M'
                                        : ($rev >= 1000 ? number_format($rev / 1000, 1) . 'K' : number_format($rev, 0));
                                ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: var(--text-muted);">
                            <i class="fas fa-box-open" style="font-size: 1.5rem; margin-bottom: 8px; display: block;"></i>
                            No product data yet
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── TOP CUSTOMERS (avatar left, no rank number) ── -->
            <div class="card" style="grid-column: span 3;">
                <div class="card-header">
                    <h3 class="card-title">Top Customers</h3>
                    <a href="customers.php" class="card-action">View All</a>
                </div>
                <div class="customer-list">
                    <?php if (mysqli_num_rows($top_customers) > 0):
                        $ci = 0;
                        while($customer = mysqli_fetch_assoc($top_customers)):
                            $palette = $avatar_colors[$ci % count($avatar_colors)];
                            $initials = getInitials($customer['full_name']);
                            $ci++;
                    ?>
                    <div class="customer-item">
                        <!-- Avatar: real image or initials circle -->
                        <div class="customer-avatar"
                             style="background: <?php echo $palette['bg']; ?>; color: <?php echo $palette['color']; ?>;">
                            <?php if (!empty($customer['avatar'])): ?>
                                <img src="../images/avatars/<?php echo htmlspecialchars($customer['avatar']); ?>"
                                     alt="<?php echo htmlspecialchars($customer['full_name']); ?>"
                                     onerror="this.style.display='none'; this.parentElement.textContent='<?php echo $initials; ?>';">
                            <?php else: ?>
                                <?php echo $initials; ?>
                            <?php endif; ?>
                        </div>
                        <div class="customer-info">
                            <div class="customer-name"><?php echo htmlspecialchars($customer['full_name']); ?></div>
                            <div class="customer-orders">#C-<?php echo str_pad(1000 + $customer['id'], 4, '0', STR_PAD_LEFT); ?> · <?php echo number_format($customer['order_count']); ?> orders</div>
                        </div>
                        <div class="customer-spent">
                            ₱<?php
                                $spent = $customer['spent'];
                                echo $spent >= 1000000
                                    ? number_format($spent / 1000000, 1) . 'M'
                                    : ($spent >= 1000 ? number_format($spent / 1000, 1) . 'K' : number_format($spent, 0));
                            ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: var(--text-muted);">
                            <i class="fas fa-users" style="font-size: 1.5rem; margin-bottom: 8px; display: block;"></i>
                            No customer data yet
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /dashboard-grid -->
    </div><!-- /content -->
</div><!-- /main -->

<script>
    // Clock and Date
    function updateClock() {
        const now = new Date();
        let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        document.getElementById('live-clock').textContent =
            String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0') + ' ' + ampm;
    }
    updateClock();
    setInterval(updateClock, 1000);
    const opts = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
    document.getElementById('today-date').textContent = new Date().toLocaleDateString('en-US', opts);

    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const gradient = salesCtx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0.05)');

    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Sales',
                data: <?= json_encode($chart_values) ?>,
                borderColor: '#3b82f6',
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#3b82f6',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: '#3b82f6',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: ctx => '₱' + ctx.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 0, maximumFractionDigits: 0})
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148,163,191,0.15)', borderDash: [5,5] },
                    ticks: {
                        callback: val => '₱' + val.toLocaleString('en-PH', {minimumFractionDigits: 0, maximumFractionDigits: 0}),
                        color: '#94a3b8',
                        font: { family: 'Poppins', size: 11 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { family: 'Poppins', size: 11 } }
                }
            }
        }
    });

    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryData   = <?= json_encode($category_values) ?>;
    const categoryLabels = <?= json_encode($category_labels) ?>;
    const categoryColors = <?= json_encode($category_colors) ?>;
    const categoryTotal  = categoryData.reduce((a, b) => a + b, 0);
    const categoryPct    = categoryData.map(v => ((v / categoryTotal) * 100).toFixed(0) + '%');

    new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: categoryPct,
            datasets: [{
                data: categoryData,
                backgroundColor: categoryColors,
                borderWidth: 0,
                cutout: '40%',
                hoverOffset: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    padding: 12,
                    callbacks: {
                        label: ctx => '₱' + ctx.parsed.toLocaleString('en-PH') + ' (' + ((ctx.parsed / categoryTotal) * 100).toFixed(1) + '%)'
                    }
                }
            }
        }
    });

    // Category legend
    const legendContainer = document.getElementById('categoryLegend');
    categoryLabels.forEach((label, i) => {
        const item = document.createElement('div');
        item.className = 'category-legend-item';
        item.innerHTML = `
            <div class="category-dot" style="background-color:${categoryColors[i]}"></div>
            <span class="category-percentage">${categoryPct[i]}</span>
            <span class="category-name">${label}</span>
        `;
        legendContainer.appendChild(item);
    });

    function changePeriod(period) {
        window.location.href = 'dashboard.php?period=' + period;
    }
</script>

</body>
</html>