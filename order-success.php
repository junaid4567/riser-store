<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Order Confirmed';
$noIndex = true; // transactional page — keep out of search results
$orderNumber = $_GET['order'] ?? '';

$order = null;
$items = [];
if ($orderNumber !== '') {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();

    if ($order) {
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $items = $stmt->fetchAll();
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="order-success">
  <?php if (!$order): ?>
    <h2>Order Not Found</h2>
    <p class="text-mute" style="margin:18px 0;">We couldn't find this order. If you just placed it, check your link.</p>
    <a href="<?= BASE_URL ?>/shop.php" class="btn">Back to Shop</a>
  <?php else: ?>
    <div class="check">✓</div>
    <h1>Order Confirmed!</h1>
    <p class="text-mute" style="margin-top:14px;">Thank you, <?= e($order['customer_name']) ?>. Your RISER order has been placed successfully.</p>
    <div class="order-num">Order #<?= e($order['order_number']) ?></div>

    <div style="text-align:left; border:1px solid var(--line-dark); padding:24px; margin-bottom:24px;">
      <h3 style="margin-bottom:16px;">Order Summary</h3>
      <?php foreach ($items as $item): ?>
        <div class="summary-row">
          <span><?= e($item['product_name']) ?> (<?= e($item['size']) ?>/<?= e($item['color']) ?>)<?= !empty($item['custom_text']) ? ' — "' . e($item['custom_text']) . '" embroidered' : '' ?> &times; <?= (int)$item['quantity'] ?></span>
          <span><?= formatPrice($item['line_total']) ?></span>
        </div>
      <?php endforeach; ?>
      <div class="summary-row" style="border-top:1px solid var(--line-dark); padding-top:14px; margin-top:14px;">
        <span>Subtotal</span><span><?= formatPrice($order['subtotal']) ?></span>
      </div>
      <div class="summary-row">
        <span>Shipping (COD)</span><span><?= formatPrice($order['shipping_fee']) ?></span>
      </div>
      <div class="summary-row total">
        <span>Total to Pay on Delivery</span><span><?= formatPrice($order['total']) ?></span>
      </div>
    </div>

    <div class="cod-note" style="text-align:left; margin-bottom:30px;">
      We'll deliver to: <?= e($order['address']) ?>, <?= e($order['city']) ?>, <?= e($order['province']) ?><br>
      Contact: <?= e($order['phone']) ?><br>
      Pay <strong><?= formatPrice($order['total']) ?></strong> in cash when your caps arrive at your door.
    </div>

    <a href="<?= BASE_URL ?>/shop.php" class="btn">Continue Shopping</a>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
