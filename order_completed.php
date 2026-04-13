<?php
session_start();
require_once 'assets/php/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order = null;
$error = '';

// ── SECURE: Validate token from URL against session ──
$token = $_GET['token'] ?? '';

if ($token && isset($_SESSION['order_confirmation']) && $_SESSION['order_confirmation']['token'] === $token) {
    // Token matches — fetch order from DB using stored order_id
    $order_id = $_SESSION['order_confirmation']['order_id'];
    
    $stmt = mysqli_prepare($conn,
        'SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'ii', $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    
    // Clear session token after use (one-time only)
    unset($_SESSION['order_confirmation']);
    
    if (!$order) {
        $error = 'Order not found.';
    }
} else {
    // No valid token — redirect to orders history
    header('Location: profile.php?tab=orders');
    exit;
}

// If order not found after validation
if (!$order) {
    header('Location: profile.php?tab=orders');
    exit;
}

// Fetch order items
$items = [];
$stmt  = mysqli_prepare($conn,
    'SELECT oi.*, p.name, p.image
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?'
);
mysqli_stmt_bind_param($stmt, 'i', $order['id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $items[] = $row;
}
mysqli_stmt_close($stmt);

// ── Totals (mirrors checkout.php logic exactly) ──
$subtotal        = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
$discount_amount = !empty($order['discount_amount']) ? (float)$order['discount_amount'] : 0;
$grand_total     = (float)$order['total_amount'];

// Derive shipping: grand_total = subtotal - discount + shipping
$shipping_fee = $grand_total - $subtotal + $discount_amount;
$is_free      = $shipping_fee <= 0;

// Estimated delivery: 5-7 days from order date
$order_date   = new DateTime($order['created_at'] ?? $order['order_date'] ?? 'now');
$delivery_min = (clone $order_date)->modify('+5 days');
$delivery_max = (clone $order_date)->modify('+7 days');
$est_delivery = $delivery_min->format('M j') . ' – ' . $delivery_max->format('M j, Y');

// ✅ Use order_number instead of padded ID
$order_display = htmlspecialchars($order['order_number']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Order Completed — Vertex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="stylesheet" href="assets/css/style.css"/>
    <style>
        * { box-sizing: border-box; }

        body {
            background: #f1f5f9;
            font-family: 'Poppins', sans-serif;
        }

        .oc-wrap {
            max-width: 960px;
            margin: 0 auto;
            padding: 6vh 1.5rem 16vh;
        }

        /* Inter for numbers */
        .oc-banner-value,
        .oc-table tbody td:last-child,
        .item-cat,
        .oc-total-row span:last-child,
        .oc-total-grand span:last-child {
            font-family: 'Inter', sans-serif;
            font-variant-numeric: tabular-nums;
        }

        .oc-check-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 1.4rem;
        }
        .oc-check {
            width: 78px; height: 78px;
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 10px 28px rgba(59,130,246,0.42);
            animation: pop-in .5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .oc-check i { color: #fff; font-size: 2.2rem; }
        @keyframes pop-in {
            from { opacity: 0; transform: scale(0.5); }
            to   { opacity: 1; transform: scale(1); }
        }

        .oc-heading { text-align: center; margin-bottom: 2.2rem; }
        .oc-heading h2 { font-size: 2.4rem; font-weight: 700; color: #2d2d3a; margin-bottom: .5rem; }
        .oc-heading p  { font-size: 1.4rem; color: #3b82f6; margin: 0; font-weight: 400; }

        /* ── Info banner — 4 cols ── */
        .oc-banner {
            background: #3b82f6;
            border-radius: 1.6rem;
            padding: 2.2rem 2.8rem;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.2rem;
            margin-bottom: 2.2rem;
            box-shadow: 0 10px 36px rgba(59,130,246,0.28);
        }
        @media (max-width: 640px) {
            .oc-banner { grid-template-columns: 1fr 1fr; padding: 1.6rem; }
        }
        .oc-banner-label {
            font-size: 0.9rem; font-weight: 600;
            color: rgba(255,255,255,0.8);
            text-transform: uppercase; letter-spacing: .07em;
            margin-bottom: .45rem;
            text-align: center;
        }
        .oc-banner-value { font-size: 1.4rem; font-weight: 700; color: #fff; text-align: center; }
        .oc-banner-item { display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .btn-track {
            background: #fff;
            color: #3b82f6;
            border: none;
            border-radius: 1rem;
            padding: 1.2rem 2rem;
            font-family: 'Poppins', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 0 4px 16px rgba(0,0,0,0.18);
            transition: background .2s, transform .15s, box-shadow .2s;
        }
        .btn-track:hover {
            background: #eff6ff;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            color: #2563eb;
        }

        /* ── Card ── */
        .oc-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 1.6rem;
            overflow: hidden;
            margin-bottom: 1.8rem;
        }
        .oc-card-header {
            padding: 1.6rem 2.2rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 1.4rem; font-weight: 700; color: #2d2d3a;
        }

        /* ── Items table ── */
        .oc-table { width: 100%; border-collapse: collapse; }
        .oc-table thead th {
            padding: 1.1rem 2.2rem;
            font-size: 1rem; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: .07em;
            border-bottom: 1.5px solid #e2e8f0; background: #fff;
        }
        .oc-table thead th:last-child { text-align: right; }
        .oc-table tbody td {
            padding: 1.6rem 2.2rem;
            font-size: 1.25rem; color: #374151; vertical-align: middle;
        }
        .oc-table tbody td:last-child { text-align: right; font-weight: 500; font-size: 1.5rem; color: #2d2d3a; }

        .item-img { width: 80px; height: 80px; border-radius: 14px; object-fit: cover; border: 1px solid #e2e8f0; }
        .item-name { font-weight: 700; color: #2d2d3a; font-size: 1.4rem; }
        .item-cat  { font-size: 1.2rem; color: #94a3b8; margin-top: 4px; font-weight: 500; }

        /* ── Totals ── */
        .oc-totals { padding: 1.8rem 2.2rem; border-top: 1px solid #e2e8f0; background: #fff; }
        .oc-total-row {
            display: flex; justify-content: space-between;
            font-size: 1.35rem; color: #6b7280; margin-bottom: .6rem;
        }
        .oc-total-row:last-child { margin-bottom: 0; }
        .oc-total-row span:last-child { color: #94a3b8; font-weight: 400; }
        .oc-free     { color: #16a34a !important; font-weight: 700; }

        .oc-discount-amount {
            color: #94a3b8;
            font-weight: 400;
            font-family: 'Inter', sans-serif;
            font-variant-numeric: tabular-nums;
        }

        .oc-total-grand {
            display: flex; justify-content: space-between;
            font-size: 1.75rem; font-weight: 700; color: #2d2d3a;
            padding-top: 1rem; border-top: 1.5px solid #e2e8f0; margin-top: 1rem;
        }

        /* ── Discount badge beside grand total ── */
        .oc-total-grand-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1.5px solid #e2e8f0;
            margin-top: 1rem;
        }
        .oc-grand-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .oc-grand-label {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2d2d3a;
        }
        .oc-discount-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 999px;
            padding: 3px 10px;
            font-size: 1rem;
            font-weight: 600;
            color: #ef4444;
            font-family: 'Inter', sans-serif;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .oc-grand-total-val {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2d2d3a;
            font-family: 'Inter', sans-serif;
            font-variant-numeric: tabular-nums;
        }

        /* ── CTAs ── */
        .oc-cta { display: flex; gap: 18px; flex-wrap: wrap; margin-top: 2.4rem; }
        .btn-primary-oc {
            flex: 1;
            background: #3b82f6;
            color: #fff; border: none; border-radius: 1.2rem;
            padding: 1.5rem 2rem;
            font-family: 'Poppins', sans-serif; font-size: 1.35rem; font-weight: 700;
            text-align: center; text-decoration: none; cursor: pointer;
            transition: opacity .2s, transform .15s;
        }
        .btn-primary-oc:hover { opacity: .9; transform: translateY(-2px); color: #fff; }
        .btn-outline-oc {
            flex: 1;
            background: #fff; color: #3b82f6;
            border: 2px solid #e2e8f0; border-radius: 1.2rem;
            padding: 1.5rem 2rem;
            font-family: 'Poppins', sans-serif; font-size: 1.35rem; font-weight: 600;
            text-align: center; text-decoration: none; cursor: pointer;
            transition: border-color .2s, transform .15s, color .2s;
        }
        .btn-outline-oc:hover { border-color: #3b82f6; color: #3b82f6; transform: translateY(-2px); }

        @media (max-width: 600px) {
            .oc-heading h2 { font-size: 1.6rem; }
            .oc-cta { flex-direction: column; }
            .oc-grand-label { font-size: 1.4rem; }
            .oc-grand-total-val { font-size: 1.4rem; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="oc-wrap">

    <!-- Check icon -->
    <div class="oc-check-wrap">
        <div class="oc-check"><i class="fas fa-check"></i></div>
    </div>

    <!-- Heading -->
    <div class="oc-heading">
        <h2>Your order is completed!</h2>
        <p>Thank you! Your Order has been received.</p>
    </div>

    <!-- Info banner — 4 cols with Track Order -->
    <div class="oc-banner">
        <div class="oc-banner-item">
            <div class="oc-banner-label">Order Number</div>
            <div class="oc-banner-value"><?= $order_display ?></div>
        </div>
        <div class="oc-banner-item">
            <div class="oc-banner-label">Payment Method</div>
            <div class="oc-banner-value"><?= htmlspecialchars(ucfirst($order['payment_method'])) ?></div>
        </div>
        <div class="oc-banner-item">
            <div class="oc-banner-label">Estimated Delivery</div>
            <div class="oc-banner-value"><?= $est_delivery ?></div>
        </div>
        <div class="oc-banner-item" style="display:flex; align-items:center; justify-content:center;">
            <a href="profile.php?tab=orders" class="btn-track">
                <i class="fas fa-truck me-2"></i> Track Order
            </a>
        </div>
    </div>

    <!-- Order Details card -->
    <div class="oc-card">
        <div class="oc-card-header">Order Details</div>
        <table class="oc-table">
            <thead>
                <tr>
                    <th colspan="2">Products</th>
                    <th style="text-align:right;">Sub Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td style="width:68px;">
                        <img class="item-img"
                             src="images/products/<?= htmlspecialchars($item['image'] ?: 'default.jpg') ?>"
                             alt="<?= htmlspecialchars($item['name']) ?>"/>
                    </td>
                    <td>
                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="item-cat">Qty: <?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></div>
                    </td>
                    <td>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="oc-totals">

            <!-- Subtotal -->
            <div class="oc-total-row">
                <span>Subtotal</span>
                <span>₱<?= number_format($subtotal, 2) ?></span>
            </div>

            <!-- Shipping -->
            <div class="oc-total-row">
                <span>Shipping</span>
                <?php if ($is_free): ?>
                    <span class="oc-free">FREE</span>
                <?php else: ?>
                    <span>₱<?= number_format($shipping_fee, 2) ?></span>
                <?php endif; ?>
            </div>

            <!-- Coupon Discount row — only if a discount was applied -->
            <?php if ($discount_amount > 0): ?>
            <div class="oc-total-row">
                <span>Coupon Discount</span>
                <span class="oc-discount-amount">-₱<?= number_format($discount_amount, 2) ?></span>
            </div>
            <?php endif; ?>

            <!-- Grand Total -->
            <div class="oc-total-grand">
                <span>Total</span>
                <span>₱<?= number_format($grand_total, 2) ?></span>
            </div>

        </div>
    </div>

    <!-- CTAs -->
    <div class="oc-cta">
        <a href="profile.php?tab=orders" class="btn-outline-oc">
            <i class="fas fa-box me-1"></i> My Orders
        </a>
        <a href="index.php" class="btn-primary-oc">
            <i class="fas fa-shopping-cart me-1"></i> Continue Shopping
        </a>
    </div>

</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>