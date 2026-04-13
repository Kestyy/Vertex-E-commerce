<?php
session_start();
require_once 'assets/php/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=rewards.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Simple rewards points calculation (placeholder) - use user spent and orders from users table
$stmt = mysqli_prepare($conn, 'SELECT orders, spent FROM users WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$points = 0;
if ($user) {
    $points = floor(($user['spent'] ?? 0) / 10); // e.g., 1 point per ₱10 spent
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Rewards • Vertex</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
  <?php include 'navbar.php'; ?>
  <main class="container" style="padding: 2rem 1rem;">
    <div class="page-header">
      <h1>Rewards</h1>
      <p>See your reward points and unlock perks as you shop.</p>
    </div>

    <div style="max-width:600px;margin:auto;">
      <div style="padding:1.25rem;border:1px solid #e5e7eb;border-radius:12px;text-align:center;">
        <h2 style="margin:0;font-size:3rem;line-height:1;"><?php echo number_format($points); ?></h2>
        <p style="margin:0.5rem 0 0;color:#6b7280;">Reward points earned from purchases.</p>
      </div>

      <div style="margin-top:1.5rem;padding:1.25rem;border:1px solid #e5e7eb;border-radius:12px;">
        <h3>How it works</h3>
        <ul style="margin:0;padding-left:1.2rem;color:#374151;">
          <li>Earn 1 point for every ₱10 spent.</li>
          <li>Redeem points for discounts at checkout (coming soon).</li>
          <li>Check back often for exclusive offers.</li>
        </ul>
      </div>
    </div>
  </main>
  <?php include 'footer.php'; ?>
</body>
</html>
