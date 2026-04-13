<?php
session_start();
require_once 'assets/php/db.php';

// Fetch deal products (products with sale_price or discount)
$deal_products = mysqli_query($conn, "
    SELECT p.*, c.name as category_name,
           COALESCE(p.discount_percentage, 0) as discount_percent
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
      AND (p.sale_price IS NOT NULL AND p.sale_price < p.price)
    ORDER BY 
      CASE 
        WHEN p.sale_price IS NOT NULL THEN ROUND(((p.price - p.sale_price) / p.price) * 100)
        ELSE 0
      END DESC, 
      p.created_at DESC
");

// If no products with sale_price, show all active products with discount_percentage
if (mysqli_num_rows($deal_products) === 0) {
    $deal_products = mysqli_query($conn, "
        SELECT p.*, c.name as category_name,
               COALESCE(p.discount_percentage, 0) as discount_percent
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active'
        ORDER BY p.discount_percentage DESC, p.created_at DESC
    ");
}

// Count deals
$deal_count = mysqli_num_rows($deal_products);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Deals • Vertex</title>
  
  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  
  <!-- Styles -->
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>

    <!-- NAVBAR -->
    <?php include 'navbar.php'; ?>

    <!-- ═══════════════════════════════════════
         DEALS SECTION
    ═══════════════════════════════════════ -->
    <section class="deal-section">
        <div class="deal-inner">
            <div class="deal-header-row">
                <div class="deal-left">
                    <h2 class="deal-title">All <span>Deals</span></h2>
                </div>
                <div class="deal-timer-wrap">
                    <p class="timer-label-top">Flash Sale ends in:</p>
                    <div class="deal-timer">
                        <div class="timer-box">
                            <div class="timer-value" id="t-hours">02</div>
                            <div class="timer-unit">Hrs</div>
                        </div>
                        <div class="timer-sep">:</div>
                        <div class="timer-box">
                            <div class="timer-value" id="t-mins">14</div>
                            <div class="timer-unit">Min</div>
                        </div>
                        <div class="timer-sep">:</div>
                        <div class="timer-box">
                            <div class="timer-value" id="t-secs">33</div>
                            <div class="timer-unit">Sec</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="deal-cards-grid">
                <?php while($product = mysqli_fetch_assoc($deal_products)): 
                  // Calculate discount and sale price
                  if ($product['sale_price'] && $product['sale_price'] < $product['price']) {
                    $discount = round(((($product['price'] - $product['sale_price']) / $product['price']) * 100));
                    $sale_price = $product['sale_price'];
                  } else {
                    $discount = (int)($product['discount_percent'] ?? 0);
                    $sale_price = $product['price'] * (1 - $discount / 100);
                  }
                ?>
                <div class="deal-card">
                    <!-- Discount Badge -->
                    <div class="deal-card-img-wrap">
                        <span class="deal-disc-pill"><?= $discount ?>% OFF</span>
                        <img src="images/products/<?= htmlspecialchars($product['image'] ?: 'default.jpg') ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             onerror="this.src='images/products/default.jpg'"/>
                    </div>

                    <!-- Product Info -->
                    <div class="deal-card-body">
                        <h3 class="deal-card-name"><?= htmlspecialchars($product['name']) ?></h3>
                        
                        <div class="product-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span class="rating-count">4.5</span>
                            <span class="rating-buyers">(2.4k)</span>
                        </div>

                        <div style="display: flex; gap: 8px; align-items: center; margin-top: 4px;">
                            <span style="font-size: 1.4rem; font-weight: 700; color: #0f172a;">₱<?= number_format($sale_price, 2) ?></span>
                            <span style="font-size: 1rem; color: #94a3b8; text-decoration: line-through;">₱<?= number_format($product['price'], 2) ?></span>
                        </div>

                        <button class="add-cart"
                                data-product-id="<?= $product['id'] ?>"
                                data-name="<?= htmlspecialchars($product['name']) ?>"
                                data-price="₱<?= number_format($sale_price, 2) ?>"
                                data-img="images/products/<?= htmlspecialchars($product['image'] ?: 'default.jpg') ?>"
                                style="margin-top: auto;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12.5 5v2.5H10v2h2.5V12h2V9.5H17v-2h-2.5V5z"></path><path d="M17.31 14H9.72L5.95 2.68A1 1 0 0 0 5 2H2v2h2.28l3.54 10.63A2 2 0 0 0 9.72 16h7.59a2 2 0 0 0 1.87-1.3l2.76-7.35-1.87-.7zM10 18a2 2 0 1 0 0 4 2 2 0 1 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 1 0 0-4"></path></svg>
                            Add to Cart
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Empty State -->
            <?php if ($deal_count === 0): ?>
            <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                <i class="fas fa-tag" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.5;"></i>
                <h3 style="font-size: 1.3rem; font-weight: 600; margin: 12px 0; color: #0f172a;">No deals available</h3>
                <p>Check back soon for amazing offers!</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- FOOTER -->
    <?php include 'footer.php'; ?>

    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/cart.js"></script>

</body>
</html>