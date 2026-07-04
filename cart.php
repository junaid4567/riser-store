<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Your Cart';
$pageDescription = 'Review your RISER cap selections — embroidered caps, snapbacks, and custom embroidery orders ready for Cash on Delivery checkout across Pakistan.';
$B = BASE_URL;
$cartItems = getCartDetails($pdo);
$subtotal = array_sum(array_column($cartItems, 'line_total'));
$shipping = !empty($cartItems) ? SHIPPING_FEE : 0;
$total = $subtotal + $shipping;

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="breadcrumb"><a href="<?= $B ?>/index.php">Home</a> / Cart</div>
  <h1>Your Cart</h1>
</section>

<section class="section">
  <?php if (empty($cartItems)): ?>
    <div class="empty-state">
      Your cart is empty.<br><br>
      <a href="<?= $B ?>/shop.php" class="btn">Start Shopping</a>
    </div>
  <?php else: ?>
    <div class="cart-layout">
      <div>
      <div class="table-scroll">
        <table class="cart-table">
          <thead>
            <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th></tr>
          </thead>
          <tbody>
            <?php foreach ($cartItems as $item): ?>
              <tr>
                <td>
                  <div class="cart-item">
                    <img src="<?= $B ?>/images/products/<?= e($item['image']) ?>"
                         alt="<?= e($item['name']) ?>" loading="lazy"
                         onerror="this.src='<?= $B ?>/images/placeholder.svg'">
                    <div>
                      <div class="name"><?= e($item['name']) ?></div>
                      <div class="meta"><?= e($item['size']) ?> / <?= e($item['color']) ?></div>
                      <?php if (!empty($item['custom_text'])): ?>
                        <div class="meta" style="color:var(--riser-red-deep); font-family:var(--font-mono); font-size:0.75rem;">
                          Embroidered: "<?= e($item['custom_text']) ?>" (+<?= formatPrice($item['custom_fee']) ?>/cap)
                        </div>
                      <?php endif; ?>
                      <button type="button" class="cart-remove js-cart-remove" data-cart-key="<?= e($item['cart_key']) ?>">Remove</button>
                    </div>
                  </div>
                </td>
                <td><?= formatPrice($item['price'] + $item['custom_fee']) ?></td>
                <td>
                  <div class="qty-stepper">
                    <button type="button" class="js-qty-dec" aria-label="Decrease">&minus;</button>
                    <input type="text" class="js-cart-qty"
                           data-cart-key="<?= e($item['cart_key']) ?>"
                           data-max="<?= (int)$item['stock'] ?>"
                           value="<?= (int)$item['qty'] ?>" inputmode="numeric">
                    <button type="button" class="js-qty-inc" aria-label="Increase">+</button>
                  </div>
                </td>
                <td><strong><?= formatPrice($item['line_total']) ?></strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
        <div class="mt-40">
          <a href="<?= $B ?>/shop.php" class="btn btn--outline">&larr; Continue Shopping</a>
        </div>
      </div>

      <aside class="summary-box">
        <h3>Order Summary</h3>
        <div class="summary-row"><span>Subtotal</span><span><?= formatPrice($subtotal) ?></span></div>
        <div class="summary-row"><span>Shipping (COD)</span><span><?= formatPrice($shipping) ?></span></div>
        <div class="summary-row total"><span>Total</span><span><?= formatPrice($total) ?></span></div>
        <div class="cod-note">Pay in cash when your order arrives. No advance payment needed.</div>
        <a href="<?= $B ?>/checkout.php" class="btn btn--full">Proceed to Checkout</a>
      </aside>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
