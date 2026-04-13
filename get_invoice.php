<?php
/**
 * get_invoice.php
 * AJAX endpoint — returns a single order's invoice data as JSON.
 * Called by openInvoiceModal() in profile.php.
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'assets/php/db.php';

header('Content-Type: application/json; charset=utf-8');

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

$user_id  = (int) $_SESSION['user_id'];
$order_id = isset($_GET['order']) ? (int) $_GET['order'] : 0;

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

// ── Fetch order (must belong to this user) ──────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT o.id, o.order_number, o.total_amount, o.payment_method,
            o.status, o.order_date,
            u.full_name, u.email
     FROM orders o
     JOIN users u ON u.id = o.user_id
     WHERE o.id = ? AND o.user_id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'ii', $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

// ── Fetch order items ───────────────────────────────────────────────────────
$istmt = mysqli_prepare($conn,
    "SELECT oi.quantity, oi.price,
            p.name  AS name,
            p.image AS image
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?"
);
mysqli_stmt_bind_param($istmt, 'i', $order_id);
mysqli_stmt_execute($istmt);
$items_raw = mysqli_fetch_all(mysqli_stmt_get_result($istmt), MYSQLI_ASSOC);
mysqli_stmt_close($istmt);

// ── Build items array ───────────────────────────────────────────────────────
$items = [];
$subtotal = 0.0;

foreach ($items_raw as $row) {
    $line_total = (float) $row['price'] * (int) $row['quantity'];
    $subtotal  += $line_total;
    $items[] = [
        'name'  => $row['name'],
        'qty'   => (int) $row['quantity'],
        'price' => (float) $row['price'],
        'image' => $row['image'] ?? null,
    ];
}

// ── Derive shipping (total − subtotal) ─────────────────────────────────────
$total    = (float) $order['total_amount'];
$shipping = max(0.0, round($total - $subtotal, 2));

// ── Format order number (fallback if column missing) ───────────────────────
$order_number = $order['order_number']
    ?? ('#ORD-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT));

// ── Format date ─────────────────────────────────────────────────────────────
$order_date = !empty($order['order_date'])
    ? date('d F Y', strtotime($order['order_date']))
    : '—';

// ── Response ────────────────────────────────────────────────────────────────
echo json_encode([
    'order_id'       => $order['id'],
    'order_number'   => $order_number,
    'order_date'     => $order_date,
    'payment_method' => $order['payment_method'] ?? 'N/A',
    'status'         => $order['status'],
    'subtotal'       => round($subtotal, 2),
    'shipping'       => $shipping,
    'total'          => $total,
    'customer'       => [
        'name'  => $order['full_name'] ?? '',
        'email' => $order['email']     ?? '',
    ],
    'items' => $items,
], JSON_UNESCAPED_UNICODE);