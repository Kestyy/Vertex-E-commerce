<?php
/**
 * admin/assets/php/categories.php
 */

// MUST auth check FIRST before any output buffering or headers
require_once __DIR__ . '/../../auth_check.php';

// Start output buffering to catch any stray output
ob_start();

// Suppress errors in output (we'll handle them properly)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set JSON header
header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../../../assets/php/db.php';

// Clear output buffer to ensure clean JSON response
ob_end_clean();

// ── Helpers ─────────────────────────────────────────────────────
function json_ok(array $data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function json_err(string $message, int $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// ── Router ───────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':   listCategories($pdo);   break;
        case 'add':    addCategory($pdo);      break;
        case 'delete': deleteCategory($pdo);   break;
        default:       json_err('Unknown action: ' . $action, 400);
    }
} catch (Exception $e) {
    json_err('Server error: ' . $e->getMessage(), 500);
}

// ═══════════════════════════════════════════════════════════════
// LIST
// ═══════════════════════════════════════════════════════════════
function listCategories(PDO $pdo) {
    try {
        $stmt = $pdo->query('SELECT id, name, image FROM categories ORDER BY id ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build public URL for image
        foreach ($rows as &$row) {
            if ($row['image']) {
                $row['image'] = 'uploads/categories/' . basename($row['image']);
            }
        }

        json_ok(['categories' => $rows]);
    } catch (Exception $e) {
        json_err('Failed to load categories: ' . $e->getMessage());
    }
}

// ═══════════════════════════════════════════════════════════════
// ADD
// ═══════════════════════════════════════════════════════════════
function addCategory(PDO $pdo) {
    try {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            json_err('Category name is required.');
        }
        if (mb_strlen($name) > 100) {
            json_err('Name must be 100 characters or fewer.');
        }

        // Check duplicate
        $chk = $pdo->prepare('SELECT id FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1');
        $chk->execute([$name]);
        if ($chk->fetch()) {
            json_err('A category with this name already exists.');
        }

        // Handle image upload
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            json_err('A category image is required.');
        }

        $file = $_FILES['image'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowed, true)) {
            json_err('Only JPG, PNG, WEBP or GIF images are allowed.');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            json_err('Image must be under 5 MB.');
        }

        // Upload directory
        $uploadDir = __DIR__ . '/../../../admin/uploads/categories/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $filename = uniqid('cat_', true) . '.' . strtolower($ext);
        $dest = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            json_err('Failed to save image. Check folder permissions.', 500);
        }

        // Insert into DB
        $stmt = $pdo->prepare('INSERT INTO categories (name, image) VALUES (?, ?)');
        $stmt->execute([$name, $filename]);
        $newId = $pdo->lastInsertId();

        json_ok([
            'category' => [
                'id' => $newId,
                'name' => $name,
                'image' => 'uploads/categories/' . $filename,
            ]
        ]);
    } catch (Exception $e) {
        json_err('Failed to add category: ' . $e->getMessage(), 500);
    }
}

// ═══════════════════════════════════════════════════════════════
// DELETE
// ═══════════════════════════════════════════════════════════════
function deleteCategory(PDO $pdo) {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            json_err('Invalid category ID.');
        }

        // Fetch existing record
        $stmt = $pdo->prepare('SELECT image FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cat) {
            json_err('Category not found.', 404);
        }

        // Delete image file
        if ($cat['image']) {
            $imgPath = __DIR__ . '/../../../admin/uploads/categories/' . basename($cat['image']);
            if (file_exists($imgPath)) {
                @unlink($imgPath);
            }
        }

        // Delete row
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);

        json_ok(['message' => 'Category deleted.']);
    } catch (Exception $e) {
        json_err('Failed to delete category: ' . $e->getMessage(), 500);
    }
}
?>