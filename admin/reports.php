<?php
require_once 'auth_check.php';
require_once 'assets/php/db.php';

// Fetch analytics data
$today = date('Y-m-d');
$period = $_GET['period'] ?? '30days';

// Date range calculation
switch($period) {
    case '7days': $start_date = date('Y-m-d', strtotime('-7 days')); break;
    case '30days': $start_date = date('Y-m-d', strtotime('-30 days')); break;
    case '90days': $start_date = date('Y-m-d', strtotime('-90 days')); break;
    case 'year': $start_date = date('Y-01-01'); break;
    default: $start_date = date('Y-m-d', strtotime('-30 days'));
}

// Revenue data
$revenue_data = mysqli_query($conn, "
    SELECT DATE(order_date) as date, SUM(total_amount) as total
    FROM orders 
    WHERE status != 'cancelled' AND order_date >= '$start_date' AND order_date <= '$today'
    GROUP BY DATE(order_date)
    ORDER BY date ASC
");

$revenue_labels = [];
$revenue_values = [];
while($row = mysqli_fetch_assoc($revenue_data)) {
    $revenue_labels[] = date('M j', strtotime($row['date']));
    $revenue_values[] = floatval($row['total']);
}

// Category sales
$category_sales = mysqli_query($conn, "
    SELECT c.name, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled' AND o.order_date >= '$start_date'
    GROUP BY c.id
    ORDER BY revenue DESC
    LIMIT 6
");

$category_labels = [];
$category_values = [];
while($row = mysqli_fetch_assoc($category_sales)) {
    $category_labels[] = $row['name'];
    $category_values[] = floatval($row['revenue']);
}

// Order status distribution removed

// Top products by revenue
$top_products = mysqli_query($conn, "
    SELECT p.name, SUM(oi.quantity * oi.price) as revenue, SUM(oi.quantity) as units
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled' AND o.order_date >= '$start_date'
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 10
");

// Customer acquisition removed

// Key metrics
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(total_amount), 0) as total 
    FROM orders 
    WHERE status != 'cancelled' AND order_date >= '$start_date'
"))['total'];

$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM orders 
    WHERE order_date >= '$start_date'
"))['total'];

$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
$new_customers = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM users 
    WHERE created_at >= '$start_date'
"))['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Vertex Admin — Reports</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --navy:      #0f172a;
      --border-dk: rgba(255,255,255,0.07);
      --accent:    #3b82f6;
      --accent-dark: #2563eb;
      --border:    #e2e8f0;
      --bg:        #f8fafc;
      --card:      #ffffff;
      --text:      #0f172a;
      --text-2:    #64748b;
      --text-muted:#94a3b8;
      --sidebar-w: 240px;
      --danger:    #ef4444;
      --success:   #10b981;
      --warning:   #f59e0b;
      --purple:    #a855f7;
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

    .topbar { background: var(--card); border-bottom: 1px solid var(--border); padding: 0 36px; height: 85px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
    .topbar-title { font-size: 1.4rem; font-weight: 700; color: var(--text); }
    .topbar-sub { font-size: 1rem; color: var(--text-muted); margin-top: 2px; font-weight: 500; }
    .topbar-left { display: flex; flex-direction: column; }
    .topbar-actions { display: flex; align-items: center; gap: 12px; }

    .content { padding: 28px; flex: 1; }

    /* Filters Bar */
    .filters-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      margin-bottom: 24px;
      flex-wrap: wrap;
      gap: 16px;
    }

    .filter-group {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .filter-label {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-2);
    }

    .filter-select {
      padding: 8px 14px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 500;
      color: var(--text);
      background: var(--bg);
      cursor: pointer;
      outline: none;
      transition: all 0.2s;
    }

    .filter-select:hover,
    .filter-select:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }

    .export-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .export-btn:hover {
      background: var(--accent-dark);
      transform: translateY(-1px);
    }

    .export-btn.secondary {
      background: var(--bg);
      color: var(--text);
      border: 1px solid var(--border);
    }

    .export-btn.secondary:hover {
      background: #e2e8f0;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 24px;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      margin-bottom: 16px;
    }

    .stat-card.blue .stat-icon { background: #dbeafe; color: #1e40af; }
    .stat-card.green .stat-icon { background: #dcfce7; color: #166534; }
    .stat-card.amber .stat-icon { background: #fed7aa; color: #92400e; }
    .stat-card.purple .stat-icon { background: #e9d5ff; color: #6b21a8; }

    .stat-value {
      font-size: 1.8rem;
      font-weight: 800;
      color: var(--text);
      margin-bottom: 4px;
    }

    .stat-label {
      font-size: 0.9rem;
      color: var(--text-muted);
      font-weight: 500;
    }

    .stat-change {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 0.8rem;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 20px;
      margin-top: 8px;
    }

    .stat-change.up { background: #f0fdf4; color: #16a34a; }
    .stat-change.down { background: #fef2f2; color: #dc2626; }

    /* Charts Grid */
    .charts-grid {
      display: grid;
      grid-template-columns: repeat(12, 1fr);
      gap: 24px;
      margin-bottom: 24px;
    }

    .chart-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 24px;
    }

    .chart-card.full { grid-column: span 12; }
    .chart-card.half { grid-column: span 6; }
    .chart-card.third { grid-column: span 4; }

    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .chart-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text);
    }

    .chart-container {
      position: relative;
      height: 300px;
    }

    .chart-container.small {
      height: 240px;
    }

    /* Tables */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.9rem;
    }

    .data-table th {
      text-align: left;
      padding: 14px 16px;
      font-weight: 700;
      color: var(--text-muted);
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid var(--border);
    }

    .data-table td {
      padding: 16px;
      border-bottom: 1px solid var(--border);
      color: var(--text-2);
    }

    .data-table tr:hover {
      background: #f8fafc;
    }

    .data-table tr:last-child td {
      border-bottom: none;
    }

    .product-name {
      font-weight: 600;
      color: var(--text);
    }

    .revenue {
      font-weight: 700;
      color: var(--success);
    }

    /* Progress Bars */
    .progress-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .progress-item {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .progress-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .progress-label {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--text);
    }

    .progress-value {
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--text-2);
    }

    .progress-bar {
      height: 8px;
      background: var(--bg);
      border-radius: 4px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      border-radius: 4px;
      transition: width 0.6s ease;
    }

    .progress-fill.blue { background: var(--accent); }
    .progress-fill.green { background: var(--success); }
    .progress-fill.amber { background: var(--warning); }
    .progress-fill.purple { background: var(--purple); }

    /* Responsive */
    @media (max-width: 1200px) {
      .charts-grid {
        grid-template-columns: 1fr;
      }
      .chart-card.full,
      .chart-card.half,
      .chart-card.third {
        grid-column: span 1;
      }
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
      .filters-bar {
        flex-direction: column;
        align-items: stretch;
      }
      .filter-group {
        justify-content: space-between;
      }
    }

    /* Export Dropdown */
    .export-dropdown {
      position: relative;
    }

    .export-menu {
      position: absolute;
      top: 100%;
      right: 0;
      margin-top: 8px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 8px;
      min-width: 180px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
      display: none;
      z-index: 100;
    }

    .export-menu.show {
      display: block;
    }

    .export-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 500;
      color: var(--text);
      cursor: pointer;
      transition: background 0.2s;
    }

    .export-item:hover {
      background: var(--bg);
    }

    .export-item i {
      width: 16px;
      text-align: center;
      color: var(--text-muted);
    }
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
      <li><a href="customers.php"><i class="fas fa-users"></i> Customers</a></li>
      <li><a href="support.php"><i class="fas fa-envelope"></i> Support</a></li>
      <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
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
    <span class="topbar-title">Reports & Analytics</span>
    <div class="topbar-actions">
      <div class="export-dropdown">
        <button class="export-btn" onclick="toggleExportMenu()">
          <i class="fas fa-download"></i> Export
        </button>
        <div class="export-menu" id="exportMenu">
          <div class="export-item" onclick="exportReport('pdf')">
            <i class="fas fa-file-pdf"></i> Export as PDF
          </div>
          <div class="export-item" onclick="exportReport('csv')">
            <i class="fas fa-file-csv"></i> Export as CSV
          </div>
          <div class="export-item" onclick="exportReport('excel')">
            <i class="fas fa-file-excel"></i> Export as Excel
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    
    <!-- Filters Bar -->
    <div class="filters-bar">
      <div class="filter-group">
        <span class="filter-label">Date Range:</span>
        <select class="filter-select" id="dateRange" onchange="updateReportPeriod()">
          <option value="7days" <?= $period === '7days' ? 'selected' : '' ?>>Last 7 Days</option>
          <option value="30days" <?= $period === '30days' ? 'selected' : '' ?>>Last 30 Days</option>
          <option value="90days" <?= $period === '90days' ? 'selected' : '' ?>>Last 90 Days</option>
          <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>This Year</option>
        </select>
      </div>
      <div class="filter-group">
        <button class="export-btn secondary" onclick="refreshData()">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="stats-grid">
      <div class="stat-card blue">
        <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
        <div class="stat-value">₱<?= number_format($total_revenue, 0) ?></div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> 12.4% vs last period</div>
      </div>
      <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-value"><?= number_format($total_orders) ?></div>
        <div class="stat-label">Total Orders</div>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> 8.1% vs last period</div>
      </div>
      <div class="stat-card amber">
        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
        <div class="stat-value">₱<?= number_format($avg_order_value, 2) ?></div>
        <div class="stat-label">Avg. Order Value</div>
        <div class="stat-change down"><i class="fas fa-arrow-down"></i> 2.3% vs last period</div>
      </div>
      <div class="stat-card purple">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-value"><?= number_format($new_customers) ?></div>
        <div class="stat-label">New Customers</div>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> 5.7% vs last period</div>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid">
      
      <!-- Revenue Trend -->
      <div class="chart-card full">
        <div class="chart-header">
          <h3 class="chart-title">Revenue Trend</h3>
        </div>
        <div class="chart-container">
          <canvas id="revenueChart"></canvas>
        </div>
      </div>

      <!-- Sales by Category -->
      <div class="chart-card half">
        <div class="chart-header">
          <h3 class="chart-title">Sales by Category</h3>
        </div>
        <div class="chart-container small">
          <canvas id="categoryChart"></canvas>
        </div>
      </div>

      <!-- Top Products -->
      <div class="chart-card half">
        <div class="chart-header">
          <h3 class="chart-title">Top Products by Revenue</h3>
        </div>
        <div class="chart-container small">
          <canvas id="productsChart"></canvas>
        </div>
      </div>

      <!-- Top Products Table -->
      <div class="chart-card full">
        <div class="chart-header">
          <h3 class="chart-title">Top Performing Products</h3>
        </div>
        <div style="overflow-x: auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Units Sold</th>
                <th>Revenue</th>
                <th>Avg. Rating</th>
              </tr>
            </thead>
            <tbody>
              <?php while($product = mysqli_fetch_assoc($top_products)): ?>
              <tr>
                <td class="product-name"><?= htmlspecialchars($product['name']) ?></td>
                <td>Laptops</td>
                <td><?= number_format($product['units']) ?></td>
                <td class="revenue">₱<?= number_format($product['revenue'], 2) ?></td>
                <td>
                  <i class="fas fa-star" style="color: #f59e0b;"></i>
                  <span style="margin-left: 4px;">4.8</span>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </div>
</div>

<script>
// Toggle export menu
function toggleExportMenu() {
  document.getElementById('exportMenu').classList.toggle('show');
}

// Close export menu when clicking outside
document.addEventListener('click', function(e) {
  if (!e.target.closest('.export-dropdown')) {
    document.getElementById('exportMenu').classList.remove('show');
  }
});

// Update report period
function updateReportPeriod() {
  const period = document.getElementById('dateRange').value;
  window.location.href = 'reports.php?period=' + period;
}

// Refresh data
function refreshData() {
  location.reload();
}

// Export functionality
function exportReport(format) {
  const period = document.getElementById('dateRange').value;
  
  if (format === 'pdf') {
    exportToPDF();
  } else if (format === 'csv') {
    exportToCSV();
  } else if (format === 'excel') {
    exportToExcel();
  }
  
  toggleExportMenu();
}

// Export to PDF
function exportToPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  
  doc.setFontSize(18);
  doc.text('Vertex E-Commerce Report', 14, 22);
  doc.setFontSize(11);
  doc.text(`Period: ${document.getElementById('dateRange').value}`, 14, 30);
  doc.text(`Generated: ${new Date().toLocaleDateString()}`, 14, 36);
  
  // Add summary table
  doc.autoTable({
    startY: 45,
    head: [['Metric', 'Value']],
    body: [
      ['Total Revenue', '₱<?= number_format($total_revenue, 2) ?>'],
      ['Total Orders', '<?= number_format($total_orders) ?>'],
      ['Avg. Order Value', '₱<?= number_format($avg_order_value, 2) ?>'],
      ['New Customers', '<?= number_format($new_customers) ?>']
    ],
  });
  
  doc.save('vertex-report.pdf');
}

// Export to CSV
function exportToCSV() {
  const headers = ['Metric', 'Value', 'Change'];
  const rows = [
    ['Total Revenue', '<?= number_format($total_revenue, 2) ?>', '+12.4%'],
    ['Total Orders', '<?= number_format($total_orders) ?>', '+8.1%'],
    ['Avg. Order Value', '<?= number_format($avg_order_value, 2) ?>', '-2.3%'],
    ['New Customers', '<?= number_format($new_customers) ?>', '+5.7%']
  ];
  
  let csv = headers.join(',') + '\n';
  rows.forEach(row => {
    csv += row.join(',') + '\n';
  });
  
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'vertex-report.csv';
  a.click();
}

// Export to Excel (simplified)
function exportToExcel() {
  // In production, use a library like SheetJS
  alert('Excel export would generate an .xlsx file with all report data.');
}

// Charts initialization
document.addEventListener('DOMContentLoaded', function() {
  
  // Revenue Chart
  const revenueCtx = document.getElementById('revenueChart').getContext('2d');
  new Chart(revenueCtx, {
    type: 'line',
    data: {
      labels: <?= json_encode($revenue_labels) ?>,
      datasets: [{
        label: 'Revenue',
        data: <?= json_encode($revenue_values) ?>,
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#3b82f6',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { callback: v => '₱' + v.toLocaleString() }
        }
      }
    }
  });

  // Category Chart
  const categoryCtx = document.getElementById('categoryChart').getContext('2d');
  new Chart(categoryCtx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($category_labels) ?>,
      datasets: [{
        label: 'Revenue',
        data: <?= json_encode($category_values) ?>,
        backgroundColor: ['#3b82f6', '#22c55e', '#f59e0b', '#a855f7', '#ef4444', '#8b5cf6']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => '₱' + v.toLocaleString() } }
      }
    }
  });

  // Products Chart
  const productsCtx = document.getElementById('productsChart').getContext('2d');
  const productNames = [];
  const productRevenues = [];
  <?php mysqli_data_seek($top_products, 0); while($p = mysqli_fetch_assoc($top_products)): ?>
  productNames.push('<?= addslashes($p['name']) ?>');
  productRevenues.push(<?= floatval($p['revenue']) ?>);
  <?php endwhile; ?>
  
  new Chart(productsCtx, {
    type: 'bar',
    data: {
      labels: productNames.slice(0, 5),
      datasets: [{
        label: 'Revenue',
        data: productRevenues.slice(0, 5),
        backgroundColor: '#3b82f6',
        borderRadius: 4
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { beginAtZero: true, ticks: { callback: v => '₱' + v.toLocaleString() } }
      }
    }
  });

});
</script>

</body>
</html>