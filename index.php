<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Caps Store in Karachi & Pakistan';
$pageDescription = 'RISER is a Karachi caps store selling embroidered caps, snapbacks, trucker caps and dad hats nationwide across Pakistan with Cash on Delivery.';
$activeNav = 'home';
$B = BASE_URL;

$featured = $pdo->query("
    SELECT p.*, c.name AS cat_name FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_featured = 1 AND p.is_active = 1
    ORDER BY p.created_at DESC LIMIT 4
")->fetchAll();
$featured = attachColorOptions($pdo, $featured);

$newArrivals = $pdo->query("
    SELECT p.*, c.name AS cat_name FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_new_arrival = 1 AND p.is_active = 1
    ORDER BY p.created_at DESC LIMIT 4
")->fetchAll();
$newArrivals = attachColorOptions($pdo, $newArrivals);

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="hero__inner">
    <div class="hero__copy">
      <p class="hero__intro">
        <strong>Every RISER cap is structured, embroidered, and built to hold its shape.</strong>
        <span class="dim"> Made in our Karachi workshop with premium materials and honest pricing, from your first drop to your fiftieth wear.</span>
      </p>

      <h1 class="hero__headline"><span>WEAR</span><span>THE</span><span>RISE.</span></h1>

      <div class="hero__cta">
        <a href="<?= $B ?>/shop.php" class="btn btn--pill">
          Shop All Caps
          <span class="btn--pill__badge" aria-hidden="true">
            <svg viewBox="0 0 20 20" width="16" height="16" fill="none"><path d="M6 14 14 6M14 6H7M14 6v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
        </a>
      </div>
    </div>

    <div class="hero__visual">
      <div class="hero__frame">
        <img src="<?= $B ?>/images/products/classic-black.jpg"
             alt="RISER Classic Snapback in black, embroidered logo"
             fetchpriority="high" decoding="async"
             onerror="this.src='<?= $B ?>/images/placeholder.svg'">
      </div>
      <div class="hero__caption">
        <span class="name">Classic Snapback — Black</span>
        <a href="<?= $B ?>/shop.php">Shop Now</a>
      </div>
    </div>
  </div>

  <?php
    // Pull a small spread of real catalog images for the numbered filmstrip,
    // so it stays data-driven instead of hardcoded photography.
    $filmstripProducts = $pdo->query("
        SELECT name, image, slug FROM products WHERE is_active = 1
        ORDER BY created_at DESC LIMIT 7
    ")->fetchAll();
  ?>
  <?php if (!empty($filmstripProducts)): ?>
  <div class="filmstrip">
    <?php foreach ($filmstripProducts as $i => $fp): ?>
      <a href="<?= $B ?>/product.php?slug=<?= e($fp['slug']) ?>" class="filmstrip__item" style="--i:<?= $i ?>">
        <span class="filmstrip__num">//0<?= $i + 1 ?></span>
        <span class="filmstrip__frame">
          <img src="<?= $B ?>/images/products/<?= e($fp['image']) ?>" alt="<?= e($fp['name']) ?>" loading="lazy" decoding="async" onerror="this.src='<?= $B ?>/images/placeholder.svg'">
        </span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<section class="section section--paper-dim">
  <div class="stat-strip fade-up">
    <div class="stat"><div class="num">8+</div><div class="label">Cap Styles</div></div>
    <div class="stat"><div class="num">100%</div><div class="label">COD Nationwide</div></div>
    <div class="stat"><div class="num">48HR</div><div class="label">Karachi Dispatch</div></div>
    <div class="stat"><div class="num">PKR</div><div class="label">Local Pricing</div></div>
  </div>
</section>

<section class="section">
  <div class="section__head fade-up">
    <div><span class="eyebrow">Shop by Category</span><h2>Find Your Fit</h2></div>
    <a href="<?= $B ?>/shop.php" class="btn btn--outline">View All</a>
  </div>
  <div class="grid">
    <?php foreach ($categories as $i => $cat): ?>
      <a href="<?= $B ?>/shop.php?category=<?= e($cat['slug']) ?>" class="card fade-up" style="text-decoration:none; transition-delay:<?= $i*0.07 ?>s;">
        <div class="card__media">
          <img src="<?= $B ?>/images/categories/<?= e($cat['slug']) ?>.jpg"
               alt="<?= e($cat['name']) ?> - streetwear caps collection, RISER Karachi"
               loading="lazy" decoding="async"
               onerror="this.src='<?= $B ?>/images/placeholder.svg'">
        </div>
        <div class="card__body">
          <span class="card__cat">Collection</span>
          <span class="card__name"><?= e($cat['name']) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<?php if (!empty($featured)): ?>
<section class="section section--paper-dim">
  <div class="section__head fade-up">
    <div><span class="eyebrow">Hand-Picked</span><h2>Featured Caps</h2></div>
    <a href="<?= $B ?>/featured.php" class="btn btn--outline">View All Featured</a>
  </div>
  <div class="grid">
    <?php foreach ($featured as $i => $p): ?>
      <?php include __DIR__ . '/includes/product-card.php'; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($newArrivals)): ?>
<section class="section">
  <div class="section__head fade-up">
    <div><span class="eyebrow">Just Dropped</span><h2>New Arrivals</h2></div>
    <a href="<?= $B ?>/new-arrivals.php" class="btn btn--outline">View All New</a>
  </div>
  <div class="grid">
    <?php foreach ($newArrivals as $i => $p): ?>
      <?php include __DIR__ . '/includes/product-card.php'; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section section--dark">
  <div class="about-block fade-up">
    <div>
      <span class="eyebrow">Why RISER</span>
      <h2 style="margin-top:14px;">Built Different.<br>Worn Everywhere.</h2>
      <p style="color:var(--mute-on-dark); max-width:48ch; margin:18px 0 28px;">Every RISER cap is structured, embroidered, and quality-checked before it leaves our Karachi workshop. No middlemen, no inflated prices — just street-ready caps shipped straight to your door with Cash on Delivery.</p>
      <div style="display:flex; gap:14px; flex-wrap:wrap; align-items:center;">
        <a href="<?= $B ?>/about.php" class="btn btn--pill btn--light btn--dark-badge">
          Our Story
          <span class="btn--pill__badge" aria-hidden="true">
            <svg viewBox="0 0 20 20" width="16" height="16" fill="none"><path d="M6 14 14 6M14 6H7M14 6v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
        </a>
        <a href="<?= $B ?>/shop.php" class="btn btn--ghost">Shop Caps</a>
      </div>
    </div>
    <img src="<?= $B ?>/images/about-stack.jpg"
         alt="Stack of RISER caps showing embroidery detail"
         loading="lazy" decoding="async"
         onerror="this.src='<?= $B ?>/images/placeholder.svg'">
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
