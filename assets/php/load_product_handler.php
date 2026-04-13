<?php
// assets/php/load_product_handler.php
// Securely load product details without exposing IDs in URL

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

// Validate product ID
if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid product']);
    exit;
}

// Verify product exists and is active
$stmt = mysqli_prepare($conn, "SELECT id FROM products WHERE id = ? AND status = 'active'");
mysqli_stmt_bind_param($stmt, 'i', $product_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

// Store in session (secure, no URL exposure)
$_SESSION['product_view_id'] = $product_id;

// Return success with redirect URL
echo json_encode([
    'success' => true,
    'redirect' => 'product_details.php'
]);
exit;
