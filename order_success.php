<?php
session_start();
require_once 'assets/php/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = (int)$_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Get order details
$order_query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, 'ii', $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    header('Location: index.php');
    exit;
}

// Get order items
$items_query = "SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
$stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
$items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = $item;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Success - Vertex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        .success-container { max-width: 800px; margin: 4rem auto; padding: 0 1rem; text-align: center; }
        .success-icon { font-size: 4rem; color: #22c55e; margin-bottom: 1rem; }
        .order-details { background: #f8f9fa; padding: 2rem; border-radius: 8px; margin-top: 2rem; text-align: left; }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar sticky-top">
        <div class="container navbar-inner">
            <a class="navbar-brand" href="index.php">
                <img src="images/brand.png" alt="Vertex" />
            </a>
        </div>
    </nav>

    <div class="success-container">
        <div class="success-icon">✓</div>
        <h1 class="mb-3">Order Placed Successfully!</h1>
        <p class="text-muted mb-4">Thank you for your order. We'll send you shipping updates at your email.</p>

        <div class="order-details">
            <h5>Order #<?php echo $order_id; ?></h5>
            <p><strong>Total:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
            <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
            <p><strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></p>
            <p><strong>Shipping Address:</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>

            <hr>
            <h6>Items Ordered:</h6>
            <ul class="list-group list-group-flush">
                <?php foreach ($items as $item): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo htmlspecialchars($item['name']); ?>
                        <span>Qty: <?php echo $item['quantity']; ?> × ₱<?php echo number_format($item['price'], 2); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="mt-4">
            <a href="index.php" class="btn btn-primary me-2">Continue Shopping</a>
            <a href="#" class="btn btn-outline-primary">View Order History</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>