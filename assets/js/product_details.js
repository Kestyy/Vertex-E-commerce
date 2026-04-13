/* ═══════════════════════════════════════════
   product_details.js — Vertex E-Commerce
   ═══════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', function () {
    const cfg = window.PRODUCT_CONFIG || {};

    /* ══ GALLERY ══ */
    const mainImg    = document.getElementById('mainProductImg');
    const thumbs     = document.querySelectorAll('.pd-thumb');
    const btnPrev    = document.getElementById('galleryPrev');
    const btnNext    = document.getElementById('galleryNext');
    let   currentIdx = 0;

    function setGalleryImg(idx) {
        currentIdx = (idx + cfg.images.length) % cfg.images.length;
        if (mainImg) {
            mainImg.style.opacity = '0';
            setTimeout(() => {
                mainImg.src = cfg.images[currentIdx];
                mainImg.style.opacity = '1';
            }, 150);
        }
        thumbs.forEach((t, i) => t.classList.toggle('active', i === currentIdx));
    }

    thumbs.forEach((t) => {
        t.addEventListener('click', () => setGalleryImg(parseInt(t.dataset.idx)));
    });

    if (btnPrev) btnPrev.addEventListener('click', () => setGalleryImg(currentIdx - 1));
    if (btnNext) btnNext.addEventListener('click', () => setGalleryImg(currentIdx + 1));

    /* ══ TABS ══ */
    const tabBtns     = document.querySelectorAll('.pd-tab-btn');
    const tabContents = document.querySelectorAll('.pd-tab-content');

    tabBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            const el = document.getElementById('tab-' + target);
            if (el) el.classList.add('active');
        });
    });

    /* ══ QUANTITY ══ */
    const qtyInput = document.getElementById('qtyInput');
    const qtyMinus = document.getElementById('qtyMinus');
    const qtyPlus  = document.getElementById('qtyPlus');

    if (qtyMinus) {
        qtyMinus.addEventListener('click', () => {
            const v = parseInt(qtyInput.value) || 1;
            if (v > 1) qtyInput.value = v - 1;
        });
    }
    if (qtyPlus) {
        qtyPlus.addEventListener('click', () => {
            const v   = parseInt(qtyInput.value) || 1;
            const max = parseInt(qtyInput.max) || 9999;
            if (v < max) qtyInput.value = v + 1;
        });
    }

    /* ══ VARIANTS ══ */
    const variantBtns = document.querySelectorAll('.variant-btn');
    variantBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            variantBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            // Update displayed price if variant has its own price
            const priceEl = document.querySelector('.pd-price');
            if (priceEl && btn.dataset.price && parseFloat(btn.dataset.price) > 0) {
                priceEl.textContent = '₱' + parseFloat(btn.dataset.price).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
            }
        });
    });

    /* ══ ADD TO CART ══ */
    const btnAddCart = document.getElementById('btnAddCart');
    if (btnAddCart) {
        btnAddCart.addEventListener('click', () => {
            const qty       = parseInt(qtyInput?.value) || 1;
            const productId = btnAddCart.dataset.productId;
            const activeVar = document.querySelector('.variant-btn.active');
            const variantId = activeVar ? activeVar.dataset.variantId : null;

            // Integrate with your existing cart.js addToCart function
            if (typeof addToCart === 'function') {
                addToCart(productId, qty, variantId);
            } else {
                fetch('assets/php/cart_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=add&product_id=${productId}&quantity=${qty}${variantId ? '&variant_id='+variantId : ''}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count badge if it exists
                        const badge = document.querySelector('.cart-count');
                        if (badge && data.cart_count) badge.textContent = data.cart_count;
                        showToast('Added to cart!', 'success');
                    } else {
                        showToast(data.message || 'Error adding to cart', 'error');
                    }
                });
            }
        });
    }

    /* ══ BUY NOW ══ */
    const btnBuyNow = document.getElementById('btnBuyNow');
    if (btnBuyNow) {
        btnBuyNow.addEventListener('click', () => {
            // ✅ Check if user is logged in before proceeding to checkout
            if (!window.isUserLoggedIn) {
                window.location.href = window.loginRedirectUrl;
                return;
            }
            
            const qty       = parseInt(qtyInput?.value) || 1;
            const productId = btnBuyNow.dataset.productId;
            
            // ✅ Send via secure POST handler (no product IDs in URL)
            fetch('assets/php/buy_now_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}&qty=${qty}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert('Error: ' + (data.error || 'Unable to proceed to checkout'));
                }
            })
            .catch(err => {
                console.error('[BUY NOW]', err);
                alert('Error processing your request');
            });
        });
    }

    /* ══ WISHLIST ══ */
    const btnWishlist = document.getElementById('btnWishlist');
    if (btnWishlist) {
        // Check if already wishlisted via your wishlist.js
        btnWishlist.addEventListener('click', () => {
            if (typeof toggleWishlist === 'function') {
                toggleWishlist(cfg.productId, btnWishlist);
            } else {
                fetch('assets/php/wishlist_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `product_id=${cfg.productId}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        btnWishlist.classList.toggle('wishlisted');
                        const icon = btnWishlist.querySelector('i');
                        if (icon) {
                            icon.classList.toggle('far');
                            icon.classList.toggle('fas');
                        }
                    }
                });
            }
        });
    }

    /* ══ WRITE REVIEW STARS ══ */
    const wrStars     = document.querySelectorAll('#wrStars i');
    const wrRatingInp = document.getElementById('wrRatingInput');

    wrStars.forEach((star, idx) => {
        star.addEventListener('mouseenter', () => {
            wrStars.forEach((s, i) => s.classList.toggle('hover', i <= idx));
        });
        star.addEventListener('mouseleave', () => {
            wrStars.forEach(s => s.classList.remove('hover'));
        });
        star.addEventListener('click', () => {
            const val = parseInt(star.dataset.val);
            if (wrRatingInp) wrRatingInp.value = val;
            wrStars.forEach((s, i) => {
                s.classList.remove('far', 'fas', 'active');
                s.classList.add(i < val ? 'fas' : 'far');
                if (i < val) s.classList.add('active');
            });
        });
    });

    /* ══ SUBMIT REVIEW ══ */
    const writeReviewForm = document.getElementById('writeReviewForm');
    if (writeReviewForm) {
        writeReviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const rating = parseInt(wrRatingInp?.value) || 0;
            if (rating === 0) { showToast('Please select a rating', 'error'); return; }

            const formData = new FormData(writeReviewForm);
            formData.append('action', 'submit');

            fetch('assets/php/review_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Review submitted! Thank you.', 'success');
                    writeReviewForm.reset();
                    wrStars.forEach(s => { s.classList.remove('fas','active'); s.classList.add('far'); });
                    if (wrRatingInp) wrRatingInp.value = 0;
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(data.message || 'Could not submit review', 'error');
                }
            });
        });
    }

    /* ══ TOAST HELPER ══ */
    function showToast(msg, type = 'success') {
        // Use your existing toast/notification system if available
        if (typeof showNotification === 'function') {
            showNotification(msg, type);
            return;
        }
        const t = document.createElement('div');
        t.textContent = msg;
        t.style.cssText = `
            position:fixed;bottom:24px;right:24px;z-index:9999;
            background:${type==='success'?'#1A1A2E':'#C62828'};
            color:#fff;padding:12px 20px;border-radius:8px;
            font-family:Poppins,sans-serif;font-size:14px;font-weight:500;
            box-shadow:0 4px 16px rgba(0,0,0,.18);
            animation:fadeInUp .3s ease;
        `;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3000);
    }

    /* ══ IMAGE FADE TRANSITION ══ */
    if (mainImg) mainImg.style.transition = 'opacity .15s ease';
});