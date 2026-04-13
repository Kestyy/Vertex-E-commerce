<?php
// /admin/auth_check.php
session_start();

// ✅ Check if user is logged in AND has admin role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    // Redirect to main login page (adjust path if needed)
    header('Location: ../auth/login.php');
    exit;
}

// ✅ Admin is authenticated - continue loading the page
?>