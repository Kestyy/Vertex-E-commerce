<?php
// assets/php/wishlist.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';
require_once 'session.php';

// Handle both JSON and form-encoded data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        $productId = isset($data['product_id']) ? (int)$data['product_id'] : 0;
        $wishlistId = isset($data['id']) ? (int)$data['id'] : 0;
    } else {
        $action = $_POST['action'] ?? '';
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $wishlistId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    }
} else {
    $action = $_GET['action'] ?? '';
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    $wishlistId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to manage your wishlist.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Add to wishlist
if ($action === 'add') {
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
        exit;
    }
    $stmt = mysqli_prepare($conn, 'INSERT IGNORE INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())');
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $productId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => true, 'message' => 'Added to wishlist.']);
    exit;
}

// Remove from wishlist
if ($action === 'remove') {
    if ($wishlistId) {
        $stmt = mysqli_prepare($conn, 'DELETE FROM wishlist WHERE id = ? AND user_id = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $wishlistId, $userId);
    } elseif ($productId) {
        $stmt = mysqli_prepare($conn, 'DELETE FROM wishlist WHERE user_id = ? AND product_id = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $userId, $productId);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => true, 'message' => 'Removed from wishlist.']);
    exit;
}

// Clear entire wishlist
if ($action === 'clear') {
    $stmt = mysqli_prepare($conn, 'DELETE FROM wishlist WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => true, 'message' => 'Wishlist cleared.']);
    exit;
}

// Get user's wishlist
if ($action === 'get' || empty($action)) {
    $stmt = mysqli_prepare($conn, 'SELECT product_id FROM wishlist WHERE user_id = ? ORDER BY created_at DESC');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = ['id' => (int)$row['product_id']];
    }
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => true, 'items' => $items]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
