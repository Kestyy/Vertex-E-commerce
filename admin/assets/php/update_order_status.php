<?php
require_once '../../auth_check.php';
require_once 'db.php';

header('Content-Type: application/json');
ob_clean();

try {
    $order_id = $_POST['order_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if (!$order_id || !$status) {
        throw new Exception('Order ID and status are required.');
    }

    // Validate status value
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status value.');
    }

    // Update order status
    $stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('si', $status, $order_id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update order: ' . $stmt->error);
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>