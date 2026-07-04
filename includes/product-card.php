<?php
/**
 * Reusable product card.
 * Expects $p (product row with optional cat_name) and optional $i (index for stagger delay).
 */
$B     = BASE_URL;
$delay = isset($i) ? ($i % 4) * 0.07 : 0;
$badge = null;
if (!empty($p['is_new_arrival'])) $badge = 'New';
if (!empty($p['compare_price']) && $p['compare_price'] > $p['price']) $badge = 'Sale';
?>
<div class="card fade-up" style="transition-delay: <?= $delay ?>s;">
  <a href="<?= $B ?>/product.php?slug=<?= e($p['slug']) ?>" class="card__link" aria-label="<?= e($p['name']) ?>"></a>
  <div class="card__media">
    <?php if ($badge): ?><span class="card__ribbon"><?= e($badge) ?></span><?php endif; ?>
    <img src="<?= $B ?>/images/products/<?= e($p['image']) ?>"
         alt="<?= e($p['name']) ?> - embroidered cap, RISER caps store Pakistan"
         loading="lazy" decoding="async"
         onerror="this.src='<?= $B ?>/images/placeholder.svg'">

    <?php if (!empty($p['colors'])): $firstColor = $p['colors'][0]; ?>
    <div class="card__hover-panel">
      <?php if (count($p['colors']) > 1): ?>
        <div class="card__swatches" role="group" aria-label="Choose color for <?= e($p['name']) ?>">
          <?php foreach (array_slice($p['colors'], 0, 6) as $ci => $c): ?>
            <button type="button"
                    class="card__swatch <?= $ci === 0 ? 'selected' : '' ?>"
                    data-variant-id="<?= (int)$c['variant_id'] ?>"
                    data-multi-size="<?= $c['multi_size'] ? '1' : '0' ?>"
                    style="background:<?= e($c['color_hex'] ?: '#999') ?>"
                    title="<?= e($c['color']) ?>"
                    aria-label="<?= e($c['color']) ?>"></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form class="card__quick-add js-add-cart"
            data-needs-size="<?= $firstColor['multi_size'] ? '1' : '0' ?>"
            data-product-url="<?= $B ?>/product.php?slug=<?= e($p['slug']) ?>">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="variant_id" class="js-quick-variant" value="<?= (int)$firstColor['variant_id'] ?>">
        <input type="hidden" name="qty" value="1">
        <?= csrfField() ?>
        <button type="submit" class="card__quick-btn">
          <span class="btn-label"><?= $firstColor['multi_size'] ? 'Select Size' : 'Add to Cart' ?></span>
          <span class="card__quick-btn__badge" aria-hidden="true">
            <svg viewBox="0 0 20 20" width="13" height="13" fill="none"><path d="M6 14 14 6M14 6H7M14 6v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
        </button>
      </form>
    </div>
    <?php endif; ?>
  </div>
  <div class="card__body">
    <span class="card__cat"><?= e($p['cat_name'] ?? '') ?></span>
    <span class="card__name"><?= e($p['name']) ?></span>
    <div class="card__price">
      <span class="now"><?= formatPrice($p['price']) ?></span>
      <?php if (!empty($p['compare_price']) && $p['compare_price'] > $p['price']): ?>
        <span class="was"><?= formatPrice($p['compare_price']) ?></span>
      <?php endif; ?>
    </div>
  </div>
</div>
