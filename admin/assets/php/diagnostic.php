<?php
require_once 'db.php';

echo "<h2>🔍 DIAGNOSTIC CHECK</h2>";

// Check database
echo "<h3>1. Database Check</h3>";
$result = mysqli_query($conn, "SELECT id, name, image FROM products LIMIT 3");
echo "<strong>Products with images:</strong><br>";
while($row = mysqli_fetch_assoc($result)) {
    $img = $row['image'] ?? 'NULL';
    echo "- {$row['name']}: <code>{$img}</code><br>";
}

// Check filesystem
echo "<h3>2. Filesystem Check</h3>";
$dirs = [
    'admin/assets/images/products' => __DIR__ . '/../../../assets/images/products/',
    'admin/assets/images/avatars' => __DIR__ . '/../../../assets/images/avatars/',
];

foreach ($dirs as $label => $path) {
    if (is_dir($path)) {
        $files = scandir($path);
        $count = count($files) - 2; // exclude . and ..
        echo "✓ <strong>$label</strong> exists - <strong>$count files</strong><br>";
        if ($count > 0) {
            echo "<ul>";
            foreach ($files as $f) {
                if ($f !== '.' && $f !== '..') {
                    $fsize = filesize($path . $f);
                    echo "<li>{$f} (" . round($fsize/1024, 1) . " KB)</li>";
                }
            }
            echo "</ul>";
        }
    } else {
        echo "✗ <strong>$label</strong> NOT FOUND at: <code>{$path}</code><br>";
    }
}

echo "<h3>3. Current Working Directory</h3>";
echo "<code>" . __DIR__ . "</code>";

echo "<h3>4. Test Image Creation</h3>";
$test_dir = __DIR__ . '/../../';
if (is_writable($test_dir)) {
    echo "✓ Directory is writable<br>";
} else {
    echo "✗ Directory is NOT writable (permission issue)<br>";
}
?>
