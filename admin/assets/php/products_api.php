<?php
// admin/assets/php/products_api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

$action = $_GET['action'] ?? '';

// ── INIT ──────────────────────────────────────────────
if ($action === 'init') {
    $result = mysqli_query($conn, "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
    $prods = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $prods[] = [
            'id'            => (int)$row['id'],
            'name'          => $row['name'],
            'description'   => $row['description'] ?? '',
            'price'         => (float)$row['price'],
            'category_id'   => (int)$row['category_id'],
            'category_name' => $row['category_name'] ?? 'Uncategorized',
            'stock_quantity'=> (int)$row['stock_quantity'],
            'image'         => $row['image'] ?? '',
            'status'        => $row['status'],
            'created_at'    => $row['created_at'],
            'updated_at'    => $row['updated_at'] ?? $row['created_at'],
        ];
    }
    $result = mysqli_query($conn, 'SELECT id, name FROM categories ORDER BY name');
    $cats = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $cats[] = ['id' => (int)$row['id'], 'name' => $row['name']];
    }
    echo json_encode(['success' => true, 'products' => $prods, 'categories' => $cats]);
    exit;
}

// ── LIST ──────────────────────────────────────────────
if ($action === 'list') {
    $result = mysqli_query($conn, "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = [
            'id'            => (int)$row['id'],
            'name'          => $row['name'],
            'description'   => $row['description'] ?? '',
            'price'         => (float)$row['price'],
            'category_id'   => (int)$row['category_id'],
            'category_name' => $row['category_name'] ?? 'Uncategorized',
            'stock_quantity'=> (int)$row['stock_quantity'],
            'image'         => $row['image'] ?? '',
            'status'        => $row['status'],
            'created_at'    => $row['created_at'],
            'updated_at'    => $row['updated_at'] ?? $row['created_at'],
        ];
    }
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ── CATEGORIES ───────────────────────────────────────
if ($action === 'categories') {
    $result = mysqli_query($conn, 'SELECT id, name FROM categories ORDER BY name');
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = ['id' => (int)$row['id'], 'name' => $row['name']];
    }
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ── Helper: handle image upload ───────────────────────
function handleImageUpload() {
    if (empty($_FILES['image']['name'])) return '';

    $upload_dir = __DIR__ . '/../../../images/products/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) return 'INVALID_TYPE';

    $filename = 'product_' . time() . '_' . rand(1000,9999) . '.' . $ext;
    $dest     = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) return $filename;
    return 'UPLOAD_FAILED';
}

// ── ADD ───────────────────────────────────────────────
if ($action === 'add') {
    $new_image = handleImageUpload();
    if ($new_image === 'INVALID_TYPE')  { echo json_encode(['success' => false, 'message' => 'Invalid image type. Use jpg, png, webp, or gif.']); exit; }
    if ($new_image === 'UPLOAD_FAILED') { echo json_encode(['success' => false, 'message' => 'Failed to upload image. Check folder permissions.']); exit; }

    $name           = trim($_POST['name']           ?? '');
    $description    = trim($_POST['description']    ?? '');
    $price          = (float)($_POST['price']        ?? 0);
    $category_id    = (int)($_POST['category_id']    ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
    $status         = $_POST['status']              ?? 'active';
    $image_filename = $new_image;

    if (!$name || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Name and price are required.']);
        exit;
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO products (name, description, price, category_id, stock_quantity, image, status, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    mysqli_stmt_bind_param($stmt, 'ssdiiss',
        $name, $description, $price, $category_id, $stock_quantity, $image_filename, $status
    );

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Product added successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . mysqli_error($conn)]);
    }
    exit;
}

// ── EDIT ──────────────────────────────────────────────
if ($action === 'edit') {
    $new_image = handleImageUpload();
    if ($new_image === 'INVALID_TYPE')  { echo json_encode(['success' => false, 'message' => 'Invalid image type.']); exit; }
    if ($new_image === 'UPLOAD_FAILED') { echo json_encode(['success' => false, 'message' => 'Failed to upload image.']); exit; }

    $id             = (int)($_POST['id']             ?? 0);
    $name           = trim($_POST['name']            ?? '');
    $description    = trim($_POST['description']     ?? '');
    $price          = (float)($_POST['price']        ?? 0);
    $category_id    = (int)($_POST['category_id']    ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
    $status         = $_POST['status']               ?? 'active';

    if (!$id || !$name || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID, name and price are required.']);
        exit;
    }

    if ($new_image) {
        // New image uploaded — update image too
        $stmt = mysqli_prepare($conn,
            "UPDATE products SET name=?, description=?, price=?, category_id=?, stock_quantity=?, image=?, status=?, updated_at=NOW() WHERE id=?"
        );
        mysqli_stmt_bind_param($stmt, 'ssdiissi',
            $name, $description, $price, $category_id, $stock_quantity, $new_image, $status, $id
        );
    } else {
        // No new image — leave existing image untouched
        $stmt = mysqli_prepare($conn,
            "UPDATE products SET name=?, description=?, price=?, category_id=?, stock_quantity=?, status=?, updated_at=NOW() WHERE id=?"
        );
        mysqli_stmt_bind_param($stmt, 'ssdissi',
            $name, $description, $price, $category_id, $stock_quantity, $status, $id
        );
    }

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . mysqli_error($conn)]);
    }
    exit;
}

// ── DELETE ────────────────────────────────────────────
if ($action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($data['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'message' => 'Product ID is required.']); exit; }

    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . mysqli_error($conn)]);
    }
    exit;
}

// ── STATS ─────────────────────────────────────────────
if ($action === 'stats') {
    $total     = (int)mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) as c FROM products'))['c'];
    $active    = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products WHERE status='active'"))['c'];
    $low_stock = (int)mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) as c FROM products WHERE stock_quantity <= 5 AND stock_quantity > 0'))['c'];
    $out       = (int)mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) as c FROM products WHERE stock_quantity = 0'))['c'];
    echo json_encode(['success' => true, 'data' => [
        'total'     => $total,
        'active'    => $active,
        'low_stock' => $low_stock,
        'out_stock' => $out,
    ]]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);