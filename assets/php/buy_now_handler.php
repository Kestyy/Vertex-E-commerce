<?php
// assets/php/buy_now_handler.php
// Securely handle "Buy Now" transactions without exposing product IDs in URL

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$qty        = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;

// Validate product ID
if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid product']);
    exit;
}

// Validate quantity
if ($qty <= 0) $qty = 1;
if ($qty > 100) $qty = 100;

// Verify product exists and is active
$stmt = mysqli_prepare($conn, "SELECT id, name, price, stock_quantity FROM products WHERE id = ? AND status = 'active'");
mysqli_stmt_bind_param($stmt, 'i', $product_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

// Check stock
if ($product['stock_quantity'] <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Product out of stock']);
    exit;
}

if ($qty > $product['stock_quantity']) {
    $qty = $product['stock_quantity'];
}

// Store in session (secure, no URL exposure)
$_SESSION['buy_now_item'] = [
    'product_id' => $product_id,
    'quantity'   => $qty,
    'timestamp'  => time()
];

// Return success with redirect URL
echo json_encode([
    'success' => true,
    'redirect' => 'checkout.php'
]);
exit;
