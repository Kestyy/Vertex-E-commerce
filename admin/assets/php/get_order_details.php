<?php
session_start();
require_once '../../assets/php/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    exit('Unauthorized');
}

$orderId = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "
    SELECT o.*, u.full_name, u.email, u.phone
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
mysqli_stmt_bind_param($stmt, 'i', $orderId);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) {
    echo '<p class="text-muted">Order not found.</p>';
    exit;
}
?>

<div class="order-detail-row">
    <span class="order-detail-label">Order ID</span>
    <span class="order-detail-value">#<?php echo $order['id']; ?></span>
</div>
<div class="order-detail-row">
    <span class="order-detail-label">Customer</span>
    <span class="order-detail-value"><?php echo htmlspecialchars($order['full_name'] ?? 'Guest'); ?></span>
</div>
<div class="order-detail-row">
    <span class="order-detail-label">Email</span>
    <span class="order-detail-value"><?php echo htmlspecialchars($order['email'] ?? ''); ?></span>
</div>
<div class="order-detail-row">
    <span class="order-detail-label">Phone</span>
    <span class="order-detail-value"><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></span>
</div>
<div class="order-detail-row">
    <span class="order-detail-label">Shipping Address</span>
    <span class="order-detail-value"><?php echo htmlspecialchars($order['shipping_address'] ?? 'N/A'); ?></span>
</div>
<div class="order-detail-row">
    <span class="order-detail-label">Payment Method</span>
    <span class="order-detail-value"><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></span>
</div>
<div class="order-detail-row">
    <span class="order-detail-label">Order Date</span>
    <span class="order-detail-value"><?php echo date('F d, Y \a\t g:i A', strtotime($order['order_date'])); ?></span>
</div>
<div class="order-detail-row" style="border-top:2px solid var(--border);margin-top:10px;padding-top:10px;">
    <span class="order-detail-label"><strong>Total Amount</strong></span>
    <span class="order-detail-value"><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></span>
</div>
<?php if ($order['discount_amount'] > 0): ?>
<div class="order-detail-row">
    <span class="order-detail-label">Discount</span>
    <span class="order-detail-value">-₱<?php echo number_format($order['discount_amount'], 2); ?></span>
</div>
<?php endif; ?>