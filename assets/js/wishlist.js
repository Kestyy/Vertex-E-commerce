/**
 * Universal Wishlist Management
 * Works across all pages with consistent functionality
 */

// ── Cooldown timer for preventing spam clicks ──
const wishlistCooldown = new Map();
const COOLDOWN_MS = 2000; // 2000ms cooldown between clicks

// ── Initialize wishlist on page load ──
document.addEventListener('DOMContentLoaded', () => {
  try {
    const userId = window.SHOP_CONFIG?.userId ?? (document.querySelector('[data-user-id]')?.dataset.userId ?? '0');
    
    // If logged in, load wishlist from database
    if (userId !== '0') {
      fetch('assets/php/wishlist.php?action=get', {
        method: 'GET',
        headers: {'Content-Type': 'application/json'}
      })
      .then(r => r.json())
      .then(data => {
        if (data.success && data.items) {
          // Store in localStorage for quick access
          const wl = data.items.map(item => ({id: item.id}));
          localStorage.setItem('wishlist', JSON.stringify(wl));
          console.log(`[Wishlist] Loaded ${wl.length} items from database`);
        }
      })
      .catch(e => console.error('[Wishlist] Failed to load from database:', e));
    } else {
      // If logged out, clear localStorage
      localStorage.removeItem('wishlist');
      console.log('[Wishlist] User logged out, wishlist cleared');
    }
    
    initializeWishlistButtons();
    updateWishlistButtonStates();
    console.log('[Wishlist] Initialized successfully');
  } catch (e) {
    console.error('[Wishlist] Initialization failed:', e);
  }
});

// ── Initialize all wishlist buttons on the page ──
function initializeWishlistButtons() {
  const buttons = document.querySelectorAll('.wish-btn, [data-wishlist-btn]');
  console.log(`[Wishlist] Found ${buttons.length} wishlist buttons to initialize`);
  
  buttons.forEach(btn => {
    // Ensure all buttons have product-wish class for consistent styling
    if (!btn.classList.contains('product-wish')) {
      btn.classList.add('product-wish');
    }
    btn.addEventListener('click', handleWishlistClick);
  });
}

// ── Handle wishlist button click ──
function handleWishlistClick(e) {
  e.preventDefault();
  e.stopPropagation();
  
  const productId = this.dataset.productId || this.closest('[data-product-id]')?.dataset.productId;
  const productName = this.dataset.productName || 'Product';
  
  console.log(`[Wishlist] Button clicked - ID: ${productId}, Name: ${productName}`);
  
  if (!productId) {
    console.warn('[Wishlist] No product ID found on wishlist button');
    return;
  }
  
  toggleWishlist({
    id: Number(productId),
    name: productName
  });
}

// ── Toggle wishlist (add/remove) ──
function toggleWishlist(product) {
  if (!product || !product.id) {
    console.warn('[Wishlist] Invalid product object:', product);
    return;
  }
  
  // Check cooldown to prevent spam
  const now = Date.now();
  if (wishlistCooldown.has(product.id) && now - wishlistCooldown.get(product.id) < COOLDOWN_MS) {
    console.log('[Wishlist] Click too frequent, ignoring');
    return;
  }
  wishlistCooldown.set(product.id, now);
  
  // Check if user is logged in
  const userId = window.SHOP_CONFIG?.userId ?? (document.querySelector('[data-user-id]')?.dataset.userId ?? '0');
  
  if (userId === '0') {
    console.log('[Wishlist] User not logged in, redirecting to login');
    window.location.href = 'auth/login.php';
    return;
  }
  
  // Find the button for this product
  const btn = document.querySelector(`.wish-btn[data-product-id="${product.id}"], [data-wishlist-btn][data-product-id="${product.id}"]`);
  if (!btn) {
    console.warn(`[Wishlist] No button found for product ID ${product.id}`);
    return;
  }
  
  if (btn.dataset.busy === 'true') {
    console.log('[Wishlist] Button is busy, ignoring click');
    return;
  }
  
  btn.dataset.busy = 'true';
  
  // Get wishlist from localStorage (for UI state only)
  let wl = JSON.parse(localStorage.getItem('wishlist') || '[]');
  const idx = wl.findIndex(i => i.id == product.id);
  const isAdding = idx < 0;
  
  console.log(`[Wishlist] ${isAdding ? 'Adding' : 'Removing'} product ID ${product.id}`);
  
  // Update UI optimistically
  if (isAdding) {
    btn.classList.add('active');
    btn.innerHTML = '<i class="fas fa-heart"></i>';
  } else {
    btn.classList.remove('active');
    btn.innerHTML = '<i class="far fa-heart"></i>';
  }
  
  // Sync with backend (user is logged in)
  fetch('assets/php/wishlist.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      action: isAdding ? 'add' : 'remove',
      product_id: product.id
    })
  })
  .then(r => r.json())
  .then(data => {
    console.log('[Wishlist] Backend sync response:', data);
    if (data.success) {
      // Update localStorage only after successful backend sync
      if (isAdding) {
        wl.push(product);
      } else {
        wl.splice(idx, 1);
      }
      localStorage.setItem('wishlist', JSON.stringify(wl));
      // showWishlistToast(product.name, isAdding);
    } else {
      // Revert UI on error
      if (isAdding) {
        btn.classList.remove('active');
        btn.innerHTML = '<i class="far fa-heart"></i>';
      } else {
        btn.classList.add('active');
        btn.innerHTML = '<i class="fas fa-heart"></i>';
      }
      // showWishlistToast('Failed to update wishlist', false);
    }
  })
  .catch(e => {
    console.error('[Wishlist] Backend sync error:', e);
    // Revert UI on error
    if (isAdding) {
      btn.classList.remove('active');
      btn.innerHTML = '<i class="far fa-heart"></i>';
    } else {
      btn.classList.add('active');
      btn.innerHTML = '<i class="fas fa-heart"></i>';
    }
    // showWishlistToast('Failed to update wishlist', false);
  });
  
  btn.dataset.busy = 'false';
}

// ── Update button states on page load ──
function updateWishlistButtonStates() {
  const userId = window.SHOP_CONFIG?.userId ?? (document.querySelector('[data-user-id]')?.dataset.userId ?? '0');
  const buttons = document.querySelectorAll('.wish-btn, [data-wishlist-btn]');
  
  buttons.forEach(btn => {
    const productId = btn.dataset.productId || btn.closest('[data-product-id]')?.dataset.productId;
    
    // If not logged in, always show empty heart
    if (userId === '0') {
      btn.classList.remove('active');
      btn.innerHTML = '<i class="far fa-heart"></i>';
      return;
    }
    
    // If logged in, check against localStorage
    const wl = JSON.parse(localStorage.getItem('wishlist') || '[]');
    const isInWishlist = wl.some(i => i.id == productId);
    
    if (isInWishlist) {
      btn.classList.add('active');
      btn.innerHTML = '<i class="fas fa-heart"></i>';
    } else {
      btn.classList.remove('active');
      btn.innerHTML = '<i class="far fa-heart"></i>';
    }
  });
}

// ── Check if product is in wishlist ──
function isInWishlist(productId) {
  const wl = JSON.parse(localStorage.getItem('wishlist') || '[]');
  return wl.some(i => i.id == productId);
}

// ── Get wishlist count ──
function getWishlistCount() {
  const wl = JSON.parse(localStorage.getItem('wishlist') || '[]');
  return wl.length;
}

// ── Show toast notification ── (DISABLED: Wishlist notifications removed)
function showWishlistToast(productName, isAdding) {
  // Wishlist notifications disabled per user request
}

// ── Add animations ──
if (!document.querySelector('style[data-wishlist-animations]')) {
  const style = document.createElement('style');
  style.setAttribute('data-wishlist-animations', 'true');
  style.textContent = `
    @keyframes slideIn {
      from { transform: translateX(-50%) translateY(+100px); opacity: 0; }
      to { transform: translateX(-50%) translateY(0); opacity: 1; }
    }
    @keyframes slideOut {
      from { transform: translateX(-50%) translateY(0); opacity: 1; }
      to { transform: translateX(-50%) translateY(+100px); opacity: 0; }
    }
    .wish-btn, [data-wishlist-btn] {
      transition: all 0.3s ease;
    }
    .wish-btn.active, [data-wishlist-btn].active {
      color: #ef4444;
    }
  `;
  document.head.appendChild(style);
}

// ── Make functions globally available ──
window.toggleWishlist = toggleWishlist;
window.isInWishlist = isInWishlist;
window.getWishlistCount = getWishlistCount;
window.initializeWishlistButtons = initializeWishlistButtons;
window.updateWishlistButtonStates = updateWishlistButtonStates;
