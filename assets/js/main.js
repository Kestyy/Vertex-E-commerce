// ═══════════════════════════════════════
// INIT
// ═══════════════════════════════════════
document.addEventListener('DOMContentLoaded', function () {
    initAnnounceBar();
    initNavDropdown();
    initScrollTop();
    initActiveNavLinks();
    initMobileToggle();
    initNewsletter();
    // ❌ Removed broken calls: initCartModal(), cartButton()
    initLogoutModal(); // ✅ Works now!
});

// Carousels need full page load to get correct dimensions
window.addEventListener('load', function () {
    initCategoryCarousel();
    initReviewsCarousel();
});

// ═══════════════════════════════════════
// ACTIVE NAV LINKS
// ═══════════════════════════════════════
function initActiveNavLinks() {
    const navLinks = document.querySelectorAll('.navbar .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        });
    });
}

// ═══════════════════════════════════════
// SCROLL TO TOP
// ═══════════════════════════════════════
function initScrollTop() {
    const scrollTopBtn = document.getElementById('fw-scroll-top');
    if (!scrollTopBtn) return;
    window.addEventListener('scroll', () => {
        scrollTopBtn.classList.toggle('fw-scroll-top-show', window.scrollY > 300);
    });
    scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// ═══════════════════════════════════════
// NAV DROPDOWN
// ═══════════════════════════════════════
function initNavDropdown() {
    const userBtn      = document.getElementById('userBtn');
    const userDropdown = document.getElementById('userDropdown');
    const isLoggedIn   = document.querySelector('[data-logged-in]') !== null;

    if (!userBtn || !userDropdown) return;

    userBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        e.preventDefault();
        if (!isLoggedIn) {
            if (!window.location.pathname.includes('/auth/login.php')) {
                window.location.href = '/vertex/auth/login.php';
            }
        } else {
            userDropdown.classList.toggle('open');
        }
    });

    // ✅ Close dropdown when clicking outside (but allow links to work)
    document.addEventListener('click', (e) => {
        // Only close if clicking outside the dropdown
        if (!userDropdown.contains(e.target) && e.target !== userBtn) {
            userDropdown.classList.remove('open');
        }
    });
}

// ═══════════════════════════════════════
// MOBILE TOGGLE
// ═══════════════════════════════════════
function initMobileToggle() {
    const mobileToggle = document.getElementById('mobileToggle');
    if (!mobileToggle) return;
    mobileToggle.addEventListener('click', () => {
        document.getElementById('navbarNav').classList.toggle('open');
    });
}

// ═══════════════════════════════════════
// ANNOUNCEMENT BAR
// ═══════════════════════════════════════
function initAnnounceBar() {
    const bar = document.getElementById('announceBar');
    if (!bar) return;
    if (localStorage.getItem('announceBarClosed') === 'true') {
        bar.style.display = 'none';
    }
}

function closeAnnounceBar() {
    const bar = document.getElementById('announceBar');
    if (bar) bar.style.display = 'none';
    localStorage.setItem('announceBarClosed', 'true');
}

function copyCode(el) {
    navigator.clipboard.writeText(el.textContent.trim());
    const orig = el.textContent;
    el.textContent = 'Copied!';
    setTimeout(() => el.textContent = orig, 1500);
}

// ═══════════════════════════════════════
// NEWSLETTER
// ═══════════════════════════════════════
function initNewsletter() {
    const submitBtn = document.getElementById('nl-submit');
    if (!submitBtn) return;

    submitBtn.addEventListener('click', function () {
        const email = document.getElementById('nl-email').value.trim();
        if (!email || !email.includes('@')) {
            const inp = document.getElementById('nl-email');
            inp.style.borderColor = 'rgba(255,100,100,0.5)';
            inp.focus();
            return;
        }
        this.textContent = 'Subscribing...';
        this.disabled = true;
        setTimeout(() => {
            document.getElementById('nl-form-wrap').style.display = 'none';
            document.getElementById('nl-success').style.display = 'flex';
        }, 800);
    });
}

// ═══════════════════════════════════════════════════════════════
// CATEGORY CAROUSEL
// Fetches categories from api/categories.php, renders .cat-card
// elements, then builds the sliding carousel track.
// ═══════════════════════════════════════════════════════════════

async function initCategoryCarousel() {
    const grid    = document.getElementById('catGrid');
    const prevBtn = document.getElementById('catPrev');
    const nextBtn = document.getElementById('catNext');
    const skeleton = document.getElementById('catSkeleton');

    if (!grid || !prevBtn || !nextBtn) return;

    const VISIBLE = 6;
    const GAP     = 18;
    let current   = 0;
    let cardWidth = 0;

    // ── 1. Fetch categories from the API ─────────────────────────
    let categories = [];
    try {
        const res  = await fetch('assets/php/categories_api.php?action=list');
        const data = await res.json();
        if (data.success) {
            categories = data.categories;
        }
    } catch (err) {
        console.error('Failed to load categories:', err);
    }

    // Remove skeleton loader
    if (skeleton) skeleton.remove();

    // If no categories, show a friendly message and bail
    if (!categories.length) {
        grid.innerHTML = '<p style="color:#94a3b8;font-size:14px;padding:32px 0;">No categories yet.</p>';
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
        return;
    }

    // ── 2. Render .cat-card elements ──────────────────────────────
    grid.innerHTML = ''; // clear

    categories.forEach(cat => {
        const a       = document.createElement('a');
        a.href        = `shop.php?category=${encodeURIComponent(cat.name)}`;
        a.className   = 'cat-card';

        // Use uploaded image if available, else a placeholder
        const imgSrc  = cat.image
            ? cat.image
            : 'images/placeholder-category.png';

        a.innerHTML = `
            <img src="${escHtml(imgSrc)}" alt="${escHtml(cat.name)}" class="cat-img"
                 onerror="this.src='images/placeholder-category.png'"/>
            <div class="cat-text"><span class="cat-name">${escHtml(cat.name)}</span></div>
        `;
        grid.appendChild(a);
    });

    // ── 3. Build carousel track ───────────────────────────────────
    function getCards() {
        return Array.from(grid.querySelectorAll('.cat-card'));
    }

    function updateArrows(total) {
        // Hide arrows if not enough items to scroll
        if (total <= VISIBLE) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'block';
            nextBtn.style.display = 'block';
            prevBtn.disabled = current === 0;
            nextBtn.disabled = current + VISIBLE >= total;
        }
    }

    function buildTrack() {
        // Destroy old track if re-initialising (e.g. on resize)
        const old = document.getElementById('catTrack');
        if (old) {
            // Move cards back to grid before removing track
            getCards().forEach(c => grid.appendChild(c));
            old.remove();
        }

        const cards     = getCards();
        const gridWidth = grid.getBoundingClientRect().width;
        cardWidth       = (gridWidth - (VISIBLE - 1) * GAP) / VISIBLE;

        const track     = document.createElement('div');
        track.id        = 'catTrack';
        track.style.cssText = `
            display: flex;
            gap: ${GAP}px;
            transition: transform 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            will-change: transform;
        `;

        cards.forEach(card => {
            card.style.display  = 'flex';
            card.style.minWidth = cardWidth + 'px';
            card.style.width    = cardWidth + 'px';
            track.appendChild(card);
        });

        grid.appendChild(track);
        current = 0;
        updateArrows(cards.length);
    }

    function slideTo(newIndex) {
        const track = document.getElementById('catTrack');
        const total = getCards().length;

        current = Math.max(0, Math.min(newIndex, total - VISIBLE));
        track.style.transform = `translateX(-${current * (cardWidth + GAP)}px)`;
        updateArrows(total);
    }

    prevBtn.addEventListener('click', () => slideTo(current - 1));
    nextBtn.addEventListener('click', () => slideTo(current + 1));

    // Debounced resize
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            buildTrack();
            slideTo(0);
        }, 150);
    });

    buildTrack();
}

// ── HTML-escape helper (used in template literals) ────────────────
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ── Run on DOM ready ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', initCategoryCarousel);

// ═══════════════════════════════════════
// CUSTOMER REVIEWS CAROUSEL
// ═══════════════════════════════════════
function initReviewsCarousel() {
    const track   = document.getElementById('reviews-track');
    const prevBtn = document.getElementById('reviewsPrev');
    const nextBtn = document.getElementById('reviewsNext');

    if (!track || !prevBtn || !nextBtn) return;

    const originalCards = Array.from(track.querySelectorAll('.review-card'));
    const total         = originalCards.length;
    const GAP           = 20;
    let current         = 0;
    let cardWidth       = 0;
    let isAnimating     = false;

    function getVisible() {
        if (window.innerWidth >= 1024) return 3;
        if (window.innerWidth >= 640)  return 2;
        return 1;
    }

    function calcCardWidth() {
        const trackWidth = track.parentElement.getBoundingClientRect().width;
        const visible    = getVisible();
        return (trackWidth - (visible - 1) * GAP) / visible;
    }

    function buildClones() {
        track.querySelectorAll('.review-card-clone').forEach(el => el.remove());
        const visible = getVisible();

        const leadingClones = originalCards.slice(-visible).map(c => {
            const cl = c.cloneNode(true);
            cl.classList.add('review-card-clone');
            return cl;
        });

        const trailingClones = originalCards.slice(0, visible).map(c => {
            const cl = c.cloneNode(true);
            cl.classList.add('review-card-clone');
            return cl;
        });

        leadingClones.reverse().forEach(cl => track.prepend(cl));
        trailingClones.forEach(cl => track.appendChild(cl));
    }

    function getAllCards() {
        return Array.from(track.querySelectorAll('.review-card, .review-card-clone'));
    }

    function setCardWidths() {
        cardWidth = calcCardWidth();
        getAllCards().forEach(card => {
            card.style.flex     = 'none';
            card.style.width    = cardWidth + 'px';
            card.style.minWidth = cardWidth + 'px';
        });
    }

    function goTo(index, animate = true) {
        const visible = getVisible();
        const offset  = (index + visible) * (cardWidth + GAP);
        track.style.transition = animate
            ? 'transform 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94)'
            : 'none';
        track.style.transform = `translateX(-${offset}px)`;
    }

    function next() {
        if (isAnimating) return;
        isAnimating = true;
        current++;
        goTo(current);

        if (current >= total) {
            setTimeout(() => { current = 0; goTo(current, false); isAnimating = false; }, 360);
        } else {
            setTimeout(() => { isAnimating = false; }, 360);
        }
    }

    function prev() {
        if (isAnimating) return;
        isAnimating = true;
        current--;
        goTo(current);

        if (current < 0) {
            setTimeout(() => { current = total - 1; goTo(current, false); isAnimating = false; }, 360);
        } else {
            setTimeout(() => { isAnimating = false; }, 360);
        }
    }

    function init() {
        track.style.cssText = `
            display: flex;
            gap: ${GAP}px;
            will-change: transform;
        `;
        buildClones();
        setCardWidths();
        goTo(current, false);
    }

    prevBtn.addEventListener('click', prev);
    nextBtn.addEventListener('click', next);
    prevBtn.disabled = false;
    nextBtn.disabled = false;

    window.addEventListener('resize', () => {
        buildClones();
        setCardWidths();
        current = 0;
        goTo(current, false);
    });

    requestAnimationFrame(init);
}

// ═══════════════════════════════════════
// LOGOUT MODAL
// ═══════════════════════════════════════
function initLogoutModal() {
    const modal = document.getElementById('logoutModal');
    const trigger = document.getElementById('logoutTrigger'); // Navbar link
    const cancel = document.getElementById('logoutCancel');
    
    if (!modal) return;

    // Helper to close modal
    function closeModal() {
        modal.classList.remove('active');
        document.body.classList.remove('modal-open');
    }

    // 1. Navbar trigger (dropdown link)
    if (trigger) {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            modal.classList.add('active');
            document.body.classList.add('modal-open');
        });
    }

    // 2. Cancel button
    if (cancel) {
        cancel.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal();
        });
    }

    // 3. Click outside modal to close
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
}

/* ════════════════════════════════════════
   SECURE PRODUCT VIEWER
   Loads product details without exposing ID in URL
════════════════════════════════════════ */
function viewProductSecure(productId) {
    // Validate product ID
    if (!productId || productId <= 0) {
        console.error('[PRODUCT VIEW] Invalid product ID');
        return;
    }
    
    // ✅ Send via secure POST handler (no product IDs in URL)
    fetch('assets/php/load_product_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${productId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            console.error('[PRODUCT VIEW]', data.error);
        }
    })
    .catch(err => {
        console.error('[PRODUCT VIEW]', err);
    });
}