<?php
session_start();
require 'assets/php/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['order'] ?? 0);

if ($order_id <= 0) {
    header('Location: profile.php?tab=orders&error=Invalid order');
    exit();
}

// Fetch order details
$stmt = mysqli_prepare($conn, "SELECT id, user_id, status, total_amount FROM orders WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$order) {
    header('Location: profile.php?tab=orders&error=Order not found');
    exit();
}

// Check if order belongs to user
if ($order['user_id'] != $user_id) {
    header('Location: profile.php?tab=orders&error=Unauthorized access');
    exit();
}

// Check if order can be cancelled (only pending and processing)
$cancellable_statuses = ['pending', 'processing'];
if (!in_array(strtolower($order['status']), $cancellable_statuses)) {
    header('Location: profile.php?tab=orders&error=' . urlencode('Order cannot be cancelled - status: ' . $order['status']));
    exit();
}

// Cancel the order
$cancel_stmt = mysqli_prepare($conn, "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
$cancel_status = 'cancelled';
mysqli_stmt_bind_param($cancel_stmt, 'si', $cancel_status, $order_id);

if (mysqli_stmt_execute($cancel_stmt)) {
    mysqli_stmt_close($cancel_stmt);
    header('Location: profile.php?tab=orders&success=' . urlencode('Order cancelled successfully'));
    exit();
} else {
    mysqli_stmt_close($cancel_stmt);
    header('Location: profile.php?tab=orders&error=' . urlencode('Failed to cancel order. Please try again.'));
    exit();
}
?>