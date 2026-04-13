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
    /* Track Order Page Specific Styles */
    body { 
      background: #f1f5f9; 
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
    }

    .track-order-page {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .main-container {
      max-width: 900px;
      padding: 3.5rem 2rem;
      margin: 0 auto;
      width: 100%;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
    }

    /* ══ HERO ══ */
    .cart-hero {
      position: relative;
      background: linear-gradient(135deg, #e8f0fe 0%, #f0f4ff 40%, #e4eefb 100%);
      text-align: center;
      padding: 52px 25px;
      overflow: hidden;
      border-bottom: 1px solid #d6e4f7;
    }
    .cart-hero::before {
      content: '';
      position: absolute;
      width: 320px; height: 320px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(99,155,255,0.18) 0%, transparent 70%);
      top: -80px; left: -60px;
      pointer-events: none;
    }
    .cart-hero::after {
      content: '';
      position: absolute;
      width: 260px; height: 260px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(139,92,246,0.1) 0%, transparent 70%);
      bottom: -60px; right: -40px;
      pointer-events: none;
    }
    .cart-hero-title {
      font-size: 4rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      color: #3b82f6;
      margin-bottom: 19px;
      position: relative;
      z-index: 1;
      line-height: 1.15;
    }
    .cart-hero-sub {
      font-size: 14px;
      color: #6b7faa;
      font-weight: 400;
      margin-bottom: 20px;
      position: relative;
      z-index: 1;
    }
    .cart-hero-bc {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255,255,255,0.6);
      border: 1px solid rgba(180,205,255,0.5);
      backdrop-filter: blur(6px);
      border-radius: 30px;
      padding: 6px 18px;
      font-size: 13px;
      color: #8aa0c8;
      position: relative;
      z-index: 1;
    }
    .cart-hero-bc a {
      color: #7a99cc;
      text-decoration: none;
      font-weight: 500;
      transition: color .2s;
    }
    .cart-hero-bc a:hover { color: #3b82f6; }
    .cart-bc-sep { color: #b0c4e8; font-size: 12px; }

    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #3b82f6;
      text-decoration: none;
      font-weight: 600;
      margin-bottom: 30px;
      transition: all 0.2s;
    }
    .back-btn:hover { gap: 12px; color: #2563eb; }

    /* Main Content Card - Centered in viewport */
    .content-card {
      background: white;
      border-radius: 20px;
      padding: 50px 40px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
      margin-bottom: 24px;
    }

    .page-header { 
      margin-bottom: 50px; 
      text-align: center;
    }
    .page-title { 
      font-size: 32px; 
      font-weight: 700; 
      color: #1e293b; 
      margin-bottom: 8px; 
    }
    .order-id { 
      color: #64748b; 
      font-size: 14px; 
    }

    /* Status Tracker - LIGHT GREEN INDICATOR ONLY */
    .status-tracker { 
      margin: 60px 0;
    }
    .tracker-steps { 
      display: flex; 
      justify-content: space-between; 
      position: relative; 
      margin-top: 60px;
      align-items: flex-start;
    }
    .tracker-steps::before { 
      content: ''; 
      position: absolute; 
      top: 28px; 
      left: 0; 
      right: 0; 
      height: 4px; 
      background: #e2e8f0; 
      border-radius: 2px; 
      z-index: 0; 
    }
    /* LIGHT GREEN PROGRESS BAR */
    .tracker-progress { 
      position: absolute; 
      top: 28px; 
      left: 0; 
      height: 4px; 
      background: linear-gradient(135deg, #6ee7b7 0%, #34d399 100%); 
      border-radius: 2px; 
      z-index: 1; 
      transition: width 0.6s ease; 
    }

    .step { 
      position: relative; 
      z-index: 2; 
      text-align: center; 
      flex: 1; 
    }
    .step-icon { 
      width: 60px; 
      height: 60px; 
      border-radius: 50%; 
      background: white; 
      border: 4px solid #e2e8f0; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      margin: 0 auto 16px; 
      font-size: 24px; 
      color: #94a3b8; 
      transition: all 0.3s ease; 
    }
    /* LIGHT GREEN COMPLETED STEPS */
    .step.completed .step-icon { 
      background: linear-gradient(135deg, #6ee7b7 0%, #34d399 100%); 
      border-color: #6ee7b7; 
      color: white; 
      transform: scale(1.1); 
    }
    /* LIGHT GREEN ACTIVE STEP */
    .step.active .step-icon { 
      background: linear-gradient(135deg, #6ee7b7 0%, #34d399 100%); 
      border-color: #6ee7b7; 
      color: white; 
      box-shadow: 0 0 0 8px rgba(110,231,183,0.2); 
      animation: pulse 2s infinite; 
    }

    @keyframes pulse { 
      0%, 100% { box-shadow: 0 0 0 8px rgba(110,231,183,0.2); } 
      50% { box-shadow: 0 0 0 12px rgba(110,231,183,0.15); } 
    }

    .step-label { 
      font-size: 14px; 
      font-weight: 600; 
      color: #64748b; 
      margin-bottom: 8px; 
    }
    .step.completed .step-label, 
    .step.active .step-label { 
      color: #1e293b; 
    }
    .step-date { 
      font-size: 12px; 
      color: #94a3b8; 
      line-height: 1.5; 
    }
    .step-date small { 
      display: block; 
      font-size: 11px; 
    }
    .step.completed .step-date, 
    .step.active .step-date { 
      color: #64748b; 
    }

    /* Products Section */
    .section-title { 
      font-size: 20px; 
      font-weight: 700; 
      color: #1e293b; 
      margin-bottom: 24px; 
      padding-bottom: 16px; 
      border-bottom: 2px solid #f1f5f9; 
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .section-title i {
      color: #3b82f6;
    }

    .product-item { 
      display: flex; 
      align-items: center; 
      gap: 20px; 
      padding: 20px 0; 
      border-bottom: 1px solid #f1f5f9; 
      transition: background 0.2s; 
    }
    .product-item:hover { 
      background: #f8fafc; 
      margin: 0 -16px; 
      padding-left: 16px; 
      padding-right: 16px; 
      border-radius: 12px; 
    }
    .product-item:last-child { 
      border-bottom: none; 
    }

    .product-image { 
      width: 80px; 
      height: 80px; 
      border-radius: 12px; 
      object-fit: cover; 
      background: #f8fafc; 
      border: 2px solid #e2e8f0; 
      flex-shrink: 0; 
    }
    .product-info { 
      flex: 1; 
    }
    .product-name { 
      font-size: 16px; 
      font-weight: 600; 
      color: #1e293b; 
      margin-bottom: 6px; 
    }
    .product-meta { 
      font-size: 13px; 
      color: #94a3b8; 
    }
    .product-qty { 
      font-size: 14px; 
      color: #64748b; 
      font-weight: 600; 
      background: #f1f5f9; 
      padding: 6px 14px; 
      border-radius: 8px; 
    }

    @media (max-width: 768px) {
      .main-container { 
        padding: 1.5rem; 
        min-height: calc(100vh - 200px);
      }
      .content-card { 
        padding: 30px 20px; 
      }
      .cart-hero-title { 
        font-size: 2.5rem; 
      }
      
      .tracker-steps { 
        flex-direction: column; 
        gap: 24px; 
      }
      .tracker-steps::before, 
      .tracker-progress { 
        display: none; 
      }
      .step { 
        display: flex; 
        align-items: center; 
        gap: 16px; 
        text-align: left; 
      }
      .step-icon { 
        margin: 0; 
      }
      .product-item { 
        flex-wrap: wrap; 
      }
      .product-image { 
        width: 60px; 
        height: 60px; 
      }
    }
  </style>
</head>
<body>

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

// Fetch order details
$stmt = mysqli_prepare($conn, "
    SELECT o.*, u.full_name as customer_name, u.email, u.phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
mysqli_stmt_bind_param($stmt, 'ii', $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) {
    header('Location: profile.php?tab=orders');
    exit;
}

// Fetch order items
$items_stmt = mysqli_prepare($conn, "
    SELECT oi.*, p.name as product_name, p.image as product_image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
mysqli_stmt_bind_param($items_stmt, 'i', $order_id);
mysqli_stmt_execute($items_stmt);
$items = mysqli_fetch_all(mysqli_stmt_get_result($items_stmt), MYSQLI_ASSOC);
mysqli_stmt_close($items_stmt);

// Status timeline configuration
$status_timeline = [
    'pending' => ['label' => 'Order Placed', 'icon' => '<i class="fas fa-box"></i>', 'date' => $order['order_date']],
    'accepted' => ['label' => 'Accepted', 'icon' => '<i class="fas fa-check"></i>', 'date' => null],
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

<!-- ══ HERO ══ -->
<div class="cart-hero">
  <div class="cart-hero-title">Track Order</div>
  <nav class="cart-hero-bc" aria-label="breadcrumb">
    <a href="index.php">Home</a>
    <span class="cart-bc-sep">›</span>
    <span>Order #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></span>
  </nav>
</div>

<main class="track-order-page">
  <div class="main-container">
    <a href="profile.php?tab=orders" class="back-btn">
      <i class="fas fa-arrow-left"></i> Back to Orders
    </a>

    <!-- Single Content Card - Vertically Centered -->
    <div class="content-card">
      <div class="page-header">
        <h1 class="page-title">Order Status</h1>
        <p class="order-id">Order ID: #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></p>
      </div>

      <!-- Status Tracker - LIGHT GREEN INDICATOR ONLY -->
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

      <!-- Products Section -->
      <div style="margin-top: 60px;">
        <h2 class="section-title">
          <i class="fas fa-box-open"></i>
          Products in This Order
        </h2>
        
        <?php foreach ($items as $item): ?>
        <div class="product-item">
            <?php if ($item['product_image']): ?>
                <img src="images/products/<?= htmlspecialchars($item['product_image']) ?>" 
                     alt="<?= htmlspecialchars($item['product_name']) ?>" 
                     class="product-image">
            <?php else: ?>
                <div class="product-image" style="display:flex;align-items:center;justify-content:center;background:#f1f5f9;">
                    <i class="fas fa-image" style="color:#cbd5e1;font-size:24px;"></i>
                </div>
            <?php endif; ?>
            
            <div class="product-info">
                <div class="product-name"><?= htmlspecialchars($item['product_name']) ?></div>
                <div class="product-meta">
                    ₱<?= number_format($item['price'], 2) ?>
                </div>
            </div>
            <div class="product-qty">Qty: <?= $item['quantity'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>