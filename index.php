<?php
session_start();
require_once 'assets/php/db.php';

$isLoggedIn = isset($_SESSION['user_id']);
$cartCount = 0;
if ($isLoggedIn) {
    $stmt = mysqli_prepare($conn, 'SELECT COALESCE(SUM(quantity), 0) as cnt FROM cart WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    $cartCount = $row ? (int)$row['cnt'] : 0;
    mysqli_stmt_close($stmt);
}

// Fetch featured products
$featured_products = [];
$result = mysqli_query($conn, "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status='active' ORDER BY p.created_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($result)) {
    $featured_products[] = $row;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vertex - Premium Laptops & Accessories</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet"/>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css" />
</head>

<body>

    <!-- NAVBAR -->
    <?php include 'navbar.php'; ?>

    <!-- ═══════════════════════════════════════
         HERO
    ═══════════════════════════════════════ -->
    <section class="hero-section hero-digistyle">
        <div class="hero-layout">
            <div class="hero-carousel-wrap">
                <div id="heroCarousel" class="carousel slide h-100" data-bs-ride="carousel" data-bs-interval="5000">
                    <div class="carousel-indicators hero-indicators">
                        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true"></button>
                        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
                        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
                    </div>
                    <div class="carousel-inner h-100">
                        <div class="carousel-item active h-100">
                            <img src="images/banner.png" class="hero-bg-img" alt="Slide 1">
                        </div>
                        <div class="carousel-item h-100">
                            <img src="images/banner4.png" class="hero-bg-img" alt="Slide 2">
                        </div>
                        <div class="carousel-item h-100">
                            <img src="images/banner2.png" class="hero-bg-img" alt="Slide 3">
                        </div>
                    </div>
                    <button class="carousel-control-prev hero-ctrl" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <button class="carousel-control-next hero-ctrl" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            </div>
            <div class="hero-banner-stack">
                <a href="#" class="side-banner">
                    <img src="images/banner3.png" alt="Promo 1" class="side-banner-img">
                </a>
                <a href="#" class="side-banner">
                    <img src="images/banner1.png" alt="Promo 2" class="side-banner-img">
                </a>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         TRUST STRIP
    ═══════════════════════════════════════ -->
    <div class="trust-strip">
        <div class="container">
            <div class="trust-strip-inner">
                <div class="trust-strip-item">
                    <div class="ts-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/>
                            <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                        </svg>
                    </div>
                    <div class="ts-text">
                        <strong>Free Shipping</strong>
                        <span>Free on orders over ₱249.</span>
                    </div>
                </div>
                <div class="trust-strip-item">
                    <div class="ts-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/>
                        </svg>
                    </div>
                    <div class="ts-text">
                        <strong>Easy Returns</strong>
                        <span>Hassle-free 10-day returns.</span>
                    </div>
                </div>
                <div class="trust-strip-item">
                    <div class="ts-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <div class="ts-text">
                        <strong>Secure Payment</strong>
                        <span>Your transactions are secured.</span>
                    </div>
                </div>
                <div class="trust-strip-item">
                    <div class="ts-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012.12 4.18 2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
                        </svg>
                    </div>
                    <div class="ts-text">
                        <strong>24/7 Support</strong>
                        <span>We're here anytime you need.</span>
                    </div>
                </div>
                <div class="trust-strip-item">
                    <div class="ts-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    </div>
                    <div class="ts-text">
                        <strong>Loyalty Rewards</strong>
                        <span>Shop more, earn more points.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         CATEGORIES  (dynamic from DB)
    ═══════════════════════════════════════ -->
    <section class="categories-section">
        <div class="container">
            <div class="cat-section-header">
                <h2 class="section-title">Shop by Category</h2>
                <div class="cat-nav-arrows">
                    <button class="cat-arrow" id="catPrev" aria-label="Previous">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <button class="cat-arrow" id="catNext" aria-label="Next">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            </div>
 
            <!--
                catGrid is intentionally left empty here.
                initCategoryCarousel() fetches categories from the API
                and injects .cat-card elements before building the track.
            -->
            <div class="cat-grid" id="catGrid">
                <!-- JS will render cards here -->
                <div class="cat-skeleton-row" id="catSkeleton" style="display:flex;gap:18px;width:100%;">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                    <div style="flex:1;min-width:0;background:#f1f5f9;border-radius:14px;aspect-ratio:1;animation:catPulse 1.4s ease-in-out infinite;animation-delay:<?= $i * 0.1 ?>s;"></div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </section>
 
    <style>
    @keyframes catPulse {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0.45; }
    }
    </style>

    <!-- ═══════════════════════════════════════
         FEATURED PRODUCTS
    ═══════════════════════════════════════ -->
    <section class="featured-section">
        <div class="container">
            <div class="featured-header">
                <h2 class="featured-title">Featured Products</h2>
                <a href="shop.php" class="featured-view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="row g-3">
                <?php foreach ($featured_products as $index => $product): ?>
                <div class="col-6 col-md-4 col-lg">
                    <div class="product-card">
                        <div class="product-image-wrap">
                            <button class="product-wish wish-btn" data-product-id="<?= $product['id'] ?>" data-product-name="<?= htmlspecialchars($product['name']) ?>"><i class="far fa-heart"></i></button>
                            <img src="images/products/<?= htmlspecialchars($product['image'] ?: 'default.jpg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image"/>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="product-rating">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                                <span class="rating-count">4.5</span>
                                <span class="rating-buyers">(2.4k)</span>
                            </div>
                            <div class="product-price-row">
                                <span class="product-price">₱<?= number_format($product['price'], 2) ?></span>
                            </div>
                            <div class="product-actions">
                                <button class="btn-cart-icon add-cart"
                                    data-product-id="<?= $product['id'] ?>"
                                    data-name="<?= htmlspecialchars($product['name']) ?>"
                                    data-price="₱<?= number_format($product['price'], 2) ?>"
                                    data-img="images/products/<?= htmlspecialchars($product['image'] ?: 'default.jpg') ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12.5 5v2.5H10v2h2.5V12h2V9.5H17v-2h-2.5V5z"></path><path d="M17.31 14H9.72L5.95 2.68A1 1 0 0 0 5 2H2v2h2.28l3.54 10.63A2 2 0 0 0 9.72 16h7.59a2 2 0 0 0 1.87-1.3l2.76-7.35-1.87-.7zM10 18a2 2 0 1 0 0 4 2 2 0 1 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 1 0 0-4"></path></svg>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         DEAL OF THE DAY
    ═══════════════════════════════════════ -->
    <section class="deal-section">
        <div class="deal-inner">
            <div class="deal-header-row">
                <div class="deal-left">
                    <h2 class="deal-title">Top Deals of <span>The Day</span></h2>
                </div>
                <div class="deal-timer-wrap">
                    <p class="timer-label-top">Hurry Up! Offer ends in:</p>
                    <div class="deal-timer">
                        <div class="timer-box">
                            <div class="timer-value" id="t-hours">08</div>
                            <div class="timer-unit">Hrs</div>
                        </div>
                        <div class="timer-sep">:</div>
                        <div class="timer-box">
                            <div class="timer-value" id="t-mins">32</div>
                            <div class="timer-unit">Min</div>
                        </div>
                        <div class="timer-sep">:</div>
                        <div class="timer-box">
                            <div class="timer-value" id="t-secs">29</div>
                            <div class="timer-unit">Sec</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="deal-right">
                <div class="deal-right-header">
                    <div class="deal-arrows">
                        <button class="deal-arrow-btn" id="deal-prev" aria-label="Previous">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <button class="deal-arrow-btn" id="deal-next" aria-label="Next">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                        </button>
                    </div>
                </div>
                <div class="deal-cards-grid" id="deal-cards-grid"></div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         NEW ARRIVALS
    ═══════════════════════════════════════ -->
    <section class="new-arrivals-section">
        <div class="container">
            <div class="featured-header">
                <h2 class="new-arrivals-title">New Arrivals</h2>
                <a href="new-arrivals.php" class="featured-view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="row g-3">

                <div class="col-6 col-md-4 col-lg">
                    <div class="product-card">
                        <div class="product-image-wrap">
                            <button class="product-wish"><i class="far fa-heart"></i></button>
                            <img src="https://p2-ofp.static.pub//fes/cms/2024/07/17/109vq5fdalv01w5jsu6vh35ncnk5jn890135.png" alt="Gaming Laptop" class="product-image"/>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">Pro Gaming Laptop</h3>
                            <div class="product-rating">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                                <span class="rating-count">4.5</span>
                                <span class="rating-buyers">(2.4k)</span>
                            </div>
                            <div class="product-price-row">
                                <span class="product-price">₱1,299.99</span>
                            </div>
                            <div class="product-actions">
                                <button class="add-cart"
                                    data-name="Pro Gaming Laptop"
                                    data-price="₱1,299.99"
                                    data-img="https://p2-ofp.static.pub//fes/cms/2024/07/17/109vq5fdalv01w5jsu6vh35ncnk5jn890135.png">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12.5 5v2.5H10v2h2.5V12h2V9.5H17v-2h-2.5V5z"></path><path d="M17.31 14H9.72L5.95 2.68A1 1 0 0 0 5 2H2v2h2.28l3.54 10.63A2 2 0 0 0 9.72 16h7.59a2 2 0 0 0 1.87-1.3l2.76-7.35-1.87-.7zM10 18a2 2 0 1 0 0 4 2 2 0 1 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 1 0 0-4"></path></svg>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg">
                    <div class="product-card">
                        <div class="product-image-wrap">
                            <button class="product-wish"><i class="far fa-heart"></i></button>
                            <img src="https://p2-ofp.static.pub//fes/cms/2024/07/17/109vq5fdalv01w5jsu6vh35ncnk5jn890135.png" alt="Ultrabook" class="product-image"/>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">Ultrabook Pro</h3>
                            <div class="product-rating">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                                <span class="rating-count">4.0</span>
                                <span class="rating-buyers">(1.1k)</span>
                            </div>
                            <div class="product-price-row">
                                <span class="product-price">₱1,499.99</span>
                                <span class="product-price-old">₱1,799.99</span>
                            </div>
                            <div class="product-actions">
                                <button class="add-cart"
                                    data-name="Ultrabook Pro"
                                    data-price="₱1,499.99"
                                    data-img="https://p2-ofp.static.pub//fes/cms/2024/07/17/109vq5fdalv01w5jsu6vh35ncnk5jn890135.png">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12.5 5v2.5H10v2h2.5V12h2V9.5H17v-2h-2.5V5z"></path><path d="M17.31 14H9.72L5.95 2.68A1 1 0 0 0 5 2H2v2h2.28l3.54 10.63A2 2 0 0 0 9.72 16h7.59a2 2 0 0 0 1.87-1.3l2.76-7.35-1.87-.7zM10 18a2 2 0 1 0 0 4 2 2 0 1 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 1 0 0-4"></path></svg>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg">
                    <div class="product-card">
                        <div class="product-image-wrap">
                            <button class="product-wish"><i class="far fa-heart"></i></button>
                            <img src="https://p2-ofp.static.pub//fes/cms/2024/07/17/109vq5fdalv01w5jsu6vh35ncnk5jn890135.png" alt="Wireless Keyboard" class="product-image"/>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">Wireless Keyboard</h3>
                            <div class="product-rating">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                <span class="rating-count">5.0</span>
                                <span class="rating-buyers">(3.2k)</span>
                            </div>
                            <div class="product-price-row">
                                <span class="product-price">₱89.99</span>
                            </div>
                            <div class="product-actions">
                                <button class="add-cart"
                                    data-name="Wireless Keyboard"
                                    data-price="₱89.99"
                                    data-img="https://p2-ofp.static.pub//fes/cms/2024/07/17/109vq5fdalv01w5jsu6vh35ncnk5jn890135.png">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12.5 5v2.5H10v2h2.5V12h2V9.5H17v-2h-2.5V5z"></path><path d="M17.31 14H9.72L5.95 2.68A1 1 0 0 0 5 2H2v2h2.28l3.54 10.63A2 2 0 0 0 9.72 16h7.59a2 2 0 0 0 1.87-1.3l2.76-7.35-1.87-.7zM10 18a2 2 0 1 0 0 4 2 2 0 1 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 1 0 0-4"></path></svg>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg">
                    <div class="product-card">
                        <div class="product-image-wrap">
                            <button class="product-wish"><i class="far fa-heart"></i></button>
                            <img src="https://p2-ofp.static.pub//fes/cms/2024/07/17/109vq5fdalv01w5jsu6vh35ncnk5jn890135.png" alt="Headphones" class="product-image"/>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">Noise Cancelling Headphones</h3>
                            <div class="product-rating">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                                <span class="rating-count">4.5</span>
                                <span class="rating-buyers">(876)</span>
                            </div>
                            <div class="product-price-row">
                                <span class="product-price">₱159.99</span>
                                <span class="product-price-old">₱199.99</span>
                            </div>
                            <div class="product-actions">
                                <button class="add-cart"
                                    data-name="Noise Cancelling Headphones"
                                    data-price="₱159.99"
                                    data-img="https://p2-ofp.static.pub//fes/cms/2024/07/17/109vq5fdalv01w5jsu6vh35ncnk5jn890135.png">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12.5 5v2.5H10v2h2.5V12h2V9.5H17v-2h-2.5V5z"></path><path d="M17.31 14H9.72L5.95 2.68A1 1 0 0 0 5 2H2v2h2.28l3.54 10.63A2 2 0 0 0 9.72 16h7.59a2 2 0 0 0 1.87-1.3l2.76-7.35-1.87-.7zM10 18a2 2 0 1 0 0 4 2 2 0 1 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 1 0 0-4"></path></svg>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg">
                    <div class="product-card">
                        <div class="product-image-wrap">
                            <button class="product-wish"><i class="far fa-heart"></i></button>
                            <img src="https://p2-ofp.static.pub//fes/cms/2024/07/17/109vq5fdalv01w5jsu6vh35ncnk5jn890135.png" alt="Wireless Mouse" class="product-image"/>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">Wireless Mouse</h3>
                            <div class="product-rating">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                                <span class="rating-count">4.2</span>
                                <span class="rating-buyers">(541)</span>
                            </div>
                            <div class="product-price-row">
                                <span class="product-price">₱49.99</span>
                                <span class="product-price-old">₱69.99</span>
                            </div>
                            <div class="product-actions">
                                <button class="add-cart"
                                    data-name="Wireless Mouse"
                                    data-price="₱49.99"
                                    data-img="https://p2-ofp.static.pub//fes/cms/2024/07/17/109vq5fdalv01w5jsu6vh35ncnk5jn890135.png">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12.5 5v2.5H10v2h2.5V12h2V9.5H17v-2h-2.5V5z"></path><path d="M17.31 14H9.72L5.95 2.68A1 1 0 0 0 5 2H2v2h2.28l3.54 10.63A2 2 0 0 0 9.72 16h7.59a2 2 0 0 0 1.87-1.3l2.76-7.35-1.87-.7zM10 18a2 2 0 1 0 0 4 2 2 0 1 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 1 0 0-4"></path></svg>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         CUSTOMER REVIEWS
    ═══════════════════════════════════════ -->
    <section class="reviews-section">
        <div class="container">
            <div class="reviews-header">
                <h2 class="reviews-title">What our customers are saying</h2>
                <div class="reviews-header-arrows">
                    <button class="reviews-arrow" id="reviewsPrev" aria-label="Previous">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <button class="reviews-arrow" id="reviewsNext" aria-label="Next">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            </div>
            <div class="reviews-track-wrap">
                <div class="reviews-track" id="reviews-track">

                    <div class="review-card">
                        <div class="review-card-top">
                            <div class="review-footer">
                                <div class="review-avatar" style="background:#2563eb;">JM</div>
                                <div class="review-meta">
                                    <div class="review-name">James M.</div>
                                    <div class="review-product">Pro Gaming Laptop</div>
                                </div>
                            </div>
                            <div class="review-stars">★★★★★</div>
                        </div>
                        <p class="review-text">"Best laptop I've ever owned. Gaming at max settings with zero lag. Shipping was super fast and packaging was immaculate."</p>
                    </div>

                    <div class="review-card">
                        <div class="review-card-top">
                            <div class="review-footer">
                                <div class="review-avatar" style="background:#7c3aed;">SC</div>
                                <div class="review-meta">
                                    <div class="review-name">Sarah C.</div>
                                    <div class="review-product">Noise Cancelling Headphones</div>
                                </div>
                            </div>
                            <div class="review-stars">★★★★★</div>
                        </div>
                        <p class="review-text">"The noise cancellation is insane. Sound quality rivals headphones twice the price. I use it every single day at the office."</p>
                    </div>

                    <div class="review-card">
                        <div class="review-card-top">
                            <div class="review-footer">
                                <div class="review-avatar" style="background:#059669;">AL</div>
                                <div class="review-meta">
                                    <div class="review-name">Alex L.</div>
                                    <div class="review-product">Mechanical Keyboard RGB</div>
                                </div>
                            </div>
                            <div class="review-stars">★★★★★</div>
                        </div>
                        <p class="review-text">"Clicky, satisfying, and looks incredible on my desk. Build feels super premium. Already recommended it to my whole team."</p>
                    </div>

                    <div class="review-card">
                        <div class="review-card-top">
                            <div class="review-footer">
                                <div class="review-avatar" style="background:#d97706;">RK</div>
                                <div class="review-meta">
                                    <div class="review-name">Rachel K.</div>
                                    <div class="review-product">4K IPS Monitor 27"</div>
                                </div>
                            </div>
                            <div class="review-stars">★★★★<span style="opacity:0.25">★</span></div>
                        </div>
                        <p class="review-text">"Colors are stunning right out of the box. Editing photos on this is a dream. An exceptional display for the price."</p>
                    </div>

                    <div class="review-card">
                        <div class="review-card-top">
                            <div class="review-footer">
                                <div class="review-avatar" style="background:#dc2626;">DT</div>
                                <div class="review-meta">
                                    <div class="review-name">Daniel T.</div>
                                    <div class="review-product">Portable SSD 2TB</div>
                                </div>
                            </div>
                            <div class="review-stars">★★★★★</div>
                        </div>
                        <p class="review-text">"Transfer speeds are blazing fast and the build is super compact. I carry it everywhere. Vertex always delivers quality."</p>
                    </div>

                    <div class="review-card">
                        <div class="review-card-top">
                            <div class="review-footer">
                                <div class="review-avatar" style="background:#0891b2;">MH</div>
                                <div class="review-meta">
                                    <div class="review-name">Maya H.</div>
                                    <div class="review-product">USB-C Docking Station</div>
                                </div>
                            </div>
                            <div class="review-stars">★★★★★</div>
                        </div>
                        <p class="review-text">"Finally a dock that just works. Connected 3 monitors, ethernet, and audio with zero issues. My WFH setup is now perfect."</p>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════
         NEWSLETTER
    ═══════════════════════════════════════ -->
    <div class="nl-wrapper">
        <div class="nl-orb nl-orb-1"></div>
        <div class="nl-orb nl-orb-2"></div>
        <section class="newsletter-section">
            <div class="nl-inner">
                <div class="nl-left">
                    <div class="nl-eyebrow">Members-Only Deals</div>
                    <h2 class="nl-title">Subscribe our <span class="nl-title-accent">Newsletter</span></h2>
                    <p class="nl-desc">Get early access to flash sales, exclusive coupon codes, new product drops, and hand-picked deals — delivered straight to your inbox every week.</p>
                    <ul class="nl-perks">
                        <li class="nl-perk">
                            <div class="nl-perk-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></div>
                            Flash Sales
                        </li>
                        <li class="nl-perk">
                            <div class="nl-perk-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/></svg></div>
                            Exclusive Coupons
                        </li>
                        <li class="nl-perk">
                            <div class="nl-perk-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                            New Arrivals
                        </li>
                    </ul>
                </div>
                <div class="nl-right">
                    <div id="nl-form-wrap">
                        <p class="nl-label">Stay up to date</p>
                        <div class="nl-form-row">
                            <input class="nl-input" id="nl-email" type="email" placeholder="Enter your email"/>
                            <button class="nl-btn" id="nl-submit" type="button">Subscribe</button>
                        </div>
                        <p class="nl-privacy">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            By subscribing you agree to our <a href="#">Privacy Policy</a>
                        </p>
                        <div class="nl-stats">
                            <div class="nl-stat"><span class="nl-stat-num">50K+</span><span class="nl-stat-label">Subscribers</span></div>
                            <div class="nl-stat"><span class="nl-stat-num">Weekly</span><span class="nl-stat-label">New Deals</span></div>
                            <div class="nl-stat"><span class="nl-stat-num">100%</span><span class="nl-stat-label">Free Forever</span></div>
                        </div>
                    </div>
                    <div class="nl-success" id="nl-success" style="display:none;">
                        <div class="nl-success-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
                        <h3 class="nl-success-title">You're subscribed! 🎉</h3>
                        <p class="nl-success-desc">Check your inbox for your first exclusive deal.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- ═══════════════════════════════════════
         SCROLL TO TOP
    ═══════════════════════════════════════ -->
    <button class="fw-scroll-top" id="fw-scroll-top" aria-label="Scroll to top">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
    </button>

    <!-- ═══════════════════════════════════════
         FOOTER
    ═══════════════════════════════════════ -->
    <footer>
        <div class="container">
            <div class="footer-top">
                <div class="footer-col footer-brand">
                    <div class="footer-logo">VERTEX</div>
                    <p>Your one-stop shop for the latest laptops and tech accessories. Quality gear, unbeatable prices.</p>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Shop</h3>
                    <ul>
                        <li><a href="#">Laptops</a></li>
                        <li><a href="#">Keyboards</a></li>
                        <li><a href="#">Mice</a></li>
                        <li><a href="#">Headphones</a></li>
                        <li><a href="#">Accessories</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Shipping &amp; Returns</a></li>
                        <li><a href="#">Warranty</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact Info</h3>
                    <ul class="footer-contact">
                        <li><span class="footer-contact-icon"><i class="fas fa-map-marker-alt"></i></span>123 Tech Street, San Francisco</li>
                        <li><span class="footer-contact-icon"><i class="fas fa-phone"></i></span>+1 (555) 123-4567</li>
                        <li><span class="footer-contact-icon"><i class="fas fa-envelope"></i></span>support@vertex.com</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Vertex. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ═══════════════════════════════════════
         CART MODAL
    ═══════════════════════════════════════ -->
    <div class="cart-overlay" id="cartModal">
        <div class="cart-modal-box">
            <div class="cart-modal-header">
                <div class="cart-success-tag">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Product added to your cart!
                </div>
                <button class="cart-modal-close" id="modalCloseBtn" aria-label="Close">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="cart-modal-product">
                <div class="product-thumb">
                    <img id="modalProductImg" src="" alt="" style="display:none;"/>
                    <span class="product-thumb-placeholder" id="modalProductEmoji">📦</span>
                </div>
                <div class="product-details">
                    <div class="product-name" id="modalProductName">Product Name</div>
                    <div class="product-meta">
                        <div class="meta-row">Qty: <span class="qty-badge" id="modalQty">1</span></div>
                        <div class="meta-row">Cart Total: <strong id="modalCartTotal">₱0.00</strong></div>
                    </div>
                </div>
            </div>
            <div class="cart-modal-summary">
                <div class="summary-text"><span id="modalItemCount">1 item</span> in your cart</div>
                <div class="summary-total">
                    <span class="total-label">Cart Total</span>
                    <span class="total-amount" id="modalTotalAmount">₱0.00</span>
                </div>
            </div>
            <div class="cart-modal-actions">
                <button class="btn-secondary" id="modalContinueBtn">Continue Shopping</button>
                <button class="btn-primary" onclick="if(window.isUserLoggedIn) window.location.href='checkout.php'; else window.location.href=window.loginRedirectUrl;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    Proceed to Checkout
                </button>
            </div>
        </div>
    </div>

    <script>
        window.SHOP_CONFIG = {
            userId: '<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '0'; ?>',
            isLoggedIn: <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/cart.js"></script>
    <script src="assets/js/deal.js"></script>
    <script src="assets/js/wishlist.js"></script>
    <script src="assets/js/main.js"></script>

</body>
</html>