<?php
session_start();
require_once 'assets/php/db.php';

$categories = [];
$res = mysqli_query($conn, "SELECT id, name, description FROM categories WHERE active = 1 ORDER BY name");
while ($row = mysqli_fetch_assoc($res)) {
    $categories[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Categories • Vertex</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
  <?php include 'navbar.php'; ?>

  <main class="container" style="padding: 2rem 1rem;">
    <div class="page-header">
      <h1>Categories</h1>
      <p>Browse products by category.</p>
    </div>

    <div class="categories-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.25rem;">
      <?php if (empty($categories)): ?>
        <div style="padding:2rem;text-align:center;color:#6b7280;">No categories found.</div>
      <?php else: ?>
        <?php foreach ($categories as $cat): ?>
          <a href="shop.php?category=<?php echo urlencode($cat['name']); ?>" class="category-card">
            <div class="category-card-body">
              <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
              <p><?php echo htmlspecialchars($cat['description'] ?: 'Browse products in this category.'); ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <?php include 'footer.php'; ?>
</body>
</html>
