// ═══════════════════════════════════════
// SHARED NAV BADGE HELPER
// navbar.php uses id="cartCount" — that's what we target here
// ═══════════════════════════════════════
function setNavCartCount(n) {
    const navCount = document.getElementById('cartCount');
    if (!navCount) return;
    navCount.textContent = n > 99 ? '99+' : n;
}

// ═══════════════════════════════════════
// SYNC CART BADGE FROM SERVER
// ═══════════════════════════════════════
function syncCartCount() {
    fetch('assets/php/cart_api.php?action=count', { method: 'GET' })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.count !== undefined) setNavCartCount(data.count);
        })
        .catch(() => {});
}

// ═══════════════════════════════════════
// ADD TO CART MODAL
// ═══════════════════════════════════════
function initCartModal() {
    if (window.__cartModalInitialised) return;
    window.__cartModalInitialised = true;

    const overlay     = document.getElementById('cartModal');
    const closeBtn    = document.getElementById('modalCloseBtn');
    const continueBtn = document.getElementById('modalContinueBtn');

    let cartTotal = 0;

    const elName        = document.getElementById('modalProductName');
    const elImg         = document.getElementById('modalProductImg');
    const elEmoji       = document.getElementById('modalProductEmoji');
    const elQty         = document.getElementById('modalQty');
    const elCartTotal   = document.getElementById('modalCartTotal');
    const elItemCount   = document.getElementById('modalItemCount');
    const elTotalAmount = document.getElementById('modalTotalAmount');

    if (!overlay) return;

    function parsePrice(str) {
        return parseFloat((str || '0').replace(/[^0-9.]/g, '')) || 0;
    }

    function getCurrencySymbol(priceStr) {
        if ((priceStr || '').includes('₱')) return '₱';
        return '$';
    }

    function formatPrice(num, symbol) {
        return symbol + num.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function setModalContent(name, priceStr, imgSrc, symbol, count) {
        if (elName)        elName.textContent       = name;
        if (elQty)         elQty.textContent         = '1';
        if (elCartTotal)   elCartTotal.textContent   = formatPrice(cartTotal, symbol);
        if (elTotalAmount) elTotalAmount.textContent = formatPrice(cartTotal, symbol);
        if (elItemCount)   elItemCount.textContent   = count + (count === 1 ? ' item' : ' items');

        if (imgSrc && elImg && elEmoji) {
            elImg.src             = imgSrc;
            elImg.alt             = name;
            elImg.style.display   = 'block';
            elEmoji.style.display = 'none';
        } else if (elImg && elEmoji) {
            elImg.style.display   = 'none';
            elEmoji.style.display = 'block';
        }
    }

    let _processingBtn = null;

    function cartModalOpen(btn) {
        if (_processingBtn === btn) return;
        _processingBtn = btn;
        setTimeout(() => { _processingBtn = null; }, 0);

        const name      = btn.dataset.name  || 'Product';
        const priceStr  = btn.dataset.price || '₱0.00';
        const imgSrc    = btn.dataset.img   || '';
        const symbol    = getCurrencySymbol(priceStr);
        const price     = parsePrice(priceStr);
        const productId = btn.dataset.productId;

        // ✅ Check if user is logged in before adding to cart
        if (productId && !window.isUserLoggedIn) {
            window.location.href = window.loginRedirectUrl;
            return;
        }

        // ✅ Add to cart via API
        if (productId) {
            fetch('assets/php/cart_api.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: parseInt(productId), quantity: 1 })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Could not add item to cart.');
                    return;
                }

                cartTotal += price;
                setNavCartCount(data.count || 1);
                setModalContent(name, priceStr, imgSrc, symbol, data.count || 1);

                btn.classList.add('pop');
                setTimeout(() => btn.classList.remove('pop'), 400);

                overlay.classList.add('open');
                document.body.style.overflow = 'hidden';
            })
            .catch((err) => {
                console.error('Cart API error:', err);
                // Fallback: open modal anyway
                cartTotal += price;
                setNavCartCount(1);
                setModalContent(name, priceStr, imgSrc, symbol, 1);
                overlay.classList.add('open');
                document.body.style.overflow = 'hidden';
            });
            return;
        }

        // Fallback for guest users (no productId)
        const navCount = document.getElementById('cartCount');
        let count = parseInt(navCount?.textContent?.replace('+', '')) || 0;
        count++;
        cartTotal += price;

        setNavCartCount(count);
        setModalContent(name, priceStr, imgSrc, symbol, count);

        btn.classList.add('pop');
        setTimeout(() => btn.classList.remove('pop'), 400);

        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function cartModalClose() {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    if (closeBtn)    closeBtn.addEventListener('click', cartModalClose);
    if (continueBtn) continueBtn.addEventListener('click', cartModalClose);

    overlay.addEventListener('click', function (e) {
        if (e.target === this) cartModalClose();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('open')) {
            cartModalClose();
        }
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-cart-icon');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        cartModalOpen(btn);
    });
}

// ═══════════════════════════════════════
// CART BUTTON (single product page)
// ═══════════════════════════════════════
function cartButton() {
    const addToCartBtn  = document.getElementById('addToCart');
    const quantityInput = document.getElementById('quantity');
    if (!addToCartBtn || !quantityInput) return;

    if (addToCartBtn.dataset.bound) return;
    addToCartBtn.dataset.bound = '1';

    addToCartBtn.addEventListener('click', function () {
        const quantity  = parseInt(quantityInput.value) || 1;
        const productId = addToCartBtn.dataset.productId;

        // ✅ Check if user is logged in before adding to cart
        if (productId && !window.isUserLoggedIn) {
            window.location.href = window.loginRedirectUrl;
            return;
        }

        if (productId) {
            fetch('assets/php/cart_api.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: parseInt(productId), quantity: quantity })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setNavCartCount(data.count || 1);
                } else {
                    alert(data.message || 'Could not add item to cart.');
                }
            })
            .catch(() => alert('Please login to add items to cart.'));
            return;
        }

        const navCount = document.getElementById('cartCount');
        let current = parseInt(navCount?.textContent?.replace('+', '')) || 0;
        setNavCartCount(current + quantity);
    });
}

// ═══════════════════════════════════════
// CART PAGE — CONSTANTS
// ═══════════════════════════════════════
const SHIPPING_FEE         = 79;
const FREE_SHIPPING_THRESH = 249;

// ═══════════════════════════════════════
// CART PAGE — FORMAT HELPER
// ═══════════════════════════════════════
function formatPHP(n) {
    return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ═══════════════════════════════════════
// CART PAGE — SUMMARY
// ═══════════════════════════════════════
function updateSummary() {
    const selectAll       = document.getElementById('selectAll');
    const checkboxes      = document.querySelectorAll('.cart-checkbox');
    const summaryContent  = document.getElementById('summaryContent');
    const summaryTotal    = document.getElementById('summaryTotal');
    const summaryTotalVal = document.getElementById('summaryTotalVal');
    const checkoutBtn     = document.getElementById('checkoutBtn');

    if (!summaryContent) return;

    const checked = document.querySelectorAll('.cart-checkbox:checked');
    let subtotal  = 0;
    let itemCount = 0;

    document.querySelectorAll('.cart-row').forEach(row => {
        const cb = row.querySelector('.cart-checkbox');
        if (cb && cb.checked) {
            const qty   = parseInt(row.dataset.quantity) || 1;
            const price = parseFloat(row.dataset.price)  || 0;
            subtotal  += price * qty;
            itemCount += qty;
        }
    });

    if (checked.length === 0) {
        summaryContent.innerHTML = '<p class="s-empty">Select items to see total</p>';
        if (summaryTotal)  summaryTotal.style.display = 'none';
        if (checkoutBtn)   checkoutBtn.disabled = true;
        if (selectAll)     selectAll.checked = false;
        return;
    }

    // Shipping logic
    const isFreeShipping = subtotal >= FREE_SHIPPING_THRESH;
    const shipping       = isFreeShipping ? 0 : SHIPPING_FEE;

    // Coupon discount
    const discount = window.appliedDiscount || 0;
    const total    = Math.max(0, subtotal + shipping - discount);

    // Shipping row
    let shippingHtml;
    if (isFreeShipping) {
        shippingHtml = `<span class="s-free">FREE</span>`;
    } else {
        shippingHtml = `<span class="s-val">${formatPHP(shipping)}</span>`;
    }

    // Coupon row (only if applied)
    const discountRow = discount > 0 ? `
        <div class="s-row">
            <span class="s-lbl">Coupon Discount</span>
            <span class="s-discount">-${formatPHP(discount)}</span>
        </div>` : '';

    summaryContent.innerHTML = `
        <div class="s-row">
            <span class="s-lbl">Items</span>
            <span class="s-val">${itemCount}</span>
        </div>
        <div class="s-row">
            <span class="s-lbl">Sub Total</span>
            <span class="s-val">${formatPHP(subtotal)}</span>
        </div>
        <div class="s-row">
            <span class="s-lbl">Shipping</span>
            ${shippingHtml}
        </div>
        ${discountRow}`;

    if (summaryTotalVal) summaryTotalVal.textContent = formatPHP(total);
    if (summaryTotal)    summaryTotal.style.display  = 'flex';
    if (checkoutBtn)     checkoutBtn.disabled        = false;

    if (selectAll) {
        selectAll.checked = checked.length === checkboxes.length && checkboxes.length > 0;
    }
}

// ═══════════════════════════════════════
// CART PAGE — QUANTITY UPDATE (no reload)
// ═══════════════════════════════════════
function updateQuantity(productId, quantity) {
    if (quantity < 1) return;
    fetch('assets/php/cart_api.php?action=update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: parseInt(productId), quantity: parseInt(quantity) })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Update failed.');
            return;
        }

        if (data.count !== undefined) setNavCartCount(data.count);

        const row = document.querySelector(`.cart-row[data-product-id="${productId}"]`);
        if (!row) return;

        row.dataset.quantity = quantity;

        const input = row.querySelector('.qty-input');
        if (input) input.value = quantity;

        const price    = parseFloat(row.dataset.price);
        const subtotal = price * parseInt(quantity);
        const subEl    = row.querySelector('.cart-subtotal');
        if (subEl) subEl.textContent = formatPHP(subtotal);

        const [minusBtn, plusBtn] = row.querySelectorAll('.qty-btn');
        if (minusBtn) minusBtn.setAttribute('onclick', `updateQuantity(${productId}, ${quantity - 1})`);
        if (plusBtn)  plusBtn.setAttribute('onclick',  `updateQuantity(${productId}, ${quantity + 1})`);

        updateSummary();
    })
    .catch(err => console.error('Quantity update failed:', err));
}

// ═══════════════════════════════════════
// CART PAGE — REMOVE ITEM (no reload)
// ═══════════════════════════════════════
let _pendingRemoveId = null, _pendingRemoveRow = null;

function removeItem(productId) {
    _pendingRemoveId  = productId;
    _pendingRemoveRow = document.querySelector(`.cart-row[data-product-id="${productId}"]`);
    const name = _pendingRemoveRow?.querySelector('.cart-name')?.textContent || 'This item';
    document.getElementById('modalItemName').innerHTML = `${name} will be removed from your cart.`;
    document.getElementById('removeModal').classList.add('open');
}

function closeRemoveModal() {
    document.getElementById('removeModal').classList.remove('open');
    _pendingRemoveId  = null;
    _pendingRemoveRow = null;
}

function confirmRemove() {
    if (!_pendingRemoveId) return;

    const rowToRemove = _pendingRemoveRow;
    const idToRemove  = _pendingRemoveId;
    closeRemoveModal();

    fetch('assets/php/cart_api.php?action=remove', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: parseInt(idToRemove) })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;

        if (data.count !== undefined) setNavCartCount(data.count);

        if (rowToRemove) {
            rowToRemove.style.transition = 'opacity 0.2s, transform 0.2s';
            rowToRemove.style.opacity    = '0';
            rowToRemove.style.transform  = 'translateX(10px)';
            setTimeout(() => {
                rowToRemove.remove();
                updateSummary();

                if (!document.querySelector('.cart-row')) {
                    const container = document.querySelector('.cart-items') || document.querySelector('.cart-container');
                    if (container) {
                        container.innerHTML = '<p style="text-align:center;padding:2rem;color:#64748b;">Your cart is empty.</p>';
                    }
                }
            }, 220);
        }
    })
    .catch(err => console.error('Remove failed:', err));
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeRemoveModal();
});

// ═══════════════════════════════════════
// CART PAGE — CHECKOUT
// ── FIX: also pass coupon code to checkout.php
// ═══════════════════════════════════════
function proceedToCheckout() {
    // ✅ Check if user is logged in before proceeding to checkout
    if (!window.isUserLoggedIn) {
        window.location.href = window.loginRedirectUrl;
        return;
    }
    
    const ids    = [...document.querySelectorAll('.cart-checkbox:checked')].map(cb => cb.dataset.id);
    const coupon = (document.getElementById('couponInput')?.value || '').trim().toUpperCase();
    let url      = 'checkout.php?items=' + ids.join(',');
    if (coupon && window.appliedDiscount > 0) {
        url += '&coupon=' + encodeURIComponent(coupon);
    }
    window.location.href = url;
}

// ═══════════════════════════════════════
// CART PAGE — COUPON
// ═══════════════════════════════════════
const COUPONS = {
    'VERTEX20': { type: 'percent', value: 20 },
    'SAVE10':   { type: 'percent', value: 10 },
    'FLAT50':   { type: 'fixed',   value: 50  }
};

function applyCoupon() {
    const input = document.getElementById('couponInput');
    const msg   = document.getElementById('couponMsg');
    const code  = input.value.trim().toUpperCase();

    if (!code) {
        msg.style.display = 'block';
        msg.style.color   = 'var(--danger)';
        msg.textContent   = 'Please enter a coupon code.';
        return;
    }

    const coupon = COUPONS[code];
    if (!coupon) {
        msg.style.display = 'block';
        msg.style.color   = 'var(--danger)';
        msg.textContent   = 'Invalid coupon code. Please try again.';
        window.appliedDiscount = 0;
        updateSummary();
        return;
    }

    // Calculate subtotal of selected items
    let subtotal = 0;
    document.querySelectorAll('.cart-row').forEach(row => {
        const cb = row.querySelector('.cart-checkbox');
        if (cb && cb.checked) {
            subtotal += parseFloat(row.dataset.price) * parseInt(row.dataset.quantity);
        }
    });

    if (coupon.type === 'percent') {
        window.appliedDiscount = subtotal * (coupon.value / 100);
        msg.textContent = `Coupon applied! ${coupon.value}% off your order.`;
    } else {
        window.appliedDiscount = coupon.value;
        msg.textContent = `Coupon applied! ₱${coupon.value}.00 off your order.`;
    }

    msg.style.display = 'block';
    msg.style.color   = '#16a34a';
    input.disabled    = true;

    updateSummary();
}

// ═══════════════════════════════════════
// CART CHECKBOX STATE (persist across reloads)
// ═══════════════════════════════════════
function saveCartCheckboxState() {
    const checkboxes = document.querySelectorAll('.cart-checkbox');
    const selectAll  = document.getElementById('selectAll');
    const checked    = [...checkboxes].filter(cb => cb.checked).map(cb => cb.dataset.id);
    sessionStorage.setItem('cartChecked', JSON.stringify(checked));
    sessionStorage.setItem('cartSelectAll', selectAll ? selectAll.checked : false);
}

function restoreCartCheckboxState() {
    const saved          = sessionStorage.getItem('cartChecked');
    const savedSelectAll = sessionStorage.getItem('cartSelectAll');
    if (!saved) return;

    const ids        = JSON.parse(saved);
    const checkboxes = document.querySelectorAll('.cart-checkbox');
    const selectAll  = document.getElementById('selectAll');

    checkboxes.forEach(cb => {
        if (ids.includes(cb.dataset.id)) cb.checked = true;
    });

    if (selectAll && savedSelectAll === 'true') selectAll.checked = true;

    sessionStorage.removeItem('cartChecked');
    sessionStorage.removeItem('cartSelectAll');

    if (typeof updateSummary === 'function') updateSummary();
}

// ═══════════════════════════════════════
// INIT
// ═══════════════════════════════════════
document.addEventListener('DOMContentLoaded', function () {
    syncCartCount();
    window.addEventListener('focus', syncCartCount);

    initCartModal();
    cartButton();

    const checkboxes = document.querySelectorAll('.cart-checkbox');
    const selectAll  = document.getElementById('selectAll');

    checkboxes.forEach(cb => cb.addEventListener('change', updateSummary));

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateSummary();
        });
    }

    const modal = document.getElementById('removeModal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) closeRemoveModal();
        });
    }

    restoreCartCheckboxState();
});