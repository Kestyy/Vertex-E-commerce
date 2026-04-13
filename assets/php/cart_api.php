<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ── Helper: get total cart count ──────────────────────────────────────────
function getCartCount($conn, $user_id) {
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    return (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
}

// ── COUNT ─────────────────────────────────────────────────────────────────
if ($action === 'count') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => true, 'count' => 0]);
        exit;
    }
    echo json_encode(['success' => true, 'count' => getCartCount($conn, $_SESSION['user_id'])]);
    exit;
}

// ── All other actions require login ──────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue.']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$data    = json_decode(file_get_contents('php://input'), true);

// ── ADD ───────────────────────────────────────────────────────────────────
if ($action === 'add') {
    $product_id = (int)($data['product_id'] ?? 0);
    $qty_to_add = max(1, (int)($data['quantity'] ?? 1));

    if (!$product_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid product.']);
        exit;
    }

    // Check product exists and is active
    $chk = mysqli_prepare($conn, "SELECT id, stock_quantity FROM products WHERE id = ? AND status = 'active'");
    mysqli_stmt_bind_param($chk, 'i', $product_id);
    mysqli_stmt_execute($chk);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit;
    }

    $stock = (int) $product['stock_quantity'];

    // Check existing cart row
    $existing = mysqli_prepare($conn, "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($existing, 'ii', $user_id, $product_id);
    mysqli_stmt_execute($existing);
    $cart_row = mysqli_fetch_assoc(mysqli_stmt_get_result($existing));

    if ($cart_row) {
        $new_qty = $cart_row['quantity'] + $qty_to_add;
        if ($new_qty > $stock) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
            exit;
        }
        $upd = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'ii', $new_qty, $cart_row['id']);
        mysqli_stmt_execute($upd);
    } else {
        if ($qty_to_add > $stock) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
            exit;
        }
        $ins = mysqli_prepare($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($ins, 'iii', $user_id, $product_id, $qty_to_add);
        mysqli_stmt_execute($ins);
    }

    // Log activity (only if table exists — fails silently)
    @mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, details) 
        VALUES ($user_id, 'add_to_cart', 'Added product ID $product_id to cart (qty: $qty_to_add)')");

    echo json_encode(['success' => true, 'count' => getCartCount($conn, $user_id)]);
    exit;
}

// ── UPDATE ────────────────────────────────────────────────────────────────
if ($action === 'update') {
    $product_id = (int)($data['product_id'] ?? 0);
    $quantity   = (int)($data['quantity']   ?? 0);

    if ($quantity <= 0) {
        $del = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($del, 'ii', $user_id, $product_id);
        mysqli_stmt_execute($del);
    } else {
        $stk = mysqli_prepare($conn, "SELECT stock_quantity FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stk, 'i', $product_id);
        mysqli_stmt_execute($stk);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stk));

        if (!$row || $quantity > (int)$row['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
            exit;
        }

        $upd = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($upd, 'iii', $quantity, $user_id, $product_id);
        mysqli_stmt_execute($upd);
    }

    echo json_encode(['success' => true, 'count' => getCartCount($conn, $user_id)]);
    exit;
}

// ── REMOVE ────────────────────────────────────────────────────────────────
if ($action === 'remove') {
    $product_id = (int)($data['product_id'] ?? 0);

    $del = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($del, 'ii', $user_id, $product_id);
    mysqli_stmt_execute($del);

    echo json_encode(['success' => true, 'count' => getCartCount($conn, $user_id)]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
?>