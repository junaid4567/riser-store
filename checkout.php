<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Checkout';
$pageDescription = 'Secure Cash on Delivery checkout for RISER caps — embroidered streetwear caps shipped nationwide across Pakistan.';
$cartItems = getCartDetails($pdo);

if (empty($cartItems)) {
    header("Location: " . BASE_URL . "/cart.php");
    exit;
}

$errors = [];
$old = [
    'first_name' => '',
    'last_name' => '',
    'phone' => '',
    'email' => '',
    'address' => '',
    'city_select' => '',
    'city_other' => '',
    'province' => '',
    'postal_code' => '',
    'notes' => '',
];

$pakistanProvinces = ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Gilgit-Baltistan', 'Azad Kashmir', 'Islamabad Capital Territory'];
$pakistanCities = ['Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta', 'Hyderabad', 'Sialkot', 'Gujranwala', 'Sargodha', 'Bahawalpur', 'Sukkur', 'Larkana'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $_) {
        $old[$key] = trim($_POST[$key] ?? '');
    }

    $customerName = trim($old['first_name'] . ' ' . $old['last_name']);
    $city = $old['city_select'] === '__other' ? $old['city_other'] : $old['city_select'];

    // ---- CSRF check ----
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Your session expired. Please try submitting the form again.';
    }

    // ---- Validation ----
    if ($old['first_name'] === '') {
        $errors['first_name'] = 'Please enter your first name.';
    }
    if ($old['last_name'] === '') {
        $errors['last_name'] = 'Please enter your last name.';
    }

    if ($old['phone'] === '' || !preg_match('/^(\+92|0)?3[0-9]{9}$/', preg_replace('/[\s-]/', '', $old['phone']))) {
        $errors['phone'] = 'Please enter a valid Pakistani mobile number, e.g. 03001234567.';
    }

    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address or leave it blank.';
    }

    if ($old['address'] === '' || mb_strlen($old['address']) < 8) {
        $errors['address'] = 'Please enter your complete delivery address.';
    }

    if ($city === '') {
        $errors['city'] = 'Please select or enter your city.';
    }

    if (!in_array($old['province'], $pakistanProvinces, true)) {
        $errors['province'] = 'Please select your province.';
    }

    // ---- Re-verify stock right before placing order ----
    if (empty($errors)) {
        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare("SELECT stock FROM product_variants WHERE id = ?");
            $stmt->execute([$item['variant_id']]);
            $current = $stmt->fetch();
            if (!$current || $current['stock'] < $item['qty']) {
                $errors['stock'] = 'Sorry, "' . $item['name'] . '" (' . $item['size'] . ' / ' . $item['color'] . ') no longer has enough stock. Please update your cart.';
                break;
            }
        }
    }

    // ---- Place order ----
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $subtotal = array_sum(array_column($cartItems, 'line_total'));
            $shipping = SHIPPING_FEE;
            $total = $subtotal + $shipping;
            $orderNumber = generateOrderNumber();

            $stmt = $pdo->prepare("
                INSERT INTO orders (order_number, customer_name, phone, email, address, city, province, postal_code, notes, subtotal, shipping_fee, total, payment_method, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'COD', 'pending')
            ");
            $stmt->execute([
                $orderNumber, $customerName, $old['phone'], $old['email'] ?: null,
                $old['address'], $city, $old['province'], $old['postal_code'] ?: null, $old['notes'] ?: null,
                $subtotal, $shipping, $total
            ]);
            $orderId = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, variant_id, product_name, size, color, price, quantity, line_total, custom_text, thread_color, custom_fee)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stockStmt = $pdo->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ?");

            foreach ($cartItems as $item) {
                $itemStmt->execute([
                    $orderId, $item['product_id'], $item['variant_id'], $item['name'],
                    $item['size'], $item['color'], $item['price'], $item['qty'], $item['line_total'],
                    $item['custom_text'] ?: null, $item['thread_color'] ?: null, $item['custom_fee']
                ]);
                $stockStmt->execute([$item['qty'], $item['variant_id']]);
            }

            $pdo->commit();
            clearCart();

            header("Location: " . BASE_URL . "/order-success.php?order=" . urlencode($orderNumber));
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['general'] = 'Something went wrong while placing your order. Please try again.';
        }
    }
}

$subtotal = array_sum(array_column($cartItems, 'line_total'));
$shipping = SHIPPING_FEE;
$total = $subtotal + $shipping;

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Home</a> / <a href="<?= BASE_URL ?>/cart.php">Cart</a> / Checkout</div>
  <h1>Checkout</h1>
</section>

<section class="section">
  <div class="cart-layout">
    <div>
      <?php if (!empty($errors)): ?>
        <div class="alert alert--error">
          <?= e($errors['general'] ?? $errors['stock'] ?? 'Please fix the errors below and try again.') ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>/checkout.php" novalidate>
        <?= csrfField() ?>

        <div class="checkout-section">
          <h3>Contact</h3>
          <div class="form-grid" style="margin-top:16px;">
            <div class="field full <?= isset($errors['email']) ? 'error' : '' ?>">
              <label for="email">Email (optional)</label>
              <input type="email" id="email" name="email" placeholder="you@example.com" value="<?= e($old['email']) ?>">
              <?php if (isset($errors['email'])): ?><span class="err-msg"><?= e($errors['email']) ?></span><?php endif; ?>
            </div>
          </div>
          <label class="checkbox-row">
            <input type="checkbox" checked>
            Email me with news and offers
          </label>
        </div>

        <div class="checkout-section">
          <h3>Delivery</h3>
          <p class="section-hint">We currently deliver nationwide across Pakistan via Cash on Delivery.</p>

          <div class="form-grid">
            <div class="field full">
              <label for="country">Country/Region</label>
              <input type="text" id="country" value="Pakistan" disabled>
            </div>

            <div class="field <?= isset($errors['first_name']) ? 'error' : '' ?>">
              <label for="first_name">First Name *</label>
              <input type="text" id="first_name" name="first_name" value="<?= e($old['first_name']) ?>" required>
              <?php if (isset($errors['first_name'])): ?><span class="err-msg"><?= e($errors['first_name']) ?></span><?php endif; ?>
            </div>

            <div class="field <?= isset($errors['last_name']) ? 'error' : '' ?>">
              <label for="last_name">Last Name *</label>
              <input type="text" id="last_name" name="last_name" value="<?= e($old['last_name']) ?>" required>
              <?php if (isset($errors['last_name'])): ?><span class="err-msg"><?= e($errors['last_name']) ?></span><?php endif; ?>
            </div>

            <div class="field full <?= isset($errors['address']) ? 'error' : '' ?>">
              <label for="address">Address *</label>
              <input type="text" id="address" name="address" placeholder="House #, Street, Area" value="<?= e($old['address']) ?>" required>
              <?php if (isset($errors['address'])): ?><span class="err-msg"><?= e($errors['address']) ?></span><?php endif; ?>
            </div>

            <div class="field <?= isset($errors['city']) ? 'error' : '' ?>">
              <label for="city_select">City *</label>
              <select id="city_select" name="city_select" required>
                <option value="">Select city</option>
                <?php foreach ($pakistanCities as $c): ?>
                  <option value="<?= e($c) ?>" <?= $old['city_select'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
                <option value="__other" <?= $old['city_select'] === '__other' ? 'selected' : '' ?>>Other (type below)</option>
              </select>
              <?php if (isset($errors['city'])): ?><span class="err-msg"><?= e($errors['city']) ?></span><?php endif; ?>
            </div>

            <div class="field">
              <label for="postal_code">Postal Code (optional)</label>
              <input type="text" id="postal_code" name="postal_code" value="<?= e($old['postal_code']) ?>">
            </div>

            <div class="field full" id="cityOtherWrap" style="<?= $old['city_select'] === '__other' ? '' : 'display:none;' ?>">
              <label for="city_other">Enter your city</label>
              <input type="text" id="city_other" name="city_other" value="<?= e($old['city_other']) ?>">
            </div>

            <div class="field <?= isset($errors['province']) ? 'error' : '' ?>">
              <label for="province">Province *</label>
              <select id="province" name="province" required>
                <option value="">Select province</option>
                <?php foreach ($pakistanProvinces as $prov): ?>
                  <option value="<?= e($prov) ?>" <?= $old['province'] === $prov ? 'selected' : '' ?>><?= e($prov) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($errors['province'])): ?><span class="err-msg"><?= e($errors['province']) ?></span><?php endif; ?>
            </div>

            <div class="field <?= isset($errors['phone']) ? 'error' : '' ?>">
              <label for="phone">Mobile Number *</label>
              <input type="tel" id="phone" name="phone" placeholder="03001234567" value="<?= e($old['phone']) ?>" required>
              <?php if (isset($errors['phone'])): ?><span class="err-msg"><?= e($errors['phone']) ?></span><?php endif; ?>
            </div>

            <div class="field full">
              <label for="notes">Order Notes (optional)</label>
              <textarea id="notes" name="notes" placeholder="Delivery instructions, landmark, etc."><?= e($old['notes']) ?></textarea>
            </div>
          </div>
        </div>

        <div class="checkout-section">
          <h3>Payment</h3>
          <p class="payment-note">All transactions are secure and encrypted.</p>

          <div class="payment-option selected">
            <input type="radio" name="payment_method_display" checked readonly>
            <span class="label">Cash on Delivery (COD)</span>
          </div>
          <div class="payment-option is-disabled">
            <input type="radio" disabled>
            <span class="label">Debit / Credit Card</span>
            <span class="tag-soon">Coming soon</span>
          </div>
          <div class="payment-option is-disabled">
            <input type="radio" disabled>
            <span class="label">Buy Now, Pay Later</span>
            <span class="tag-soon">Coming soon</span>
          </div>
        </div>

        <button type="submit" class="btn btn--pill btn--full">
          <span class="btn-label">Complete Order — <?= formatPrice($total) ?></span>
          <span class="btn--pill__badge" aria-hidden="true">
            <svg viewBox="0 0 20 20" width="16" height="16" fill="none"><path d="M6 14 14 6M14 6H7M14 6v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
        </button>
      </form>
    </div>

    <aside class="summary-box">
      <h3>Order Summary</h3>

      <div class="summary-items">
        <?php foreach ($cartItems as $item): ?>
          <div class="summary-item">
            <div class="summary-item__thumb">
              <img src="<?= BASE_URL ?>/images/products/<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>" onerror="this.src='<?= BASE_URL ?>/images/placeholder.svg'">
              <span class="summary-item__qty"><?= (int)$item['qty'] ?></span>
            </div>
            <div class="summary-item__body">
              <div class="summary-item__name"><?= e($item['name']) ?></div>
              <div class="summary-item__meta"><?= e($item['color']) ?> / <?= e($item['size']) ?><?= !empty($item['custom_text']) ? ' — "' . e($item['custom_text']) . '"' : '' ?></div>
            </div>
            <div class="summary-item__price"><?= formatPrice($item['line_total']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="discount-row">
        <input type="text" placeholder="Discount code or gift card" id="discountInput">
        <button type="button" id="discountApplyBtn">Apply</button>
      </div>

      <div class="summary-row" style="border-top:1px solid var(--line-dark); padding-top:14px;">
        <span>Subtotal</span>
        <span><?= formatPrice($subtotal) ?></span>
      </div>
      <div class="summary-row">
        <span>Shipping</span>
        <span><?= formatPrice($shipping) ?></span>
      </div>
      <div class="summary-row total">
        <span>Total</span>
        <span><?= formatPrice($total) ?></span>
      </div>
    </aside>
  </div>
</section>

<script>
  document.getElementById('city_select')?.addEventListener('change', function () {
    document.getElementById('cityOtherWrap').style.display = this.value === '__other' ? '' : 'none';
  });
  document.getElementById('discountApplyBtn')?.addEventListener('click', function () {
    showToast('Discount codes aren\'t available yet — check back soon!');
  });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
