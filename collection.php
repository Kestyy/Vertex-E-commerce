<?php
session_start();
require_once 'assets/php/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Collections • Vertex</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/collection.css" />
</head>
<body>

    <?php include 'navbar.php'; ?>

    <!-- ══ HERO HEADER ══ -->
    <div class="coll-hero">
        <div class="coll-hero-title">Collections</div>
        <div class="coll-hero-sub">Explore our curated categories and find exactly what you need</div>
        <nav class="coll-hero-bc">
            <a href="index.php">Home</a>
            <span class="coll-bc-sep">›</span>
            <span>Collections</span>
        </nav>
    </div>

    <div class="coll-page">

        <!-- ══ BROWSE COLLECTIONS GRID ══ -->
        <div class="browse-header">
            <h2 class="browse-title">Browse Our Collections</h2>
        </div>

        <div class="browse-slider-wrap">
            <div class="browse-track" id="browseTrack">
                
                <!-- 1. Gaming Setup -->
                <a href="shop.php?category=Gaming" class="browse-card">
                    <div class="browse-card-img">
                        <img src="images/collection/gaming.jpg" alt="Gaming Setup" loading="lazy" onerror="this.src='images/collection/right.jpg'">
                    </div>
                    <div class="browse-card-body">
                        <div class="browse-card-name">Gaming Setup</div>
                        <div class="browse-card-desc">Level up your play with high-performance gear.</div>
                    </div>
                </a>

                <!-- 2. Work From Home Setup -->
                <a href="shop.php?category=Office" class="browse-card">
                    <div class="browse-card-img">
                        <img src="images/collection/wfh.jpg" alt="Work From Home Setup" loading="lazy" onerror="this.src='images/collection/right.jpg'">
                    </div>
                    <div class="browse-card-body">
                        <div class="browse-card-name">Work From Home Setup</div>
                        <div class="browse-card-desc">Create a productive and comfortable workspace.</div>
                    </div>
                </a>

                <!-- 3. Student Essentials -->
                <a href="shop.php?category=Student" class="browse-card">
                    <div class="browse-card-img">
                        <img src="images/collection/student.jpg" alt="Student Essentials" loading="lazy" onerror="this.src='images/collection/right.jpg'">
                    </div>
                    <div class="browse-card-body">
                        <div class="browse-card-name">Student Essentials</div>
                        <div class="browse-card-desc">Affordable tech and supplies for academic success.</div>
                    </div>
                </a>

                <!-- 4. Content Creator Setup -->
                <a href="shop.php?category=Creator" class="browse-card">
                    <div class="browse-card-img">
                        <img src="images/collection/creator.jpg" alt="Content Creator Setup" loading="lazy" onerror="this.src='images/collection/right.jpg'">
                    </div>
                    <div class="browse-card-body">
                        <div class="browse-card-name">Content Creator Setup</div>
                        <div class="browse-card-desc">Professional tools to capture and create amazing content.</div>
                    </div>
                </a>

            </div>

            <!-- Dot indicators -->
            <div class="browse-dots" id="browseDots"></div>
        </div>

    </div><!-- /.coll-page -->

    <?php include 'footer.php'; ?>

    <script>
        window.SHOP_CONFIG = {
            userId: '<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '0'; ?>',
            isLoggedIn: <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="assets/js/ecommerce-core.js" defer></script>
    <script src="assets/js/cart.js" defer></script>
    <script src="assets/js/cart-manager.js" defer></script>
    <script src="assets/js/wishlist.js" defer></script>
    <script src="assets/js/main.js" defer></script>
    <script>
    /* ── Simple dot-paginated slider ── */
    (function () {
        const track  = document.getElementById('browseTrack');
        const dotsEl = document.getElementById('browseDots');
        if (!track) return;

        const cards      = track.querySelectorAll('.browse-card');
        const perPage    = () => window.innerWidth < 600 ? 1 : window.innerWidth < 960 ? 2 : 3;
        let   page       = 0;

        function pages() { return Math.ceil(cards.length / perPage()); }

        function buildDots() {
            dotsEl.innerHTML = '';
            const n = pages();
            for (let i = 0; i < n; i++) {
                const d = document.createElement('button');
                d.className = 'browse-dot' + (i === page ? ' active' : '');
                d.addEventListener('click', () => goTo(i));
                dotsEl.appendChild(d);
            }
        }

        function goTo(p) {
            page = Math.max(0, Math.min(p, pages() - 1));
            const card  = track.querySelector('.browse-card');
            const style = getComputedStyle(track);
            const gap   = parseFloat(style.gap) || 20;
            const w     = card.offsetWidth + gap;
            track.style.transform = `translateX(-${page * perPage() * w}px)`;
            dotsEl.querySelectorAll('.browse-dot').forEach((d, i) =>
                d.classList.toggle('active', i === page));
        }

        buildDots();
        window.addEventListener('resize', () => { buildDots(); goTo(0); });
    })();
    </script>
</body>
</html>