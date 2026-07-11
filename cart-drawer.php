<?php
/**
 * RISER — Cart Drawer Fragment
 * Returns just the inner HTML of the slide-out cart drawer. Fetched via
 * JS whenever the drawer opens or the cart changes, so the drawer and the
 * full cart page (cart.php) both read from the exact same cart logic —
 * no duplicated business rules between the two views.
 */
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex'); // this fragment is never a real page, keep it out of search results

$B = BASE_URL;
$cartItems = getCartDetails($pdo);
$subtotal = array_sum(array_column($cartItems, 'line_total'));
$shipping = !empty($cartItems) ? SHIPPING_FEE : 0;
$total = $subtotal + $shipping;
?>
<?php if (empty($cartItems)): ?>
  <div class="cart-drawer__empty">
    <svg viewBox="0 0 24 24" fill="none" width="40" height="40"><path d="M5 7h14l-1.2 12.6a1.2 1.2 0 0 1-1.2 1.1H7.4a1.2 1.2 0 0 1-1.2-1.1L5 7Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M8.5 7a3.5 3.5 0 0 1 7 0" stroke="currentColor" stroke-width="1.4"/></svg>
    <p>Your cart is empty.</p>
    <a href="<?= $B ?>/shop.php" class="btn btn--pill" data-close-drawer>
      Start Shopping
      <span class="btn--pill__badge" aria-hidden="true">
        <svg viewBox="0 0 20 20" width="16" height="16" fill="none"><path d="M6 14 14 6M14 6H7M14 6v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
    </a>
  </div>
<?php else: ?>
  <ul class="cart-drawer__list">
    <?php foreach ($cartItems as $item): ?>
      <li class="cart-drawer__item">
        <img src="<?= $B ?>/images/products/<?= e($item['image']) ?>"
             alt="<?= e($item['name']) ?>" loading="lazy"
             onerror="this.src='<?= $B ?>/images/placeholder.svg'">
        <div class="cart-drawer__item-info">
          <div class="cart-drawer__item-top">
            <div>
              <div class="name"><?= e($item['name']) ?></div>
              <div class="meta"><?= e($item['size']) ?> / <?= e($item['color']) ?></div>
              <?php if (!empty($item['custom_text'])): ?>
                <div class="meta" style="color:var(--riser-red-deep); font-family:var(--font-mono); font-size:0.72rem;">
                  Embroidered: "<?= e($item['custom_text']) ?>"
                </div>
              <?php endif; ?>
            </div>
            <button type="button" class="cart-remove js-drawer-remove" data-cart-key="<?= e($item['cart_key']) ?>" aria-label="Remove <?= e($item['name']) ?>">&times;</button>
          </div>
          <div class="cart-drawer__item-row">
            <div class="qty-stepper qty-stepper--sm">
              <button type="button" class="js-drawer-qty-dec" data-cart-key="<?= e($item['cart_key']) ?>" aria-label="Decrease">&minus;</button>
              <input type="text" class="js-drawer-qty" readonly
                     data-cart-key="<?= e($item['cart_key']) ?>"
                     data-max="<?= (int)$item['stock'] ?>"
                     value="<?= (int)$item['qty'] ?>" inputmode="numeric">
              <button type="button" class="js-drawer-qty-inc" data-cart-key="<?= e($item['cart_key']) ?>" aria-label="Increase">+</button>
            </div>
            <strong><?= formatPrice($item['line_total']) ?></strong>
          </div>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="cart-drawer__summary">
    <div class="summary-row"><span>Subtotal</span><span><?= formatPrice($subtotal) ?></span></div>
    <div class="summary-row"><span>Shipping (COD)</span><span><?= formatPrice($shipping) ?></span></div>
    <div class="summary-row total"><span>Total</span><span><?= formatPrice($total) ?></span></div>
    <a href="<?= $B ?>/checkout.php" class="btn btn--pill btn--full">
      Checkout
      <span class="btn--pill__badge" aria-hidden="true">
        <svg viewBox="0 0 20 20" width="16" height="16" fill="none"><path d="M6 14 14 6M14 6H7M14 6v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
    </a>
    <a href="<?= $B ?>/cart.php" class="cart-drawer__view-all">View Full Cart</a>
  </div>
<?php endif; ?>
