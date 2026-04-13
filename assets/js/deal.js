// ═══════════════════════════════════════
// DEAL OF THE DAY
// ═══════════════════════════════════════
document.addEventListener('DOMContentLoaded', function () {

    const products = [
        { name: "Pro Gaming Laptop",          img: "https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=500&q=80", price: 1099.99, original: 1299.99, rating: 4.5, reviews: "2.4k", stock: 8  },
        { name: "Ultrabook Pro 14",            img: "https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500&q=80", price: 1199.99, original: 1499.99, rating: 4.0, reviews: "1.1k", stock: 5  },
        { name: "Noise Cancelling Headphones", img: "https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&q=80", price: 129.99,  original: 199.99,  rating: 4.5, reviews: "876",  stock: 14 },
        { name: "4K IPS Monitor 27\"",         img: "https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=500&q=80", price: 349.99,  original: 449.99,  rating: 4.7, reviews: "918",  stock: 3  },
        { name: "Wireless Gaming Mouse",       img: "https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=500&q=80", price: 44.99,   original: 69.99,   rating: 4.2, reviews: "541",  stock: 22 },
        { name: "Portable SSD 2TB",            img: "https://images.unsplash.com/photo-1531492744918-a07c5e7e0cc5?w=500&q=80", price: 109.99,  original: 149.99,  rating: 4.8, reviews: "1.5k", stock: 11 },
        { name: "Mechanical Keyboard RGB",     img: "https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=500&q=80", price: 79.99,   original: 109.99,  rating: 5.0, reviews: "3.2k", stock: 40 },
        { name: "USB-C Docking Station",       img: "https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=500&q=80", price: 59.99,   original: 89.99,   rating: 4.3, reviews: "672",  stock: 19 },
    ];

    const VISIBLE    = 5;
    let page         = 0;
    const totalPages = Math.ceil(products.length / VISIBLE);

    const grid    = document.getElementById('deal-cards-grid');
    const prevBtn = document.getElementById('deal-prev');
    const nextBtn = document.getElementById('deal-next');

    if (!grid || !prevBtn || !nextBtn) return;

    function fmt(n) {
        return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function starsHTML(r) {
        let h = '<div class="product-rating" style="gap:2px;">';
        for (let i = 1; i <= 5; i++) {
            if (r >= i)
                h += '<i class="fas fa-star" style="color:#f59e0b;font-size:1rem;"></i>';
            else if (r >= i - 0.5)
                h += '<i class="fas fa-star-half-alt" style="color:#f59e0b;font-size:1rem;"></i>';
            else
                h += '<i class="far fa-star" style="color:#d1d5db;font-size:1rem;"></i>';
        }
        h += `<span class="rating-count">${r.toFixed(1)}</span>`;
        return h + '</div>';
    }

    function urgency(s) {
        return s <= 5 ? 'critical' : s <= 15 ? 'low' : 'ok';
    }

    function render() {
        const slice = products.slice(page * VISIBLE, page * VISIBLE + VISIBLE);
        grid.innerHTML = '';

        slice.forEach((p, i) => {
            const globalIndex = page * VISIBLE + i;
            const disc  = p.original ? Math.round((1 - p.price / p.original) * 100) : null;
            const save  = p.original ? (p.original - p.price).toFixed(2) : null;
            const u     = urgency(p.stock);
            // stock bar width: treat 50 as "full stock" baseline
            const pct   = Math.min(100, Math.round((p.stock / 50) * 100));

            const el = document.createElement('div');
            el.className = 'deal-card';
            el.innerHTML = `
                <div class="deal-card-img-wrap">
                    ${disc ? `<div class="deal-disc-pill">−${disc}%</div>` : ''}
                    <img src="${p.img}" alt="${p.name}" loading="lazy"/>
                </div>
                <div class="deal-card-body">
                    <p class="deal-card-name">${p.name}</p>
                    <div class="deal-card-rating">
                        ${starsHTML(p.rating)}
                        <span class="deal-rating-count">(${p.reviews})</span>
                    </div>
                    <div class="deal-card-pricing">
                        <span class="deal-price-now">₱${fmt(p.price)}</span>
                        ${p.original ? `<span class="deal-price-was">₱${fmt(p.original)}</span>` : ''}
                    </div>
                    <div class="deal-stock stock-${u}">
                        <p class="deal-stock-label">
                            <strong>Only ${p.stock} left!</strong>
                            ${save ? `<span class="deal-save-inline">Save ₱${save}</span>` : ''}
                        </p>
                        <div class="deal-stock-bar">
                            <div class="deal-stock-fill" style="width:${pct}%"></div>
                        </div>
                    </div>
                    <button class="btn-cart-icon add-cart" aria-label="Add to cart"
                        data-name="${p.name}"
                        data-price="₱${fmt(p.price)}"
                        data-img="${p.img}"
                        data-index="${globalIndex}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.5 5v2.5H10v2h2.5V12h2V9.5H17v-2h-2.5V5z"></path>
                            <path d="M17.31 14H9.72L5.95 2.68A1 1 0 0 0 5 2H2v2h2.28l3.54 10.63A2 2 0 0 0 9.72 16h7.59a2 2 0 0 0 1.87-1.3l2.76-7.35-1.87-.7zM10 18a2 2 0 1 0 0 4 2 2 0 1 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 1 0 0-4"></path>
                        </svg>
                        Add to Cart
                    </button>
                </div>
            `;
            grid.appendChild(el);
        });

        prevBtn.disabled = page === 0;
        nextBtn.disabled = page + 1 >= totalPages;
    }

    prevBtn.addEventListener('click', () => { page--; render(); });
    nextBtn.addEventListener('click', () => { page++; render(); });
    render();

    // ── Deal countdown ───────────────────────────────────────
    const target = new Date();
    target.setHours(target.getHours() + 8);
    target.setMinutes(target.getMinutes() + 32);
    target.setSeconds(target.getSeconds() + 29);

    const tH = document.getElementById('t-hours');
    const tM = document.getElementById('t-mins');
    const tS = document.getElementById('t-secs');

    function pad(n) { return String(n).padStart(2, '0'); }

    function tickEl(el, val) {
        if (el && el.textContent !== val) {
            el.textContent = val;
            el.classList.add('tick');
            setTimeout(() => el.classList.remove('tick'), 280);
        }
    }

    setInterval(() => {
        const diff = target - Date.now();
        if (diff <= 0) return;
        tickEl(tH, pad(Math.floor(diff / 3600000)));
        tickEl(tM, pad(Math.floor((diff % 3600000) / 60000)));
        const val = pad(Math.floor((diff % 60000) / 1000));
        if (tS && tS.textContent !== val) tS.textContent = val;
    }, 1000);

});