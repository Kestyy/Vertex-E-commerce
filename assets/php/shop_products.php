<?php
/**
 * shop_products.php — AJAX partial, returns product cards + pagination only.
 */
session_start();
require_once __DIR__ . '/db.php';

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_user_id    (user_id),
    INDEX idx_created_at (created_at)
)");

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
$perPage         = 16;
$offset          = ($page - 1) * $perPage;
$arrivalFilter   = isset($_GET['arrival'])  ? trim($_GET['arrival'])  : '';
$sellerFilter    = isset($_GET['seller'])   ? trim($_GET['seller'])   : '';
$discountFilter  = isset($_GET['discount']) ? trim($_GET['discount']) : '';
$ratingFilterRaw = isset($_GET['rating'])   ? trim($_GET['rating'])   : '';
$ratingValues    = array_values(array_filter(
    array_map('intval', explode(',', $ratingFilterRaw)),
    function($v){ return $v >= 1 && $v <= 5; }
));

/* WHERE */
$whereParts = ["p.status = 'active'"];
$params = []; $types = '';

if ($category) {
    if (is_numeric($category)) { $whereParts[] = "p.category_id = ?"; $params[] = (int)$category; $types .= 'i'; }
    else { $whereParts[] = "c.name = ?"; $params[] = $category; $types .= 's'; }
}
if ($search) {
    $t = "%{$search}%"; $whereParts[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = $t; $params[] = $t; $types .= 'ss';
}
$whereParts[] = "p.price BETWEEN ? AND ?"; $params[] = $minPrice; $params[] = $maxPrice; $types .= 'dd';

if ($arrivalFilter === 'last_7_days')      $whereParts[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
elseif ($arrivalFilter === 'last_30_days') $whereParts[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
elseif ($arrivalFilter === 'this_month')   $whereParts[] = "MONTH(p.created_at)=MONTH(NOW()) AND YEAR(p.created_at)=YEAR(NOW())";

if ($discountFilter === 'clearance') {
    $whereParts[] = "p.original_price > 0 AND ((p.original_price-p.price)/p.original_price*100) >= 50";
} elseif (is_numeric($discountFilter)) {
    $pct = (int)$discountFilter;
    $whereParts[] = "p.original_price > 0 AND ((p.original_price-p.price)/p.original_price*100) >= $pct";
}
if (!empty($ratingValues)) {
    $minRating = min($ratingValues);
    $whereParts[] = "(SELECT COALESCE(AVG(rating),0) FROM reviews WHERE product_id=p.id) >= ?";
    $params[] = $minRating; $types .= 'i';
}

$where = 'WHERE '.implode(' AND ', $whereParts);

/* COUNT */
$cStmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id=c.id $where");
if ($params) mysqli_stmt_bind_param($cStmt, $types, ...$params);
mysqli_stmt_execute($cStmt);
$total      = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($cStmt))['total'];
$totalPages = max(1, (int)ceil($total / $perPage));
mysqli_stmt_close($cStmt);

/* ORDER */
if ($sellerFilter === 'top_all_time') {
    $orderBy = "ORDER BY (SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id=o.id WHERE oi.product_id=p.id) DESC, p.created_at DESC";
} elseif ($sellerFilter === 'most_purchased_week') {
    $orderBy = "ORDER BY (SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id=o.id WHERE oi.product_id=p.id AND o.created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)) DESC, p.created_at DESC";
} elseif ($sellerFilter === 'trending') {
    $orderBy = "ORDER BY p.views DESC, p.created_at DESC";
} else {
    switch ($sort) {
        case 'price_low':  $orderBy = "ORDER BY p.price ASC"; break;
        case 'price_high': $orderBy = "ORDER BY p.price DESC"; break;
        case 'rating':     $orderBy = "ORDER BY (SELECT COALESCE(AVG(rating),0) FROM reviews WHERE product_id=p.id) DESC, p.created_at DESC"; break;
        default:           $orderBy = "ORDER BY p.created_at DESC";
    }
}

/* FETCH */
$stmt = mysqli_prepare($conn,
    "SELECT p.*, c.name as category_name,
            (SELECT COUNT(*) FROM reviews WHERE product_id=p.id) as review_count,
            (SELECT AVG(rating) FROM reviews WHERE product_id=p.id) as avg_rating
     FROM products p LEFT JOIN categories c ON p.category_id=c.id
     $where $orderBy LIMIT $offset, $perPage");
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['avg_rating']   = round($row['avg_rating'] ?? 0, 1);
    $row['review_count'] = (int)$row['review_count'];
    $products[] = $row;
}
mysqli_stmt_close($stmt);

header('Content-Type: text/html; charset=utf-8');
?>
<?php if (empty($products)): ?>
<div class="empty-state">
    <div class="empty-icon"><i class="fas fa-search"></i></div>
    <h3>No products found</h3>
    <p>Try adjusting your filters or search terms.</p>
</div>
<?php else: ?>
<?php foreach ($products as $p):
    $isOut    = $p['stock_quantity'] <= 0;
    $isLow    = !$isOut && $p['stock_quantity'] < 10;
    $hasOrig  = isset($p['original_price']) && $p['original_price'] > $p['price'];
    $discount = $hasOrig ? round((1 - $p['price'] / $p['original_price']) * 100) : 0;
    
    // ✅ FIXED: Added 'images/products/' prefix to image path
    $imgSrc   = !empty($p['image']) ? 'images/products/' . htmlspecialchars($p['image']) : 'images/products/default.jpg';
    
    $pName    = addslashes(htmlspecialchars($p['name']));
    $rating   = $p['avg_rating'] > 0 ? $p['avg_rating'] : 4.5;
?>
<div class="product-card" data-product-id="<?php echo $p['id']; ?>">

    <!-- Image -->
    <div class="product-img-wrap">
        <img src="<?php echo $imgSrc; ?>" 
             alt="<?php echo htmlspecialchars($p['name']); ?>" 
             loading="lazy"
             onerror="this.src='images/products/default.jpg'">

        <!-- Badges -->
        <div class="badge-wrap">
            <?php if ($discount >= 5): ?>
                <span class="badge badge-sale"><?php echo $discount; ?>% off</span>
            <?php endif; ?>
            <?php if ($isLow): ?>
                <span class="badge badge-low">Low Stock</span>
            <?php elseif ($isOut): ?>
                <span class="badge badge-out">Sold Out</span>
            <?php endif; ?>
        </div>

        <!-- Wishlist -->
        <button class="product-wish wish-btn" data-product-id="<?php echo $p['id']; ?>" data-product-name="<?php echo htmlspecialchars($pName); ?>"
            onclick="event.stopPropagation(); toggleWishlist({id:<?php echo $p['id']; ?>, name:'<?php echo $pName; ?>', price:<?php echo $p['price']; ?>, image:'<?php echo $imgSrc; ?>'})"
            aria-label="Wishlist"><i class="far fa-heart"></i></button>
    </div>

    <!-- Body -->
    <div class="product-body" data-product-id="<?php echo $p['id']; ?>" onclick="viewProductSecure(<?php echo $p['id']; ?>)">

        <!-- Category + Rating -->
        <div class="product-meta-row">
            <span class="product-cat-tag"><?php echo htmlspecialchars($p['category_name'] ?? 'Product'); ?></span>
            <span class="product-rating-pill">
                <span class="rating-star"><i class="fas fa-star"></i></span>
                <span class="rating-val"><?php echo $rating; ?></span>
            </span>
        </div>

        <!-- Product name -->
        <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>

        <!-- Price -->
        <div class="price-row">
            <span class="price-now">₱<?php echo number_format($p['price'], 2); ?></span>
            <?php if ($hasOrig): ?>
                <span class="price-orig">₱<?php echo number_format($p['original_price'], 2); ?></span>
            <?php endif; ?>
        </div>

    </div>

    <!-- Add to Cart -->
    <div class="card-actions">
        <button class="btn-cart btn-cart-icon"
                <?php echo $isOut ? 'disabled' : ''; ?>
                data-product-id="<?php echo $p['id']; ?>"
                data-name="<?php echo $pName; ?>"
                data-price="₱<?php echo number_format($p['price'], 2); ?>"
                data-img="<?php echo $imgSrc; ?>"
                <?php echo $isOut ? 'title="Out of Stock"' : ''; ?>>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12.5 5v2.5H10v2h2.5V12h2V9.5H17v-2h-2.5V5z"></path><path d="M17.31 14H9.72L5.95 2.68A1 1 0 0 0 5 2H2v2h2.28l3.54 10.63A2 2 0 0 0 9.72 16h7.59a2 2 0 0 0 1.87-1.3l2.76-7.35-1.87-.7zM10 18a2 2 0 1 0 0 4 2 2 0 1 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 1 0 0-4"></path></svg>
            <?php echo $isOut ? 'Out of Stock' : 'Add to Cart'; ?>
        </button>
    </div>

</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($totalPages >= 1): ?>
<div class="pagination" id="ajaxPagination">
    <?php
    // Prev
    if ($page > 1)
        echo '<a href="#" data-page="'.($page-1).'" class="page-arrow"><i class="fas fa-chevron-left"></i></a>';
    else
        echo '<span class="page-arrow disabled"><i class="fas fa-chevron-left"></i></span>';

    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++):
        if ($i === 1 || $i === $totalPages || abs($i - $page) <= 1):
            echo '<a href="#" data-page="'.$i.'" class="'.($i === $page ? 'active' : '').'">'.$i.'</a>';
        elseif ($i === 2 && $page > 3):
            echo '<span class="page-dots">…</span>';
        elseif ($i === $totalPages - 1 && $page < $totalPages - 2):
            echo '<span class="page-dots">…</span>';
        endif;
    endfor;

    // Next
    if ($page < $totalPages)
        echo '<a href="#" data-page="'.($page+1).'" class="page-arrow"><i class="fas fa-chevron-right"></i></a>';
    else
        echo '<span class="page-arrow disabled"><i class="fas fa-chevron-right"></i></span>';
    ?>
</div>
<?php endif; ?>

<script>
window._shopMeta = {
    total:      <?php echo $total; ?>,
    page:       <?php echo $page; ?>,
    totalPages: <?php echo $totalPages; ?>,
    offset:     <?php echo $offset; ?>,
    perPage:    <?php echo $perPage; ?>
};
</script>