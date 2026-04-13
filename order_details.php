<?php
session_start();
require_once 'assets/php/db.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$orderId) {
    header('Location: profile.php?tab=orders');
    exit;
}

// Fetch order
$stmt = mysqli_prepare($conn, 'SELECT * FROM orders WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $orderId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$order) {
    header('Location: profile.php?tab=orders');
    exit;
}

// Ensure user can view the order (either the owner or an admin)
$ownerId = $order['user_id'];
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_id'] !== $ownerId && !$isAdmin)) {
    header('Location: profile.php?tab=orders');
    exit;
}

// Fetch order items
$items = [];
$stmt = mysqli_prepare($conn, 'SELECT oi.*, p.name, p.image, p.price as list_price FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $orderId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $items[] = $row;
}
mysqli_stmt_close($stmt);

// ✅ Use order_number for display
$order_display = htmlspecialchars($order['order_number'] ?? '#N/A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $order_display ?> • Vertex</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
  <?php include 'navbar.php'; ?>
  <main class="container" style="padding: 2rem 1rem;">
    <div class="page-header">
      <!-- ✅ Display order_number prominently -->
      <h1><?= $order_display ?></h1>
      <p>Order placed on <?php echo date('F j, Y', strtotime($order['order_date'])); ?> — status: <strong><?php echo htmlspecialchars($order['status']); ?></strong></p>
    </div>

    <div style="display:grid;grid-template-columns:1fr;gap:1.5rem;">
      <div style="padding:1.25rem;border:1px solid #e5e7eb;border-radius:12px;">
        <h2 style="margin-top:0;">Order Summary</h2>
        <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
          <div>
            <!-- ✅ Show order_number, keep ID internal -->
            <div><strong>Order Number</strong>: <?= $order_display ?></div>
            <div><strong>Status</strong>: <?php echo htmlspecialchars($order['status']); ?></div>
            <div><strong>Payment</strong>: <?php echo htmlspecialchars($order['payment_method']); ?></div>
          </div>
          <div>
            <div><strong>Shipping Address</strong></div>
            <div style="white-space:pre-wrap;"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
          </div>
        </div>
      </div>

      <div style="padding:1.25rem;border:1px solid #e5e7eb;border-radius:12px;">
        <h2 style="margin-top:0;">Items</h2>
        <?php if (empty($items)): ?>
          <p>No items found for this order.</p>
        <?php else: ?>
          <table style="width:100%;border-collapse:collapse;">
            <thead>
              <tr style="border-bottom:1px solid #e5e7eb;">
                <th style="text-align:left;padding:0.75rem;">Product</th>
                <th style="text-align:right;padding:0.75rem;">Qty</th>
                <th style="text-align:right;padding:0.75rem;">Unit Price</th>
                <th style="text-align:right;padding:0.75rem;">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <tr>
                  <td style="padding:0.75rem;">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                      <img src="images/products/<?php echo htmlspecialchars($item['image'] ?: 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width:48px;height:48px;border-radius:8px;object-fit:cover;" />
                      <span><?php echo htmlspecialchars($item['name']); ?></span>
                    </div>
                  </td>
                  <td style="padding:0.75rem;text-align:right;"><?php echo $item['quantity']; ?></td>
                  <td style="padding:0.75rem;text-align:right;">₱<?php echo number_format($item['price'], 2); ?></td>
                  <td style="padding:0.75rem;text-align:right;">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr style="border-top:1px solid #e5e7eb;">
                <td colspan="3" style="padding:0.75rem;text-align:right;font-weight:600;">Order Total</td>
                <td style="padding:0.75rem;text-align:right;font-weight:600;">₱<?php echo number_format($order['total_amount'], 2); ?></td>
              </tr>
            </tfoot>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <div style="margin-top:2rem;">
      <a href="profile.php?tab=orders" class="btn btn-outline">Back to Orders</a>
    </div>
  </main>
  <?php include 'footer.php'; ?>
</body>
</html>