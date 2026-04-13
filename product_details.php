<?php
session_start();
require_once 'assets/php/db.php';

// ── Get product ID from secure session (not from URL) ──
$product_id = 0;
if (isset($_SESSION['product_view_id'])) {
    $product_id = (int)$_SESSION['product_view_id'];
    unset($_SESSION['product_view_id']); // Clear after reading (one-time use)
}

if (!$product_id) {
    header('Location: shop.php');
    exit;
}

// Fetch product
$stmt = mysqli_prepare($conn, "
    SELECT p.*, c.name AS category_name,
           COALESCE(AVG(r.rating), 0) AS avg_rating,
           COUNT(r.id) AS review_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON r.product_id = p.id
    WHERE p.id = ? AND p.status = 'active'
    GROUP BY p.id
");
mysqli_stmt_bind_param($stmt, 'i', $product_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    header('Location: shop.php');
    exit;
}

// Map actual DB columns to template variables
// price = original price (strikethrough), sale_price = discounted selling price
$product['display_price']     = ($product['sale_price'] > 0) ? (float)$product['sale_price'] : (float)$product['price'];
$product['compare_price']     = ($product['sale_price'] > 0) ? (float)$product['price'] : 0;
$product['has_discount']      = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']);
$product['short_description'] = $product['short_description'] ?? '';

// Fetch product images from product table
$images = [];
if (!empty($product['image'])) {
    $images[] = ['image_url' => 'images/products/' . htmlspecialchars($product['image'])];
} else {
    $images[] = ['image_url' => 'images/products/default.jpg'];
}

// Fetch variants/sizes (graceful fallback if table missing)
$variants = [];

// Fetch tags (graceful fallback if tables missing)
$tags = [];

// Fetch reviews (paginated) — graceful fallback if reviews table missing
$reviewPage    = max(1, (int)($_GET['rpage'] ?? 1));
$reviewPerPage = 4;
$reviewOffset  = ($reviewPage - 1) * $reviewPerPage;
$sortReview    = isset($_GET['rsort']) && $_GET['rsort'] === 'oldest' ? 'ASC' : 'DESC';

$totalReviews = 0;
$revCountRes  = mysqli_query($conn, "SELECT COUNT(*) FROM reviews WHERE product_id = $product_id");
if ($revCountRes) { $totalReviews = (int)mysqli_fetch_row($revCountRes)[0]; }

$reviews = [];
$revRes = mysqli_query($conn, "
    SELECT r.*, u.full_name,
           IFNULL(u.avatar, '') AS profile_image,
           (SELECT COUNT(*) FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE oi.product_id = r.product_id AND o.user_id = r.user_id) AS is_verified
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.product_id = $product_id
    ORDER BY r.created_at $sortReview
    LIMIT $reviewPerPage OFFSET $reviewOffset
");
if ($revRes) { while ($rv = mysqli_fetch_assoc($revRes)) { $reviews[] = $rv; } }

// Star distribution
$starDist = [5=>0,4=>0,3=>0,2=>0,1=>0];
$sdRes = mysqli_query($conn, "SELECT rating, COUNT(*) as cnt FROM reviews WHERE product_id = $product_id GROUP BY rating");
if ($sdRes) { while ($sd = mysqli_fetch_assoc($sdRes)) { $starDist[(int)$sd['rating']] = (int)$sd['cnt']; } }

$avgRating   = round((float)$product['avg_rating'], 1);
$reviewCount = (int)$product['review_count'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($product['name']); ?> — Vertex</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/style.css"/>
  <link rel="stylesheet" href="assets/css/product_details.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- ══ PRODUCT HERO ══ -->
<div class="cart-hero">
  <div class="cart-hero-title">Product Details</div>
  <nav class="cart-hero-bc" aria-label="breadcrumb">
    <a href="index.php">Home</a>
    <span class="cart-bc-sep">›</span>
    <a href="shop.php">Shop</a>
    <span class="cart-bc-sep">›</span>
    <span><?php echo htmlspecialchars($product['name']); ?></span>
  </nav>
</div>

<main class="cart-page">
<section class="pd-section">
    <div class="pd-container">
        <div class="pd-grid">

            <!-- ── LEFT: Gallery ── -->
            <div class="pd-gallery">
                <div class="pd-main-img-wrap">
                    <button class="gallery-arrow gallery-prev" id="galleryPrev">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <img src="<?php echo htmlspecialchars($images[0]['image_url']); ?>"
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="pd-main-img" id="mainProductImg" />
                    <button class="gallery-arrow gallery-next" id="galleryNext">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <?php if ($product['has_discount']): ?>
                    <span class="pd-badge-sale">Sale</span>
                    <?php endif; ?>
                </div>
                <div class="pd-thumbs" id="pdThumbs">
                    <?php foreach ($images as $idx => $img): ?>
                    <div class="pd-thumb <?php echo $idx === 0 ? 'active' : ''; ?>"
                         data-src="<?php echo htmlspecialchars($img['image_url']); ?>"
                         data-idx="<?php echo $idx; ?>">
                        <img src="<?php echo htmlspecialchars($img['image_url']); ?>"
                             alt="Thumb <?php echo $idx+1; ?>" />
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ── RIGHT: Info ── -->
            <div class="pd-info">
                <div class="pd-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Product'); ?></div>

                <div class="pd-title-row">
                    <h1 class="pd-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <?php if ($product['stock_quantity'] > 0): ?>
                    <span class="pd-stock-badge in-stock">In Stock</span>
                    <?php else: ?>
                    <span class="pd-stock-badge out-stock">Out of Stock</span>
                    <?php endif; ?>
                </div>

                <!-- Stars -->
                <div class="pd-rating-row">
                    <div class="pd-stars">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i class="<?php echo $s <= round($avgRating) ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="pd-rating-val"><?php echo $avgRating; ?></span>
                    <span class="pd-rating-count">(<?php echo $reviewCount; ?> Review<?php echo $reviewCount !== 1 ? 's' : ''; ?>)</span>
                </div>

                <!-- Price -->
                <div class="pd-price-row">
                    <span class="pd-price">₱<?php echo number_format($product['display_price'], 2); ?></span>
                    <?php if ($product['has_discount']): ?>
                    <span class="pd-compare-price">₱<?php echo number_format($product['compare_price'], 2); ?></span>
                    <?php if ($product['discount_percentage'] > 0): ?>
                    <span class="pd-discount-badge">-<?php echo (int)$product['discount_percentage']; ?>%</span>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <p class="pd-short-desc">
                    <?php echo nl2br(htmlspecialchars(!empty($product['short_description']) ? $product['short_description'] : $product['description'])); ?>
                </p>

                <!-- Variants / Size -->
                <?php if (!empty($variants)): ?>
                <div class="pd-variants">
                    <span class="pd-label">Size/Volume</span>
                    <div class="pd-variant-btns" id="variantBtns">
                        <?php foreach ($variants as $vi => $v): ?>
                        <button class="variant-btn <?php echo $vi === 0 ? 'active' : ''; ?>"
                                data-variant-id="<?php echo $v['id']; ?>"
                                data-price="<?php echo $v['price'] ?? $product['price']; ?>">
                            <?php echo htmlspecialchars($v['name'] ?? $v['value']); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quantity + Actions -->
                <div class="pd-actions-row">
                    <div class="pd-qty">
                        <button class="qty-btn" id="qtyMinus"><i class="fas fa-minus"></i></button>
                        <input type="number" class="qty-input" id="qtyInput" value="1" min="1"
                               max="<?php echo (int)$product['stock_quantity']; ?>" />
                        <button class="qty-btn" id="qtyPlus"><i class="fas fa-plus"></i></button>
                    </div>
                    <button class="btn-add-cart" id="btnAddCart"
                            data-product-id="<?php echo $product_id; ?>"
                            <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12.5 5v2.5H10v2h2.5V12h2V9.5H17v-2h-2.5V5z"></path><path d="M17.31 14H9.72L5.95 2.68A1 1 0 0 0 5 2H2v2h2.28l3.54 10.63A2 2 0 0 0 9.72 16h7.59a2 2 0 0 0 1.87-1.3l2.76-7.35-1.87-.7zM10 18a2 2 0 1 0 0 4 2 2 0 1 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 1 0 0-4"></path></svg>
                        Add To Cart
                    </button>
                    <button class="btn-buy-now" id="btnBuyNow"
                            data-product-id="<?php echo $product_id; ?>"
                            <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                        Buy Now
                    </button>
                    <button class="btn-wishlist" id="btnWishlist"
                            data-product-id="<?php echo $product_id; ?>">
                        <i class="far fa-heart"></i>
                    </button>
                </div>

                <!-- Meta -->
                <div class="pd-meta">
    
                    <?php if (!empty($tags)): ?>
                    <div class="pd-meta-row">
                        <span class="pd-meta-label">Tags :</span>
                        <span class="pd-meta-val"><?php echo htmlspecialchars(implode(', ', $tags)); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="pd-meta-row">
                        <span class="pd-meta-label">Share :</span>
                        <div class="pd-share-icons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']); ?>"
                               target="_blank" class="share-icon facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($product['name']); ?>"
                               target="_blank" class="share-icon twitter"><i class="fab fa-twitter"></i></a>
                            <a href="https://www.instagram.com/" target="_blank" class="share-icon instagram"><i class="fab fa-instagram"></i></a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']); ?>"
                               target="_blank" class="share-icon linkedin"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ TABS: Description / Additional Info / Review ══ -->
<section class="pd-tabs-section">
    <div class="pd-container">
        <div class="pd-tabs-header">
            <button class="pd-tab-btn active" data-tab="review">Review</button>
        </div>

        <!-- Review Tab -->
        <div class="pd-tab-content active" id="tab-review">
            <div class="review-summary">
                <!-- Left: Overall Score -->
                <div class="review-score-box">
                    <div class="review-score-num"><?php echo $avgRating; ?></div>
                    <div class="review-score-label">out of 5</div>
                    <div class="review-score-stars">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i class="<?php echo $s <= round($avgRating) ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="review-score-count">(<?php echo $reviewCount; ?> Review<?php echo $reviewCount !== 1 ? 's' : ''; ?>)</div>
                </div>

                <!-- Right: Star Bars -->
                <div class="review-bars">
                    <?php foreach ([5,4,3,2,1] as $star): ?>
                    <?php
                        $cnt  = $starDist[$star] ?? 0;
                        $pct  = $reviewCount > 0 ? round(($cnt / $reviewCount) * 100) : 0;
                    ?>
                    <div class="review-bar-row">
                        <span class="rbar-label"><?php echo $star; ?> Star</span>
                        <div class="rbar-track">
                            <div class="rbar-fill <?php echo $star >= 4 ? 'fill-high' : ($star === 3 ? 'fill-mid' : 'fill-low'); ?>"
                                 style="width:<?php echo $pct; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Review List -->
            <div class="review-list-header">
                <h3 class="review-list-title">Review List</h3>
            </div>
            <div class="review-list-meta">
                <span>Showing <?php echo min($reviewOffset+1, $totalReviews); ?>–<?php echo min($reviewOffset+$reviewPerPage, $totalReviews); ?> of <?php echo $totalReviews; ?> results</span>
                <div class="review-sort">
                    <span>Sort by :</span>
                    <select id="reviewSortSelect" onchange="location.href=updateQueryParam('rsort', this.value)">
                        <option value="newest" <?php echo ($sortReview==='DESC')?'selected':''; ?>>Newest</option>
                        <option value="oldest" <?php echo ($sortReview==='ASC')?'selected':''; ?>>Oldest</option>
                    </select>
                </div>
            </div>

            <div class="review-list" id="reviewList">
                <?php if (empty($reviews)): ?>
                <div class="no-reviews">No reviews yet. Be the first to review!</div>
                <?php endif; ?>
                <?php foreach ($reviews as $rv): ?>
                <div class="review-card">
                    <div class="review-card-header">
                        <div class="reviewer-avatar">
                            <?php if (!empty($rv['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars('images/avatars/'.$rv['profile_image']); ?>"
                                 alt="<?php echo htmlspecialchars($rv['full_name']); ?>" />
                            <?php else: ?>
                            <div class="reviewer-initials">
                                <?php echo strtoupper(substr($rv['full_name'],0,2)); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="reviewer-info">
                            <div class="reviewer-name">
                                <?php echo htmlspecialchars($rv['full_name']); ?>
                                <?php if ($rv['is_verified']): ?>
                                <span class="verified-badge">(Verified)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="review-date">
                            <?php
                                $diff = time() - strtotime($rv['created_at']);
                                if ($diff < 86400) echo 'Today';
                                elseif ($diff < 172800) echo '1 day ago';
                                elseif ($diff < 2592000) echo floor($diff/86400).' days ago';
                                elseif ($diff < 5184000) echo '1 month ago';
                                else echo floor($diff/2592000).' months ago';
                            ?>
                        </div>
                    </div>
                    <div class="review-headline"><?php echo htmlspecialchars($rv['title'] ?? ''); ?></div>
                    <p class="review-body"><?php echo nl2br(htmlspecialchars($rv['comment'] ?? $rv['body'] ?? '')); ?></p>
                    <div class="review-footer">
                        <div class="review-stars">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <i class="<?php echo $s <= $rv['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                            <?php endfor; ?>
                            <span class="review-star-val"><?php echo number_format($rv['rating'],1); ?></span>
                        </div>
                    </div>

                    <!-- Review Images -->
                    <?php
                    $rimgs = [];
                    $rimgRes = mysqli_query($conn, "SELECT image_url FROM review_images WHERE review_id = ".(int)$rv['id']." LIMIT 4");
                    if ($rimgRes) { while ($ri = mysqli_fetch_assoc($rimgRes)) { $rimgs[] = $ri['image_url']; } }
                    ?>
                    <?php if (!empty($rimgs)): ?>
                    <div class="review-images">
                        <?php foreach ($rimgs as $rimg): ?>
                        <div class="review-img-thumb">
                            <img src="<?php echo htmlspecialchars($rimg); ?>" alt="Review image" />
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Review Pagination -->
            <?php if ($totalReviews > $reviewPerPage): ?>
            <div class="review-pagination">
                <?php
                $totalRPages = ceil($totalReviews / $reviewPerPage);
                for ($rp = 1; $rp <= $totalRPages; $rp++):
                    $url = '?id='.$product_id.'&rpage='.$rp.(isset($_GET['rsort'])?'&rsort='.urlencode($_GET['rsort']):'');
                ?>
                <a href="<?php echo $url; ?>"
                   class="rpage-btn <?php echo $rp === $reviewPage ? 'active' : ''; ?>"><?php echo $rp; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <!-- Write a Review -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="write-review-section">
                <h4 class="write-review-title">Write a Review</h4>
                <form class="write-review-form" id="writeReviewForm">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <div class="wr-rating-row">
                        <span class="wr-label">Your Rating</span>
                        <div class="wr-stars" id="wrStars">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <i class="far fa-star" data-val="<?php echo $s; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="wrRatingInput" value="0">
                    </div>
                    <input type="text" class="wr-input" name="title" placeholder="Review title" />
                    <textarea class="wr-textarea" name="comment" rows="4" placeholder="Share your experience..."></textarea>
                    <button type="submit" class="wr-submit-btn">Submit Review</button>
                </form>
            </div>
            <?php else: ?>
            <div class="review-login-prompt">
                <a href="auth/login.php">Login</a> to write a review.
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
</main>

<?php include 'footer.php'; ?>
<?php include 'assets/php/cart_modal.php'; ?>

<script>
window.PRODUCT_CONFIG = {
    productId: <?php echo $product_id; ?>,
    userId:    '<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '0'; ?>',
    images:    <?php echo json_encode(array_column($images, 'image_url')); ?>,
};

function updateQueryParam(key, val) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, val);
    url.searchParams.set('rpage', 1);
    return url.toString();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="assets/js/cart.js" defer></script>
<script src="assets/js/main.js" defer></script>
<script src="assets/js/wishlist.js" defer></script>
<script src="assets/js/product_details.js" defer></script>
</body>
</html>