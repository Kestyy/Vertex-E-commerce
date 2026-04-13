<?php
session_start();
require_once 'assets/php/db.php';

// Placeholder for new arrival products.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>New Arrivals • Vertex</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
  <?php include 'navbar.php'; ?>
  <main class="container" style="padding: 2rem 1rem;">
    <div class="page-header">
      <h1>New Arrivals</h1>
      <p>Discover the latest products added to our store.</p>
    </div>

    <div style="padding:2rem;text-align:center;color:#6b7280;">
      <p>New arrivals are being added regularly. Browse the <a href="shop.php">shop</a> to see the newest items.</p>
    </div>
  </main>
  <?php include 'footer.php'; ?>
</body>
</html>
