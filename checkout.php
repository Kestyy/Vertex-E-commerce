<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'assets/php/db.php';

if (isset($_GET['quick']) && $_GET['quick'] == '1') {
    header('Location: cart.php?quick=1');
    exit;
}

// ── Auth guard ──
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ── Get user info ──
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// ── Get saved addresses ──
$saved_addresses = [];
$addr_stmt = mysqli_prepare($conn, "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
mysqli_stmt_bind_param($addr_stmt, 'i', $user_id);
mysqli_stmt_execute($addr_stmt);
$addr_result = mysqli_stmt_get_result($addr_stmt);
while ($addr = mysqli_fetch_assoc($addr_result)) {
    $saved_addresses[] = $addr;
}

// ── Check for Buy Now session data (secure, no URL exposure) ──
$buy_now_item = null;
if (isset($_SESSION['buy_now_item'])) {
    $buy_now_item = $_SESSION['buy_now_item'];
    unset($_SESSION['buy_now_item']); // Clear after reading
}

// ── Get selected item IDs from cart page ──
$selected_ids = [];
if (!empty($_GET['items'])) {
    $selected_ids = array_filter(array_map('intval', explode(',', $_GET['items'])));
}

// ── Load cart items (only selected ones) ──
$cart_items = [];
$total      = 0;

// If Buy Now, load that single product first
if ($buy_now_item) {
    $product_id = (int)$buy_now_item['product_id'];
    $qty = (int)$buy_now_item['quantity'];
    
    $stmt = mysqli_prepare($conn, "SELECT id, name, price, image, stock_quantity FROM products WHERE id = ? AND status = 'active'");
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if ($product) {
        $product['quantity'] = min($qty, (int)$product['stock_quantity']);
        $product['subtotal'] = (float)$product['price'] * $product['quantity'];
        $total += $product['subtotal'];
        $cart_items[] = $product;
    }
}

if (!empty($selected_ids)) {
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
    $types        = 'i' . str_repeat('i', count($selected_ids));
    $params       = array_merge([$user_id], $selected_ids);

    $sql  = "SELECT c.quantity, p.id, p.name, p.price, p.image, p.stock_quantity
             FROM cart c
             JOIN products p ON c.product_id = p.id
             WHERE c.user_id = ? AND p.id IN ($placeholders)
             ORDER BY c.added_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($item = mysqli_fetch_assoc($result)) {
        if ($item['quantity'] > $item['stock_quantity']) {
            $item['quantity'] = $item['stock_quantity'];
        }
        if ($item['quantity'] > 0) {
            $item['subtotal'] = $item['price'] * $item['quantity'];
            $total           += $item['subtotal'];
            $cart_items[]     = $item;
        }
    }
}

// ── If nothing to checkout, redirect back ──
if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

$item_count = count($cart_items);

// ── Shipping ──
define('SHIPPING_FEE',         79);
define('FREE_SHIPPING_THRESH', 249);

$is_free_shipping = $total >= FREE_SHIPPING_THRESH;
$shipping_fee     = $is_free_shipping ? 0 : SHIPPING_FEE;

// ── Coupon / Discount ──
$COUPONS = [
    'VERTEX20' => ['type' => 'percent', 'value' => 20],
    'SAVE10'   => ['type' => 'percent', 'value' => 10],
    'FLAT50'   => ['type' => 'fixed',   'value' => 50],
];

$coupon_code     = strtoupper(trim($_GET['coupon'] ?? ''));
$discount_amount = 0.0;
$applied_coupon  = null;

if ($coupon_code && isset($COUPONS[$coupon_code])) {
    $c               = $COUPONS[$coupon_code];
    $applied_coupon  = $coupon_code;
    $discount_amount = $c['type'] === 'percent'
        ? $total * ($c['value'] / 100)
        : (float)$c['value'];
}

$grand_total = max(0, $total + $shipping_fee - $discount_amount);

// ── Handle order placement ──
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name      = trim($_POST['full_name_final'] ?? $_POST['full_name'] ?? '');
    $address        = trim($_POST['address']         ?? '');
    $barangay       = trim($_POST['barangay']        ?? '');
    $city           = trim($_POST['city']            ?? '');
    $province       = trim($_POST['province']        ?? '');
    $zip            = trim($_POST['zip']             ?? '');
    $phone          = trim($_POST['phone_full']      ?? $_POST['phone'] ?? '');
    $payment_method = trim($_POST['payment_method']  ?? 'card');
    $save_address   = isset($_POST['save_info']) && $_POST['save_info'] === 'on';
    $region         = trim($_POST['region']          ?? '');

    $post_coupon     = strtoupper(trim($_POST['coupon_code'] ?? $coupon_code));
    $post_discount   = 0.0;
    if ($post_coupon && isset($COUPONS[$post_coupon])) {
        $c             = $COUPONS[$post_coupon];
        $post_discount = $c['type'] === 'percent'
            ? $total * ($c['value'] / 100)
            : (float)$c['value'];
    }
    $final_discount = max($discount_amount, $post_discount);
    $final_total    = max(0, $total + $shipping_fee - $final_discount);

    if (!$full_name || !$address || !$city || !$province || !$zip || !$phone) {
        $error = 'Please fill in all required shipping fields.';
    } else {
        $full_address = "$address, $barangay, $city, $province $zip";

        mysqli_begin_transaction($conn);
        try {
            // ── Generate date-based order number ──
            $date_prefix = date('Ymd'); // e.g., 20260413
            
        // Keep generating until we get a unique order number
        do {
            $seq_stmt = mysqli_prepare($conn,
                "SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE()");
            mysqli_stmt_execute($seq_stmt);
            $seq_result = mysqli_fetch_assoc(mysqli_stmt_get_result($seq_stmt));

            // Also count any already attempted in this loop to avoid infinite retries
            static $extra = 0;
            $sequence     = str_pad(($seq_result['count'] + 1 + $extra), 3, '0', STR_PAD_LEFT);
            $order_number = 'VTX-' . $date_prefix . '-' . $sequence;
            $extra++;

            $chk = mysqli_prepare($conn, "SELECT id FROM orders WHERE order_number = ?");
            mysqli_stmt_bind_param($chk, 's', $order_number);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);
        } while (mysqli_stmt_num_rows($chk) > 0);

            // ── Insert order WITH order_number ──
            $stmt = mysqli_prepare($conn,
                "INSERT INTO orders (user_id, order_number, total_amount, discount_amount, status, payment_method, shipping_address)
                 VALUES (?, ?, ?, ?, 'pending', ?, ?)");
            mysqli_stmt_bind_param($stmt, 'isddss', $user_id, $order_number, $final_total, $final_discount, $payment_method, $full_address);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($conn);

            foreach ($cart_items as $item) {
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'iiid', $order_id, $item['id'], $item['quantity'], $item['price']);
                mysqli_stmt_execute($stmt);

                $stmt = mysqli_prepare($conn,
                    "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'ii', $item['quantity'], $item['id']);
                mysqli_stmt_execute($stmt);

                $stmt = mysqli_prepare($conn,
                    "DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                mysqli_stmt_bind_param($stmt, 'ii', $user_id, $item['id']);
                mysqli_stmt_execute($stmt);
            }

            if ($save_address) {
                $phone_for_db = $phone !== '' ? preg_replace('/\s+/', '', $phone) : '';
                if (!str_starts_with($phone_for_db, '+63')) {
                    $phone_for_db = '+63' . ltrim($phone_for_db, '0');
                }

                $chk_tbl = mysqli_query($conn, "SHOW TABLES LIKE 'user_addresses'");
                if (mysqli_num_rows($chk_tbl) === 0) {
                    mysqli_query($conn, "CREATE TABLE user_addresses (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        full_name VARCHAR(100) NOT NULL,
                        street VARCHAR(255) NOT NULL,
                        barangay VARCHAR(100),
                        city VARCHAR(100) NOT NULL,
                        province VARCHAR(100) NOT NULL,
                        region VARCHAR(100),
                        zip VARCHAR(10) NOT NULL,
                        phone VARCHAR(20) NOT NULL,
                        is_default TINYINT(1) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                }

                $addr_stmt = mysqli_prepare($conn,
                    "INSERT INTO user_addresses (user_id, full_name, street, barangay, city, province, region, zip, phone, is_default)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
                mysqli_stmt_bind_param($addr_stmt, 'issssssss',
                    $user_id, $full_name, $address, $barangay, $city, $province, $region, $zip, $phone_for_db);
                mysqli_stmt_execute($addr_stmt);
            }

            $details = "Placed order #" . htmlspecialchars($order_number) . " for ₱" . number_format($final_total, 2);
            $stmt    = mysqli_prepare($conn,
                "INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'order_placed', ?)");
            mysqli_stmt_bind_param($stmt, 'is', $user_id, $details);
            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);

            // ── 🔐 SECURE REDIRECT: Token-based, no IDs in URL ──
            $order_token = bin2hex(random_bytes(16)); // 32-char secure random token
            
            // Store minimal order data in session (auto-expires on logout/session end)
            $_SESSION['order_confirmation'] = [
                'token'        => $order_token,
                'order_id'     => $order_id,
                'order_number' => $order_number,
                'total'        => $final_total,
                'timestamp'    => time()
            ];

            // Redirect with token ONLY — no sensitive data in URL
            header('Location: order_completed.php?token=' . $order_token);
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = 'Failed to place order: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Checkout — Vertex</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/style.css"/>
  <link rel="stylesheet" href="assets/css/checkout.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- ══ CHECKOUT HERO ══ -->
<div class="checkout-hero">
  <div class="checkout-hero-title">Checkout</div>
  <div class="checkout-hero-sub">
    <?php
      $total_items = array_sum(array_column($cart_items, 'quantity'));
      echo $item_count . ' item' . ($item_count !== 1 ? 's' : '') . ' · ₱' . number_format($grand_total, 2);
    ?>
  </div>
  <nav class="checkout-hero-bc" aria-label="breadcrumb">
    <a href="index.php">Home</a>
    <span class="checkout-bc-sep">›</span>
    <a href="cart.php">Shopping Cart</a>
    <span class="checkout-bc-sep">›</span>
    <span>Checkout</span>
  </nav>
</div>

<form method="POST" id="checkoutForm" novalidate>
  <input type="hidden" name="items"           value="<?php echo htmlspecialchars($_GET['items'] ?? ''); ?>"/>
  <input type="hidden" name="coupon_code"     value="<?php echo htmlspecialchars($coupon_code); ?>"/>
  <!-- Dedicated hidden field for full_name — always populated by JS regardless of saved/manual address -->
  <input type="hidden" name="full_name_final" id="hid_fullname"/>

  <div class="checkout-wrap">

    <!-- ── LEFT ── -->
    <div>

      <?php if ($error): ?>
      <div class="form-err">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error); ?>
      </div>
      <?php endif; ?>

      <!-- Shipping -->
      <div class="co-card">
        <div class="co-card-header">
          <div class="co-icon"><i class="fas fa-truck"></i></div>
          <div class="co-title">Shipping Information</div>
        </div>
        <div class="co-body">

          <!-- ── Saved Addresses Section ── -->
          <?php if (!empty($saved_addresses)): ?>
          <div class="saved-addr-section">
            <div class="saved-addr-label">Saved Addresses</div>
            <div class="saved-addr-list">

              <?php foreach ($saved_addresses as $addr): ?>
                <?php
                  // Strip +63 prefix for the phone parameter passed to JS
                  $phone_digits = preg_replace('/^\+63/', '', $addr['phone']);
                  // Use province field; fall back to region if province is empty
                  $province_val = !empty($addr['province']) ? $addr['province'] : ($addr['region'] ?? '');
                ?>
                <div class="addr-card <?= $addr['is_default'] ? 'selected' : '' ?>"
                     data-fullname="<?= htmlspecialchars($addr['full_name'], ENT_QUOTES) ?>"
                     data-street="<?= htmlspecialchars($addr['street'], ENT_QUOTES) ?>"
                     data-barangay="<?= htmlspecialchars($addr['barangay'] ?? '', ENT_QUOTES) ?>"
                     data-city="<?= htmlspecialchars($addr['city'], ENT_QUOTES) ?>"
                     data-province="<?= htmlspecialchars($province_val, ENT_QUOTES) ?>"
                     data-region="<?= htmlspecialchars($addr['region'] ?? '', ENT_QUOTES) ?>"
                     data-zip="<?= htmlspecialchars($addr['zip'], ENT_QUOTES) ?>"
                     data-phone="<?= htmlspecialchars($phone_digits, ENT_QUOTES) ?>"
                     onclick="selectAddrFromCard(this)">
                  <div class="addr-radio"><div class="addr-radio-dot"></div></div>
                  <div class="addr-info">
                    <div class="addr-name">
                      <?= htmlspecialchars($addr['full_name']) ?>
                      <?php if ($addr['is_default']): ?>
                        <span class="addr-default-badge">Default</span>
                      <?php endif; ?>
                    </div>
                    <div class="addr-line">
                      <?= htmlspecialchars($addr['street']) ?>
                      <?php if (!empty($addr['barangay'])): ?>, Brgy. <?= htmlspecialchars($addr['barangay']) ?><?php endif; ?>
                    </div>
                    <div class="addr-line">
                      <?= htmlspecialchars($addr['city']) ?>,
                      <?= htmlspecialchars(!empty($addr['region']) ? $addr['region'] : ($addr['province'] ?? '')) ?>
                      · <?= htmlspecialchars($addr['phone']) ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>

              <div class="addr-card addr-card-new" id="addrNewToggle" onclick="toggleNewAddress()">
                <div class="addr-radio"><div class="addr-radio-dot"></div></div>
                <div class="addr-info">
                  <div class="addr-name" style="color: var(--accent); display:flex; align-items:center; gap:7px;">
                    <i class="fas fa-plus" style="font-size:11px;"></i>
                    <span>Use a different address</span>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- ── Divider ── -->
          <div class="addr-divider"><span>or fill in manually</span></div>
          <?php endif; ?>

          <!-- ── Manual form ── -->
          <div id="manualForm" class="manual-form-wrap <?= empty($saved_addresses) ? '' : 'collapsed' ?>">

            <input type="hidden" name="address"    id="hid_address"/>
            <input type="hidden" name="barangay"   id="hid_barangay"/>
            <input type="hidden" name="city"       id="hid_city"/>
            <input type="hidden" name="region"     id="hid_region"/>
            <input type="hidden" name="province"   id="hid_province"/>
            <input type="hidden" name="zip"        id="hid_zip"/>
            <input type="hidden" name="phone_full" id="ff_phone_full"/>

            <div class="f-row full">
              <div class="f-field">
                <label class="f-label">Full Name <span class="req">*</span></label>
                <input class="f-input" name="full_name" id="ff_fullname" type="text"
                       placeholder="Juan Dela Cruz" autocomplete="name"
                       oninput="limitNameSpecialChars(this); formatNameInput(this); syncFullName(this.value)"/>
                <span class="f-err-msg" id="err_fullname">
                  <i class="fas fa-circle-exclamation" style="font-size:10px;"></i>
                  Please enter your full name.
                </span>
              </div>
            </div>

            <div class="f-row full">
              <div class="f-field">
                <label class="f-label">Street Address <span class="req">*</span></label>
                <input class="f-input" name="address_manual" id="ff_address" type="text"
                  placeholder="House/Unit No., Street Name" autocomplete="street-address"
                  oninput="stripAddressChars(this)"/>
                <span class="f-err-msg" id="err_address"><i class="fas fa-circle-exclamation" style="font-size:10px;"></i> Please enter your address.</span>
              </div>
            </div>

            <div class="f-row">
              <div class="f-field">
                <label class="f-label">Region <span class="req">*</span></label>
                <div class="select-wrap">
                  <select class="f-input" id="ff_region" name="region_manual">
                    <option value="" disabled selected>Select region…</option>
                  </select>
                  <i class="fas fa-chevron-down select-arrow"></i>
                </div>
                <span class="f-err-msg" id="err_region"><i class="fas fa-circle-exclamation" style="font-size:10px;"></i> Please select a region.</span>
              </div>
              <div class="f-field">
                <label class="f-label">Province <span class="req">*</span></label>
                <div class="select-wrap">
                  <select class="f-input" id="ff_province" name="province_manual">
                    <option value="" disabled selected>Select province…</option>
                  </select>
                  <i class="fas fa-chevron-down select-arrow"></i>
                </div>
                <span class="f-err-msg" id="err_province"><i class="fas fa-circle-exclamation" style="font-size:10px;"></i> Please select a province.</span>
              </div>
            </div>

            <div class="f-row">
              <div class="f-field">
                <label class="f-label">City / Municipality <span class="req">*</span></label>
                <div class="select-wrap">
                  <select class="f-input" id="ff_city" name="city_manual" disabled>
                    <option value="" disabled selected>Select city…</option>
                  </select>
                  <i class="fas fa-chevron-down select-arrow"></i>
                </div>
                <span class="f-err-msg" id="err_city"><i class="fas fa-circle-exclamation" style="font-size:10px;"></i> Please select a city.</span>
              </div>
              <div class="f-field">
                <label class="f-label">Barangay <span class="req">*</span></label>
                <div class="select-wrap">
                  <select class="f-input" id="ff_barangay" name="barangay_manual" disabled>
                    <option value="" disabled selected>Select barangay…</option>
                  </select>
                  <i class="fas fa-chevron-down select-arrow"></i>
                </div>
                <span class="f-err-msg" id="err_barangay"><i class="fas fa-circle-exclamation" style="font-size:10px;"></i> Please select a barangay.</span>
              </div>
            </div>

            <div class="f-row">
              <div class="f-field">
                <label class="f-label">ZIP Code <span class="req">*</span></label>
                <input class="f-input" name="zip_manual" id="ff_zip" type="text" placeholder="e.g. 1000"
                  maxlength="4" autocomplete="postal-code"
                  oninput="this.value=this.value.replace(/\D/g,'')"/>
                <span class="f-err-msg" id="err_zip"><i class="fas fa-circle-exclamation" style="font-size:10px;"></i> Please enter a valid 4-digit ZIP code.</span>
              </div>
              <div class="f-field">
                <label class="f-label">Phone Number <span class="req">*</span></label>
                <div class="phone-wrap" id="phoneWrap">
                  <span class="phone-prefix">+63</span>
                  <input
                    class="f-input"
                    name="phone"
                    id="ff_phone"
                    type="tel"
                    placeholder="912 345 6789"
                    maxlength="13"
                    oninput="formatPhone(this)"
                    onfocus="document.getElementById('phoneWrap').classList.add('focused')"
                    onblur="document.getElementById('phoneWrap').classList.remove('focused')"
                    autocomplete="tel-national"/>
                </div>
                <span class="f-err-msg" id="err_phone"><i class="fas fa-circle-exclamation" style="font-size:10px;"></i> Please enter a valid 10-digit phone number.</span>
              </div>
            </div>

            <label class="save-check">
              <input type="checkbox" name="save_info"/>
              <span>Save this address to my account</span>
            </label>

          </div>

        </div>
      </div>

      <!-- Payment -->
      <div class="co-card">
        <div class="co-card-header">
          <div class="co-icon"><i class="fas fa-credit-card"></i></div>
          <div class="co-title">Payment Method</div>
        </div>
        <div class="co-body">

          <input type="hidden" name="payment_method" id="paymentMethodInput" value="card"/>

          <div class="pay-opt selected" onclick="selectPay(this,'card')">
            <div class="pay-radio"></div>
            <div class="pay-lbl">Credit / Debit Card</div>
            <div class="pay-badge" style="background:#1a1f71;color:#fff;">VISA</div>
          </div>

          <div class="pay-opt" onclick="selectPay(this,'gcash')">
            <div class="pay-radio"></div>
            <div class="pay-lbl">GCash</div>
            <div class="pay-badge" style="background:#007bff;color:#fff;">GCash</div>
          </div>

          <div class="pay-opt" onclick="selectPay(this,'paypal')">
            <div class="pay-radio"></div>
            <div class="pay-lbl">PayPal</div>
            <div class="pay-badge" style="background:#003087;color:#fff;">PayPal</div>
          </div>

          <div class="card-fields show" id="cardFields">
            <div class="f-row full" style="margin-bottom:14px;">
              <div class="f-field">
                <label class="f-label">Card Number</label>
                <input class="f-input" id="cc_num" type="text" placeholder="1234 5678 9012 3456"
                  maxlength="19" oninput="formatCardNum(this)"/>
                <span class="f-err-msg" id="err_cc_num"><i class="fas fa-circle-exclamation" style="font-size:10px;"></i> Please enter a valid 16-digit card number.</span>
              </div>
            </div>
            <div class="f-row" style="margin-bottom:14px;">
              <div class="f-field">
                <label class="f-label">Expiry Date</label>
                <input class="f-input" id="cc_exp" type="text" placeholder="MM/YY"
                  maxlength="5" oninput="formatExpiry(this)"/>
                <span class="f-err-msg" id="err_cc_exp"><i class="fas fa-circle-exclamation" style="font-size:10px;"></i> Please enter a valid expiry date.</span>
              </div>
              <div class="f-field">
                <label class="f-label">CVC</label>
                <input class="f-input" id="cc_cvc" type="text" placeholder="123"
                  maxlength="4" oninput="this.value=this.value.replace(/\D/g,'')"/>
                <span class="f-err-msg" id="err_cc_cvc"><i class="fas fa-circle-exclamation" style="font-size:10px;"></i> Please enter a valid CVC.</span>
              </div>
            </div>
            <div class="f-row full" style="margin-bottom:0;">
              <div class="f-field">
                <label class="f-label">Name on Card</label>
                <input class="f-input" id="cc_name" type="text" placeholder="Full name on card"
                  oninput="limitNameSpecialChars(this); formatNameInput(this)"/>
                <span class="f-err-msg" id="err_cc_name"><i class="fas fa-circle-exclamation" style="font-size:10px;"></i> Please enter the name on your card.</span>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>

    <!-- ── RIGHT — Order Summary ── -->
    <div class="summary-card">
      <div class="s-header">
        <div class="s-title">Order Summary</div>
        <div class="s-count"><?php echo $item_count; ?> item<?php echo $item_count !== 1 ? 's' : ''; ?></div>
      </div>

      <?php foreach ($cart_items as $item): ?>
      <div class="o-item">
        <div class="o-img">
          <img src="images/products/<?php echo htmlspecialchars($item['image'] ?: 'default.jpg'); ?>"
               alt="<?php echo htmlspecialchars($item['name']); ?>"/>
        </div>
        <div class="o-info">
          <div class="o-name"><?php echo htmlspecialchars($item['name']); ?></div>
          <div class="o-qty">Qty: <?php echo $item['quantity']; ?> × ₱<?php echo number_format($item['price'], 2); ?></div>
        </div>
        <div class="o-price">₱<?php echo number_format($item['subtotal'], 2); ?></div>
      </div>
      <?php endforeach; ?>

      <div class="totals">
        <div class="t-row">
          <span class="t-lbl">Subtotal</span>
          <span class="t-val">₱<?php echo number_format($total, 2); ?></span>
        </div>
        <div class="t-row">
          <span class="t-lbl">Shipping</span>
          <?php if ($is_free_shipping): ?>
            <span class="t-free">FREE</span>
          <?php else: ?>
            <span class="t-val">₱<?php echo number_format($shipping_fee, 2); ?></span>
          <?php endif; ?>
        </div>
        <?php if ($discount_amount > 0): ?>
        <div class="t-row">
          <span class="t-lbl">Coupon Discount</span>
          <span class="t-dsc">-₱<?php echo number_format($discount_amount, 2); ?></span>
        </div>
        <?php endif; ?>
      </div>

      <div class="t-grand">
        <span>Total</span>
        <span>₱<?php echo number_format($grand_total, 2); ?></span>
      </div>

      <!-- Order Notes -->
      <div class="order-notes-wrap" style="padding: 16px 24px; border-bottom: 1px solid var(--border);">
        <label style="font-size: 11.5px; font-weight: 600; color: var(--text-2); letter-spacing: 0.05em; text-transform: uppercase; display: flex; align-items: center; gap: 7px; margin-bottom: 10px;">
          Order Notes <span style="font-weight: 400; color: var(--text-muted); text-transform: none; letter-spacing: 0;">(optional)</span>
        </label>
        <textarea
          name="order_notes"
          rows="3"
          placeholder="e.g. Please leave at the door, ring the bell twice, fragile items inside…"
          style="width: 100%; padding: 11px 14px; border: 1px solid var(--border); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 13px; color: var(--text); background: var(--bg); outline: none; resize: none; line-height: 1.6; transition: border-color 0.2s, box-shadow 0.2s;"
          onfocus="this.style.borderColor='var(--accent)'; this.style.boxShadow='0 0 0 3px var(--accent-glow)'; this.style.background='#fff';"
          onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none'; this.style.background='var(--bg)';"
        ></textarea>
      </div>

      <div class="s-cta">
        <button type="submit" class="btn-place">
          Place Order
        </button>
        <a href="cart.php" class="btn-back-cart">
          <i class="fas fa-arrow-left" style="font-size:10px;"></i>
          Back to Cart
        </a>
      </div>

    </div>

  </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/checkout.js"></script>

<style>
.t-discount {
  color: #ef4444;
  font-weight: 600;
  font-family: 'Inter', sans-serif;
  font-variant-numeric: tabular-nums;
}
</style>

<script>
function limitNameSpecialChars(el) {
  let v = el.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\s\-'.]/g, '');
  if ((v.match(/-/g) || []).length > 1) { let p = v.split('-'); v = p[0] + '-' + p.slice(1).join(''); }
  if ((v.match(/'/g) || []).length > 1) { let p = v.split("'"); v = p[0] + "'" + p.slice(1).join(''); }
  if ((v.match(/\./g) || []).length > 1) { let p = v.split('.'); v = p[0] + '.' + p.slice(1).join(''); }
  el.value = v;
}

function stripAddressChars(input) {
  input.value = input.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ0-9\s#,.\-\/']/g, '');
}

/* Keep hid_fullname in sync when typing in manual name field */
function syncFullName(val) {
  document.getElementById('hid_fullname').value = val;
}

/* ── Pre-select default saved address on page load ── */
document.addEventListener('DOMContentLoaded', function () {
  const defaultCard = document.querySelector('.addr-card.selected:not(#addrNewToggle)');
  if (defaultCard) {
    selectAddrFromCard(defaultCard);
  }
});
</script>

<?php include 'footer.php'; ?>

</body>
</html>