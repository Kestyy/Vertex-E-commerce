<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Shopping Cart — Vertex</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/style.css"/>
  <link rel="stylesheet" href="assets/css/cart.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<?php
require_once 'assets/php/db.php';

$cart_items = [];
if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];
  $stmt = mysqli_prepare($conn,
    "SELECT c.quantity, p.id, p.name, p.price, p.image, p.stock_quantity
     FROM cart c
     JOIN products p ON c.product_id = p.id
     WHERE c.user_id = ?
     ORDER BY c.added_at DESC");
  mysqli_stmt_bind_param($stmt, 'i', $user_id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  while ($item = mysqli_fetch_assoc($result)) $cart_items[] = $item;
}
?>

<!-- ══ CART HERO ══ -->
<div class="cart-hero">
  <div class="cart-hero-title">Shopping Cart</div>
  <nav class="cart-hero-bc" aria-label="breadcrumb">
    <a href="index.php">Home</a>
    <span class="cart-bc-sep">›</span>
    <span>Shopping Cart</span>
  </nav>
</div>

<main class="cart-page">
  <div class="cart-container">

    <?php if (!isset($_SESSION['user_id'])): ?>
      <div class="cart-empty">
        <i class="fas fa-shopping-bag"></i>
        <h3>You're not logged in</h3>
        <p>Please log in to view your shopping cart.</p>
        <a href="auth/login.php" class="btn-browse">Log In / Sign Up</a>
      </div>

    <?php elseif (empty($cart_items)): ?>
      <div class="cart-empty">
        <i class="fas fa-shopping-bag"></i>
        <h3>Your cart is empty</h3>
        <p>Looks like you haven't added anything yet.</p>
        <a href="shop.php" class="btn-browse"><i class="fas fa-store"></i> Browse Products</a>
      </div>

    <?php else: ?>

      <div class="cart-layout">

        <!-- LEFT: cart items -->
        <div class="cart-card">

          <div class="cart-table-head">
            <div class="th cart-check-wrap">
              <input type="checkbox" class="cart-select-all" id="selectAll"/>
            </div>
            <div class="th">Product</div>
            <div class="th center">Price</div>
            <div class="th center">Quantity</div>
            <div class="th center">Subtotal</div>
            <div class="th"></div>
          </div>

          <?php foreach ($cart_items as $item):
            $subtotal = $item['price'] * $item['quantity'];
          ?>
          <div class="cart-row"
               data-product-id="<?php echo $item['id']; ?>"
               data-price="<?php echo $item['price']; ?>"
               data-quantity="<?php echo $item['quantity']; ?>">

            <div class="cart-check-wrap">
              <input type="checkbox" class="cart-checkbox" data-id="<?php echo $item['id']; ?>"/>
            </div>

            <div class="cart-product">
              <img src="images/products/<?php echo htmlspecialchars($item['image'] ?: 'default.jpg'); ?>"
                   alt="<?php echo htmlspecialchars($item['name']); ?>"
                   class="cart-img"/>
              <h5 class="cart-name"><?php echo htmlspecialchars($item['name']); ?></h5>
            </div>

            <div class="cart-price">₱<?php echo number_format($item['price'], 2); ?></div>

            <div class="qty-wrap">
              <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">−</button>
              <input type="number" class="qty-input"
                     value="<?php echo $item['quantity']; ?>"
                     min="1" max="<?php echo $item['stock_quantity']; ?>"
                     onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)"/>
              <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
            </div>

            <div class="cart-subtotal">₱<?php echo number_format($subtotal, 2); ?></div>

            <div class="cart-remove-wrap">
              <button class="cart-remove" onclick="removeItem(<?php echo $item['id']; ?>)" title="Remove">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                  <path d="M10 11v6"/><path d="M14 11v6"/>
                  <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                </svg>
              </button>
            </div>

          </div>
          <?php endforeach; ?>

        </div>

        <!-- RIGHT: summary -->
        <div class="summary-card">

          <div class="s-header">Order Summary</div>

          <div class="s-body" id="summaryContent">
            <p class="s-empty">Select items to see total</p>
          </div>

          <div class="s-total" id="summaryTotal">
            <span>Total</span>
            <span id="summaryTotalVal">₱0.00</span>
          </div>

          <!-- Coupon -->
          <div class="coupon-wrap">
            <div class="coupon-label">Apply Coupon</div>
            <div class="coupon-row">
              <input type="text" id="couponInput" class="coupon-input" placeholder="Enter coupon code"/>
              <button class="coupon-btn" onclick="applyCoupon()">Apply</button>
            </div>
            <div id="couponMsg" class="coupon-msg"></div>
          </div>

          <!-- CTA -->
          <div class="s-cta">
            <button class="btn-checkout" id="checkoutBtn" onclick="proceedToCheckout()" disabled>
              Proceed to Checkout
            </button>
            <a href="index.php" class="btn-shop">
              <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
          </div>

        </div>

      </div>

    <?php endif; ?>

  </div>
</main>

<!-- Remove Item Modal -->
<div class="modal-backdrop" id="removeModal">
  <div class="modal-box">
    <div class="modal-icon-wrap">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="3 6 5 6 21 6"/>
        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
        <path d="M10 11v6"/><path d="M14 11v6"/>
        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
      </svg>
    </div>
    <p class="modal-title">Remove item?</p>
    <p class="modal-desc" id="modalItemName"></p>
    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeRemoveModal()">Cancel</button>
      <button class="btn-remove-confirm" onclick="confirmRemove()">Remove</button>
    </div>
  </div>
</div>

<div id="toastContainer"></div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/cart.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>