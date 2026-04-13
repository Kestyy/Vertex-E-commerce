<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn)) {
    require_once __DIR__ . '/assets/php/db.php';
}

/*
 * __DIR__ is always the directory of navbar.php itself (the project root),
 * regardless of which page includes it. We use that to build a stable base URL.
 */
if (!defined('NAV_BASE')) {
    $docRoot  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $navDir   = rtrim(str_replace('\\', '/', __DIR__), '/');
    define('NAV_BASE', str_replace($docRoot, '', $navDir)); // e.g. "/vertex"
}

$_nb = NAV_BASE; // short alias

$isLoggedIn = isset($_SESSION['user_id']);
$cartCount  = 0;
$nav_user   = null;

if ($isLoggedIn) {
    // Fetch user (avatar, name, etc.)
    if (isset($user)) {
        $nav_user = $user; // already fetched by the page (e.g. profile.php)
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT full_name, avatar FROM users WHERE id = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
            $nav_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
        }
    }

    // Cart count
    $stmt = mysqli_prepare($conn, 'SELECT COALESCE(SUM(quantity), 0) as cnt FROM cart WHERE user_id = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        $cartCount = $row ? (int)$row['cnt'] : 0;
        mysqli_stmt_close($stmt);
    }
}

$activePage = basename($_SERVER['PHP_SELF']);
?>
<!-- Announce bar -->
<div class="announce-bar" id="announceBar">
  <div class="announce-inner">
    <span class="announce-text">
      🎉 GET FLAT <strong>20% OFF</strong> ON 1ST ORDER — USE CODE
      <span class="announce-code" onclick="copyCode(this)" title="Click to copy">VERTEX20</span>
    </span>
    <button class="announce-close"
      onclick="document.getElementById('announceBar').style.display='none'; localStorage.setItem('announceBarClosed','1');"
      aria-label="Close">×</button>
  </div>
</div>
<script>
  if (localStorage.getItem('announceBarClosed') === '1') {
    document.getElementById('announceBar').style.display = 'none';
  }
  
  // ✅ Expose login status globally for cart functions
  window.isUserLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
  window.loginRedirectUrl = '<?php echo $_nb; ?>/auth/login.php';
</script>

<nav class="navbar sticky-top">
    <div class="container navbar-inner">
        <!-- Brand -->
        <a class="navbar-brand" href="<?= $_nb ?>/index.php">
            <img src="<?= $_nb ?>/images/brand.png" alt="Vertex" />
        </a>

        <!-- Nav links -->
        <ul class="navbar-nav" id="navbarNav">
            <li class="nav-item">
                <a class="nav-link<?= $activePage === 'index.php' ? ' active' : '' ?>" href="<?= $_nb ?>/index.php">
                    <div class="nav-link-inner"><span>Home</span></div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $activePage === 'shop.php' ? ' active' : '' ?>" href="<?= $_nb ?>/shop.php">
                    <div class="nav-link-inner"><span>Shop</span></div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $activePage === 'collection.php' ? ' active' : '' ?>" href="<?= $_nb ?>/collection.php">
                    <div class="nav-link-inner"><span>Collection</span></div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $activePage === 'deals.php' ? ' active' : '' ?>" href="<?= $_nb ?>/deals.php">
                    <div class="nav-link-inner"><span>Deals</span></div>
                </a>
            </li>
        </ul>

        <!-- Right: search + icons -->
        <div class="navbar-right">

            <!-- Search -->
            <div class="navbar-search">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" placeholder="Search…" autocomplete="off" />
            </div>

            <!-- Cart -->
            <a href="<?= $_nb ?>/cart.php" id="cartIcon" class="nav-icon-btn nav-cart-btn" aria-label="Cart">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <span class="nav-cart-count" id="cartCount"><?= $cartCount > 99 ? '99+' : $cartCount ?></span>
            </a>

            <!-- User -->
            <div class="nav-user-wrap">
                <button class="nav-icon-btn" id="userBtn" aria-label="Account">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                </button>
                <div class="nav-user-dropdown" id="userDropdown">
                    <?php if ($isLoggedIn): ?>
                        <a href="<?= $_nb ?>/profile.php" class="nav-drop-item nav-drop-profile">
                            <div class="nav-drop-profile-left">
                            <div class="nav-drop-avatar">
                            <?php if (!empty($nav_user['avatar'])): ?>
                                <img src="<?= $_nb ?>/images/avatars/<?= htmlspecialchars($nav_user['avatar']) ?>"
                                    alt="Avatar"
                                    style="width:42px;height:42px;border-radius:50%;object-fit:cover;display:block;"/>
                            <?php else: ?>
                                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" width="36" height="36">
                                <circle cx="50" cy="50" r="50" fill="#aaaaaa"/>
                                <circle cx="50" cy="40" r="17" fill="#e0e0e0"/>
                                <ellipse cx="50" cy="88" rx="30" ry="22" fill="#e0e0e0"/>
                                </svg>
                            <?php endif; ?>
                            </div>
                                <span><?= htmlspecialchars($nav_user['full_name'] ?? $_SESSION['user_name']) ?></span>
                            </div>
                            <i class="fas fa-chevron-right nav-drop-chevron"></i>
                        </a>
                    <?php else: ?>
                        <a href="<?= $_nb ?>/auth/login.php" class="nav-drop-item nav-drop-auth">
                            <div class="nav-drop-item-left">
                                <i class="fas fa-user"></i>
                                <span>Log In / Sign Up</span>
                            </div>
                            <i class="fas fa-chevron-right nav-drop-chevron"></i>
                        </a>
                    <?php endif; ?>
                    <div class="nav-drop-divider"></div>
                    <a href="<?= $_nb ?>/profile.php?tab=orders" class="nav-drop-item <?= !$isLoggedIn ? 'requires-auth' : '' ?>" data-redirect="<?= $_nb ?>/profile.php?tab=orders">
                        <div class="nav-drop-item-left"><i class="fas fa-box"></i><span>My Orders</span></div>
                    </a>
                    <a href="<?= $_nb ?>/wishlist.php" class="nav-drop-item <?= !$isLoggedIn ? 'requires-auth' : '' ?>" data-redirect="<?= $_nb ?>/wishlist.php">
                        <div class="nav-drop-item-left"><i class="fas fa-heart"></i><span>My Wishlist</span></div>
                    </a>
                    <!-- <a href="<?= $_nb ?>/coupons.php" class="nav-drop-item <?= !$isLoggedIn ? 'requires-auth' : '' ?>" data-redirect="<?= $_nb ?>/coupons.php">
                        <div class="nav-drop-item-left"><i class="fas fa-tag"></i><span>My Coupons</span></div>
                    </a> -->
                    <a href="<?= $_nb ?>/rewards.php" class="nav-drop-item <?= !$isLoggedIn ? 'requires-auth' : '' ?>" data-redirect="<?= $_nb ?>/rewards.php">
                        <div class="nav-drop-item-left"><i class="fas fa-star"></i><span>My Rewards</span></div>
                    </a>
                    <a href="<?= $_nb ?>/customer_support.php" class="nav-drop-item <?= !$isLoggedIn ? 'requires-auth' : '' ?>" data-redirect="<?= $_nb ?>/customer_support.php">
                        <div class="nav-drop-item-left"><i class="fas fa-headset"></i><span>Customer Support</span></div>
                    </a>
                    <?php if ($isLoggedIn): ?>
                        <a href="#" class="nav-drop-item nav-drop-logout" id="logoutTrigger">
                            <div class="nav-drop-item-left"><i class="fas fa-sign-out-alt"></i><span>Log Out</span></div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mobile toggle -->
            <button class="nav-icon-btn d-lg-none" id="mobileToggle" aria-label="Menu">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

        </div>
    </div>
</nav>

<!-- Logout Confirmation Modal -->
<div class="modal-overlay" id="logoutModal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
        <div class="modal-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <h3 id="logoutTitle">Log out?</h3>
        <p>You'll need to log in again to access your account.</p>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-secondary" id="logoutCancel">Cancel</button>
            <a href="<?= $_nb ?>/assets/php/logout.php" class="modal-btn modal-btn-primary" id="logoutConfirm">Log Out</a>
        </div>
    </div>
</div>

<script>
// ✅ Handle auth-required links: redirect to login with return URL
document.addEventListener('DOMContentLoaded', function() {
    const authLinks = document.querySelectorAll('.requires-auth');

    authLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!<?= $isLoggedIn ? 'true' : 'false' ?>) {
                e.preventDefault();
                const redirect = this.dataset.redirect || window.location.href;
                const loginUrl = '<?= $_nb ?>/auth/login.php?redirect=' + encodeURIComponent(redirect);
                window.location.href = loginUrl;
            }
        });
    });
});
</script>