<!-- ═══════════════════════════════════════
     CART MODAL — include this on every page that uses cart.js
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