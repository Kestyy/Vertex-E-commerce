<?php
session_start();
require_once 'assets/php/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Guard against malformed or missing JSON body
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Helper: get total cart item count for user
function getCartCount($conn, $user_id) {
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return (int) mysqli_fetch_assoc($result)['total'];
}

if (!isset($data['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing product_id.']);
    exit;
}

$product_id = (int) $data['product_id'];

// ── REMOVE ──────────────────────────────────────────────
if (isset($data['remove']) && $data['remove'] === true) {
    $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $product_id);
    mysqli_stmt_execute($stmt);

    echo json_encode([
        'success'    => true,
        'cart_count' => getCartCount($conn, $user_id)
    ]);
    exit;
}

// ── UPDATE QUANTITY ──────────────────────────────────────
if (isset($data['quantity'])) {
    $quantity = (int) $data['quantity'];

    if ($quantity <= 0) {
        // Treat quantity 0 as remove
        $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $product_id);
        mysqli_stmt_execute($stmt);
    } else {
        // Stock check
        $stock_check = mysqli_prepare($conn, "SELECT stock_quantity FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stock_check, 'i', $product_id);
        mysqli_stmt_execute($stock_check);
        $stock_result = mysqli_stmt_get_result($stock_check);
        $row          = mysqli_fetch_assoc($stock_result);

        if (!$row || $quantity > (int) $row['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
            exit;
        }

        $update = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($update, 'iii', $quantity, $user_id, $product_id);
        mysqli_stmt_execute($update);
    }

    echo json_encode([
        'success'    => true,
        'cart_count' => getCartCount($conn, $user_id)
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'No action specified.']);
?>