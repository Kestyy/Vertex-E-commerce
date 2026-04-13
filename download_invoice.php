<?php
ob_start(); // ADD THIS FIRST LINE
require_once 'assets/php/db.php';
session_start();

if (!isset($_SESSION['user_id'])) { http_response_code(403); exit('Unauthorized'); }

$order_id = intval($_GET['order'] ?? 0);
if (!$order_id) { http_response_code(400); exit('Invalid order'); }

$stmt = $conn->prepare("
    SELECT o.*, u.full_name AS customer_name, u.email AS customer_email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) { http_response_code(404); exit('Order not found'); }

$stmt2 = $conn->prepare("
    SELECT oi.quantity, oi.price, p.name, p.image
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$stmt2->bind_param("i", $order_id);
$stmt2->execute();
$items = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

require_once 'vendor/autoload.php';

$subtotal = 0;
$itemsHtml = '';
foreach ($items as $item) {
    // FIX: use 'quantity' instead of 'qty'
    $line = $item['price'] * $item['quantity'];
    $subtotal += $line;
    $itemsHtml .= "
        <tr>
            <td style='padding:10px;border-bottom:1px solid #f1f5f9'>" . htmlspecialchars($item['name']) . "</td>
            <td style='padding:10px;border-bottom:1px solid #f1f5f9;text-align:center'>{$item['quantity']}</td>
            <td style='padding:10px;border-bottom:1px solid #f1f5f9;text-align:right'>&#8369;" . number_format($item['price'], 2) . "</td>
            <td style='padding:10px;border-bottom:1px solid #f1f5f9;text-align:right'>&#8369;" . number_format($line, 2) . "</td>
        </tr>";
}

$shipping = $order['shipping_fee'] ?? 0;
$total = $subtotal + $shipping;
$shippingText = $shipping == 0 ? 'Free' : '&#8369;' . number_format($shipping, 2);
$orderNum = htmlspecialchars($order['order_number']);
$custName = htmlspecialchars($order['customer_name']);
$custEmail = htmlspecialchars($order['customer_email']);
// FIX: use 'order_date' instead of 'created_at'
$orderDate = !empty($order['order_date'])
    ? date('d F Y', strtotime($order['order_date']))
    : '—';
$payMethod = htmlspecialchars($order['payment_method']);

$html = "
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size: 13px; color: #1e293b; padding: 10px; }
    h1 { font-size: 20px; margin-bottom: 4px; }
    .sub { color: #94a3b8; font-size: 12px; margin-bottom: 20px; }
    .meta { display: flex; gap: 20px; margin-bottom: 24px; }
    .meta-box { background: #f8fafc; border-radius: 8px; padding: 10px 14px; flex: 1; }
    .meta-label { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 3px; }
    .meta-val { font-size: 13px; font-weight: 600; }
    .meta-small { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    th { background: #f8fafc; padding: 8px 10px; text-align: left; font-size: 9px; color: #94a3b8; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
    .totals-table { width: 260px; margin-left: auto; }
    .totals-table td { padding: 5px 0; font-size: 13px; }
    .grand td { border-top: 2px solid #e2e8f0; padding-top: 10px; font-weight: 700; font-size: 15px; }
</style>

<h1>Order Invoice</h1>
<div class='sub'>$orderNum</div>

<div class='meta'>
    <div class='meta-box'>
        <div class='meta-label'>Billed To</div>
        <div class='meta-val'>$custName</div>
        <div class='meta-small'>$custEmail</div>
    </div>
    <div class='meta-box'>
        <div class='meta-label'>Order Date</div>
        <div class='meta-val'>$orderDate</div>
        <div class='meta-small'>Via $payMethod</div>
    </div>
</div>

<table>
    <tr>
        <th>Item</th>
        <th>Qty</th>
        <th style='text-align:right'>Unit Price</th>
        <th style='text-align:right'>Total</th>
    </tr>
    $itemsHtml
</table>

<table class='totals-table'>
    <tr><td>Subtotal</td><td style='text-align:right'>&#8369;" . number_format($subtotal, 2) . "</td></tr>
    <tr><td>Shipping</td><td style='text-align:right'>$shippingText</td></tr>
    <tr class='grand'><td><b>Total</b></td><td style='text-align:right'><b>&#8369;" . number_format($total, 2) . "</b></td></tr>
</table>
";

$mpdf = new \Mpdf\Mpdf([
    'margin_top'    => 15,
    'margin_bottom' => 15,
    'margin_left'   => 15,
    'margin_right'  => 15,
]);

ob_end_clean(); // ADD THIS BEFORE OUTPUT
$mpdf->WriteHTML($html);
$mpdf->Output("Invoice-{$orderNum}.pdf", 'D');