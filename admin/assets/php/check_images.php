<?php
require_once 'db.php';

echo "<h2>PRODUCTS WITH IMAGES</h2>";
$result = mysqli_query($conn, "SELECT id, name, image FROM products LIMIT 10");
while($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['id']}, Name: {$row['name']}, Image: " . ($row['image'] ?? 'NULL') . "<br>";
}

echo "<h2>CUSTOMERS WITH AVATARS</h2>";
$result = mysqli_query($conn, "SELECT id, full_name, avatar FROM users WHERE role='customer' LIMIT 10");
while($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['id']}, Name: {$row['full_name']}, Avatar: " . ($row['avatar'] ?? 'NULL') . "<br>";
}
?>
