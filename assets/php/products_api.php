<?php
// assets/php/products_api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

$action = $_GET['action'] ?? '';

// ── GET featured products ────────────────────────────────
if ($action === 'featured') {
    $query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status='active' ORDER BY p.created_at DESC LIMIT 6";
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => (float)$row['price'],
            'category_name' => $row['category_name'],
            'image' => $row['image'],
            'stock_quantity' => (int)$row['stock_quantity'],
        ];
    }
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ── GET all products for catalog (SECURE VERSION) ────────────────────────────────
if ($action === 'catalog') {
    $category = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'newest';

    // Build WHERE clause with placeholders for prepared statements
    $whereParts = ["p.status='active'"];
    $params = [];
    $types = '';

    // Category filter (safe with placeholder)
    if ($category) {
        $whereParts[] = "c.name = ?";
        $params[] = $category;
        $types .= 's';
    }

    // Search filter (safe with placeholders)
    if ($search) {
        $whereParts[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ss';
    }

    $where = 'WHERE ' . implode(' AND ', $whereParts);

    // Safe ORDER BY (whitelist values only - no user input in SQL)
    $orderMap = [
        'newest' => 'p.created_at DESC',
        'price_low' => 'p.price ASC',
        'price_high' => 'p.price DESC',
        'name' => 'p.name ASC'
    ];
    $order = 'ORDER BY ' . ($orderMap[$sort] ?? 'p.created_at DESC');

    // Prepare and execute the secure query
    $query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where $order";
    $stmt = mysqli_prepare($conn, $query);
    
    // Bind parameters if we have any
    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => (float)$row['price'],
            'category_name' => $row['category_name'],
            'image' => $row['image'],
            'stock_quantity' => (int)$row['stock_quantity'],
        ];
    }
    mysqli_stmt_close($stmt);
    
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ── GET product details ────────────────────────────────
if ($action === 'details') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Product ID required.']);
        exit;
    }

    $query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.status='active'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);

    if ($product) {
        echo json_encode(['success' => true, 'data' => [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => (float)$product['price'],
            'category_name' => $product['category_name'],
            'image' => $product['image'],
            'stock_quantity' => (int)$product['stock_quantity'],
        ]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);