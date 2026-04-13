(() => {
'use strict';

const DEBOUNCE_MS = 380;
let debounceTimer = null, abortCtrl = null;

const filterState = {
    q:         window.SHOP_CONFIG?.q         ?? '',
    category:  window.SHOP_CONFIG?.category  ?? '',
    min_price: window.SHOP_CONFIG?.min_price ?? '',
    max_price: window.SHOP_CONFIG?.max_price ?? '',
    rating:    window.SHOP_CONFIG?.rating    ?? '',
    arrival:   window.SHOP_CONFIG?.arrival   ?? '',
    seller:    window.SHOP_CONFIG?.seller    ?? '',
    discount:  window.SHOP_CONFIG?.discount  ?? '',
    stock:     window.SHOP_CONFIG?.stock     ?? '',
    sort:      window.SHOP_CONFIG?.sort      ?? 'newest',
    page: 1,
};
const MAX_PRICE = window.SHOP_CONFIG?.maxPrice ?? 10000;

/* ── Comma helpers ── */
function formatComma(val) {
    const n = parseInt(String(val).replace(/,/g, '')) || 0;
    return n.toLocaleString('en-PH');
}
function parseComma(val) {
    return parseInt(String(val).replace(/,/g, '')) || 0;
}

/* ── Build query string ── */
function buildQS(extra = {}) {
    const s = Object.assign({}, filterState, extra), p = new URLSearchParams();
    for (const [k, v] of Object.entries(s)) {
        if (v !== '' && v !== null && v !== undefined) p.set(k, v);
    }
    return p.toString();
}

/* ── Fetch products (debounced) ── */
function fetchProducts(immediate = false) {
    clearTimeout(debounceTimer);
    if (!immediate) {
        debounceTimer = setTimeout(() => fetchProducts(true), DEBOUNCE_MS);
        return;
    }
    if (abortCtrl) abortCtrl.abort();
    abortCtrl = new AbortController();

    const grid = document.getElementById('productsGrid');
    grid.classList.add('is-loading');

    const qs = buildQS();
    history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));

    fetch('assets/php/shop_products.php?' + qs, { signal: abortCtrl.signal })
        .then(r => r.text())
        .then(html => {
            const tmp = document.createElement('div');
            tmp.innerHTML = html;

            const ms = tmp.querySelector('script');
            if (ms) { (0, eval)(ms.textContent); ms.remove(); }

            const pEl = tmp.querySelector('#ajaxPagination');
            if (pEl) pEl.remove();

            grid.innerHTML = tmp.innerHTML;
            grid.classList.remove('is-loading');

            const paginationWrap = document.getElementById('paginationWrap');
            paginationWrap.innerHTML = pEl ? pEl.outerHTML : '';

            paginationWrap.querySelectorAll('a[data-page]').forEach(a => {
                a.addEventListener('click', e => {
                    e.preventDefault();
                    filterState.page = parseInt(a.dataset.page) || 1;
                    fetchProducts(true);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });

            initWishlistIcons();
            
            // Re-initialize wishlist buttons for newly loaded products
            if (window.initializeWishlistButtons) {
                window.initializeWishlistButtons();
            }

            if (window._shopMeta) {
                const { total, offset, perPage } = window._shopMeta;
                document.getElementById('rFrom').textContent  = total > 0 ? offset + 1 : 0;
                document.getElementById('rTo').textContent    = Math.min(offset + perPage, total);
                document.getElementById('rTotal').textContent = total;
            }

            renderFilterChips();
        })
        .catch(err => {
            if (err.name !== 'AbortError') {
                grid.classList.remove('is-loading');
                console.error(err);
            }
        });
}

/* ── Filter chips ── */
const arrivalLabels  = { last_7_days: 'New Arrivals', last_30_days: 'Last 30 Days', this_month: 'This Month' };
const sellerLabels   = { top_all_time: 'Best Sellers', most_purchased_week: 'Most Purchased This Week', trending: 'Trending Now' };
const discountLabels = { '10': 'On Sale', '25': '25%+ Discount', '50': '50%+ Discount', clearance: 'Clearance Sale' };

function renderFilterChips() {
    const bar = document.getElementById('activeFilterBar');
    const chips = [];

    if (filterState.q) chips.push([`Search: "${filterState.q}"`, () => { filterState.q = ''; fetchProducts(true); }]);

    if (filterState.category) {
        const cats = filterState.category.split(',').filter(Boolean);
        cats.forEach(cat => {
            chips.push([cat, () => {
                const remaining = filterState.category.split(',').filter(c => c !== cat).join(',');
                filterState.category = remaining;
                document.querySelectorAll('input[name="fcat"]').forEach(cb => {
                    if (cb.value === cat) cb.checked = false;
                });
                fetchProducts(true);
            }]);
        });
    }

    if (filterState.min_price || filterState.max_price)
        chips.push([`₱${formatComma(filterState.min_price || 0)} – ₱${formatComma(filterState.max_price || MAX_PRICE)}`,
            () => { filterState.min_price = ''; filterState.max_price = ''; resetPriceUI(); fetchProducts(true); }]);
    if (filterState.rating)   chips.push([filterState.rating + '★ & up', () => { filterState.rating = ''; document.querySelectorAll('[name="frating"]').forEach(c => c.checked = false); fetchProducts(true); }]);
    if (filterState.arrival)  chips.push([arrivalLabels[filterState.arrival]  || filterState.arrival,  () => { filterState.arrival  = ''; const el = document.getElementById('fcheck-arrival');  if (el) el.checked = false; fetchProducts(true); }]);
    if (filterState.seller)   chips.push([sellerLabels[filterState.seller]    || filterState.seller,   () => { filterState.seller   = ''; const el = document.getElementById('fcheck-seller');   if (el) el.checked = false; fetchProducts(true); }]);
    if (filterState.discount) chips.push([discountLabels[filterState.discount] || filterState.discount, () => { filterState.discount = ''; const el = document.getElementById('fcheck-discount'); if (el) el.checked = false; fetchProducts(true); }]);

    if (!chips.length) { bar.style.display = 'none'; return; }

    bar.style.display = 'flex';
    bar.innerHTML =
        '<span class="active-filter-label">Active Filters</span>' +
        chips.map(([l], i) =>
            `<span class="filter-chip">${l}<button class="filter-chip-x" data-chip="${i}" aria-label="Remove filter">×</button></span>`
        ).join('') +
        '<button class="filter-clear-all" id="btnClearAll">Clear All</button>';

    bar.querySelectorAll('.filter-chip-x').forEach(b =>
        b.addEventListener('click', () => { filterState.page = 1; chips[+b.dataset.chip][1](); })
    );
    document.getElementById('btnClearAll').addEventListener('click', clearAllFilters);
}

function clearAllFilters() {
    filterState.q = filterState.category = filterState.min_price = filterState.max_price =
    filterState.rating = filterState.arrival = filterState.seller = filterState.discount = filterState.stock = '';
    filterState.page = 1;
    filterState.sort = 'newest';
    document.querySelectorAll('input[name="fcat"]').forEach(c => c.checked = false);
    document.querySelectorAll('[name="frating"]').forEach(c => c.checked = false);
    ['fcheck-arrival', 'fcheck-seller', 'fcheck-discount', 'fcheck-instock', 'fcheck-outstock'].forEach(id => {
        const el = document.getElementById(id); if (el) el.checked = false;
    });
    document.getElementById('sortSelect').value = 'newest';
    resetPriceUI();
    fetchProducts(true);
}

/* ── Price slider ── */
function updateRangeFill() {
    const fill = document.getElementById('rangeFill');
    const rMin = document.getElementById('rangeMin');
    const rMax = document.getElementById('rangeMax');
    if (!fill || !rMin || !rMax) return;
    const lo = parseInt(rMin.value), hi = parseInt(rMax.value);
    fill.style.left  = (lo / MAX_PRICE * 100) + '%';
    fill.style.width = ((hi - lo) / MAX_PRICE * 100) + '%';
}

function resetPriceUI() {
    const rMin = document.getElementById('rangeMin');
    const rMax = document.getElementById('rangeMax');
    const pMin = document.getElementById('priceMin');
    const pMax = document.getElementById('priceMax');
    if (rMin) { rMin.value = 0;         if (pMin) pMin.value = '0'; }
    if (rMax) { rMax.value = MAX_PRICE; if (pMax) pMax.value = formatComma(MAX_PRICE); }
    updateRangeFill();
}

/* ── Bind comma-formatted text input ── */
function bindPriceTextInput(inputEl, isMin) {
    if (!inputEl) return;

    /* Only allow digit keys while typing */
    inputEl.addEventListener('keydown', e => {
        const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
        if (!allowed.includes(e.key) && !/^\d$/.test(e.key)) e.preventDefault();
    });

    /* Auto-format with commas on every keystroke */
    inputEl.addEventListener('input', () => {
        const raw    = inputEl.value.replace(/\D/g, '');
        const cursor = inputEl.selectionStart;
        const prevLen = inputEl.value.length;
        inputEl.value = raw ? parseInt(raw).toLocaleString('en-PH') : '';
        const diff = inputEl.value.length - prevLen;
        try { inputEl.setSelectionRange(cursor + diff, cursor + diff); } catch(e) {}
    });

    /* On blur: clamp value, sync slider, trigger fetch */
    inputEl.addEventListener('change', () => {
        const rMin = document.getElementById('rangeMin');
        const rMax = document.getElementById('rangeMax');
        if (!rMin || !rMax) return;

        let val = parseComma(inputEl.value);

        if (isMin) {
            const hi = parseInt(rMax.value);
            val = Math.max(0, Math.min(val, hi - 10));
            rMin.value = val;
            inputEl.value = formatComma(val);
            filterState.min_price = val > 0 ? String(val) : '';
        } else {
            const lo = parseInt(rMin.value);
            val = Math.min(MAX_PRICE, Math.max(val, lo + 10));
            rMax.value = val;
            inputEl.value = formatComma(val);
            filterState.max_price = val < MAX_PRICE ? String(val) : '';
        }

        updateRangeFill();
        filterState.page = 1;
        fetchProducts();
    });
}

/* ── Wishlist icons ── */
function initWishlistIcons() {
    const wl = JSON.parse(localStorage.getItem('wishlist') || '[]');
    document.querySelectorAll('.wish-btn').forEach(btn => {
        const active = wl.some(i => i.id == btn.dataset.productId);
        btn.classList.toggle('active', active);
        btn.innerHTML = active ? '<i class="fas fa-heart"></i>' : '<i class="far fa-heart"></i>';
    });
}

/* ── DOMContentLoaded ── */
document.addEventListener('DOMContentLoaded', () => {
    fetchProducts(true);

    /* Sort */
    document.getElementById('sortSelect').addEventListener('change', function () {
        filterState.sort = this.value; filterState.page = 1; fetchProducts(true);
    });

    /* Category — single-checkbox (mutually exclusive) */
    document.querySelectorAll('input[name="fcat"]').forEach(cb => {
        cb.addEventListener('change', () => {
            if (cb.checked) {
                // Uncheck all other category checkboxes
                document.querySelectorAll('input[name="fcat"]').forEach(c => {
                    if (c !== cb) c.checked = false;
                });
                filterState.category = cb.value;
            } else {
                filterState.category = '';
            }
            filterState.page = 1; fetchProducts(true);
        });
    });

    /* Rating */
    document.querySelectorAll('[name="frating"]').forEach(cb =>
        cb.addEventListener('change', () => {
            filterState.rating = [...document.querySelectorAll('[name="frating"]:checked')].map(c => c.value).join(',');
            filterState.page = 1; fetchProducts();
        })
    );

    /* Checkbox filters */
    const bindCheck = (id, key) => {
        const el = document.getElementById(id); if (!el) return;
        el.addEventListener('change', () => { filterState[key] = el.checked ? el.value : ''; filterState.page = 1; fetchProducts(); });
    };
    bindCheck('fcheck-arrival',  'arrival');
    bindCheck('fcheck-seller',   'seller');
    bindCheck('fcheck-discount', 'discount');

    /* Stock (mutually exclusive) */
    const inCb  = document.getElementById('fcheck-instock');
    const outCb = document.getElementById('fcheck-outstock');
    inCb?.addEventListener('change',  () => { if (inCb.checked  && outCb) outCb.checked = false; filterState.stock = inCb.checked  ? 'in'  : ''; filterState.page = 1; fetchProducts(); });
    outCb?.addEventListener('change', () => { if (outCb.checked && inCb)  inCb.checked  = false; filterState.stock = outCb.checked ? 'out' : ''; filterState.page = 1; fetchProducts(); });

    /* Price range */
    const minInput = document.getElementById('priceMin');
    const maxInput = document.getElementById('priceMax');
    const rMin     = document.getElementById('rangeMin');
    const rMax     = document.getElementById('rangeMax');

    /* Set initial formatted values */
    if (minInput) minInput.value = formatComma(minInput.value || 0);
    if (maxInput) maxInput.value = formatComma(maxInput.value || MAX_PRICE);

    /* Bind comma formatting + clamping */
    bindPriceTextInput(minInput, true);
    bindPriceTextInput(maxInput, false);

    if (rMin && rMax) {
        updateRangeFill();

        rMin.addEventListener('input', () => {
            let lo = parseInt(rMin.value), hi = parseInt(rMax.value);
            if (lo >= hi) { lo = hi - 10; rMin.value = lo; }
            if (minInput) minInput.value = formatComma(lo);
            updateRangeFill();
            filterState.min_price = lo > 0 ? String(lo) : '';
            filterState.page = 1; fetchProducts();
        });

        rMax.addEventListener('input', () => {
            let lo = parseInt(rMin.value), hi = parseInt(rMax.value);
            if (hi <= lo) { hi = lo + 10; rMax.value = hi; }
            if (maxInput) maxInput.value = formatComma(hi);
            updateRangeFill();
            filterState.max_price = hi < MAX_PRICE ? String(hi) : '';
            filterState.page = 1; fetchProducts();
        });
    }
});

/* Wishlist functionality now handled by wishlist.js */

})();