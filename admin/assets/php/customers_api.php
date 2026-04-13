<?php
session_start();
require_once '../../assets/php/db.php';

// Check admin auth
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

// ── LIST CUSTOMERS ──
if ($action === 'list') {
    // Only fetch customers (not admins)
    $stmt = mysqli_prepare($conn, "
        SELECT id, full_name, email, phone, orders, spent, type, active, created_at, role, avatar
        FROM users 
        WHERE role = 'customer'
        ORDER BY created_at DESC
    ");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $customers = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    echo json_encode(['success' => true, 'data' => $customers]);
    exit;
}

// ── ADD CUSTOMER ──
if ($action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $full_name = trim(($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? ''));
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $orders = (int)($data['orders'] ?? 0);
    $spent = (float)($data['spent'] ?? 0);
    $validTypes = ['New', 'First-time buyers', 'Regular', 'Loyal', 'Bulk', 'Inactive', 'At-Risk'];
    $type = in_array($data['type'] ?? '', $validTypes) ? $data['type'] : 'New';
    $active = (int)($data['active'] ?? 1);

    // Check if email already exists
    $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($chk, 's', $email);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    
    if (mysqli_stmt_num_rows($chk) > 0) {
        mysqli_stmt_close($chk);
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    mysqli_stmt_close($chk);

    $stmt = mysqli_prepare($conn, "
        INSERT INTO users (full_name, email, phone, orders, spent, type, active, role, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'customer', NOW())
    ");
    mysqli_stmt_bind_param($stmt, 'sssidii', $full_name, $email, $phone, $orders, $spent, $type, $active);
    
    if (mysqli_stmt_execute($stmt)) {
        $id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => false, 'message' => 'Failed to add customer']);
    }
    exit;
}

// ── UPDATE CUSTOMER ──
if ($action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = (int)($data['id'] ?? 0);
    $full_name = trim(($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? ''));
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $orders = (int)($data['orders'] ?? 0);
    $spent = (float)($data['spent'] ?? 0);
    $validTypes = ['New', 'First-time buyers', 'Regular', 'Loyal', 'Bulk', 'Inactive', 'At-Risk'];
    $type = in_array($data['type'] ?? '', $validTypes) ? $data['type'] : 'New';

    // Check if email exists for another user
    $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
    mysqli_stmt_bind_param($chk, 'si', $email, $id);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    
    if (mysqli_stmt_num_rows($chk) > 0) {
        mysqli_stmt_close($chk);
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    mysqli_stmt_close($chk);

    $stmt = mysqli_prepare($conn, "
        UPDATE users 
        SET full_name = ?, email = ?, phone = ?, orders = ?, spent = ?, type = ?
        WHERE id = ? AND role = 'customer'
    ");
    mysqli_stmt_bind_param($stmt, 'sssidii', $full_name, $email, $phone, $orders, $spent, $type, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => true]);
    } else {
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => false, 'message' => 'Failed to update customer']);
    }
    exit;
}

// ── TOGGLE ACTIVE/INACTIVE ──
if ($action === 'toggle') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = (int)($data['id'] ?? 0);
    $active = (int)($data['active'] ?? 0);

    $stmt = mysqli_prepare($conn, "
        UPDATE users 
        SET active = ?
        WHERE id = ? AND role = 'customer'
    ");
    mysqli_stmt_bind_param($stmt, 'ii', $active, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => true]);
    } else {
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>