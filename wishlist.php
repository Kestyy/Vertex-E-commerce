<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Wishlist — Vertex</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/style.css"/>
  <style>
    body { 
      background: #f8fafc; 
      font-family: 'Poppins', sans-serif;
    }

    /* Wishlist Header */
    .wishlist-header { 
      background: linear-gradient(135deg, #e8f0fe 0%, #f0f4ff 40%, #e4eefb 100%); 
      text-align: center; 
      padding: 52px 25px; 
      overflow: hidden; 
      border-bottom: 1px solid #d6e4f7; 
      margin-bottom: 3rem; 
      position: relative; 
    }
    .wishlist-header::before { 
      content: ''; 
      position: absolute; 
      width: 320px; 
      height: 320px; 
      border-radius: 50%; 
      background: radial-gradient(circle, rgba(99,155,255,0.18) 0%, transparent 70%); 
      top: -80px; 
      left: -60px; 
      pointer-events: none; 
    }
    .wishlist-header::after { 
      content: ''; 
      position: absolute; 
      width: 260px; 
      height: 260px; 
      border-radius: 50%; 
      background: radial-gradient(circle, rgba(139,92,246,0.1) 0%, transparent 70%); 
      bottom: -60px; 
      right: -40px; 
      pointer-events: none; 
    }
    .wishlist-header h1 { 
      font-size: 4rem; 
      font-weight: 700; 
      letter-spacing: -0.02em; 
      color: #3b82f6; 
      margin-bottom: 19px; 
      position: relative; 
      z-index: 1; 
      line-height: 1.15; 
    }
    .wishlist-header nav { 
      display: inline-flex; 
      align-items: center; 
      gap: 8px; 
      background: rgba(255,255,255,0.6); 
      border: 1px solid rgba(180,205,255,0.5); 
      backdrop-filter: blur(6px); 
      border-radius: 30px; 
      padding: 6px 18px; 
      font-size: 13px; 
      color: #8aa0c8; 
      position: relative; 
      z-index: 1; 
    }
    .wishlist-header nav a { 
      color: #7a99cc; 
      text-decoration: none; 
      font-weight: 500; 
      transition: color .2s; 
    }
    .wishlist-header nav a:hover { 
      color: #3b82f6; 
    }
    .wishlist-header nav span { 
      color: #b0c4e8; 
      font-size: 12px; 
    }

    /* Main Container - INCREASED SPACING */
    .wishlist-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px 60px;
    }

    /* Wishlist Table */
    .wishlist-table-wrap {
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    .wishlist-table { 
      width: 100%; 
      border-collapse: collapse; 
    }

    .wishlist-table thead { 
      background: #e4e7ec; 
    }

    .wishlist-table th { 
      padding: 18px 20px; 
      text-align: center; 
      font-weight: 700; 
      color: #64748b; 
      font-size: 13px; 
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .wishlist-table td { 
      padding: 20px; 
      border-bottom: 1px solid #f1f5f9; 
      vertical-align: middle;
    }

    .wishlist-table tbody tr {
      transition: background 0.2s;
    }

    .wishlist-table tbody tr:hover { 
      background: #f8fafc; 
    }

    .wishlist-table tbody tr:last-child td {
      border-bottom: none;
    }

    /* Remove Button Column - Centered */
    .wishlist-remove-col {
      text-align: center;
      width: 6%;
    }

    /* Remove Button - INCREASED SIZE */
    .wishlist-remove {
      background: none;
      border: none;
      color: #64748b;
      font-size: 28px;
      cursor: pointer;
      padding: 0;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s;
    }

    .wishlist-remove:hover {
      color: #ef4444;
    }

    /* Product Column - Left Aligned */
    .wishlist-product-col {
      text-align: left;
      width: 34%;
    }

    /* Product Info */
    .wishlist-product { 
      display: flex; 
      align-items: center; 
      gap: 16px; 
    }

    .wishlist-img { 
      width: 90px; 
      height: 90px; 
      object-fit: cover; 
      border-radius: 8px; 
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
      flex-shrink: 0;
    }

    .wishlist-info {
      flex: 1;
      min-width: 0;
    }

    .wishlist-info h5 { 
      font-size: 15px; 
      font-weight: 600; 
      color: #0f172a; 
      margin: 0 0 6px 0;
      line-height: 1.3;
    }

    .wishlist-info p { 
      font-size: 12px; 
      color: #94a3b8; 
      margin: 0;
      line-height: 1.4;
    }

    /* Price Column - Centered */
    .wishlist-price-col {
      text-align: center;
      width: 15%;
    }

    .wishlist-price { 
      font-weight: 600; 
      color: #0f172a; 
      font-size: 15px;
    }

    /* Date Column - Centered */
    .wishlist-date-col {
      text-align: center;
      width: 15%;
    }

    .wishlist-date { 
      color: #64748b; 
      font-size: 13px; 
    }

    /* Stock Status Column - Centered */
    .wishlist-stock-col {
      text-align: center;
      width: 15%;
    }

    .wishlist-stock { 
      font-size: 13px; 
      font-weight: 500; 
    }

    .stock-instock { 
      color: #16a34a; 
    }

    .stock-outstock { 
      color: #dc2626; 
    }

    /* Action Column - Centered */
    .wishlist-action-col {
      text-align: center;
      width: 15%;
    }

    /* Add to Cart Button - BLUE */
    .wishlist-add-btn { 
      background: #3b82f6; 
      border: none; 
      color: #fff; 
      padding: 10px 24px; 
      border-radius: 6px; 
      font-weight: 600; 
      font-size: 13px; 
      cursor: pointer; 
      transition: all 0.2s;
      white-space: nowrap;
    }

    .wishlist-add-btn:hover { 
      background: #2563eb; 
      color: #fff; 
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(59,130,246,0.3);
    }

    .wishlist-add-btn:disabled { 
      opacity: 0.5; 
      cursor: not-allowed; 
      transform: none;
    }

    /* Footer Actions */
    .wishlist-footer { 
      display: flex; 
      align-items: center; 
      justify-content: space-between; 
      padding: 24px; 
      background: #fff; 
      border-radius: 12px; 
      margin-top: 24px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      flex-wrap: wrap;
      gap: 20px;
    }

    .wishlist-link-wrap { 
      display: flex; 
      align-items: center; 
      gap: 12px; 
    }

    .wishlist-link-label { 
      font-size: 13px; 
      font-weight: 500; 
      color: #64748b; 
      white-space: nowrap;
    }

    .wishlist-link-input { 
      border: 1px solid #e2e8f0; 
      background: #f8fafc; 
      padding: 10px 14px; 
      border-radius: 6px; 
      font-size: 12px; 
      width: 280px;
      color: #64748b;
    }

    .wishlist-copy-btn { 
      background: #3b82f6; 
      border: none; 
      color: #fff; 
      padding: 10px 20px; 
      border-radius: 6px; 
      font-weight: 600; 
      font-size: 13px; 
      cursor: pointer;
      transition: all 0.2s;
    }

    .wishlist-copy-btn:hover { 
      background: #2563eb; 
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(59,130,246,0.3);
    }

    .wishlist-actions { 
      display: flex; 
      gap: 12px; 
    }

    /* Clear Wishlist Button - GRAY */
    .wishlist-clear-btn { 
      background: #f1f5f9; 
      border: 1px solid #e2e8f0; 
      color: #64748b; 
      padding: 10px 20px; 
      border-radius: 6px; 
      font-weight: 600; 
      font-size: 13px; 
      cursor: pointer;
      transition: all 0.2s;
    }

    .wishlist-clear-btn:hover { 
      background: #e2e8f0; 
      border-color: #cbd5e1;
    }

    /* Add All to Cart Button - BLUE */
    .wishlist-add-all-btn { 
      background: #3b82f6; 
      border: none; 
      color: #fff; 
      padding: 10px 24px; 
      border-radius: 6px; 
      font-weight: 600; 
      font-size: 13px; 
      cursor: pointer;
      transition: all 0.2s;
    }

    .wishlist-add-all-btn:hover { 
      background: #2563eb; 
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(59,130,246,0.3);
    }

    /* Empty State */
    .cart-empty {
      background: #fff;
      border-radius: 12px;
      padding: 60px 40px;
      text-align: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    .cart-empty i {
      font-size: 64px;
      color: #cbd5e1;
      margin-bottom: 20px;
    }

    .cart-empty h3 {
      font-size: 22px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 10px;
    }

    .cart-empty p {
      font-size: 14px;
      color: #64748b;
      margin-bottom: 24px;
    }

    .btn-browse {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #3b82f6;
      color: #fff;
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.2s;
    }

    .btn-browse:hover {
      background: #2563eb;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(59,130,246,0.3);
    }

    /* Toast */
    #toastContainer {
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 9999;
    }

    .toast {
      background: #22c55e;
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 500;
      margin-top: 8px;
      animation: slideUp 0.3s ease;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .toast.error {
      background: #ef4444;
    }

    @keyframes slideUp {
      from { 
        transform: translateY(20px); 
        opacity: 0; 
      }
      to { 
        transform: translateY(0); 
        opacity: 1; 
      }
    }

    @media (max-width: 768px) {
      .wishlist-header h1 {
        font-size: 2.5rem;
      }

      .wishlist-table-wrap {
        overflow-x: auto;
      }

      .wishlist-table {
        min-width: 900px;
      }

      .wishlist-footer {
        flex-direction: column;
        align-items: stretch;
      }

      .wishlist-link-wrap {
        width: 100%;
      }

      .wishlist-link-input {
        width: 100%;
        flex: 1;
      }

      .wishlist-actions {
        justify-content: stretch;
        width: 100%;
      }

      .wishlist-actions button {
        flex: 1;
      }
    }
  </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<?php
require_once 'assets/php/db.php';

$isLoggedIn = isset($_SESSION['user_id']);
$wishlistItems = [];

if ($isLoggedIn) {
  $userId = $_SESSION['user_id'];
  $stmt = mysqli_prepare($conn, 
    "SELECT w.id as wid, p.id, p.name, p.price, p.image, p.stock_quantity, p.status, w.added_at
     FROM wishlist w
     JOIN products p ON w.product_id = p.id
     WHERE w.user_id = ?
     ORDER BY w.added_at DESC");
  mysqli_stmt_bind_param($stmt, 'i', $userId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  while ($item = mysqli_fetch_assoc($result)) {
    $wishlistItems[] = $item;
  }
  mysqli_stmt_close($stmt);
}
?>

<!-- WISHLIST HERO -->
<div class="wishlist-header">
  <h1>Wishlist</h1>
  <nav>
    <a href="index.php">Home</a>
    <span>›</span>
    <span>Wishlist</span>
  </nav>
</div>

<main>
  <div class="wishlist-container">

    <?php if (!$isLoggedIn): ?>
      <div class="cart-empty">
        <i class="fas fa-heart"></i>
        <h3>You're not logged in</h3>
        <p>Please log in to view your wishlist.</p>
        <a href="auth/login.php" class="btn-browse">Log In / Sign Up</a>
      </div>

    <?php elseif (empty($wishlistItems)): ?>
      <div class="cart-empty">
        <i class="fas fa-heart"></i>
        <h3>Your wishlist is empty</h3>
        <p>Start adding products to your wishlist!</p>
        <a href="shop.php" class="btn-browse"><i class="fas fa-store"></i> Browse Products</a>
      </div>

    <?php else: ?>

      <div class="wishlist-table-wrap">
        <table class="wishlist-table">
          <thead>
            <tr>
              <th class="wishlist-remove-col"></th>
              <th class="wishlist-product-col" style="text-align: left; padding-left: 20px;">Product</th>
              <th class="wishlist-price-col">Price</th>
              <th class="wishlist-date-col">Date Added</th>
              <th class="wishlist-stock-col">Stock Status</th>
              <th class="wishlist-action-col">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($wishlistItems as $item): 
              $inStock = $item['stock_quantity'] > 0;
              $dateAdded = date('d F Y', strtotime($item['added_at']));
            ?>
            <tr data-product-id="<?php echo $item['id']; ?>">
              <td class="wishlist-remove-col">
                <button class="wishlist-remove" 
                        onclick="removeFromWishlist(<?php echo $item['wid']; ?>, <?php echo $item['id']; ?>)"
                        title="Remove">×</button>
              </td>
              <td class="wishlist-product-col">
                <div class="wishlist-product">
                  <img src="images/products/<?php echo htmlspecialchars($item['image'] ?: 'default.jpg'); ?>" 
                       alt="<?php echo htmlspecialchars($item['name']); ?>"
                       class="wishlist-img"
                       onerror="this.src='images/products/default.jpg'"/>
                  <div class="wishlist-info">
                    <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                    <p>Color · Light Brown | Size · XXL</p>
                  </div>
                </div>
              </td>
              <td class="wishlist-price-col">
                <div class="wishlist-price">₱<?php echo number_format($item['price'], 2); ?></div>
              </td>
              <td class="wishlist-date-col">
                <div class="wishlist-date"><?php echo $dateAdded; ?></div>
              </td>
              <td class="wishlist-stock-col">
                <div class="wishlist-stock <?php echo $inStock ? 'stock-instock' : 'stock-outstock'; ?>">
                  <?php echo $inStock ? 'Instock' : 'Out of Stock'; ?>
                </div>
              </td>
              <td class="wishlist-action-col">
                <button class="wishlist-add-btn" 
                        onclick="addToCart(<?php echo $item['id']; ?>)"
                        <?php echo !$inStock ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''; ?>>
                  Add to Cart
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- FOOTER ACTIONS -->
      <div class="wishlist-footer">
        <div class="wishlist-link-wrap">
          <label class="wishlist-link-label">Wishlist link:</label>
          <input type="text" class="wishlist-link-input" id="wishlistLink" 
                 value="https://www.example.com/wishlist?id=<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>"
                 readonly/>
          <button class="wishlist-copy-btn" onclick="copyWishlistLink()">Copy</button>
        </div>
        <div class="wishlist-actions">
          <button class="wishlist-clear-btn" onclick="clearWishlist()">Clear Wishlist</button>
          <button class="wishlist-add-all-btn" onclick="addAllToCart()">Add All to Cart</button>
        </div>
      </div>

    <?php endif; ?>

  </div>
</main>

<div id="toastContainer"></div>

<script>
function removeFromWishlist(wishlistId, productId) {
  if (!confirm('Remove from wishlist?')) return;
  
  fetch('assets/php/wishlist.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=remove&id=${wishlistId}`
  })
  .then(() => {
    document.querySelector(`tr[data-product-id="${productId}"]`).remove();
    showToast('Removed from wishlist');
    setTimeout(() => location.reload(), 500);
  })
  .catch(e => showToast('Error: ' + e, 'error'));
}

function clearWishlist() {
  if (!confirm('Clear entire wishlist?')) return;
  
  fetch('assets/php/wishlist.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=clear'
  })
  .then(() => {
    showToast('Wishlist cleared');
    setTimeout(() => location.reload(), 500);
  })
  .catch(e => showToast('Error: ' + e, 'error'));
}

function addToCart(productId) {
  // ✅ Check if user is logged in before adding to cart
  if (!window.isUserLoggedIn) {
    window.location.href = window.loginRedirectUrl;
    return;
  }

  fetch('assets/php/cart_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=add&product_id=${productId}&quantity=1`
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast('Added to cart');
    } else {
      showToast(data.message || 'Error adding to cart', 'error');
    }
  })
  .catch(e => showToast('Error: ' + e, 'error'));
}

function addAllToCart() {
  // ✅ Check if user is logged in before adding to cart
  if (!window.isUserLoggedIn) {
    window.location.href = window.loginRedirectUrl;
    return;
  }

  const buttons = document.querySelectorAll('.wishlist-add-btn:not(:disabled)');
  
  if (buttons.length === 0) {
    showToast('No items available to add', 'error');
    return;
  }

  let added = 0;
  buttons.forEach((btn, index) => {
    const row = btn.closest('tr');
    const productId = row.getAttribute('data-product-id');
    
    setTimeout(() => {
      fetch('assets/php/cart_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&product_id=${productId}&quantity=1`
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) added++;
        if (added === buttons.length) {
          showToast(`Added ${added} items to cart`);
        }
      });
    }, index * 100);
  });
}

function copyWishlistLink() {
  const link = document.getElementById('wishlistLink');
  link.select();
  document.execCommand('copy');
  showToast('Wishlist link copied!');
}

function showToast(msg, type = 'success') {
  const toast = document.createElement('div');
  toast.className = 'toast' + (type === 'error' ? ' error' : '');
  toast.textContent = msg;
  document.getElementById('toastContainer').appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

function copyCode(el) {
  const code = el.textContent;
  navigator.clipboard.writeText(code).then(() => {
    const orig = el.textContent;
    el.textContent = '✓ Copied!';
    setTimeout(() => { el.textContent = orig; }, 2000);
  });
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>