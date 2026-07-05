<?php
/**
 * Shared site header — included on every customer-facing page.
 * Expects $pageTitle and optionally $activeNav, $pageDescription,
 * $pageKeywords, $noIndex to be set before include.
 */
$activeNav = $activeNav ?? '';
$cartCount = cartCount();
$B = BASE_URL; // shorthand for this template

$metaDescription = $pageDescription ?? defaultMetaDescription();
$metaKeywords    = $pageKeywords ?? defaultMetaKeywords();
$canonicalUrl    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
                    . ($_SERVER['HTTP_HOST'] ?? 'riser.pk') . ($_SERVER['REQUEST_URI'] ?? '');

$orgSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'ClothingStore',
    'name' => 'RISER',
    'description' => 'Streetwear cap store in Karachi, Pakistan — embroidered caps, snapbacks, trucker caps and dad hats with nationwide Cash on Delivery.',
    'image' => $B . '/images/products/classic-black.jpg',
    'url' => $B ?: '/',
    'address' => [
        '@type' => 'PostalAddress',
        'addressLocality' => 'Karachi',
        'addressRegion' => 'Sindh',
        'addressCountry' => 'PK',
    ],
    'areaServed' => 'Pakistan',
    'priceRange' => 'Rs. 1800 - Rs. 3000',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'RISER') ?> | RISER — Caps Store in Karachi &amp; All Pakistan</title>
<meta name="description" content="<?= e($metaDescription) ?>">
<meta name="keywords" content="<?= e($metaKeywords) ?>">
<meta name="author" content="RISER">
<?php if (!empty($noIndex)): ?>
<meta name="robots" content="noindex, nofollow">
<?php else: ?>
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= e($canonicalUrl) ?>">
<?php endif; ?>

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="RISER">
<meta property="og:title" content="<?= e($pageTitle ?? 'RISER') ?> | RISER Caps Pakistan">
<meta property="og:description" content="<?= e($metaDescription) ?>">
<meta property="og:locale" content="en_PK">
<meta property="og:image" content="<?= $B ?>/images/products/classic-black.jpg">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle ?? 'RISER') ?> | RISER Caps Pakistan">
<meta name="twitter:description" content="<?= e($metaDescription) ?>">

<meta name="geo.placename" content="Karachi, Pakistan">
<meta name="geo.region" content="PK-SD">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= assetUrl('/css/style.css') ?>">
<script type="application/ld+json"><?= json_encode($orgSchema, JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body>
<a href="#main" class="skip-link">Skip to content</a>

<div class="site-frame">

<header class="site-header">
  <nav class="nav">
    <a href="<?= $B ?>/index.php" class="logo">
      <span class="logo__mark" aria-hidden="true">R</span>
      <span class="logo__word">RISER</span>
    </a>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <div class="nav__pill" id="navPill">
      <span class="nav__indicator" id="navIndicator" aria-hidden="true"></span>
      <ul class="nav__links" id="navLinksPill">
        <li><a href="<?= $B ?>/index.php"       class="<?= $activeNav==='home'     ? 'active':'' ?>">Home</a></li>
        <li><a href="<?= $B ?>/shop.php"        class="<?= $activeNav==='shop'     ? 'active':'' ?>">Shop</a></li>
        <li><a href="<?= $B ?>/featured.php"    class="<?= $activeNav==='featured' ? 'active':'' ?>">Featured</a></li>
        <li><a href="<?= $B ?>/new-arrivals.php" class="<?= $activeNav==='new'    ? 'active':'' ?>">New Arrivals</a></li>
        <li><a href="<?= $B ?>/about.php"       class="<?= $activeNav==='about'    ? 'active':'' ?>">About</a></li>
        <li><a href="<?= $B ?>/contact.php"     class="<?= $activeNav==='contact'  ? 'active':'' ?>">Contact</a></li>
      </ul>
    </div>

    <div class="nav__actions">
      <a href="<?= $B ?>/cart.php" class="cart-link">
        <svg viewBox="0 0 20 20" fill="none" width="18" height="18"><path d="M4 6h12l-1 10.5a1 1 0 0 1-1 .9H6a1 1 0 0 1-1-.9L4 6Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M7 6a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="1.6"/></svg>
        <?php if ($cartCount > 0): ?>
          <span class="cart-badge" id="cartBadge"><?= (int)$cartCount ?></span>
        <?php else: ?>
          <span class="cart-badge" id="cartBadge" style="display:none;">0</span>
        <?php endif; ?>
      </a>
    </div>
  </nav>

  <!-- Full-screen mobile menu overlay: circular close button + clean list nav -->
  <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
  <div class="mobile-menu" id="mobileMenu" aria-hidden="true">
    <button class="mobile-menu__close" id="mobileMenuClose" aria-label="Close menu">&times;</button>
    <ul class="mobile-menu__list">
      <li style="--i:0"><a href="<?= $B ?>/index.php"        class="<?= $activeNav==='home'     ? 'active':'' ?>">Home</a></li>
      <li style="--i:1"><a href="<?= $B ?>/shop.php"         class="<?= $activeNav==='shop'     ? 'active':'' ?>">View All</a></li>
      <li style="--i:2"><a href="<?= $B ?>/featured.php"     class="<?= $activeNav==='featured' ? 'active':'' ?>">Featured</a></li>
      <li style="--i:3"><a href="<?= $B ?>/new-arrivals.php" class="<?= $activeNav==='new'      ? 'active':'' ?>">New Arrivals</a></li>
      <li style="--i:4"><a href="<?= $B ?>/about.php"        class="<?= $activeNav==='about'    ? 'active':'' ?>">About</a></li>
      <li style="--i:5"><a href="<?= $B ?>/contact.php"      class="<?= $activeNav==='contact'  ? 'active':'' ?>">Contact</a></li>
      <li style="--i:6"><a href="<?= $B ?>/cart.php">Cart<?= $cartCount > 0 ? ' (' . (int)$cartCount . ')' : '' ?></a></li>
    </ul>
  </div>
</header>

<div class="ticker" aria-hidden="true">
  <div class="ticker__track">
    <span>Cash on Delivery Nationwide</span>
    <span>Embroidered Caps in Karachi</span>
    <span>Free Shipping Over Rs. 5000</span>
    <span>New Drops Every Month</span>
    <span>Cash on Delivery Nationwide</span>
    <span>Embroidered Caps in Karachi</span>
    <span>Free Shipping Over Rs. 5000</span>
    <span>New Drops Every Month</span>
  </div>
</div>
<main id="main">
