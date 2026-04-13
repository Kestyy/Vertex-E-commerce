<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Track Order — Vertex</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/style.css"/>
  <style>
    .track-order-page { margin: 0; padding: 0; }
    .cart-container { display: flex; flex-direction: column; gap: 0; }
    .page-header { margin-bottom: 30px; }
    .page-title { font-size: 28px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
    .order-id { color: #64748b; font-size: 14px; }
    .back-btn { display: inline-flex; align-items: center; gap: 8px; color: #3b82f6; text-decoration: none; font-weight: 600; margin-bottom: 20px; transition: all 0.2s; }
    .back-btn:hover { gap: 12px; color: #2563eb; }
    
    /* Status Tracker */
    .status-tracker { background: white; border-radius: 16px; padding: 40px; margin-bottom: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
    .tracker-steps { display: flex; justify-content: space-between; position: relative; margin-top: 50px; }
    .tracker-steps::before { content: ''; position: absolute; top: 24px; left: 0; right: 0; height: 4px; background: #e2e8f0; border-radius: 2px; z-index: 0; }
    .tracker-progress { position: absolute; top: 24px; left: 0; height: 4px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 2px; z-index: 1; transition: width 0.6s ease; }
    
    .step { position: relative; z-index: 2; text-align: center; flex: 1; }
    .step-icon { width: 52px; height: 52px; border-radius: 50%; background: white; border: 4px solid #e2e8f0; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 20px; color: #94a3b8; transition: all 0.3s ease; }
    .step.completed .step-icon { background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-color: #10b981; color: white; transform: scale(1.1); }
    .step.active .step-icon { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-color: #3b82f6; color: white; box-shadow: 0 0 0 6px rgba(59,130,246,0.15); animation: pulse 2s infinite; }
    @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 6px rgba(59,130,246,0.15); } 50% { box-shadow: 0 0 0 10px rgba(59,130,246,0.1); } }
    
    .step-label { font-size: 14px; font-weight: 600; color: #64748b; margin-bottom: 8px; }
    .step.completed .step-label, .step.active .step-label { color: #1e293b; }
    .step-date { font-size: 12px; color: #94a3b8; line-height: 1.5; }
    .step-date small { display: block; font-size: 11px; }
    .step.completed .step-date, .step.active .step-date { color: #64748b; }
    
    /* Products Section */
    .products-section { background: white; border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
    .section-title { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #f1f5f9; }
    
    .product-item { display: flex; align-items: center; gap: 20px; padding: 20px 0; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
    .product-item:hover { background: #f8fafc; margin: 0 -16px; padding-left: 16px; padding-right: 16px; border-radius: 12px; }
    .product-item:last-child { border-bottom: none; }
    
    .product-image { width: 80px; height: 80px; border-radius: 12px; object-fit: cover; background: #f8fafc; border: 2px solid #e2e8f0; flex-shrink: 0; }
    .product-info { flex: 1; }
    .product-name { font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 6px; }
    .product-meta { font-size: 13px; color: #94a3b8; }
    .product-qty { font-size: 14px; color: #64748b; font-weight: 600; background: #f1f5f9; padding: 6px 14px; border-radius: 8px; }
    
    /* Order Details */
    .details-section { background: white; border-radius: 16px; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
    .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
    
    .detail-block h4 { font-size: 14px; font-weight: 600; color: #64748b; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px; }
    .detail-block h4 i { color: #3b82f6; }
    .detail-content { font-size: 14px; color: #1e293b; line-height: 1.8; }
    .detail-content strong { font-weight: 600; color: #0f172a; }
    
    .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; background: #dbeafe; color: #1e40af; margin-top: 8px; }
    .status-badge::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
    
    .order-summary-box { background: #f8fafc; border-radius: 12px; padding: 20px; margin-top: 16px; }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
    .summary-row.total { border-top: 2px solid #e2e8f0; padding-top: 12px; margin-top: 12px; font-size: 18px; font-weight: 700; color: #1e293b; }
    
    @media (max-width: 768px) {
      .tracker-steps { flex-direction: column; gap: 24px; }
      .tracker-steps::before, .tracker-progress { display: none; }
      .step { display: flex; align-items: center; gap: 16px; text-align: left; }
      .step-icon { margin: 0; }
      .details-grid { grid-template-columns: 1fr; }
      .product-item { flex-wrap: wrap; }
      .product-image { width: 60px; height: 60px; }
    }
  </style>
</head>
<body>

<!-- Announce bar -->
<div class="announce-bar" id="announceBar">
  <div class="announce-inner">
    <span class="announce-text">
      🎉 GET FLAT <strong>20% OFF</strong> ON 1ST ORDER — USE CODE
      <span class="announce-code" onclick="copyCode(this)" title="Click to copy">VERTEX20</span>
    </span>
    <button class="announce-close"
      onclick="document.getElementById('announceBar').style.display='none'; sessionStorage.setItem('announceBarClosed','1');"
      aria-label="Close">×</button>
  </div>
</div>
<script>
  if (sessionStorage.getItem('announceBarClosed') === '1') {
    document.getElementById('announceBar').style.display = 'none';
  }
</script>

<?php include 'navbar.php'; ?>

<?php
require_once 'assets/php/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['order'] ?? 0);

if (!$order_id) {
    header('Location: profile.php?tab=orders');
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT o.*, u.full_name as customer_name, u.email, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) {
    header('Location: profile.php?tab=orders');
    exit;
}

$items_stmt = mysqli_prepare($conn, "SELECT oi.*, p.name as product_name, p.image as product_image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
mysqli_stmt_bind_param($items_stmt, 'i', $order_id);
mysqli_stmt_execute($items_stmt);
$items = mysqli_fetch_all(mysqli_stmt_get_result($items_stmt), MYSQLI_ASSOC);
mysqli_stmt_close($items_stmt);

$shipping_address = $order['shipping_address'] ?? null;

$status_timeline = [
    'pending' => ['label' => 'Order Placed', 'icon' => '<i class="fas fa-box"></i>', 'date' => $order['order_date']],
    'accepted' => ['label' => 'Accepted', 'icon' => '<i class="fas fa-check"></i>', 'date' => $order['updated_at']],
    'processing' => ['label' => 'In Progress', 'icon' => '<i class="fas fa-cog"></i>', 'date' => null],
    'shipped' => ['label' => 'On the Way', 'icon' => '<i class="fas fa-truck"></i>', 'date' => null],
    'delivered' => ['label' => 'Delivered', 'icon' => '<i class="fas fa-home"></i>', 'date' => null]
];

$current_status = strtolower($order['status']);
$status_order = ['pending', 'accepted', 'processing', 'shipped', 'delivered'];
$current_index = array_search($current_status, $status_order);
if ($current_index === false) $current_index = 0;

function formatDate($date) {
    if (!$date) return 'Expected';
    return date('d F Y', strtotime($date)) . '<br><small>' . date('h:i A', strtotime($date)) . '</small>';
}
?>

<!-- ══ TRACK ORDER HERO ══ -->
<div class="cart-hero">
  <div class="cart-hero-title">Track Order</div>
  <nav class="cart-hero-bc" aria-label="breadcrumb">
    <a href="index.php">Home</a>
    <span class="cart-bc-sep">›</span>
    <span>Order #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></span>
  </nav>
</div>

<main class="track-order-page">
  <div class="cart-container">
    <a href="profile.php?tab=orders" class="back-btn">
      <i class="fas fa-arrow-left"></i> Back to Orders
    </a>

    <div class="page-header">
      <h1 class="page-title">Order Status</h1>
      <p class="order-id">Order ID: #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></p>
    </div>

    <!-- Status Tracker -->
    <div class="status-tracker">
      <div class="tracker-steps">
        <div class="tracker-progress" style="width: <?= ($current_index / 4) * 100 ?>%"></div>
        <?php foreach ($status_timeline as $key => $step): 
          $step_index = array_search($key, $status_order);
          $is_completed = $step_index <= $current_index;
          $is_active = $step_index == $current_index;
          $class = $is_completed ? 'completed' : '';
          if ($is_active) $class .= ' active';
        ?>
        <div class="step <?= $class ?>">
          <div class="step-icon"><?= $step['icon'] ?></div>
          <div class="step-label"><?= $step['label'] ?></div>
          <div class="step-date"><?= formatDate($step['date']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Products -->
    <div class="products-section">
      <h2 class="section-title"><i class="fas fa-box-open" style="margin-right: 10px; color: #3b82f6;"></i>Products</h2>
      <?php foreach ($items as $item): ?>
      <div class="product-item">
        <?php if ($item['product_image']): ?>
          <img src="images/products/<?= htmlspecialchars($item['product_image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="product-image">
        <?php else: ?>
          <div class="product-image" style="display:flex;align-items:center;justify-content:center;background:#f1f5f9;">
            <i class="fas fa-image" style="color:#cbd5e1;font-size:24px;"></i>
          </div>
        <?php endif; ?>
        <div class="product-info">
          <div class="product-name"><?= htmlspecialchars($item['product_name']) ?></div>
          <div class="product-meta">₱<?= number_format($item['price'], 2) ?></div>
        </div>
        <div class="product-qty">Qty: <?= $item['quantity'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Order Details -->
    <div class="details-section">
      <div class="details-grid">
        <div class="detail-block">
          <h4><i class="fas fa-map-marker-alt"></i> Shipping Address</h4>
          <div class="detail-content">
            <?php if ($shipping_address): ?>
              <?= nl2br(htmlspecialchars($shipping_address)) ?>
            <?php else: ?>
              <span style="color: #94a3b8;">No shipping address provided</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="detail-block">
          <h4><i class="fas fa-receipt"></i> Order Summary</h4>
          <div class="order-summary-box">
            <div class="summary-row">
              <span>Order Date:</span>
              <strong><?= date('F d, Y', strtotime($order['order_date'])) ?></strong>
            </div>
            <div class="summary-row">
              <span>Payment Method:</span>
              <strong><?= ucfirst($order['payment_method']) ?></strong>
            </div>
            <div class="summary-row">
              <span>Status:</span>
              <span class="status-badge"><?= ucfirst($order['status']) ?></span>
            </div>
            <div class="summary-row total">
              <span>Total Amount:</span>
              <span>₱<?= number_format($order['total_amount'], 2) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
