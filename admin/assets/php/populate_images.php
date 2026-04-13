<?php
require_once 'db.php';

// Get all products
$result = mysqli_query($conn, "SELECT id, name FROM products ORDER BY id");
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo "<h2>Updating " . count($products) . " products with image filenames...</h2>";

foreach ($products as $product) {
    // Generate filename from product name
    $name = preg_replace('/[^a-z0-9]+/', '_', strtolower($product['name']));
    $filename = 'product_' . $product['id'] . '_' . $name . '.jpg';
    
    // Update database
    $stmt = mysqli_prepare($conn, "UPDATE products SET image = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $filename, $product['id']);
    mysqli_stmt_execute($stmt);
    
    echo "✓ Product #{$product['id']}: {$product['name']} → {$filename}<br>";
}

echo "<h2>Updating customers with avatar initials...</h2>";

// Get all customers
$result = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='customer' ORDER BY id");
$customers = mysqli_fetch_all($result, MYSQLI_ASSOC);

foreach ($customers as $customer) {
    // Generate avatar filename from initials
    $parts = explode(' ', trim($customer['full_name']));
    $initials = strtoupper(substr($parts[0], 0, 1)) . (isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '');
    $filename = 'avatar_' . $customer['id'] . '_' . strtolower($initials) . '.jpg';
    
    // Update database
    $stmt = mysqli_prepare($conn, "UPDATE users SET avatar = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $filename, $customer['id']);
    mysqli_stmt_execute($stmt);
    
    echo "✓ Customer #{$customer['id']}: {$customer['full_name']} → {$filename}<br>";
}

echo "<h2>✅ Database updated! Now creating placeholder images...</h2>";

// Create product placeholder images
$product_dir = __DIR__ . '/../../../assets/images/products/';
if (!is_dir($product_dir)) mkdir($product_dir, 0755, true);

foreach ($products as $product) {
    $name = preg_replace('/[^a-z0-9]+/', '_', strtolower($product['name']));
    $filename = 'product_' . $product['id'] . '_' . $name . '.jpg';
    $filepath = $product_dir . $filename;
    
    if (!file_exists($filepath)) {
        // Create placeholder image
        $img = imagecreatetruecolor(200, 200);
        $colors = [
            0xFF6B6B, 0x4ECDC4, 0x45B7D1, 0xFFA07A, 0x98D8C8,
            0xF7DC6F, 0xBB8FCE, 0x85C1E2, 0xF8B88B, 0x80CBC4
        ];
        $color = $colors[$product['id'] % count($colors)];
        $r = ($color >> 16) & 0xFF;
        $g = ($color >> 8) & 0xFF;
        $b = $color & 0xFF;
        
        imagefilledrectangle($img, 0, 0, 200, 200, imagecolorallocate($img, $r, $g, $b));
        
        $white = imagecolorallocate($img, 255, 255, 255);
        imagestring($img, 5, 40, 90, substr($product['name'], 0, 20), $white);
        
        imagejpeg($img, $filepath, 85);
        imagedestroy($img);
        echo "✓ Created: {$filename}<br>";
    }
}

// Create customer placeholder avatars
$avatar_dir = __DIR__ . '/../../../assets/images/avatars/';
if (!is_dir($avatar_dir)) mkdir($avatar_dir, 0755, true);

foreach ($customers as $customer) {
    $parts = explode(' ', trim($customer['full_name']));
    $initials = strtoupper(substr($parts[0], 0, 1)) . (isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '');
    $filename = 'avatar_' . $customer['id'] . '_' . strtolower($initials) . '.jpg';
    $filepath = $avatar_dir . $filename;
    
    if (!file_exists($filepath)) {
        // Create circular avatar with initials
        $size = 150;
        $img = imagecreatetruecolor($size, $size);
        
        $colors = [
            ['bg' => [230, 241, 251], 'text' => [24, 95, 165]],
            ['bg' => [238, 237, 254], 'text' => [83, 74, 183]],
            ['bg' => [225, 245, 238], 'text' => [15, 110, 86]],
            ['bg' => [250, 236, 231], 'text' => [153, 60, 29]],
            ['bg' => [251, 234, 240], 'text' => [153, 53, 86]]
        ];
        
        $palette = $colors[$customer['id'] % count($colors)];
        $bg = imagecolorallocate($img, $palette['bg'][0], $palette['bg'][1], $palette['bg'][2]);
        $text = imagecolorallocate($img, $palette['text'][0], $palette['text'][1], $palette['text'][2]);
        
        imagefilledrectangle($img, 0, 0, $size, $size, $bg);
        imagestring($img, 5, 50, 65, $initials, $text);
        
        imagejpeg($img, $filepath, 85);
        imagedestroy($img);
        echo "✓ Created: {$filename}<br>";
    }
}

echo "<h2>✅ ALL DONE! Check your dashboard now.</h2>";
?>
