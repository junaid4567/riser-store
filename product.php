<?php
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS cat_name FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.slug = ? AND p.is_active = 1
");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    include __DIR__ . '/includes/header.php';
    echo '<section class="section text-center"><h2>Cap Not Found</h2><p class="text-mute" style="margin:18px 0;">This product may have been removed or sold out permanently.</p><a href="' . BASE_URL . '/shop.php" class="btn">Back to Shop</a></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $product['name'];
$pageDescription = $product['name'] . ' — embroidered cap from RISER, a Karachi cap store. ' . mb_substr(strip_tags($product['description'] ?? ''), 0, 120) . ' Cash on Delivery across Pakistan.';
$pageKeywords = $product['name'] . ', ' . ($product['cat_name'] ?? 'caps') . ', embroidered caps, caps store in Pakistan, streetwear caps, caps in Karachi';

// Variants
$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY size, color");
$stmt->execute([$product['id']]);
$variants = $stmt->fetchAll();

$sizes = [];
$colors = [];
foreach ($variants as $v) {
    $sizes[$v['size']] = true;
    $colors[$v['color']] = $v['color_hex'];
}
$sizes = array_keys($sizes);

// Related products (same category)
$related = [];
if ($product['category_id']) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS cat_name FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
        LIMIT 4
    ");
    $stmt->execute([$product['category_id'], $product['id']]);
    $related = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';

$productSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'description' => strip_tags($product['description'] ?? ''),
    'image' => BASE_URL . '/images/products/' . ($product['image'] ?: 'placeholder.svg'),
    'brand' => ['@type' => 'Brand', 'name' => 'RISER'],
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'PKR',
        'price' => (string)$product['price'],
        'availability' => 'https://schema.org/InStock',
        'areaServed' => 'PK',
    ],
];
?>
<script type="application/ld+json"><?= json_encode($productSchema, JSON_UNESCAPED_SLASHES) ?></script>


<section class="page-banner" style="padding-bottom:0;">
  <div class="breadcrumb">
    <a href="<?= BASE_URL ?>/index.php">Home</a> / <a href="<?= BASE_URL ?>/shop.php">Shop</a> / <?= e($product['cat_name'] ?? '') ?>
  </div>
</section>

<section class="pdp">
  <div class="pdp__media">
    <img src="<?= BASE_URL ?>/images/products/<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" fetchpriority="high" decoding="async" onerror="this.src='<?= BASE_URL ?>/images/placeholder.svg'">
  </div>

  <div class="pdp__info">
    <span class="eyebrow"><?= e($product['cat_name'] ?? 'RISER') ?></span>
    <h1><?= e($product['name']) ?></h1>

    <div class="pdp__price">
      <span class="now"><?= formatPrice($product['price']) ?></span>
      <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
        <span class="was"><?= formatPrice($product['compare_price']) ?></span>
      <?php endif; ?>
    </div>

    <p class="pdp__desc"><?= nl2br(e($product['description'])) ?></p>

    <form id="pdpForm">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="variant_id" id="selectedVariantId" value="">
      <?= csrfField() ?>

      <?php if (!empty($sizes)): ?>
      <div class="option-group">
        <label class="title">Size</label>
        <div class="swatches">
          <?php foreach ($sizes as $i => $size): ?>
            <button type="button" class="swatch js-size-swatch <?= $i === 0 ? 'selected' : '' ?>" data-size="<?= e($size) ?>"><?= e($size) ?></button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($colors)): ?>
      <div class="option-group">
        <label class="title">Color</label>
        <div class="swatches">
          <?php foreach ($colors as $colorName => $hex): ?>
            <button type="button" class="swatch js-color-swatch" data-color="<?= e($colorName) ?>">
              <span class="dot" style="background:<?= e($hex) ?>"></span><?= e($colorName) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="option-group embro-customizer">
        <label class="title">Live Embroidery Customizer <span class="text-mute" style="font-weight:400;">(optional, +<?= formatPrice(CUSTOM_EMBROIDERY_FEE) ?>)</span></label>
        <div class="embro-customizer__layout">
          <canvas id="embroPreview" width="280" height="220" data-cap-image="<?= BASE_URL ?>/images/products/<?= e($product['image']) ?>" aria-label="Live preview of your custom embroidery"></canvas>
          <div class="embro-customizer__controls">
            <input type="text" id="embroText" maxlength="12" placeholder="Add your name or text" autocomplete="off">
            <span class="text-mute" id="embroCharCount" style="font-family:var(--font-mono); font-size:0.72rem;">0/12</span>
            <div class="thread-swatches" role="radiogroup" aria-label="Thread color">
              <label><input type="radio" name="embroThreadSwatch" value="#E8432C" checked><span class="thread-dot" style="background:#E8432C"></span></label>
              <label><input type="radio" name="embroThreadSwatch" value="#F5F2EA"><span class="thread-dot" style="background:#F5F2EA; border:1px solid #ccc;"></span></label>
              <label><input type="radio" name="embroThreadSwatch" value="#1c1c1c"><span class="thread-dot" style="background:#1c1c1c"></span></label>
              <label><input type="radio" name="embroThreadSwatch" value="#d8c4a0"><span class="thread-dot" style="background:#d8c4a0"></span></label>
            </div>
          </div>
        </div>
        <p id="embroFeeNote" class="text-mute" style="display:none; font-size:0.8rem; margin-top:8px;">Custom embroidery adds <?= formatPrice(CUSTOM_EMBROIDERY_FEE) ?> and is hand-stitched in our Karachi workshop — allow 1–2 extra dispatch days.</p>
      </div>
      <input type="hidden" name="custom_text" id="customTextField" value="">
      <input type="hidden" name="thread_color" id="threadColorField" value="#E8432C">

      <div class="qty-row">
        <div class="qty-stepper">
          <button type="button" class="js-qty-dec" aria-label="Decrease quantity">&minus;</button>
          <input type="text" name="qty" class="js-pdp-qty" value="1" inputmode="numeric" aria-label="Quantity">
          <button type="button" class="js-qty-inc" aria-label="Increase quantity">+</button>
        </div>
        <span class="stock-note" id="stockNote">Select options to check availability</span>
      </div>

      <div class="pdp__actions">
        <button type="submit" class="btn btn--accent btn--full" id="pdpAddBtn" disabled>
          <span class="btn-label">Add to Cart</span>
        </button>
      </div>
    </form>

    <ul class="trust-list">
      <li><span class="ic">✓</span> Cash on Delivery available nationwide</li>
      <li><span class="ic">✓</span> Dispatched within 48 hours from Karachi</li>
      <li><span class="ic">✓</span> Structured 6-panel build, adjustable fit</li>
      <li><span class="ic">✓</span> Easy exchange within 7 days of delivery</li>
    </ul>
  </div>
</section>

<!-- Mobile-only sticky add-to-cart bar: mirrors the in-page button once it
     scrolls out of view, so checkout is always one tap away on phones -->
<div class="mobile-sticky-cta" id="mobileStickyCta">
  <span class="price"><?= formatPrice($product['price']) ?></span>
  <button type="button" class="btn" id="mobileStickyCtaBtn">Add to Cart</button>
</div>

<script>
  // Pass variant data to main.js for client-side size/color → variant matching
  window.RISER_VARIANTS = <?= json_encode(array_map(fn($v) => [
      'id' => (int)$v['id'],
      'size' => $v['size'],
      'color' => $v['color'],
      'stock' => (int)$v['stock'],
  ], $variants)) ?>;
</script>

<?php if (!empty($related)): ?>
<section class="section section--paper-dim">
  <div class="section__head fade-up">
    <div>
      <span class="eyebrow">You Might Also Like</span>
      <h2>More in <?= e($product['cat_name']) ?></h2>
    </div>
  </div>
  <div class="grid">
    <?php foreach ($related as $i => $p): ?>
      <?php include __DIR__ . '/includes/product-card.php'; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
