<?php
session_start();
require_once 'assets/php/db.php';

$search          = isset($_GET['q'])        ? trim($_GET['q'])        : '';
$category        = isset($_GET['category']) ? trim($_GET['category']) : '';
$minPrice        = isset($_GET['min_price']) ? max(0, floatval($_GET['min_price'])) : 0;

// Get price range from DB first
$priceRes     = mysqli_query($conn, "SELECT MIN(price) as min_p, MAX(price) as max_p FROM products WHERE status='active'");
$priceRange   = mysqli_fetch_assoc($priceRes);
$maxAvailable = (float)($priceRange['max_p'] ?? 10000);

// Only set maxPrice if explicitly provided in URL, otherwise use max available
$maxPrice        = isset($_GET['max_price']) ? floatval($_GET['max_price']) : $maxAvailable;
$sort            = isset($_GET['sort']) && in_array($_GET['sort'], ['newest','price_low','price_high','popular','rating'])
                   ? $_GET['sort'] : 'newest';
$page            = max(1, (int)($_GET['page'] ?? 1));
$perPage         = 12;
$offset          = ($page - 1) * $perPage;
$arrivalFilter   = isset($_GET['arrival'])  ? trim($_GET['arrival'])  : '';
$sellerFilter    = isset($_GET['seller'])   ? trim($_GET['seller'])   : '';
$discountFilter  = isset($_GET['discount']) ? trim($_GET['discount']) : '';
$ratingFilterRaw = isset($_GET['rating'])   ? trim($_GET['rating'])   : '';
$ratingValues    = array_values(array_filter(
    array_map('intval', explode(',', $ratingFilterRaw)),
    function($v){ return $v >= 1 && $v <= 5; }
));
$ratingFilter    = count($ratingValues) === 1 ? $ratingValues[0] : 0;

$catRes     = mysqli_query($conn, "SELECT id, name FROM categories WHERE active = 1 ORDER BY name");
$categories = [];
while ($c = mysqli_fetch_assoc($catRes)) { $categories[] = $c; }

$categoryNames = $category ? array_filter(array_map('trim', explode(',', $category))) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $category ? htmlspecialchars($category).' - Shop' : 'Shop'; ?> • Vertex</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/shop.css" />
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="shop-hero">
        <div class="hero-dots hero-dots-left"> <!-- your dots svg --> </div>
        <div class="hero-dots hero-dots-right"> <!-- your dots svg --> </div>

        <h1 class="hero-title">Shop</h1>
        <p class="hero-subtitle">Discover thousands of products curated just for you</p>
        <nav class="hero-breadcrumb">
            <a href="index.php">Home</a>
            <span class="bc-sep">></span>
            <span>Shop</span>
        </nav>
    </div>

    <script>
    window.SHOP_CONFIG = {
        q:         <?php echo json_encode($search); ?>,
        category:  <?php echo json_encode($category); ?>,
        min_price: <?php echo json_encode($minPrice > 0 ? (string)$minPrice : ''); ?>,
        max_price: <?php echo json_encode($maxPrice < $maxAvailable ? (string)$maxPrice : ''); ?>,
        rating:    <?php echo json_encode($ratingFilterRaw); ?>,
        arrival:   <?php echo json_encode($arrivalFilter); ?>,
        seller:    <?php echo json_encode($sellerFilter); ?>,
        discount:  <?php echo json_encode($discountFilter); ?>,
        stock:     <?php echo json_encode(isset($_GET['stock']) ? $_GET['stock'] : ''); ?>,
        sort:      <?php echo json_encode($sort); ?>,
        maxPrice:  <?php echo (int)$maxAvailable; ?>,
        userId:    '<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '0'; ?>',
    };
    </script>

    <div class="shop-layout">

        <!-- ══ SIDEBAR ══ -->
        <aside class="sidebar" id="shopSidebar">
            <div class="sidebar-panel">
                <div class="panel-title">Filter Options</div>

                <!-- ── Price ── -->
                <div class="filter-group">
                    <span class="filter-group-title">Price</span>
                    <div class="price-group-body">
                        <div class="price-input-row">
                            <div class="price-input-box">
                                <span class="price-input-sym">₱</span>
                                <input type="text" inputmode="numeric" id="priceMin"
                                    value="<?php echo number_format((int)$minPrice); ?>"
                                    placeholder="Min">
                            </div>
                            <span class="price-dash">—</span>
                            <div class="price-input-box">
                                <span class="price-input-sym">₱</span>
                                <input type="text" inputmode="numeric" id="priceMax"
                                    value="<?php echo number_format($maxPrice >= $maxAvailable ? (int)$maxAvailable : (int)$maxPrice); ?>"
                                    placeholder="Max">
                            </div>
                        </div>
                        <div class="range-slider-wrap">
                            <div class="range-track"><div class="range-fill" id="rangeFill"></div></div>
                            <input type="range" id="rangeMin" class="range-input"
                                min="0" max="<?php echo (int)$maxAvailable; ?>" step="10"
                                value="<?php echo (int)$minPrice; ?>">
                            <input type="range" id="rangeMax" class="range-input"
                                min="0" max="<?php echo (int)$maxAvailable; ?>" step="10"
                                value="<?php echo $maxPrice >= $maxAvailable ? (int)$maxAvailable : (int)$maxPrice; ?>">
                        </div>
                    </div>
                </div>

                <div class="sidebar-line"></div>

                <!-- ── By Categories ── -->
                <div class="filter-group category-group">
                    <span class="filter-group-title">By Categories</span>
                    <div class="category-scroll">
                        <?php foreach ($categories as $cat): ?>
                        <label class="filter-row">
                            <input type="checkbox" name="fcat" value="<?php echo htmlspecialchars($cat['name']); ?>"
                                <?php echo in_array($cat['name'], $categoryNames) ? 'checked' : ''; ?>>
                            <span class="frow-check"></span>
                            <span class="frow-label"><?php echo htmlspecialchars($cat['name']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="sidebar-line"></div>

                <!-- ── Review ── -->
                <div class="filter-group">
                    <span class="filter-group-title">Review</span>
                    <?php foreach ([5,4,3,2,1] as $star): ?>
                    <label class="filter-row">
                        <input type="checkbox" name="frating" value="<?php echo $star; ?>"
                            <?php echo in_array($star, $ratingValues) ? 'checked' : ''; ?>>
                        <span class="frow-check"></span>
                        <span class="frow-stars">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="<?php echo $s <= $star ? 'fas' : 'far'; ?> fa-star"></i>
                            <?php endfor; ?>
                            <span class="frow-star-label"><?php echo $star; ?> Star<?php echo $star > 1 ? 's' : ''; ?></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="sidebar-line"></div>

                <!-- ── By Promotions ── -->
                <div class="filter-group">
                    <span class="filter-group-title">By Promotions</span>
                    <label class="filter-row">
                        <input type="checkbox" id="fcheck-arrival" value="last_30_days"
                            <?php echo $arrivalFilter === 'last_30_days' ? 'checked' : ''; ?>>
                        <span class="frow-check"></span>
                        <span class="frow-label">New Arrivals</span>
                    </label>
                    <label class="filter-row">
                        <input type="checkbox" id="fcheck-seller" value="top_all_time"
                            <?php echo $sellerFilter === 'top_all_time' ? 'checked' : ''; ?>>
                        <span class="frow-check"></span>
                        <span class="frow-label">Best Sellers</span>
                    </label>
                    <label class="filter-row">
                        <input type="checkbox" id="fcheck-discount" value="10"
                            <?php echo $discountFilter !== '' ? 'checked' : ''; ?>>
                        <span class="frow-check"></span>
                        <span class="frow-label">On Sale</span>
                    </label>
                </div>

                <div class="sidebar-line"></div>

                <!-- ── Availability ── -->
                <div class="filter-group">
                    <span class="filter-group-title">Availability</span>
                    <label class="filter-row">
                        <input type="checkbox" id="fcheck-instock"
                            <?php echo (isset($_GET['stock']) && $_GET['stock']==='in') ? 'checked' : ''; ?>>
                        <span class="frow-check"></span>
                        <span class="frow-label">In Stock</span>
                    </label>
                    <label class="filter-row">
                        <input type="checkbox" id="fcheck-outstock"
                            <?php echo (isset($_GET['stock']) && $_GET['stock']==='out') ? 'checked' : ''; ?>>
                        <span class="frow-check"></span>
                        <span class="frow-label">Out of Stock</span>
                    </label>
                </div>

            </div>
        </aside>

        <!-- ══ MAIN ══ -->
        <main class="shop-main">
            <div class="results-bar">
                <div class="results-text">
                    Showing <strong id="rFrom">…</strong>–<strong id="rTo">…</strong>
                    of <strong id="rTotal">…</strong> results
                </div>
                <div class="results-controls">
                    <label class="sort-label">Sort by :</label>
                    <select class="sort-select" id="sortSelect">
                        <option value="newest"     <?php echo $sort==='newest'    ?'selected':''; ?>>Default Sorting</option>
                        <option value="price_low"  <?php echo $sort==='price_low' ?'selected':''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort==='price_high'?'selected':''; ?>>Price: High to Low</option>
                        <option value="rating"     <?php echo $sort==='rating'    ?'selected':''; ?>>Highest Rated</option>
                        <option value="popular"    <?php echo $sort==='popular'   ?'selected':''; ?>>Most Popular</option>
                    </select>
                </div>
            </div>

            <div class="active-filter-bar" id="activeFilterBar" style="display:none"></div>

            <div class="products-grid" id="productsGrid">
                <?php for ($i = 0; $i < 12; $i++): ?>
                <div class="skeleton-card">
                    <div class="skeleton-img"></div>
                    <div class="skeleton-body">
                        <div class="skeleton-line w40"></div>
                        <div class="skeleton-line w70"></div>
                        <div class="skeleton-line w50"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <div id="paginationWrap"></div>
        </main>
    </div>

    <?php include 'footer.php'; ?>
    <?php include 'assets/php/cart_modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="assets/js/cart.js" defer></script>
    <script src="assets/js/main.js" defer></script>
    <script src="assets/js/wishlist.js" defer></script>
    <script src="assets/js/shop.js" defer></script>
</body>
</html>