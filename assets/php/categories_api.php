<?php
// assets/php/categories_api.php
// Public API for categories (no authentication required)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

$action = $_GET['action'] ?? '';

// ── GET all categories ──────────────────────────────────
if ($action === 'list') {
    $query = "SELECT id, name, image FROM categories ORDER BY id ASC";
    $result = mysqli_query($conn, $query);
    $rows = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Build public URL for image
            $image = $row['image'] ? 'admin/uploads/categories/' . basename($row['image']) : null;
            
            $rows[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'image' => $image,
            ];
        }
    }
    
    echo json_encode(['success' => true, 'categories' => $rows]);
    exit;
}

// Default response
echo json_encode(['success' => false, 'message' => 'Unknown action']);
