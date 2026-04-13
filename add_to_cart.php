<?php
session_start();
require_once 'assets/php/db.php';
header('Content-Type: application/json');

// ── GET ?count=1  — called by JS on every page load to sync the badge ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['cart_count' => 0]);
        exit;
    }
    $uid  = $_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    $count = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    echo json_encode(['cart_count' => $count]);
    exit;
}

// ── POST — add item to cart ─────────────────────────────────────────────
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart.']);
    exit;
}

$user_id    = $_SESSION['user_id'];
$product_id = (int)($data['product_id'] ?? 0);
$qty_to_add = max(1, (int)($data['quantity'] ?? 1));

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product.']);
    exit;
}

// Check product exists and is active
$product_check = mysqli_prepare($conn, "SELECT id, stock_quantity FROM products WHERE id = ? AND status = 'active'");
mysqli_stmt_bind_param($product_check, 'i', $product_id);
mysqli_stmt_execute($product_check);
$result = mysqli_stmt_get_result($product_check);

if (mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}

$product   = mysqli_fetch_assoc($result);
$stock_qty = (int)$product['stock_quantity'];

// Check if item already exists in cart
$cart_check = mysqli_prepare($conn, "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
mysqli_stmt_bind_param($cart_check, 'ii', $user_id, $product_id);
mysqli_stmt_execute($cart_check);
$cart_result = mysqli_stmt_get_result($cart_check);

if (mysqli_num_rows($cart_result) > 0) {
    // Item exists — increment by qty_to_add
    $cart_item    = mysqli_fetch_assoc($cart_result);
    $new_quantity = $cart_item['quantity'] + $qty_to_add;

    if ($new_quantity > $stock_qty) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
        exit;
    }

    $update = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE id = ?");
    mysqli_stmt_bind_param($update, 'ii', $new_quantity, $cart_item['id']);
    mysqli_stmt_execute($update);
} else {
    // New cart item
    if ($qty_to_add > $stock_qty) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
        exit;
    }

    $insert = mysqli_prepare($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($insert, 'iii', $user_id, $product_id, $qty_to_add);
    mysqli_stmt_execute($insert);
}

// Return authoritative cart count so JS badge updates instantly
$count_stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ?");
mysqli_stmt_bind_param($count_stmt, 'i', $user_id);
mysqli_stmt_execute($count_stmt);
$cart_count = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];

// Log activity
$log     = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'add_to_cart', ?)");
$details = "Added product ID $product_id to cart (qty: $qty_to_add)";
mysqli_stmt_bind_param($log, 'is', $user_id, $details);
mysqli_stmt_execute($log);

echo json_encode(['success' => true, 'cart_count' => $cart_count]);
?>