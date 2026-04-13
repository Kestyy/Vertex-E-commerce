<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'assets/php/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ── Fetch user ──
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// ── Split full_name into first/last for the form ──
$name_parts         = explode(' ', $user['full_name'] ?? '', 2);
$user['first_name'] = $name_parts[0] ?? '';
$user['last_name']  = $name_parts[1] ?? '';

$success = '';
$error   = '';

// ── Handle form submission ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ── Personal Info update ──
    if ($_POST['action'] === 'update_info') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name']  ?? '');
        $email      = trim($_POST['email']      ?? '');
        $phone      = trim($_POST['phone']      ?? '');
        $gender     = trim($_POST['gender']     ?? '');

        if ($phone !== '') {
            $phone = '+63' . preg_replace('/\s+/', '', $phone);
        }

        $full_name_db = trim($first_name . ' ' . $last_name);

        if (!$first_name || !$last_name || !$email) {
            $error = 'First name, last name, and email are required.';
        } elseif (strlen($first_name) > 30 || strlen($last_name) > 30) {
            $error = 'Name fields must not exceed 30 characters.';
        } else {
            $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
            mysqli_stmt_bind_param($chk, 'si', $email, $user_id);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);
            if (mysqli_stmt_num_rows($chk) > 0) {
                $error = 'That email address is already in use.';
            } else {
                $upd = mysqli_prepare($conn,
                    "UPDATE users SET full_name=?, email=?, phone=?, gender=? WHERE id=?");
                mysqli_stmt_bind_param($upd, 'ssssi',
                    $full_name_db, $email, $phone, $gender, $user_id);
                mysqli_stmt_execute($upd);
                $success = 'Profile updated successfully.';
                $_SESSION['user_name'] = $full_name_db;

                $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, 'i', $user_id);
                mysqli_stmt_execute($stmt);
                $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                $name_parts         = explode(' ', $user['full_name'] ?? '', 2);
                $user['first_name'] = $name_parts[0] ?? '';
                $user['last_name']  = $name_parts[1] ?? '';
            }
            mysqli_stmt_close($chk);
        }
    }

    // ── Password change ──
    if ($_POST['action'] === 'change_password') {
        $current  = $_POST['current_password']  ?? '';
        $new_pass = $_POST['new_password']       ?? '';
        $confirm  = $_POST['confirm_password']   ?? '';

        if (!$current || !$new_pass || !$confirm) {
            $error = 'All password fields are required.';
        } elseif ($new_pass !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_pass) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $upd    = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
            mysqli_stmt_bind_param($upd, 'si', $hashed, $user_id);
            mysqli_stmt_execute($upd);
            $success = 'Password changed successfully.';
        }
    }

    // ── Avatar upload ──
    if ($_POST['action'] === 'upload_avatar' && isset($_FILES['avatar'])) {
        $file     = $_FILES['avatar'];
        $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize  = 2 * 1024 * 1024;

        if (!in_array($file['type'], $allowed)) {
            $error = 'Only JPG, PNG, WEBP, or GIF images are allowed.';
        } elseif ($file['size'] > $maxSize) {
            $error = 'Image must be under 2MB.';
        } else {
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            $dest     = 'images/avatars/' . $filename;

            if (!is_dir('images/avatars')) mkdir('images/avatars', 0755, true);

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $upd = mysqli_prepare($conn, "UPDATE users SET avatar=? WHERE id=?");
                mysqli_stmt_bind_param($upd, 'si', $filename, $user_id);
                mysqli_stmt_execute($upd);
                $user['avatar'] = $filename;
                $success = 'Profile photo updated.';
            } else {
                $error = 'Failed to upload image. Please try again.';
            }
        }
    }

    // ── Add Address ──
    if ($_POST['action'] === 'add_address') {
        $addr_full_name = trim($_POST['addr_full_name'] ?? '');
        $addr_street    = trim($_POST['addr_street']    ?? '');
        $addr_barangay  = trim($_POST['addr_barangay']  ?? '');
        $addr_city      = trim($_POST['addr_city']      ?? '');
        $addr_province  = trim($_POST['addr_province']  ?? '');
        $addr_region    = trim($_POST['addr_region']    ?? '');
        $addr_zip       = trim($_POST['addr_zip']       ?? '');
        $addr_phone_raw = trim($_POST['addr_phone']     ?? '');
        $addr_phone     = $addr_phone_raw !== '' ? '+63' . preg_replace('/\s+/', '', $addr_phone_raw) : '';

        $isMetroManila = $addr_region === 'Metro Manila';
        $validProvince = $isMetroManila || !empty($addr_province);

        if (!$addr_full_name || !$addr_street || !$addr_city || !$validProvince || !$addr_zip || !$addr_phone) {
            $error = 'Please fill in all required address fields.';
        } else {
            $chk_tbl = mysqli_query($conn, "SHOW TABLES LIKE 'user_addresses'");
            if (mysqli_num_rows($chk_tbl) === 0) {
                mysqli_query($conn, "CREATE TABLE user_addresses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    full_name VARCHAR(100) NOT NULL,
                    street VARCHAR(255) NOT NULL,
                    barangay VARCHAR(100),
                    city VARCHAR(100) NOT NULL,
                    province VARCHAR(100) NOT NULL,
                    region VARCHAR(100),
                    zip VARCHAR(10) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    is_default TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            }

            $cnt = mysqli_prepare($conn, "SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
            mysqli_stmt_bind_param($cnt, 'i', $user_id);
            mysqli_stmt_execute($cnt);
            $cnt_result = mysqli_stmt_get_result($cnt);
            $cnt_row = mysqli_fetch_row($cnt_result);
            $is_default = ($cnt_row[0] == 0) ? 1 : 0;

            $ins = mysqli_prepare($conn,
                "INSERT INTO user_addresses (user_id, full_name, street, barangay, city, province, region, zip, phone, is_default)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($ins, 'issssssssi',
                $user_id, $addr_full_name, $addr_street, $addr_barangay,
                $addr_city, $addr_province, $addr_region, $addr_zip, $addr_phone, $is_default);
            mysqli_stmt_execute($ins);
            $success = 'Address added successfully.';
        }
    }

    // ── Edit Address ──
    if ($_POST['action'] === 'edit_address') {
        $addr_id        = intval($_POST['addr_id'] ?? 0);
        $addr_full_name = trim($_POST['addr_full_name'] ?? '');
        $addr_street    = trim($_POST['addr_street']    ?? '');
        $addr_barangay  = trim($_POST['addr_barangay']  ?? '');
        $addr_city      = trim($_POST['addr_city']      ?? '');
        $addr_province  = trim($_POST['addr_province']  ?? '');
        $addr_region    = trim($_POST['addr_region']    ?? '');
        $addr_zip       = trim($_POST['addr_zip']       ?? '');
        $addr_phone_raw = trim($_POST['addr_phone']     ?? '');
        $addr_phone     = $addr_phone_raw !== '' ? '+63' . preg_replace('/\s+/', '', $addr_phone_raw) : '';

        $isMetroManila = $addr_region === 'Metro Manila';
        $validProvince = $isMetroManila || !empty($addr_province);

        if ($addr_id <= 0) {
            $error = 'Invalid address ID.';
        } elseif (!$addr_full_name || !$addr_street || !$addr_city || !$validProvince || !$addr_zip || !$addr_phone) {
            $error = 'Please fill in all required address fields.';
        } else {
            $upd = mysqli_prepare($conn,
                "UPDATE user_addresses SET full_name = ?, street = ?, barangay = ?, city = ?, province = ?, region = ?, zip = ?, phone = ?
                 WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($upd, 'ssssssssii',
                $addr_full_name, $addr_street, $addr_barangay, $addr_city, $addr_province, $addr_region, $addr_zip, $addr_phone, $addr_id, $user_id);
            mysqli_stmt_execute($upd);
            $success = 'Address updated successfully.';
        }
    }

    // ── Delete Address ──
    if ($_POST['action'] === 'delete_address') {
        $addr_id = intval($_POST['addr_id'] ?? 0);
        if ($addr_id > 0) {
            $del = mysqli_prepare($conn, "DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($del, 'ii', $addr_id, $user_id);
            mysqli_stmt_execute($del);
            $success = 'Address deleted.';
        }
    }

    // ── Set Default Address ──
    if ($_POST['action'] === 'set_default_address') {
        $addr_id = intval($_POST['addr_id'] ?? 0);
        if ($addr_id > 0) {
            mysqli_query($conn, "UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
            $upd = mysqli_prepare($conn, "UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($upd, 'ii', $addr_id, $user_id);
            mysqli_stmt_execute($upd);
            $success = 'Default address updated.';
        }
    }
}

$tab = $_GET['tab'] ?? 'info';

// ── Filter for orders ──
$filter = $_GET['filter'] ?? 'all';

// ── Fetch orders with items (for orders tab) ──
$orders = [];
$total_orders = 0;
$orders_per_page = 5;
$current_page = intval($_GET['order_page'] ?? 1);
if ($current_page < 1) $current_page = 1;

if ($tab === 'orders') {
    $where = "WHERE o.user_id = ?";
    $types = 'i';
    $params = [$user_id];

    if ($filter !== 'all') {
        $where .= " AND o.status = ?";
        $types .= 's';
        $params[] = $filter;
    }

    $count_sql = "SELECT COUNT(*) as total FROM orders o $where";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_orders = $count_row['total'] ?? 0;
    mysqli_stmt_close($count_stmt);

    $total_pages = ceil($total_orders / $orders_per_page);
    if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

    $offset = ($current_page - 1) * $orders_per_page;

    $sql = "SELECT o.id, o.order_number, o.total_amount, o.payment_method,
                   o.status, o.order_date, o.updated_at
            FROM orders o
            $where
            ORDER BY o.order_date DESC
            LIMIT ? OFFSET ?";

    $params[] = $orders_per_page;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $orders_result = mysqli_stmt_get_result($stmt);
    $orders = mysqli_fetch_all($orders_result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    foreach ($orders as &$order) {
        $oid = $order['id'];
        $istmt = mysqli_prepare($conn,
            "SELECT oi.id, oi.order_id, oi.product_id, oi.quantity, oi.price,
                    p.name AS product_name, p.image AS product_image
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?");
        mysqli_stmt_bind_param($istmt, 'i', $oid);
        mysqli_stmt_execute($istmt);
        $order['items'] = mysqli_fetch_all(mysqli_stmt_get_result($istmt), MYSQLI_ASSOC);
        mysqli_stmt_close($istmt);
    }
    unset($order);
}

// ── Status badge config ──
function statusBadge($status) {
    $map = [
        'pending'    => ['label' => 'Pending',    'color' => '#f59e0b', 'bg' => '#fffbeb', 'border' => '#fde68a'],
        'processing' => ['label' => 'Processing', 'color' => '#3b82f6', 'bg' => '#eff6ff', 'border' => '#bfdbfe'],
        'accepted'   => ['label' => 'Accepted',   'color' => '#f59e0b', 'bg' => '#fffbeb', 'border' => '#fde68a'],
        'shipped'    => ['label' => 'Shipped',    'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'border' => '#ddd6fe'],
        'delivered'  => ['label' => 'Delivered',  'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#bbf7d0'],
        'cancelled'  => ['label' => 'Cancelled',  'color' => '#ef4444', 'bg' => '#fef2f2', 'border' => '#fecaca'],
    ];
    return $map[strtolower($status)] ?? ['label' => ucfirst($status), 'color' => '#64748b', 'bg' => '#f8fafc', 'border' => '#e2e8f0'];
}

// ── Fetch saved addresses ──
$saved_addresses = [];
$tbl_check = mysqli_query($conn, "SHOW TABLES LIKE 'user_addresses'");
if (mysqli_num_rows($tbl_check) > 0) {
    $a_stmt = mysqli_prepare($conn, "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    mysqli_stmt_bind_param($a_stmt, 'i', $user_id);
    mysqli_stmt_execute($a_stmt);
    $a_result = mysqli_stmt_get_result($a_stmt);
    while ($row = mysqli_fetch_assoc($a_result)) {
        $saved_addresses[] = $row;
    }
}

$full_name  = trim($user['full_name'] ?? '');
$avatar_src = !empty($user['avatar'])
    ? 'images/avatars/' . htmlspecialchars($user['avatar'])
    : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Account — Vertex</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/style.css"/>
  <style>
    /* ══════════════════════════════════════
       PROFILE PAGE — VERTEX
    ══════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; }

    body {
      background: #f1f5f9;
      font-family: 'Poppins', sans-serif;
      color: #1e293b;
    }

    /* ── Hero ── */
    .profile-hero {
        position: relative;
        background: linear-gradient(135deg, #e8f0fe 0%, #f0f4ff 40%, #e4eefb 100%);
        text-align: center;
        padding: 35px 25px;
        overflow: hidden;
        border-bottom: 1px solid #d6e4f7;
    }
    .profile-hero::before {
      content: '';
      position: absolute;
      width: 320px; height: 320px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(99,155,255,0.18) 0%, transparent 70%);
      top: -80px; left: -60px;
      pointer-events: none;
    }
    .profile-hero::after {
      content: '';
      position: absolute;
      width: 260px; height: 260px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(139,92,246,0.1) 0%, transparent 70%);
      bottom: -60px; right: -40px;
      pointer-events: none;
    }
    .profile-hero-title {
      font-size: 4rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      color: #3b82f6;
      margin-bottom: 12px;
      position: relative;
      z-index: 1;
      line-height: 1.15;
    }
    .profile-hero-sub {
      font-size: 14px;
      color: #6b7faa;
      font-weight: 400;
      margin-bottom: 20px;
      position: relative;
      z-index: 1;
    }
    .profile-hero-bc {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: rgba(255,255,255,0.6);
      border: 1px solid rgba(180,205,255,0.5);
      backdrop-filter: blur(6px);
      border-radius: 30px;
      padding: 6px 18px;
      font-size: 13px;
      color: #8aa0c8;
      position: relative;
      z-index: 1;
    }
    .profile-hero-bc a {
      color: #7a99cc;
      text-decoration: none;
      font-weight: 500;
      transition: color .2s;
    }
    .profile-hero-bc a:hover { color: #3b82f6; }
    .profile-hero-bc-sep { color: #b0c4e8; font-size: 12px; }

    /* ── Layout ── */
    .profile-wrap {
      max-width: 1250px;
      margin: 40px auto 80px;
      padding: 0 2rem;
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 40px;
      align-items: start;
    }
    @media (max-width: 768px) {
      .profile-wrap { grid-template-columns: 1fr; margin-top: 28px; gap: 20px; }
    }

    /* ── Sidebar ── */
    .sidebar-card {
      background: #fff;
      border-radius: 18px;
      border: 1px solid #e2e8f0;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }

    .sidebar-avatar-block {
      padding: 28px 24px 20px;
      text-align: center;
      border-bottom: 1px solid #f1f5f9;
    }

    .avatar-circle-wrap {
      position: relative;
      display: inline-block;
      margin-bottom: 12px;
      cursor: pointer;
    }

    .avatar-circle {
      width: 82px;
      height: 82px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #e2e8f0;
      display: block;
    }

    .avatar-default {
      width: 82px;
      height: 82px;
      border-radius: 50%;
      background: #aaaaaa;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 3px solid #e2e8f0;
      overflow: hidden;
    }

    .avatar-edit-btn {
      position: absolute;
      bottom: 2px;
      right: 2px;
      width: 26px;
      height: 26px;
      background: #3b82f6;
      color: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      border: 2px solid #fff;
      box-shadow: 0 2px 8px rgba(59,130,246,0.4);
      opacity: 1;
      transform: scale(1);
      pointer-events: auto;
      z-index: 2;
      transition: transform 0.2s, background 0.2s, box-shadow 0.2s;
    }

    .avatar-edit-btn:hover {
      background: #2563eb;
      box-shadow: 0 4px 12px rgba(59,130,246,0.6);
    }

    .sidebar-name {
      font-size: 15px; font-weight: 600; color: #1e293b;
      margin-bottom: 3px;
    }
    .sidebar-email {
      font-size: 12px; color: #94a3b8; font-weight: 400;
    }

    /* Nav links */
    .sidebar-nav { padding: 8px 0; }
    .sidebar-nav-item {
      display: flex; align-items: center; gap: 12px;
      padding: 13px 22px;
      font-size: 14px; font-weight: 500; color: #475569;
      cursor: pointer;
      text-decoration: none;
      transition: background .15s, color .15s;
      border-left: 3px solid transparent;
      position: relative;
    }
    .sidebar-nav-item:hover {
      background: #f8fafc; color: #3b82f6;
    }
    .sidebar-nav-item.active {
      background: #eff6ff;
      color: #3b82f6;
      border-left-color: #3b82f6;
      font-weight: 600;
    }
    .sidebar-nav-item i {
      width: 18px; text-align: center;
      font-size: 13.5px; opacity: .75;
    }
    .sidebar-nav-item.active i { opacity: 1; }
    .sidebar-nav-divider {
      height: 1px; background: #f1f5f9;
      margin: 6px 0;
    }
    .sidebar-nav-item.logout { color: #ef4444; }
    .sidebar-nav-item.logout:hover {
      background: #fef2f2; color: #dc2626;
      border-left-color: #ef4444;
    }

    /* ── Main card ── */
    .main-card {
      background: #fff;
      border-radius: 18px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
      overflow: hidden;
    }
    .main-card-header {
      padding: 22px 28px 18px;
      border-bottom: 1px solid #f1f5f9;
      display: flex; align-items: center; gap: 12px;
    }
    .main-card-header-icon {
      width: 38px; height: 38px;
      background: #eff6ff; color: #3b82f6;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 15px;
    }
    .main-card-header-title {
      font-size: 16px; font-weight: 700; color: #1e293b;
    }
    .main-card-header-sub {
      font-size: 12px; color: #94a3b8; margin-top: 1px;
    }
    .main-card-body { padding: 28px; }

    /* ── Alert messages ── */
    .profile-alert {
      display: flex; align-items: center; gap: 10px;
      padding: 13px 16px;
      border-radius: 10px;
      font-size: 13.5px; font-weight: 500;
      position: absolute;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      width: max-content;
      max-width: 90%;
      z-index: 999;
      box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    }
    .profile-alert.success {
      background: #f0fdf4; color: #166534;
      border: 1px solid #bbf7d0;
    }
    .profile-alert.error {
      background: #fef2f2; color: #991b1b;
      border: 1px solid #fecaca;
    }

    /* ── Form fields ── */
    .f-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
    }
    .f-grid-1 { display: grid; grid-template-columns: 1fr; gap: 18px; }
    @media (max-width: 560px) { .f-grid-2 { grid-template-columns: 1fr; } }

    .f-group { display: flex; flex-direction: column; gap: 6px; }
    .f-label {
      font-size: 12px; font-weight: 600;
      color: #64748b; letter-spacing: .04em;
      text-transform: uppercase;
    }
    .f-label .req { color: #ef4444; }
    .f-input {
      width: 100%; padding: 11px 14px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px; font-weight: 500;
      font-family: 'Poppins', sans-serif;
      font-size: 15px; color: #0f172a;
      background: #fff; outline: none;
      transition: border-color .2s, box-shadow .2s;
    }
    .f-input:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
    }
    .f-input::placeholder { color: #cbd5e1; }
    .f-input[readonly] {
      background: #f8fafc;
      color: #94a3b8;
      cursor: not-allowed;
      border-color: #e2e8f0;
    }
    .f-input[readonly]:focus {
      border-color: #e2e8f0;
      box-shadow: none;
    }

    select.f-input {
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2394a3b8' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      padding-right: 38px;
      cursor: pointer;
      color: #cbd5e1;
    }

    select.f-input:invalid,
    select.f-input option[value=""] {
      color: #cbd5e1;
    }

    select.f-input option:not([value=""]) {
      color: #1e293b;
    }

    select.f-input.has-value,
    select.f-input:not(:disabled):focus {
      color: #1e293b;
    }

    select.f-input:disabled {
      cursor: not-allowed;
      opacity: 0.55;
      background-color: #f8fafc;
      color: #cbd5e1;
    }

    /* ── Phone input ── */
    .phone-input-wrap {
      display: flex;
      align-items: center;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      overflow: hidden;
      transition: border-color .2s, box-shadow .2s;
      background: #fff;
    }
    .phone-input-wrap:focus-within {
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
    }
    .phone-prefix {
      padding: 11px 12px 11px 14px;
      font-size: 13.5px;
      color: #64748b;
      background: #f8fafc;
      border-right: 1.5px solid #e2e8f0;
      white-space: nowrap;
      user-select: none;
    }
    .phone-input {
      border: none !important;
      box-shadow: none !important;
      border-radius: 0 !important;
      flex: 1;
    }
    .phone-input:focus {
      border: none !important;
      box-shadow: none !important;
    }

    /* ── Field error messages & validation ── */
    .field-error {
      font-size: 12px;
      color: #ef4444;
      margin-top: 6px;
      display: none;
      align-items: center;
      gap: 6px;
    }
    .field-error.show { display: flex; }

    .f-input.error-field {
      border-color: #ef4444;
      box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.08);
    }

    .f-input.success-field {
      border-color: #22c55e;
    }

    .f-input.invalid {
      border-color: #ef4444 !important;
      box-shadow: 0 0 0 3px rgba(239,68,68,0.12) !important;
    }

    /* ── Action button ── */
    .btn-save {
      display: inline-flex; align-items: center; gap: 8px;
      background: #3b82f6; color: #fff;
      border: none; border-radius: 10px;
      padding: 12px 28px;
      font-family: 'Poppins', sans-serif;
      font-size: 14px; font-weight: 600;
      cursor: pointer;
      transition: background .2s, transform .15s, box-shadow .2s, opacity .2s;
      margin-top: 24px;
    }
    
    .form-actions .btn-save {
      margin-top: 0;
    }
    .btn-save:hover {
      background: #1d4ed8;
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(59,130,246,0.32);
    }
    .btn-save:active { transform: translateY(0); }
    .btn-save:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none !important;
    }

    #avatarFileInput { display: none; }

    /* ══════════════════════════════════════
       MANAGE ADDRESS STYLES
    ══════════════════════════════════════ */

    .addr-list { margin-bottom: 24px; }
    
    .addr-card {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      padding: 16px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      margin-bottom: 12px;
      transition: all 0.2s;
    }

    .addr-card:hover {
      background: #f0f4f8;
      border-color: #cbd5e1;
    }

    .addr-info {
      flex: 1;
    }

    .addr-name {
      font-size: 15px;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    .addr-default-badge {
      background: #dbeafe;
      color: #1e40af;
      font-size: 11px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .addr-line {
      font-size: 13px;
      color: #64748b;
      line-height: 1.4;
      margin: 0;
      display: flex;
      align-items: flex-start;
      gap: 4px;
    }

    .addr-actions-bottom {
      font-size: 12px;
      color: #64748b;
      margin-top: 8px;
    }

    .addr-link-btn {
      background: none;
      border: none;
      color: #3b82f6;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      transition: color 0.2s;
    }

    .addr-link-btn:hover {
      color: #1d4ed8;
    }

    .addr-row-actions {
      display: flex;
      gap: 8px;
      flex-shrink: 0;
    }
    
    .addr-act-edit {
      background: none;
      border: none;
      color: #3b82f6;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      padding: 6px 12px;
      border-radius: 6px;
      transition: all .15s;
      font-family: 'Poppins', sans-serif;
    }
    .addr-act-edit:hover {
      background: #dbeafe;
      color: #1d4ed8;
    }
    
    .addr-act-delete {
      background: none;
      border: none;
      color: #ef4444;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      padding: 6px 12px;
      border-radius: 6px;
      transition: all .15s;
      font-family: 'Poppins', sans-serif;
    }
    .addr-act-delete:hover {
      background: #fee2e2;
      color: #dc2626;
    }

    .addr-empty {
      text-align: center;
      padding: 32px 0 8px;
      color: #94a3b8;
    }
    .addr-empty i {
      font-size: 2.2rem;
      margin-bottom: 12px;
      display: block;
      opacity: .4;
    }
    .addr-empty-title {
      font-size: 15px;
      font-weight: 600;
      color: #64748b;
      margin-bottom: 4px;
    }
    .addr-empty-sub { font-size: 13px; }

    .addr-section-divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 8px 0 24px;
      color: #94a3b8;
      font-size: 13px;
      font-weight: 500;
    }
    .addr-section-divider::before,
    .addr-section-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #e2e8f0;
    }

    .addr-form-title {
      font-size: 15px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .addr-form-title i {
      color: #3b82f6;
      font-size: 14px;
    }

    .select-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .select-wrap select.f-input {
      appearance: none;
      -webkit-appearance: none;
      width: 100%;
    }

    .select-wrap .select-arrow {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      font-size: 11px;
      pointer-events: none;
      transition: color 0.2s, transform 0.2s;
    }

    .select-wrap:focus-within .select-arrow {
      color: #3b82f6;
    }

    .select-wrap.loading .select-arrow {
      animation: spin 0.7s linear infinite;
    }

    @keyframes spin {
      to { transform: translateY(-50%) rotate(360deg); }
    }

    /* Delete / Cancel Modal */
    .delete-modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .delete-modal {
      background: white;
      border-radius: 10px;
      box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
      max-width: 420px;
      width: 90%;
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .delete-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px;
      border-bottom: 1px solid #e2e8f0;
    }

    .delete-modal-header h3 {
      margin: 0;
      font-size: 16px;
      font-weight: 700;
      color: #1f2937;
    }

    .delete-modal-close {
      background: none;
      border: none;
      font-size: 18px;
      color: #6b7280;
      cursor: pointer;
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s;
    }

    .delete-modal-close:hover { color: #1f2937; }

    .delete-modal-body {
      padding: 20px;
    }

    .delete-modal-body p {
      margin: 0 0 8px;
      font-size: 14px;
      color: #4b5563;
      line-height: 1.5;
    }

    .delete-modal-footer {
      display: flex;
      gap: 12px;
      padding: 16px 20px 20px;
      border-top: 1px solid #e2e8f0;
    }

    .delete-modal-btn {
      flex: 1;
      padding: 10px 16px;
      border: 1px solid #d1d5db;
      background: white;
      color: #374151;
      font-size: 14px;
      font-weight: 600;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.2s;
      font-family: 'Poppins', sans-serif;
    }

    .delete-modal-btn:hover {
      background: #f3f4f6;
      border-color: #9ca3af;
    }

    .delete-modal-btn.confirm {
      background: #ef4444;
      border-color: #dc2626;
      color: white;
    }

    .delete-modal-btn.confirm:hover {
      background: #dc2626;
      border-color: #b91c1c;
    }

    .delete-modal-btn i { margin-right: 6px; }

    /* Form actions wrapper */
    .form-actions {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .btn-cancel {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #f3f4f6;
      border: 1px solid #d1d5db;
      color: #374151;
      padding: 12px 28px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s, border-color 0.2s, transform 0.15s;
      font-family: 'Poppins', sans-serif;
      flex: 0 0 auto;
    }

    .btn-cancel:hover {
      background: #e5e7eb;
      border-color: #9ca3af;
      transform: translateY(-1px);
    }

    .btn-cancel:active { transform: translateY(0); }

    .btn-cancel:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none !important;
    }

    /* ══════════════════════════════════════
       INVOICE MODAL STYLES
    ══════════════════════════════════════ */
    .inv-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.52);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      padding: 20px;
      animation: invFadeIn .2s ease;
    }
    @keyframes invFadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }
    .inv-modal {
      background: #fff;
      border-radius: 18px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 24px 60px rgba(0,0,0,0.18);
      width: 100%;
      max-width: 560px;
      max-height: 90vh;
      overflow-y: auto;
      animation: invSlideUp .25s ease;
    }
    @keyframes invSlideUp {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .inv-modal::-webkit-scrollbar { width: 4px; }
    .inv-modal::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

    .inv-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 18px 24px;
      border-bottom: 1px solid #f1f5f9;
      position: sticky;
      top: 0;
      background: #fff;
      z-index: 2;
      border-radius: 18px 18px 0 0;
    }
    .inv-header-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .inv-header-icon {
      width: 36px; height: 36px;
      background: #eff6ff;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      color: #3b82f6;
      font-size: 14px;
      flex-shrink: 0;
    }
    .inv-header-title {
      font-size: 15px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 1px;
    }
    .inv-header-sub {
      font-size: 12px;
      color: #94a3b8;
      font-weight: 400;
    }
    .inv-close-btn {
      width: 30px; height: 30px;
      background: #f1f5f9;
      border: none;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 14px;
      color: #64748b;
      cursor: pointer;
      transition: background .15s, color .15s;
      flex-shrink: 0;
    }
    .inv-close-btn:hover { background: #e2e8f0; color: #1e293b; }

    .inv-body { padding: 22px 24px; }

    .inv-meta-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-bottom: 20px;
    }
    .inv-meta-card {
      background: #f8fafc;
      border-radius: 10px;
      padding: 12px 14px;
      border: 1px solid #f1f5f9;
    }
    .inv-meta-label {
      font-size: 10px;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: .06em;
      margin-bottom: 5px;
    }
    .inv-meta-val {
      font-size: 13px;
      font-weight: 600;
      color: #1e293b;
      line-height: 1.35;
    }
    .inv-meta-sub {
      font-size: 11.5px;
      color: #94a3b8;
      margin-top: 2px;
      font-weight: 400;
    }

    .inv-items-wrap {
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      overflow: hidden;
      margin-bottom: 16px;
    }
    .inv-items-head {
      display: grid;
      grid-template-columns: 2fr 60px 100px 100px;
      background: #f8fafc;
      padding: 9px 14px;
      border-bottom: 1px solid #e2e8f0;
      gap: 0;
    }
    .inv-items-head span {
      font-size: 10px;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: .06em;
    }
    .inv-items-head span:not(:first-child) { text-align: right; }

    .inv-item-row {
      display: grid;
      grid-template-columns: 2fr 60px 100px 100px;
      align-items: center;
      padding: 12px 14px;
      border-bottom: 1px solid #f1f5f9;
      gap: 0;
    }
    .inv-item-row:last-child { border-bottom: none; }

    .inv-item-name-wrap {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
    }
    .inv-item-img {
      width: 38px; height: 38px;
      border-radius: 8px;
      object-fit: cover;
      border: 1px solid #e2e8f0;
      background: #f8fafc;
      flex-shrink: 0;
    }
    .inv-item-img-placeholder {
      width: 38px; height: 38px;
      border-radius: 8px;
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
      display: flex; align-items: center; justify-content: center;
      color: #cbd5e1;
      font-size: 14px;
      flex-shrink: 0;
    }
    .inv-item-name {
      font-size: 13px;
      font-weight: 600;
      color: #1e293b;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .inv-item-qty  { font-size: 13px; color: #64748b; text-align: right; }
    .inv-item-price { font-size: 13px; color: #64748b; text-align: right; }
    .inv-item-total { font-size: 13px; font-weight: 600; color: #1e293b; text-align: right; }

    .inv-totals {
      border-top: 1px solid #e2e8f0;
      padding-top: 14px;
      margin-bottom: 20px;
    }
    .inv-total-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }
    .inv-total-row .lbl { font-size: 13px; color: #64748b; }
    .inv-total-row .val { font-size: 13px; color: #1e293b; font-weight: 500; }
    .inv-total-row .val.free { color: #16a34a; }
    .inv-total-grand {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-top: 12px;
      border-top: 1px solid #e2e8f0;
      margin-top: 4px;
    }
    .inv-total-grand .lbl { font-size: 14px; font-weight: 700; color: #1e293b; }
    .inv-total-grand .val { font-size: 17px; font-weight: 700; color: #1e293b; }

    .inv-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
      padding-top: 16px;
      border-top: 1px solid #f1f5f9;
    }
    .inv-status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 13px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      border: 1px solid;
    }
    .inv-dl-btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: #1e293b;
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 9px 20px;
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: background .2s, transform .15s;
      text-decoration: none;
    }
    .inv-dl-btn:hover {
      background: #0f172a;
      transform: translateY(-1px);
      color: #fff;
    }

    .inv-loading, .inv-error {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 50px 30px;
      text-align: center;
      color: #94a3b8;
      gap: 12px;
    }
    .inv-loading i { font-size: 1.8rem; animation: invSpin .8s linear infinite; }
    .inv-error i   { font-size: 1.8rem; color: #fca5a5; }
    .inv-error p   { font-size: 14px; color: #64748b; margin: 0; }
    @keyframes invSpin { to { transform: rotate(360deg); } }

    @media (max-width: 480px) {
      .inv-meta-grid { grid-template-columns: 1fr; }
      .inv-items-head,
      .inv-item-row { grid-template-columns: 1fr 44px 80px 80px; }
      .inv-body { padding: 16px; }
    }

    @media (max-width: 768px) {
      .main-card-body { padding: 20px; }
      .profile-hero-title { font-size: 2.2rem; }
    }
  </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- ══ HERO ══ -->
<div class="profile-hero">
  <div class="profile-hero-title">My Account</div>
  <div class="profile-hero-sub">Manage your personal information and settings</div>
  <nav class="profile-hero-bc" aria-label="breadcrumb">
    <a href="index.php">Home</a>
    <span class="profile-hero-bc-sep">›</span>
    <span>My Account</span>
  </nav>
</div>

<div class="profile-wrap">

  <!-- ══ SIDEBAR ══ -->
  <aside>
    <div class="sidebar-card">

      <div class="sidebar-avatar-block">

        <form method="POST" enctype="multipart/form-data" id="avatarForm" style="display:none;">
          <input type="hidden" name="action" value="upload_avatar"/>
          <input type="file" name="avatar" id="avatarFileInput" accept="image/*"
                 onchange="document.getElementById('avatarForm').submit()"/>
        </form>

        <div class="avatar-circle-wrap" onclick="document.getElementById('avatarFileInput').click()" title="Change profile photo">
          <?php if ($avatar_src): ?>
            <img src="<?= $avatar_src ?>" alt="Avatar" class="avatar-circle"/>
          <?php else: ?>
            <div class="avatar-default">
              <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" width="48" height="48">
                <circle cx="50" cy="35" r="20" fill="#e0e0e0"/>
                <ellipse cx="50" cy="95" rx="40" ry="30" fill="#e0e0e0"/>
              </svg>
            </div>
          <?php endif; ?>
          <div class="avatar-edit-btn">
            <i class="fas fa-pen"></i>
          </div>
        </div>

        <div class="sidebar-name"><?= htmlspecialchars($full_name ?: 'My Account') ?></div>
        <div class="sidebar-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
      </div>

      <!-- Nav -->
      <nav class="sidebar-nav">
        <a href="?tab=info"     class="sidebar-nav-item <?= $tab==='info'     ? 'active' : '' ?>">
          <i class="fas fa-user"></i> Personal Information
        </a>
        <a href="?tab=orders" class="sidebar-nav-item <?= $tab==='orders' ? 'active' : '' ?>">
          <i class="fas fa-box"></i> My Orders
        </a>
        <a href="?tab=address"  class="sidebar-nav-item <?= $tab==='address'  ? 'active' : '' ?>">
          <i class="fas fa-map-marker-alt"></i> Manage Address
        </a>
        <a href="?tab=payment"  class="sidebar-nav-item <?= $tab==='payment'  ? 'active' : '' ?>">
          <i class="fas fa-credit-card"></i> Payment Method
        </a>
        <a href="?tab=password" class="sidebar-nav-item <?= $tab==='password' ? 'active' : '' ?>">
          <i class="fas fa-lock"></i> Password Manager
        </a>
        <div class="sidebar-nav-divider"></div>
        <a href="#" class="sidebar-nav-item logout" id="sidebarLogout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </nav>

    </div>
  </aside>

  <!-- ══ MAIN ══ -->
  <main>

    <?php if ($success || $error): ?>
    <div style="position: relative; height: 0;">
    <?php if ($success): ?>
    <div class="profile-alert success">
        <i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="profile-alert error">
        <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ══ TAB: Personal Information ══ -->
    <?php if ($tab === 'info'): ?>
    <div class="main-card">
      <div class="main-card-header">
        <div class="main-card-header-icon"><i class="fas fa-user"></i></div>
        <div>
          <div class="main-card-header-title">Personal Information</div>
          <div class="main-card-header-sub">Update your name, email and contact details</div>
        </div>
      </div>
      <div class="main-card-body">

        <form method="POST">
          <input type="hidden" name="action" value="update_info"/>

          <div class="f-grid-2">
            <div class="f-group">
              <label class="f-label">First Name</label>
                <input class="f-input" type="text" name="first_name"
                    value="<?= htmlspecialchars($user['first_name'] ?? '') ?>"
                    placeholder="Juan" required maxlength="30"
                    oninput="validateName(this)"/>
            </div>
            <div class="f-group">
              <label class="f-label">Last Name</label>
                <input class="f-input" type="text" name="last_name"
                    value="<?= htmlspecialchars($user['last_name'] ?? '') ?>"
                    placeholder="Dela Cruz" required maxlength="30"
                    oninput="validateName(this)"/>
            </div>
          </div>

          <div class="f-grid-1" style="margin-top:18px;">
            <div class="f-group">
              <label class="f-label">Email Address</label>
              <input class="f-input" type="email" name="email"
                     value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                     placeholder="juan@example.com"
                     readonly/>
            </div>
          </div>

          <div class="f-grid-2" style="margin-top:18px;">
            <div class="f-group">
              <label class="f-label">Phone Number</label>
              <div class="phone-input-wrap">
                <span class="phone-prefix">+63</span>
                <input class="f-input phone-input" type="tel" name="phone"
                       id="phoneInput"
                       value="<?= htmlspecialchars(preg_replace('/^\+63/', '', $user['phone'] ?? '')) ?>"
                       placeholder="912 345 6789"
                       maxlength="12"
                       oninput="formatPhone(this)"/>
              </div>
            </div>
            <div class="f-group">
              <label class="f-label">Gender</label>
              <select class="f-input" name="gender" id="genderSelect">
                <option value="" disabled <?= empty($user['gender']) ? 'selected' : '' ?>>Select gender</option>
                <option value="male"   <?= ($user['gender'] ?? '') === 'male'   ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                <option value="other"  <?= ($user['gender'] ?? '') === 'other'  ? 'selected' : '' ?>>Other</option>
              </select>
            </div>
          </div>

          <button type="submit" class="btn-save">
            <i class="fas fa-check"></i> Update Changes
          </button>
        </form>
      </div>
    </div>

    <!-- ══ TAB: Manage Address ══ -->
    <?php elseif ($tab === 'address'): ?>
    <div class="main-card">
      <div class="main-card-header">
        <div class="main-card-header-icon"><i class="fas fa-map-marker-alt"></i></div>
        <div>
          <div class="main-card-header-title">Manage Address</div>
          <div class="main-card-header-sub">Your saved delivery addresses</div>
        </div>
      </div>
      <div class="main-card-body">

        <?php if (!empty($saved_addresses)): ?>
        <div class="addr-list">
          <?php foreach ($saved_addresses as $addr): ?>
            <div class="addr-card">
              <div class="addr-info">
                <div class="addr-name">
                  <?= htmlspecialchars($addr['full_name']) ?>
                  <?php if ($addr['is_default']): ?>
                    <span class="addr-default-badge">Default</span>
                  <?php else: ?>
                    · <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="set_default_address"/>
                        <input type="hidden" name="addr_id" value="<?= $addr['id'] ?>"/>
                        <button type="submit" class="addr-link-btn">Set as Default</button>
                      </form>
                  <?php endif; ?>
                </div>
                <div class="addr-line">
                  <?= htmlspecialchars($addr['street']) ?><?php if (!empty($addr['barangay'])): ?>, <?= htmlspecialchars($addr['barangay']) ?><?php endif; ?>
                </div>
                <div class="addr-line"><?= htmlspecialchars($addr['city']) ?>, <?= htmlspecialchars($addr['province']) ?><?php if (!empty($addr['zip'])): ?> <?= htmlspecialchars($addr['zip']) ?><?php endif; ?> · <?= htmlspecialchars($addr['phone']) ?></div>
              </div>
              <div class="addr-row-actions">
                <button type="button" class="addr-act-edit" onclick="editAddress(<?= htmlspecialchars(json_encode($addr)) ?>)">Edit</button>
                <button type="button" class="addr-act-delete" onclick="deleteAddressConfirm(<?= $addr['id'] ?>, <?= htmlspecialchars(json_encode($addr['full_name'])) ?>)">Delete</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="addr-empty">
          <i class="fas fa-map-pin"></i>
          <div class="addr-empty-title">No saved addresses yet</div>
          <div class="addr-empty-sub">Add an address below to save it for faster checkout.</div>
        </div>
        <?php endif; ?>

        <div class="addr-section-divider">Add New Address</div>

        <form method="POST" id="addAddressForm" novalidate>
          <input type="hidden" name="action" id="formAction" value="add_address"/>
          <input type="hidden" name="addr_id" id="addrEditId" value=""/>

          <div class="f-grid-1" style="margin-bottom:18px;">
            <div class="f-group">
              <label class="f-label">Full Name <span class="req">*</span></label>
              <input class="f-input" type="text" name="addr_full_name" id="addr_full_name"
                     placeholder="Juan Dela Cruz" maxlength="100"
                     oninput="validateAddrName(this)"/>
              <span class="field-error" id="err_addr_name">
                <i class="fas fa-circle-exclamation"></i> Please enter a full name.
              </span>
            </div>
          </div>

          <div class="f-grid-1" style="margin-bottom:18px;">
            <div class="f-group">
              <label class="f-label">Street Address <span class="req">*</span></label>
              <input class="f-input" type="text" name="addr_street" id="addr_street"
                     placeholder="House/Unit No., Street Name"
                     oninput="stripAddressChars(this)"/>
              <span class="field-error" id="err_addr_street">
                <i class="fas fa-circle-exclamation"></i> Please enter a street address.
              </span>
            </div>
          </div>

          <div class="f-grid-2" style="margin-bottom:18px;">
            <div class="f-group">
              <label class="f-label">Region <span class="req">*</span></label>
              <div class="select-wrap">
                <select class="f-input" id="addr_region" name="addr_region" onchange="onRegionChange()">
                  <option value="" disabled selected>Select region…</option>
                </select>
                <i class="fas fa-chevron-down select-arrow"></i>
              </div>
            </div>
            <div class="f-group">
              <label class="f-label">Province <span class="req">*</span></label>
              <div class="select-wrap">
                <select class="f-input" id="addr_province" name="addr_province" onchange="onProvinceChange()" disabled>
                  <option value="" disabled selected>Select province…</option>
                </select>
                <i class="fas fa-chevron-down select-arrow"></i>
              </div>
            </div>
          </div>

          <div class="f-grid-2" style="margin-bottom:18px;">
            <div class="f-group">
              <label class="f-label">City / Municipality <span class="req">*</span></label>
              <div class="select-wrap">
                <select class="f-input" id="addr_city" name="addr_city" onchange="onCityChange()" disabled>
                  <option value="" disabled selected>Select city…</option>
                </select>
                <i class="fas fa-chevron-down select-arrow"></i>
              </div>
            </div>
            <div class="f-group">
              <label class="f-label">Barangay <span class="req">*</span></label>
              <div class="select-wrap">
                <select class="f-input" id="addr_barangay" name="addr_barangay" disabled>
                  <option value="" disabled selected>Select barangay…</option>
                </select>
                <i class="fas fa-chevron-down select-arrow"></i>
              </div>
            </div>
          </div>

          <div class="f-grid-2" style="margin-bottom:18px;">
            <div class="f-group">
              <label class="f-label">ZIP Code <span class="req">*</span></label>
              <input class="f-input" type="text" name="addr_zip" id="addr_zip"
                     placeholder="e.g. 1000" maxlength="4"
                     oninput="this.value=this.value.replace(/\D/g,'')"/>
              <span class="field-error" id="err_addr_zip">
                <i class="fas fa-circle-exclamation"></i> Please enter a valid 4-digit ZIP code.
              </span>
            </div>
            <div class="f-group">
              <label class="f-label">Phone Number <span class="req">*</span></label>
              <div class="phone-input-wrap">
                <span class="phone-prefix">+63</span>
                <input class="f-input phone-input" type="tel" name="addr_phone" id="addr_phone"
                       placeholder="912 345 6789" maxlength="13"
                       oninput="formatPhone(this)"/>
              </div>
              <span class="field-error" id="err_addr_phone">
                <i class="fas fa-circle-exclamation"></i> Please enter a valid 10-digit phone number.
              </span>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-save" id="btnAddAddress">
              <i class="fas fa-plus"></i> Add Address
            </button>
            <button type="button" class="btn-cancel" id="btnCancelEdit" style="display:none;" onclick="resetAddressForm()">Cancel</button>
          </div>
        </form>

      </div>
    </div>

    <!-- ══ TAB: Payment Method ══ -->
    <?php elseif ($tab === 'payment'): ?>
    <div class="main-card">
      <div class="main-card-header">
        <div class="main-card-header-icon"><i class="fas fa-credit-card"></i></div>
        <div>
          <div class="main-card-header-title">Payment Method</div>
          <div class="main-card-header-sub">Your saved payment options</div>
        </div>
      </div>
      <div class="main-card-body">
        <div style="text-align:center;padding:40px 0;color:#94a3b8;">
          <i class="fas fa-credit-card" style="font-size:2.5rem;margin-bottom:14px;display:block;opacity:.4;"></i>
          <div style="font-size:15px;font-weight:600;color:#64748b;margin-bottom:6px;">No payment methods saved</div>
          <div style="font-size:13px;">Payment methods used during checkout will appear here.</div>
        </div>
      </div>
    </div>

    <!-- ══ TAB: Password Manager ══ -->
    <?php elseif ($tab === 'password'): ?>
    <div class="main-card">
      <div class="main-card-header">
        <div class="main-card-header-icon"><i class="fas fa-lock"></i></div>
        <div>
          <div class="main-card-header-title">Password Manager</div>
          <div class="main-card-header-sub">Change your account password</div>
        </div>
      </div>
      <div class="main-card-body">
        <form method="POST" id="passwordForm" novalidate>
          <input type="hidden" name="action" value="change_password"/>

          <div class="f-grid-1">
            <div class="f-group">
              <label class="f-label">Current Password</label>
              <div style="position:relative;">
                <input class="f-input" type="password" name="current_password"
                       id="cur_pw" placeholder="Enter current password"
                       style="padding-right:42px;" required/>
                <span class="pw-toggle" onclick="togglePw('cur_pw',this)"
                      style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#94a3b8;font-size:13px;">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
            </div>

            <div class="f-group">
              <label class="f-label">New Password</label>
              <div style="position:relative;">
                <input class="f-input" type="password" name="new_password"
                       id="new_pw" placeholder="At least 8 characters"
                       style="padding-right:42px;" minlength="8" required/>
                <span class="pw-toggle" onclick="togglePw('new_pw',this)"
                      style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#94a3b8;font-size:13px;">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
              <p class="field-error" id="errNewPassword" role="alert">
                <i class="fas fa-circle-exclamation"></i>
                <span>Password must be at least 8 characters.</span>
              </p>
            </div>

            <div class="f-group">
              <label class="f-label">Confirm New Password</label>
              <div style="position:relative;">
                <input class="f-input" type="password" name="confirm_password"
                       id="con_pw" placeholder="Re-enter new password"
                       style="padding-right:42px;" required/>
                <span class="pw-toggle" onclick="togglePw('con_pw',this)"
                      style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#94a3b8;font-size:13px;">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
              <p class="field-error" id="errConfirmPassword" role="alert">
                <i class="fas fa-circle-exclamation"></i>
                <span>Passwords do not match.</span>
              </p>
            </div>
          </div>

          <button type="submit" class="btn-save" id="btnSavePassword">
            <i class="fas fa-lock"></i> Change Password
          </button>
        </form>
      </div>
    </div>

    <!-- ══ TAB: My Orders ══ -->
    <?php elseif ($tab === 'orders'): ?>
    <div class="main-card">
      <div class="main-card-header">
        <div class="main-card-header-icon"><i class="fas fa-box"></i></div>
        <div>
          <div class="main-card-header-title">My Orders</div>
          <div class="main-card-header-sub">Track and manage your order history</div>
        </div>
      </div>
      
      <!-- Toolbar: Filter -->
      <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 28px;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;gap:10px;">
        <div style="font-size:13px;font-weight:600;color:#1e293b;">
          Orders <span style="display:inline-flex;align-items:center;justify-content:center;background:#3b82f6;color:#fff;border-radius:20px;padding:2px 10px;font-size:12px;font-weight:600;margin-left:8px;"><?= $total_orders ?></span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;">
          <span>Sort by :</span>
          <form method="GET" id="filterForm" style="display:inline;">
            <input type="hidden" name="tab" value="orders"/>
            <input type="hidden" name="order_page" value="1"/>
            <select name="filter" onchange="document.getElementById('filterForm').submit()" style="border:1.5px solid #e2e8f0;border-radius:8px;padding:6px 30px 6px 12px;font-size:13px;font-family:'Poppins',sans-serif;font-weight:500;color:#1e293b;background:#fff;background-image:url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%228%22 fill=%22none%22%3E%3Cpath d=%22M1 1l5 5 5-5%22 stroke=%22%2394a3b8%22 stroke-width=%221.6%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 10px center;appearance:none;cursor:pointer;outline:none;">
              <option value="all"        <?= $filter === 'all'        ? 'selected' : '' ?>>All</option>
              <option value="pending"    <?= $filter === 'pending'    ? 'selected' : '' ?>>Pending</option>
              <option value="accepted"   <?= $filter === 'accepted'   ? 'selected' : '' ?>>Accepted</option>
              <option value="shipped"    <?= $filter === 'shipped'    ? 'selected' : '' ?>>Shipped</option>
              <option value="delivered"  <?= $filter === 'delivered'  ? 'selected' : '' ?>>Delivered</option>
              <option value="cancelled"  <?= $filter === 'cancelled'  ? 'selected' : '' ?>>Cancelled</option>
            </select>
          </form>
        </div>
      </div>

      <!-- Orders body -->
      <div style="padding:30px 28px;">
        <?php if (empty($orders)): ?>
        <div style="padding:60px 30px;text-align:center;">
          <div style="font-size:3rem;color:#cbd5e1;margin-bottom:16px;"><i class="fas fa-box-open"></i></div>
          <div style="font-size:16px;font-weight:600;color:#64748b;margin-bottom:8px;">No orders found</div>
          <div style="font-size:13px;color:#94a3b8;">
            <?= $filter !== 'all' ? 'No ' . htmlspecialchars($filter) . ' orders yet.' : 'You haven\'t placed any orders yet.' ?>
          </div>
        </div>

        <?php else: ?>
        <?php foreach ($orders as $order):
            $badge = statusBadge($order['status']);
            $status = strtolower($order['status']);
            $date_value = date('d F Y', strtotime($order['order_date']));
            $status_messages = [
                'pending'    => 'Your Order is Pending',
                'processing' => 'Your Order is Being Processed',
                'accepted'   => 'Your Order has been Accepted',
                'shipped'    => 'Your Order is On the Way',
                'delivered'  => 'Your Order has been Delivered',
                'cancelled'  => 'Your Order has been Cancelled',
            ];
            $status_msg = $status_messages[$status] ?? 'Order Status: ' . ucfirst($status);
            $order_num = $order['order_number'] ?? '#ORD-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
        ?>
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:20px;overflow:hidden;transition:box-shadow .2s;">
          <!-- Header row -->
          <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;background:#f3f4f6;border-bottom:1px solid #e5e7eb;padding:14px 20px;">
            <div style="display:flex;flex-direction:column;gap:3px;">
              <span style="font-size:11px;font-weight:600;color:#4b5563;text-transform:uppercase;letter-spacing:.04em;">Order ID</span>
              <span style="font-size:13.5px;font-weight:700;color:#1f2937;"><?= htmlspecialchars($order_num) ?></span>
            </div>
            <div style="display:flex;flex-direction:column;gap:3px;">
              <span style="font-size:11px;font-weight:600;color:#4b5563;text-transform:uppercase;letter-spacing:.04em;">Total Payment</span>
              <span style="font-size:13.5px;font-weight:700;color:#1e293b;">₱<?= number_format($order['total_amount'], 2) ?></span>
            </div>
            <div style="display:flex;flex-direction:column;gap:3px;">
              <span style="font-size:11px;font-weight:600;color:#4b5563;text-transform:uppercase;letter-spacing:.04em;">Payment Method</span>
              <span style="font-size:13.5px;font-weight:700;color:#1e293b;"><?= htmlspecialchars(ucfirst($order['payment_method'] ?? 'N/A')) ?></span>
            </div>
            <div style="display:flex;flex-direction:column;gap:3px;">
              <span style="font-size:11px;font-weight:600;color:#4b5563;text-transform:uppercase;letter-spacing:.04em;">Order Date</span>
              <span style="font-size:13.5px;font-weight:700;color:#1e293b;"><?= htmlspecialchars($date_value) ?></span>
            </div>
          </div>

          <!-- Items -->
          <div style="padding:0;">
            <?php foreach ($order['items'] as $item): ?>
            <div style="display:flex;align-items:center;gap:16px;padding:14px 20px;border-bottom:1px solid #f1f5f9;transition:background .15s;">
              <?php
                $img_path = '';
                if (!empty($item['product_image'])) {
                    $img_path = 'images/products/' . $item['product_image'];
                }
              ?>
              <?php if ($img_path): ?>
                <img src="<?= htmlspecialchars($img_path) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" style="width:60px;height:60px;border-radius:10px;object-fit:cover;border:1px solid #e2e8f0;flex-shrink:0;background:#f8fafc;"/>
              <?php else: ?>
                <div style="width:60px;height:60px;border-radius:10px;background:#f1f5f9;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#cbd5e1;font-size:20px;"><i class="fas fa-image"></i></div>
              <?php endif; ?>
              <div style="flex:1;min-width:0;">
                <div style="font-size:14px;font-weight:600;color:#1e293b;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($item['product_name']) ?></div>
                <div style="font-size:12px;color:#94a3b8;font-weight:400;"><?= htmlspecialchars(($item['quantity'] ?? 1) . ' Qty.') ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Footer: status + actions -->
          <div style="padding:12px 20px;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fafbfc;">
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="display:inline-flex;align-items:center;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;border:1px solid;color:<?= $badge['color'] ?>;background:<?= $badge['bg'] ?>;border-color:<?= $badge['border'] ?>;">
                <?= htmlspecialchars($badge['label']) ?>
              </span>
              <span style="font-size:13px;color:#64748b;font-weight:400;"><?= htmlspecialchars($status_msg) ?></span>
            </div>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
              <?php if ($status === 'delivered'): ?>
                <a href="review.php?order=<?= $order['id'] ?>" style="display:inline-flex;align-items:center;gap:7px;background:#1e293b;color:#fff;border:none;border-radius:9px;padding:8px 18px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s;text-decoration:none;">
                  <i class="fas fa-star"></i> Add Review
                </a>
                <!-- ✅ INVOICE BUTTON — opens modal instead of navigating away -->
                <a href="#" onclick="openInvoiceModal(<?= intval($order['id']) ?>); return false;" style="display:inline-flex;align-items:center;gap:7px;background:#fff;color:#1e293b;border:1.5px solid #e2e8f0;border-radius:9px;padding:7px 18px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:border-color .2s,background .2s;text-decoration:none;">
                  <i class="fas fa-file-invoice"></i> Invoice
                </a>

              <?php elseif ($status === 'cancelled'): ?>
                <!-- ✅ INVOICE BUTTON — opens modal instead of navigating away -->
                <a href="#" onclick="openInvoiceModal(<?= intval($order['id']) ?>); return false;" style="display:inline-flex;align-items:center;gap:7px;background:#fff;color:#1e293b;border:1.5px solid #e2e8f0;border-radius:9px;padding:7px 18px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:border-color .2s,background .2s;text-decoration:none;">
                  <i class="fas fa-file-invoice"></i> Invoice
                </a>

              <?php else: ?>
                <a href="track_order.php?order=<?= $order['id'] ?>" style="display:inline-flex;align-items:center;gap:7px;background:#1e293b;color:#fff;border:none;border-radius:9px;padding:8px 18px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s,transform .15s;text-decoration:none;">
                  <i class="fas fa-location-dot"></i> Track Order
                </a>
                <!-- ✅ INVOICE BUTTON — opens modal instead of navigating away -->
                <a href="#" onclick="openInvoiceModal(<?= intval($order['id']) ?>); return false;" style="display:inline-flex;align-items:center;gap:7px;background:#fff;color:#1e293b;border:1.5px solid #e2e8f0;border-radius:9px;padding:7px 18px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:border-color .2s,background .2s;text-decoration:none;">
                  <i class="fas fa-file-invoice"></i> Invoice
                </a>
                <?php if (in_array($status, ['pending', 'processing'])): ?>
                  <a href="#" onclick="cancelOrderConfirm(<?= intval($order['id']) ?>, parseFloat('<?= number_format($order['total_amount'], 2, '.', '') ?>'), '<?= htmlspecialchars(strtoupper($status)) ?>'); return false;" style="background:none;border:none;color:#ef4444;font-size:13px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;transition:text-decoration .2s;text-decoration:none;display:inline;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                    Cancel Order
                  </a>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:32px;padding-top:24px;border-top:1px solid #f1f5f9;">
          <?php if ($current_page > 1): ?>
            <a href="?tab=orders&filter=<?= urlencode($filter) ?>&order_page=<?= $current_page - 1 ?>" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border:1px solid #e2e8f0;border-radius:6px;color:#64748b;font-weight:600;text-decoration:none;transition:all .2s;background:#fff;" onmouseover="this.style.background='#f9fafb';this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='#fff';this.style.borderColor='#e2e8f0'">
              <i class="fas fa-chevron-left" style="font-size:12px;"></i>
            </a>
          <?php endif; ?>
          
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $current_page): ?>
              <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border:1px solid #3b82f6;background:#3b82f6;color:#fff;border-radius:6px;font-weight:600;font-size:13px;"><?= $i ?></span>
            <?php elseif ($i >= $current_page - 1 && $i <= $current_page + 1): ?>
              <a href="?tab=orders&filter=<?= urlencode($filter) ?>&order_page=<?= $i ?>" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border:1px solid #e2e8f0;border-radius:6px;color:#64748b;font-weight:600;text-decoration:none;transition:all .2s;background:#fff;" onmouseover="this.style.background='#f9fafb';this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='#fff';this.style.borderColor='#e2e8f0'"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          
          <?php if ($current_page < $total_pages): ?>
            <a href="?tab=orders&filter=<?= urlencode($filter) ?>&order_page=<?= $current_page + 1 ?>" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border:1px solid #e2e8f0;border-radius:6px;color:#64748b;font-weight:600;text-decoration:none;transition:all .2s;background:#fff;" onmouseover="this.style.background='#f9fafb';this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='#fff';this.style.borderColor='#e2e8f0'">
              <i class="fas fa-chevron-right" style="font-size:12px;"></i>
            </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
      </div>
    </div>

    <?php endif; ?>

  </main>
</div>

<?php include 'footer.php'; ?>

<!-- ══════════════════════════════════════
     INVOICE MODAL
══════════════════════════════════════ -->
<div id="invoiceModalOverlay" class="inv-overlay" style="display:none;" onclick="if(event.target===this) closeInvoiceModal()">
    <div class="inv-modal" id="invoiceModalBox">

        <!-- Header -->
        <div class="inv-header">
            <div class="inv-header-left">
                <div class="inv-header-icon"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <div class="inv-header-title">Order Invoice</div>
                    <div class="inv-header-sub" id="invOrderNum">—</div>
                </div>
            </div>
            <button class="inv-close-btn" onclick="closeInvoiceModal()" title="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Dynamic body -->
        <div class="inv-body" id="invoiceModalBody">
            <div class="inv-loading">
                <i class="fas fa-circle-notch"></i>
                <span>Loading invoice…</span>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
// ── Password visibility toggle ──
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  const ico = btn.querySelector('i');
  if (inp.type === 'password') {
    inp.type = 'text';
    ico.className = 'fas fa-eye-slash';
  } else {
    inp.type = 'password';
    ico.className = 'fas fa-eye';
  }
}

// ── Gender placeholder color ──
const genderSelect = document.getElementById('genderSelect');
function updateGenderColor() {
  if (genderSelect) {
    genderSelect.style.color = genderSelect.value === '' ? '#cbd5e1' : '#1e293b';
  }
}
if (genderSelect) {
  updateGenderColor();
  genderSelect.addEventListener('change', updateGenderColor);
}

// ── Name validation ──
function validateName(input) {
  input.value = input.value.replace(/[^a-zA-ZÀ-ÿ\s\-]/g, '');
  const dashes = input.value.match(/-/g);
  if (dashes && dashes.length > 1) {
    input.value = input.value.replace(/-(?=.*-)/, '');
  }
  if (input.value.length > 30) {
    input.value = input.value.slice(0, 30);
  }
}

// ── Phone formatting ──
function formatPhone(input) {
  let digits = input.value.replace(/\D/g, '').slice(0, 10);
  let formatted = '';
  if (digits.length <= 3) {
    formatted = digits;
  } else if (digits.length <= 6) {
    formatted = digits.slice(0,3) + ' ' + digits.slice(3);
  } else {
    formatted = digits.slice(0,3) + ' ' + digits.slice(3,6) + ' ' + digits.slice(6);
  }
  input.value = formatted;
}

// ── Address field helpers ──
function validateAddrName(input) {
  input.value = input.value.replace(/[^a-zA-ZÀ-ÿ\s\-'.]/g, '').slice(0, 100);
}
function stripAddressChars(input) {
  input.value = input.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ0-9\s#,.\-\/']/g, '');
}

// ── Password validation (real-time) ──
document.addEventListener('DOMContentLoaded', function() {
  const newPw = document.getElementById('new_pw');
  const conPw = document.getElementById('con_pw');
  const errNew = document.getElementById('errNewPassword');
  const errCon = document.getElementById('errConfirmPassword');
  const wrapNew = newPw?.closest('.f-group')?.querySelector('.f-input');
  const wrapCon = conPw?.closest('.f-group')?.querySelector('.f-input');
  const submitBtn = document.getElementById('btnSavePassword');
  const passwordForm = document.getElementById('passwordForm');
  
  if (!newPw || !conPw) return;
  
  function validateNew() {
    const val = newPw.value;
    if (val.length === 0) {
      wrapNew?.classList.remove('error-field', 'success-field');
      errNew?.classList.remove('show');
      return false;
    }
    if (val.length < 8) {
      wrapNew?.classList.add('error-field');
      wrapNew?.classList.remove('success-field');
      errNew?.classList.add('show');
      return false;
    }
    wrapNew?.classList.remove('error-field');
    wrapNew?.classList.add('success-field');
    errNew?.classList.remove('show');
    return true;
  }
  
  function validateConfirm() {
    const newVal = newPw.value;
    const conVal = conPw.value;
    if (conVal.length === 0) {
      wrapCon?.classList.remove('error-field', 'success-field');
      errCon?.classList.remove('show');
      return false;
    }
    if (newVal !== conVal) {
      wrapCon?.classList.add('error-field');
      wrapCon?.classList.remove('success-field');
      errCon?.classList.add('show');
      return false;
    }
    wrapCon?.classList.remove('error-field');
    wrapCon?.classList.add('success-field');
    errCon?.classList.remove('show');
    return true;
  }
  
  function updateSubmit() {
    const newValid = validateNew();
    const conValid = validateConfirm();
    if (submitBtn) {
      submitBtn.disabled = !(newValid && conValid);
    }
  }
  
  newPw.addEventListener('input', function() {
    validateNew();
    if (conPw.value.length > 0) validateConfirm();
    updateSubmit();
  });
  
  conPw.addEventListener('input', function() {
    validateConfirm();
    updateSubmit();
  });
  
  newPw.addEventListener('blur', validateNew);
  conPw.addEventListener('blur', validateConfirm);
  
  if (passwordForm && submitBtn) {
    passwordForm.addEventListener('submit', function(e) {
      if (!validateNew() || !validateConfirm()) {
        e.preventDefault();
        updateSubmit();
        return;
      }
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i> Updating...';
    });
  }
});

// ── Auto-fade alert messages ──
document.querySelectorAll('.profile-alert').forEach(alert => {
  setTimeout(() => {
    alert.style.transition = 'opacity 0.6s ease';
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 600);
  }, 3000);
});

// ── Sidebar logout trigger ──
document.addEventListener('DOMContentLoaded', function() {
    const sidebarLogout = document.getElementById('sidebarLogout');
    const logoutModal = document.getElementById('logoutModal');
    
    if (sidebarLogout && logoutModal) {
        sidebarLogout.addEventListener('click', function(e) {
            e.preventDefault();
            logoutModal.classList.add('active');
            document.body.classList.add('modal-open');
        });
    }
});

// ══════════════════════════════════════
// PHILIPPINE ADDRESS DROPDOWNS
// ══════════════════════════════════════
(function initProfileAddressCascade() {
  const BASE = 'https://psgc.cloud/api';

  const REGION_GROUPS = {
    'Metro Manila':  ['National Capital'],
    'North Luzon':   ['Region I', 'Region II', 'Region III', 'Cordillera'],
    'South Luzon':   ['Region IV', 'Region V', 'MIMAROPA'],
    'Visayas':       ['Region VI', 'Region VII', 'Region VIII'],
    'Mindanao':      ['Region IX', 'Region X', 'Region XI', 'Region XII', 'Region XIII', 'BARMM', 'Bangsamoro'],
  };

  function formatCityName(raw) {
    const decoded = raw
      .replace(/Ã±/g, 'ñ').replace(/Ã©/g, 'é').replace(/Ã¡/g, 'á')
      .replace(/Ã­/g, 'í').replace(/Ã³/g, 'ó').replace(/Ãº/g, 'ú').replace(/Ã'/g, 'Ñ');
    const trimmed = decoded.trim();
    const match   = trimmed.match(/^City\s+of\s+(.+)$/i);
    return match ? `${match[1].trim()} City` : trimmed;
  }

  const selRegion   = document.getElementById('addr_region');
  const selProvince = document.getElementById('addr_province');
  const selCity     = document.getElementById('addr_city');
  const selBarangay = document.getElementById('addr_barangay');

  const wrapRegion   = selRegion?.closest('.select-wrap');
  const wrapProvince = selProvince?.closest('.select-wrap');
  const wrapCity     = selCity?.closest('.select-wrap');
  const wrapBarangay = selBarangay?.closest('.select-wrap');

  if (!selRegion || !selProvince || !selCity || !selBarangay) return;

  async function psgcFetch(url, wrapEl) {
    wrapEl?.classList.add('loading');
    try {
      const res = await fetch(url);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    } catch (err) {
      console.error('[PSGC]', err);
      return null;
    } finally {
      wrapEl?.classList.remove('loading');
    }
  }

  function resetSelect(sel, placeholder) {
    sel.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
    sel.classList.remove('has-value');
  }

  function disableSelects(...selects) {
    selects.forEach(s => { s.disabled = true; s.classList.remove('has-value'); });
  }

  async function populateRegions() {
    resetSelect(selRegion, 'Loading regions…');
    selRegion.disabled = true;
    disableSelects(selProvince, selCity, selBarangay);

    const allRegions = await psgcFetch(`${BASE}/regions`, wrapRegion);
    if (!allRegions) {
      resetSelect(selRegion, 'Failed to load — reload page');
      return;
    }

    window._psgcAllRegions = allRegions;

    resetSelect(selRegion, 'Select region…');
    Object.keys(REGION_GROUPS).forEach(groupLabel => {
      const opt = document.createElement('option');
      opt.value = groupLabel;
      opt.dataset.name = groupLabel;
      opt.dataset.isNcr = groupLabel === 'Metro Manila' ? '1' : '0';
      opt.textContent = groupLabel;
      selRegion.appendChild(opt);
    });

    selRegion.disabled = false;
  }

  async function onRegionChange() {
    const selectedGroup = selRegion.value;
    const isNCR = selRegion.options[selRegion.selectedIndex]?.dataset.isNcr === '1';

    resetSelect(selProvince, 'Loading provinces…');
    resetSelect(selCity,     'Select city / municipality…');
    resetSelect(selBarangay, 'Select barangay…');
    disableSelects(selProvince, selCity, selBarangay);

    selRegion.classList.add('has-value');

    if (!selectedGroup) return;

    if (isNCR) {
      resetSelect(selProvince, 'N/A (Metro Manila)');
      selProvince.disabled = true;

      const ncrRegion = window._psgcAllRegions?.find(r => r.name.includes('National Capital'));
      if (ncrRegion) {
        const cities = await psgcFetch(`${BASE}/regions/${ncrRegion.code}/cities-municipalities`, wrapCity);
        populateCities(cities);
      }
      return;
    }

    const keywords = REGION_GROUPS[selectedGroup];
    const matchingRegionCodes = (window._psgcAllRegions || [])
      .filter(r => keywords.some(kw => r.name.includes(kw)))
      .map(r => r.code);

    if (!matchingRegionCodes.length) {
      resetSelect(selProvince, 'No provinces found');
      return;
    }

    const provincePromises = matchingRegionCodes.map(code =>
      psgcFetch(`${BASE}/regions/${code}/provinces`, wrapProvince)
    );

    const results = await Promise.all(provincePromises);
    const allProvinces = results
      .filter(arr => Array.isArray(arr))
      .flat()
      .filter((v, i, a) => a.findIndex(p => p.code === v.code) === i);

    if (!allProvinces.length) {
      resetSelect(selProvince, 'No provinces found');
      return;
    }

    resetSelect(selProvince, 'Select province…');
    allProvinces
      .sort((a, b) => a.name.localeCompare(b.name))
      .forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.code;
        opt.dataset.name = p.name;
        opt.textContent = p.name;
        selProvince.appendChild(opt);
      });

    selProvince.disabled = false;
  }

  async function onProvinceChange() {
    const provinceCode = selProvince.value;

    resetSelect(selCity,     'Loading cities…');
    resetSelect(selBarangay, 'Select barangay…');
    disableSelects(selCity, selBarangay);

    selProvince.classList.add('has-value');

    if (!provinceCode) return;

    const cities = await psgcFetch(`${BASE}/provinces/${provinceCode}/cities-municipalities`, wrapCity);
    populateCities(cities);
  }

  function populateCities(cities) {
    if (!cities || cities.length === 0) {
      resetSelect(selCity, 'No cities found');
      return;
    }
    resetSelect(selCity, 'Select city / municipality…');
    cities
      .map(c => ({ ...c, displayName: formatCityName(c.name) }))
      .sort((a, b) => a.displayName.localeCompare(b.displayName))
      .forEach(c => {
        const opt = document.createElement('option');
        opt.value        = c.code;
        opt.dataset.name = c.displayName;
        opt.textContent  = c.displayName;
        selCity.appendChild(opt);
      });
    selCity.disabled = false;
  }

  async function onCityChange() {
    const cityCode = selCity.value;

    resetSelect(selBarangay, 'Loading barangays…');
    selBarangay.disabled = true;

    selCity.classList.add('has-value');

    if (!cityCode) return;

    const barangays = await psgcFetch(
      `${BASE}/cities-municipalities/${cityCode}/barangays`,
      wrapBarangay
    );

    if (!barangays || barangays.length === 0) {
      resetSelect(selBarangay, 'No barangays found');
      return;
    }

    resetSelect(selBarangay, 'Select barangay…');
    barangays
      .sort((a, b) => a.name.localeCompare(b.name))
      .forEach(b => {
        const opt = document.createElement('option');
        opt.value        = b.name;
        opt.dataset.name = b.name;
        opt.textContent  = b.name;
        selBarangay.appendChild(opt);
      });

    selBarangay.disabled = false;
  }

  function onBarangayChange() {
    selBarangay.classList.add('has-value');
  }

  selRegion.addEventListener('change',   onRegionChange);
  selProvince.addEventListener('change', onProvinceChange);
  selCity.addEventListener('change',     onCityChange);
  selBarangay.addEventListener('change', onBarangayChange);

  populateRegions();
})();

// ══════════════════════════════════════
// ADDRESS FORM VALIDATION & SUBMISSION
// ══════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
  const regionSel  = document.getElementById('addr_region');
  const provSel    = document.getElementById('addr_province');
  const citySel    = document.getElementById('addr_city');
  const brySel     = document.getElementById('addr_barangay');
  const nameField  = document.getElementById('addr_full_name');
  const streetField = document.getElementById('addr_street');
  const zipField   = document.getElementById('addr_zip');
  const phoneField = document.getElementById('addr_phone');

  nameField?.addEventListener('input', function() {
    if (this.value.trim()) {
      this.classList.remove('error-field');
      document.getElementById('err_addr_name')?.classList.remove('show');
    }
  });

  streetField?.addEventListener('input', function() {
    if (this.value.trim()) {
      this.classList.remove('error-field');
      document.getElementById('err_addr_street')?.classList.remove('show');
    }
  });

  zipField?.addEventListener('input', function() {
    if (this.value.trim().length >= 4) {
      this.classList.remove('error-field');
      document.getElementById('err_addr_zip')?.classList.remove('show');
    }
  });

  phoneField?.addEventListener('input', function() {
    const digits = this.value.replace(/\D/g,'');
    if (digits.length >= 10) {
      this.classList.remove('error-field');
      document.getElementById('err_addr_phone')?.classList.remove('show');
    }
  });

  regionSel?.addEventListener('change', function() { if (this.value) this.classList.remove('invalid'); });
  provSel?.addEventListener('change',   function() { if (this.value) this.classList.remove('invalid'); });
  citySel?.addEventListener('change',   function() { if (this.value) this.classList.remove('invalid'); });
  brySel?.addEventListener('change',    function() { if (this.value) this.classList.remove('invalid'); });
});

document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('addAddressForm');
  if (!form) return;

  form.addEventListener('submit', function(e) {
    const regionSel  = document.getElementById('addr_region');
    const provSel    = document.getElementById('addr_province');
    const citySel    = document.getElementById('addr_city');
    const brySel     = document.getElementById('addr_barangay');
    const name       = document.getElementById('addr_full_name');
    const street     = document.getElementById('addr_street');
    const zip        = document.getElementById('addr_zip');
    const phone      = document.getElementById('addr_phone');

    function getSelectedText(sel) {
      return sel?.options[sel.selectedIndex]?.text || '';
    }

    let valid = true;

    if (!name?.value.trim()) {
      name?.classList.add('error-field');
      document.getElementById('err_addr_name')?.classList.add('show');
      valid = false;
    } else {
      name?.classList.remove('error-field');
      document.getElementById('err_addr_name')?.classList.remove('show');
    }

    if (!street?.value.trim()) {
      street?.classList.add('error-field');
      document.getElementById('err_addr_street')?.classList.add('show');
      valid = false;
    } else {
      street?.classList.remove('error-field');
      document.getElementById('err_addr_street')?.classList.remove('show');
    }

    if (!regionSel?.value) {
      regionSel?.classList.add('invalid');
      valid = false;
    } else {
      regionSel?.classList.remove('invalid');
    }

    if (!provSel?.disabled && !provSel?.value) {
      provSel?.classList.add('invalid');
      valid = false;
    } else {
      provSel?.classList.remove('invalid');
    }

    if (!citySel?.value) {
      citySel?.classList.add('invalid');
      valid = false;
    } else {
      citySel?.classList.remove('invalid');
    }

    if (!brySel?.value) {
      brySel?.classList.add('invalid');
      valid = false;
    } else {
      brySel?.classList.remove('invalid');
    }

    if (!zip?.value.trim() || zip.value.trim().length < 4) {
      zip?.classList.add('error-field');
      document.getElementById('err_addr_zip')?.classList.add('show');
      valid = false;
    } else {
      zip?.classList.remove('error-field');
      document.getElementById('err_addr_zip')?.classList.remove('show');
    }

    const phoneDigits = phone?.value.replace(/\D/g,'') || '';
    if (!phone?.value.trim() || phoneDigits.length < 10) {
      phone?.classList.add('error-field');
      document.getElementById('err_addr_phone')?.classList.add('show');
      valid = false;
    } else {
      phone?.classList.remove('error-field');
      document.getElementById('err_addr_phone')?.classList.remove('show');
    }

    if (!valid) {
      e.preventDefault();
      return;
    }

    if (regionSel  && regionSel.value)  { regionSel.options[regionSel.selectedIndex].value   = getSelectedText(regionSel);  }
    if (provSel    && provSel.value)    { provSel.options[provSel.selectedIndex].value         = getSelectedText(provSel);    }
    if (citySel    && citySel.value)    { citySel.options[citySel.selectedIndex].value         = getSelectedText(citySel);    }
    if (brySel     && brySel.value)     { brySel.options[brySel.selectedIndex].value           = getSelectedText(brySel);     }

    const btn = document.getElementById('btnAddAddress');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';
    }
  });
});

function editAddress(addr) {
  document.getElementById('formAction').value = 'edit_address';
  document.getElementById('addrEditId').value = addr.id || '';

  document.querySelectorAll('.error-field').forEach(el => el.classList.remove('error-field'));
  document.querySelectorAll('.invalid').forEach(el => el.classList.remove('invalid'));
  document.querySelectorAll('.field-error').forEach(el => el.classList.remove('show'));

  const nameEl   = document.getElementById('addr_full_name');
  const streetEl = document.getElementById('addr_street');
  const zipEl    = document.getElementById('addr_zip');
  const phoneEl  = document.getElementById('addr_phone');
  const regionSel = document.getElementById('addr_region');
  const provSel = document.getElementById('addr_province');
  const citySel = document.getElementById('addr_city');
  const brySel = document.getElementById('addr_barangay');

  if (nameEl)   nameEl.value   = addr.full_name || '';
  if (streetEl) streetEl.value = addr.street    || '';
  if (zipEl)    zipEl.value    = addr.zip        || '';
  if (phoneEl)  phoneEl.value  = (addr.phone || '').replace(/^\+63/, '');

  if (regionSel) { regionSel.value = addr.region || ''; regionSel.classList.add('has-value'); }
  if (provSel && addr.province) { provSel.value = addr.province || ''; provSel.classList.add('has-value'); }
  if (citySel && addr.city) { citySel.value = addr.city || ''; citySel.classList.add('has-value'); }
  if (brySel && addr.barangay) { brySel.value = addr.barangay || ''; brySel.classList.add('has-value'); }

  const btn = document.getElementById('btnAddAddress');
  const cancelBtn = document.getElementById('btnCancelEdit');
  if (btn) btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
  if (cancelBtn) cancelBtn.style.display = 'inline-block';

  document.getElementById('addAddressForm')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  nameEl?.focus();
}

function resetAddressForm() {
  const form = document.getElementById('addAddressForm');
  const action = document.getElementById('formAction');
  const addrId = document.getElementById('addrEditId');
  
  form.reset();
  action.value = 'add_address';
  addrId.value = '';
  
  document.querySelectorAll('.error-field').forEach(el => el.classList.remove('error-field'));
  document.querySelectorAll('.invalid').forEach(el => el.classList.remove('invalid'));
  document.querySelectorAll('.field-error').forEach(el => el.classList.remove('show'));
  document.querySelectorAll('.select-wrap .f-input').forEach(el => el.classList.remove('has-value'));
  
  const regionSel = document.getElementById('addr_region');
  if (regionSel) { regionSel.disabled = false; regionSel.value = ''; }
  document.getElementById('addr_province')?.setAttribute('disabled', '');
  document.getElementById('addr_city')?.setAttribute('disabled', '');
  document.getElementById('addr_barangay')?.setAttribute('disabled', '');
  
  const btn = document.getElementById('btnAddAddress');
  const cancelBtn = document.getElementById('btnCancelEdit');
  if (btn) { btn.innerHTML = '<i class="fas fa-plus"></i> Add Address'; btn.disabled = false; }
  if (cancelBtn) cancelBtn.style.display = 'none';
}

function deleteAddressConfirm(addrId, addrName) {
  const modal = document.createElement('div');
  modal.className = 'delete-modal-overlay';
  modal.innerHTML = `
    <div class="delete-modal">
      <div class="delete-modal-header">
        <h3>Delete Address</h3>
        <button type="button" class="delete-modal-close" onclick="this.closest('.delete-modal-overlay').remove()">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="delete-modal-body">
        <p>Are you sure you want to delete this address?</p>
        <p style="font-weight: 600; color: #1f2937; margin: 12px 0;">${addrName || 'This address'}</p>
        <p style="font-size: 12px; color: #6b7280;">This action cannot be undone.</p>
      </div>
      <div class="delete-modal-footer">
        <button type="button" class="delete-modal-btn cancel" onclick="this.closest('.delete-modal-overlay').remove()">
          Cancel
        </button>
        <button type="button" class="delete-modal-btn confirm" onclick="submitDeleteAddress(${addrId})">
          <i class="fas fa-trash"></i> Delete
        </button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);
}

function submitDeleteAddress(addrId) {
  const form = document.createElement('form');
  form.method = 'POST';
  form.style.display = 'none';
  form.innerHTML = `
    <input type="hidden" name="action" value="delete_address"/>
    <input type="hidden" name="addr_id" value="${addrId}"/>
  `;
  document.body.appendChild(form);
  form.submit();
}

function cancelOrderConfirm(orderId, totalPrice, orderStatus) {
  const modal = document.createElement('div');
  modal.className = 'delete-modal-overlay';
  modal.innerHTML = `
    <div class="delete-modal">
      <div class="delete-modal-header">
        <h3>Cancel Order</h3>
        <button type="button" class="delete-modal-close" onclick="this.closest('.delete-modal-overlay').remove()">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="delete-modal-body">
        <p>Are you sure you want to cancel this order?</p>
        <div style="background: #f3f4f6; padding: 12px; border-radius: 8px; margin: 12px 0;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
            <span style="color: #6b7280;">Order Status:</span>
            <span style="font-weight: 600; color: #f59e0b;">${orderStatus}</span>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="color: #6b7280;">Amount:</span>
            <span style="font-weight: 600; color: #1f2937;">₱${parseFloat(totalPrice).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
          </div>
        </div>
        <p style="font-size: 12px; color: #6b7280;">Once cancelled, you cannot undo this action. A refund will be processed if applicable.</p>
      </div>
      <div class="delete-modal-footer">
        <button type="button" class="delete-modal-btn cancel" onclick="this.closest('.delete-modal-overlay').remove()">
          Keep Order
        </button>
        <button type="button" class="delete-modal-btn confirm" onclick="submitCancelOrder(${orderId})" style="background: #ef4444; border-color: #ef4444; color: white;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
          <i class="fas fa-ban"></i> Cancel Order
        </button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);
}

function submitCancelOrder(orderId) {
  window.location.href = `cancel_order.php?order=${orderId}`;
}

// ══════════════════════════════════════
// INVOICE MODAL — JS
// ══════════════════════════════════════
function openInvoiceModal(orderId) {
    const overlay = document.getElementById('invoiceModalOverlay');
    const body    = document.getElementById('invoiceModalBody');
    const numEl   = document.getElementById('invOrderNum');

    numEl.textContent = '—';
    body.innerHTML = `<div class="inv-loading"><i class="fas fa-circle-notch"></i><span>Loading invoice…</span></div>`;
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    fetch('get_invoice.php?order=' + encodeURIComponent(orderId))
        .then(r => { if (!r.ok) throw new Error('Server error ' + r.status); return r.json(); })
        .then(data => renderInvoiceModal(data))
        .catch(err => {
            console.error(err);
            body.innerHTML = `<div class="inv-error">
                <i class="fas fa-circle-exclamation"></i>
                <p>Could not load invoice. Please try again.</p>
            </div>`;
        });
}

function closeInvoiceModal() {
    document.getElementById('invoiceModalOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeInvoiceModal();
});

function renderInvoiceModal(d) {
    const statusMap = {
        pending:    { label:'Pending',    color:'#f59e0b', bg:'#fffbeb', border:'#fde68a' },
        processing: { label:'Processing', color:'#3b82f6', bg:'#eff6ff', border:'#bfdbfe' },
        accepted:   { label:'Accepted',   color:'#f59e0b', bg:'#fffbeb', border:'#fde68a' },
        shipped:    { label:'Shipped',    color:'#8b5cf6', bg:'#f5f3ff', border:'#ddd6fe' },
        delivered:  { label:'Delivered',  color:'#16a34a', bg:'#f0fdf4', border:'#bbf7d0' },
        cancelled:  { label:'Cancelled',  color:'#ef4444', bg:'#fef2f2', border:'#fecaca' },
    };
    const badge = statusMap[(d.status || '').toLowerCase()] || { label: d.status, color:'#64748b', bg:'#f8fafc', border:'#e2e8f0' };

    document.getElementById('invOrderNum').textContent = d.order_number || ('Order #' + d.order_id);

    const itemsHtml = (d.items || []).map(item => {
        const imgHtml = item.image
            ? `<img class="inv-item-img" src="images/products/${escHtml(item.image)}" alt="${escHtml(item.name)}">`
            : `<div class="inv-item-img-placeholder"><i class="fas fa-image"></i></div>`;
        const lineTotal = (parseFloat(item.price) * parseInt(item.qty, 10)).toFixed(2);
        return `
        <div class="inv-item-row">
            <div class="inv-item-name-wrap">
                ${imgHtml}
                <div class="inv-item-name">${escHtml(item.name)}</div>
            </div>
            <div class="inv-item-qty">${parseInt(item.qty, 10)}</div>
            <div class="inv-item-price">₱${fmtMoney(item.price)}</div>
            <div class="inv-item-total">₱${fmtMoney(lineTotal)}</div>
        </div>`;
    }).join('');

    const shippingHtml = parseFloat(d.shipping) === 0
        ? `<span class="val free">Free</span>`
        : `<span class="val">₱${fmtMoney(d.shipping)}</span>`;

    const payMethod = d.payment_method
        ? d.payment_method.charAt(0).toUpperCase() + d.payment_method.slice(1)
        : 'N/A';

    document.getElementById('invoiceModalBody').innerHTML = `
        <div class="inv-meta-grid">
            <div class="inv-meta-card">
                <div class="inv-meta-label">Billed to</div>
                <div class="inv-meta-val">${escHtml(d.customer?.name || '—')}</div>
                <div class="inv-meta-sub">${escHtml(d.customer?.email || '')}</div>
            </div>
            <div class="inv-meta-card">
                <div class="inv-meta-label">Order date</div>
                <div class="inv-meta-val">${escHtml(d.order_date || '—')}</div>
                <div class="inv-meta-sub">Via ${escHtml(payMethod)}</div>
            </div>
        </div>

        <div class="inv-items-wrap">
            <div class="inv-items-head">
                <span>Item</span>
                <span>Qty</span>
                <span>Unit price</span>
                <span>Total</span>
            </div>
            ${itemsHtml}
        </div>

        <div class="inv-totals">
            <div class="inv-total-row">
                <span class="lbl">Subtotal</span>
                <span class="val">₱${fmtMoney(d.subtotal)}</span>
            </div>
            <div class="inv-total-row">
                <span class="lbl">Shipping</span>
                ${shippingHtml}
            </div>
            <div class="inv-total-grand">
                <span class="lbl">Total</span>
                <span class="val">₱${fmtMoney(d.total)}</span>
            </div>
        </div>

        <div class="inv-footer">
            <span class="inv-status-badge" style="color:${badge.color};background:${badge.bg};border-color:${badge.border};">
                ${escHtml(badge.label)}
            </span>
            <a class="inv-dl-btn" href="download_invoice.php?order=${encodeURIComponent(d.order_id)}" target="_blank">
                <i class="fas fa-download"></i> Download PDF
            </a>
        </div>
    `;
}

function fmtMoney(v) {
    return parseFloat(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>
</body>
</html>