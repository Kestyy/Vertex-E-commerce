<?php
/**
 * INVOICE MODAL — Drop this file's contents into profile.php
 *
 * 1. Paste the <style> block into your existing <style> section (or leave it here).
 * 2. Paste the modal HTML just before </body>.
 * 3. Paste the <script> block alongside your other scripts.
 * 4. Replace every Invoice <a> tag in the orders tab with the new onclick call.
 *
 * BEFORE (in your orders loop):
 *   <a href="invoice.php?order=<?= $order['id'] ?>" ...>Invoice</a>
 *
 * AFTER:
 *   <a href="#" onclick="openInvoiceModal(<?= intval($order['id']) ?>); return false;" ...>Invoice</a>
 */
?>

<!-- ═══════════════════════════════════════
     1. ADD TO YOUR <style> SECTION
═══════════════════════════════════════ -->
<style>
/* Invoice Modal */
.inv-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.52);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
    animation: invFadeIn .2s ease;
}
@keyframes invFadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}
.inv-modal {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 24px 60px rgba(0,0,0,0.18);
    width: 100%;
    max-width: 560px;
    max-height: 90vh;
    overflow-y: auto;
    animation: invSlideUp .25s ease;
}
@keyframes invSlideUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
.inv-modal::-webkit-scrollbar { width: 4px; }
.inv-modal::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

.inv-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #f1f5f9;
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 2;
    border-radius: 18px 18px 0 0;
}
.inv-header-left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.inv-header-icon {
    width: 36px; height: 36px;
    background: #eff6ff;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #3b82f6;
    font-size: 14px;
    flex-shrink: 0;
}
.inv-header-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 1px;
}
.inv-header-sub {
    font-size: 12px;
    color: #94a3b8;
    font-weight: 400;
}
.inv-close-btn {
    width: 30px; height: 30px;
    background: #f1f5f9;
    border: none;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    color: #64748b;
    cursor: pointer;
    transition: background .15s, color .15s;
    flex-shrink: 0;
}
.inv-close-btn:hover { background: #e2e8f0; color: #1e293b; }

.inv-body { padding: 22px 24px; }

.inv-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 20px;
}
.inv-meta-card {
    background: #f8fafc;
    border-radius: 10px;
    padding: 12px 14px;
    border: 1px solid #f1f5f9;
}
.inv-meta-label {
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 5px;
}
.inv-meta-val {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.35;
}
.inv-meta-sub {
    font-size: 11.5px;
    color: #94a3b8;
    margin-top: 2px;
    font-weight: 400;
}

/* Items table */
.inv-items-wrap {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 16px;
}
.inv-items-head {
    display: grid;
    grid-template-columns: 2fr 60px 100px 100px;
    background: #f8fafc;
    padding: 9px 14px;
    border-bottom: 1px solid #e2e8f0;
    gap: 0;
}
.inv-items-head span {
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.inv-items-head span:not(:first-child) { text-align: right; }

.inv-item-row {
    display: grid;
    grid-template-columns: 2fr 60px 100px 100px;
    align-items: center;
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    gap: 0;
}
.inv-item-row:last-child { border-bottom: none; }

.inv-item-name-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.inv-item-img {
    width: 38px; height: 38px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    flex-shrink: 0;
}
.inv-item-img-placeholder {
    width: 38px; height: 38px;
    border-radius: 8px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: center;
    color: #cbd5e1;
    font-size: 14px;
    flex-shrink: 0;
}
.inv-item-name {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.inv-item-qty  { font-size: 13px; color: #64748b; text-align: right; }
.inv-item-price { font-size: 13px; color: #64748b; text-align: right; }
.inv-item-total { font-size: 13px; font-weight: 600; color: #1e293b; text-align: right; }

/* Totals */
.inv-totals {
    border-top: 1px solid #e2e8f0;
    padding-top: 14px;
    margin-bottom: 20px;
}
.inv-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.inv-total-row .lbl { font-size: 13px; color: #64748b; }
.inv-total-row .val { font-size: 13px; color: #1e293b; font-weight: 500; }
.inv-total-row .val.free { color: #16a34a; }
.inv-total-grand {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
    margin-top: 4px;
}
.inv-total-grand .lbl { font-size: 14px; font-weight: 700; color: #1e293b; }
.inv-total-grand .val { font-size: 17px; font-weight: 700; color: #1e293b; }

/* Footer */
.inv-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
}
.inv-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 13px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid;
}
.inv-dl-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #1e293b;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 9px 20px;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s, transform .15s;
    text-decoration: none;
}
.inv-dl-btn:hover {
    background: #0f172a;
    transform: translateY(-1px);
    color: #fff;
}

/* Loading / Error states */
.inv-loading, .inv-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 50px 30px;
    text-align: center;
    color: #94a3b8;
    gap: 12px;
}
.inv-loading i { font-size: 1.8rem; animation: spin .8s linear infinite; }
.inv-error i   { font-size: 1.8rem; color: #fca5a5; }
.inv-error p   { font-size: 14px; color: #64748b; margin: 0; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 480px) {
    .inv-meta-grid { grid-template-columns: 1fr; }
    .inv-items-head,
    .inv-item-row {
        grid-template-columns: 1fr 44px 80px 80px;
    }
    .inv-body { padding: 16px; }
}
</style>


<!-- ═══════════════════════════════════════
     2. PASTE JUST BEFORE </body>
═══════════════════════════════════════ -->

<!-- Invoice Modal -->
<div id="invoiceModalOverlay" class="inv-overlay" style="display:none;" onclick="if(event.target===this) closeInvoiceModal()">
    <div class="inv-modal" id="invoiceModalBox">

        <!-- Header -->
        <div class="inv-header">
            <div class="inv-header-left">
                <div class="inv-header-icon"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <div class="inv-header-title">Order Invoice</div>
                    <div class="inv-header-sub" id="invOrderNum">—</div>
                </div>
            </div>
            <button class="inv-close-btn" onclick="closeInvoiceModal()" title="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Dynamic body -->
        <div class="inv-body" id="invoiceModalBody">
            <div class="inv-loading">
                <i class="fas fa-circle-notch"></i>
                <span>Loading invoice…</span>
            </div>
        </div>

    </div>
</div>


<!-- ═══════════════════════════════════════
     3. ADD TO YOUR <script> SECTION
═══════════════════════════════════════ -->
<script>
/* ── Invoice Modal ─────────────────────── */

function openInvoiceModal(orderId) {
    const overlay = document.getElementById('invoiceModalOverlay');
    const body    = document.getElementById('invoiceModalBody');
    const numEl   = document.getElementById('invOrderNum');

    // Reset & show
    numEl.textContent = '—';
    body.innerHTML = `<div class="inv-loading"><i class="fas fa-circle-notch"></i><span>Loading invoice…</span></div>`;
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Fetch invoice data
    fetch('get_invoice.php?order=' + encodeURIComponent(orderId))
        .then(r => { if (!r.ok) throw new Error('Server error ' + r.status); return r.json(); })
        .then(data => renderInvoiceModal(data))
        .catch(err => {
            console.error(err);
            body.innerHTML = `<div class="inv-error">
                <i class="fas fa-circle-exclamation"></i>
                <p>Could not load invoice. Please try again.</p>
            </div>`;
        });
}

function closeInvoiceModal() {
    document.getElementById('invoiceModalOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeInvoiceModal();
});

function renderInvoiceModal(d) {
    /* d = JSON from get_invoice.php — shape:
    {
        order_number: "VTX-20260413-001",
        order_date:   "13 April 2026",
        payment_method: "GCash",
        status:       "pending",
        subtotal:     1200.00,
        shipping:     0,
        total:        1200.00,
        customer: { name: "Juan Dela Cruz", email: "juan@example.com" },
        items: [
            { name: "Monitor Fancy", qty: 1, price: 1200.00, image: "monitor.jpg" }
        ]
    }
    */

    // Status badge colours (mirrors your PHP statusBadge())
    const statusMap = {
        pending:    { label:'Pending',    color:'#f59e0b', bg:'#fffbeb', border:'#fde68a' },
        processing: { label:'Processing', color:'#3b82f6', bg:'#eff6ff', border:'#bfdbfe' },
        accepted:   { label:'Accepted',   color:'#f59e0b', bg:'#fffbeb', border:'#fde68a' },
        shipped:    { label:'Shipped',    color:'#8b5cf6', bg:'#f5f3ff', border:'#ddd6fe' },
        delivered:  { label:'Delivered',  color:'#16a34a', bg:'#f0fdf4', border:'#bbf7d0' },
        cancelled:  { label:'Cancelled',  color:'#ef4444', bg:'#fef2f2', border:'#fecaca' },
    };
    const badge = statusMap[(d.status || '').toLowerCase()] || { label: d.status, color:'#64748b', bg:'#f8fafc', border:'#e2e8f0' };

    // Update header order number
    document.getElementById('invOrderNum').textContent = d.order_number || ('Order #' + d.order_id);

    // Build items rows
    const itemsHtml = (d.items || []).map(item => {
        const imgHtml = item.image
            ? `<img class="inv-item-img" src="images/products/${escHtml(item.image)}" alt="${escHtml(item.name)}">`
            : `<div class="inv-item-img-placeholder"><i class="fas fa-image"></i></div>`;
        const lineTotal = (parseFloat(item.price) * parseInt(item.qty, 10)).toFixed(2);
        return `
        <div class="inv-item-row">
            <div class="inv-item-name-wrap">
                ${imgHtml}
                <div class="inv-item-name">${escHtml(item.name)}</div>
            </div>
            <div class="inv-item-qty">${parseInt(item.qty, 10)}</div>
            <div class="inv-item-price">₱${fmtMoney(item.price)}</div>
            <div class="inv-item-total">₱${fmtMoney(lineTotal)}</div>
        </div>`;
    }).join('');

    const shippingHtml = parseFloat(d.shipping) === 0
        ? `<span class="val free">Free</span>`
        : `<span class="val">₱${fmtMoney(d.shipping)}</span>`;

    const payMethod = d.payment_method
        ? d.payment_method.charAt(0).toUpperCase() + d.payment_method.slice(1)
        : 'N/A';

    document.getElementById('invoiceModalBody').innerHTML = `

        <!-- Meta cards -->
        <div class="inv-meta-grid">
            <div class="inv-meta-card">
                <div class="inv-meta-label">Billed to</div>
                <div class="inv-meta-val">${escHtml(d.customer?.name || '—')}</div>
                <div class="inv-meta-sub">${escHtml(d.customer?.email || '')}</div>
            </div>
            <div class="inv-meta-card">
                <div class="inv-meta-label">Order date</div>
                <div class="inv-meta-val">${escHtml(d.order_date || '—')}</div>
                <div class="inv-meta-sub">Via ${escHtml(payMethod)}</div>
            </div>
        </div>

        <!-- Items -->
        <div class="inv-items-wrap">
            <div class="inv-items-head">
                <span>Item</span>
                <span>Qty</span>
                <span>Unit price</span>
                <span>Total</span>
            </div>
            ${itemsHtml}
        </div>

        <!-- Totals -->
        <div class="inv-totals">
            <div class="inv-total-row">
                <span class="lbl">Subtotal</span>
                <span class="val">₱${fmtMoney(d.subtotal)}</span>
            </div>
            <div class="inv-total-row">
                <span class="lbl">Shipping</span>
                ${shippingHtml}
            </div>
            <div class="inv-total-grand">
                <span class="lbl">Total</span>
                <span class="val">₱${fmtMoney(d.total)}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="inv-footer">
            <span class="inv-status-badge" style="color:${badge.color};background:${badge.bg};border-color:${badge.border};">
                ${escHtml(badge.label)}
            </span>
            <a class="inv-dl-btn" href="download_invoice.php?order=${encodeURIComponent(d.order_id)}" target="_blank">
                <i class="fas fa-download"></i> Download PDF
            </a>
        </div>
    `;
}

function fmtMoney(v) {
    return parseFloat(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>