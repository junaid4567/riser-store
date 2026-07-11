<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'About RISER — Karachi Cap Brand';
$pageDescription = 'RISER is a Karachi-based streetwear caps store. Learn how we design and embroider caps locally and ship Cash on Delivery across Pakistan.';
$activeNav = 'about';
include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Home</a> / About</div>
  <h1>About RISER</h1>
</section>

<section class="section">
  <div class="about-block fade-up">
    <div>
      <span class="eyebrow">Our Story</span>
      <h2 style="margin-top:14px;">Started in Karachi.<br>Built for Pakistan.</h2>
      <p style="color:#3a3a3a; margin:20px 0; max-width:50ch;">RISER began in a small embroidery shop in Karachi with one idea: Pakistan's streetwear scene deserved caps that matched the energy of its streets, without the import markup. We design every silhouette in-house, embroider locally, and ship Cash on Delivery so anyone — from Gilgit to Gwadar — can wear the rise.</p>
      <p style="color:#3a3a3a; max-width:50ch;">No overseas factories. No middlemen. Just a small team that obsesses over stitch density, brim curve, and fit — so every cap leaving our workshop is one we'd wear ourselves.</p>
    </div>
    <img src="<?= BASE_URL ?>/images/about-workshop.jpg" alt="RISER embroidery workshop in Karachi" loading="lazy" decoding="async" onerror="this.src='<?= BASE_URL ?>/images/placeholder.svg'">
  </div>
</section>

<section class="section section--paper-dim">
  <div class="section__head fade-up">
    <div>
      <span class="eyebrow">What We Stand For</span>
      <h2>The RISER Standard</h2>
    </div>
  </div>
  <div class="value-grid">
    <div class="value-card fade-up">
      <div class="num">LOCAL</div>
      <h3>Made in Pakistan</h3>
      <p style="color:#3a3a3a; margin-top:12px;">Every cap is cut, structured, and embroidered locally — supporting Pakistani craftsmanship at every step.</p>
    </div>
    <div class="value-card fade-up" style="transition-delay:.08s;">
      <div class="num">HONEST</div>
      <h3>Fair Pricing</h3>
      <p style="color:#3a3a3a; margin-top:12px;">No import tax, no inflated "international brand" pricing. Just quality caps priced for the people who'll actually wear them.</p>
    </div>
    <div class="value-card fade-up" style="transition-delay:.16s;">
      <div class="num">EASY</div>
      <h3>Cash on Delivery</h3>
      <p style="color:#3a3a3a; margin-top:12px;">Order with zero risk. Pay only when your cap is in your hands — nationwide, every time.</p>
    </div>
  </div>
</section>

<section class="section section--dark text-center">
  <div class="fade-up">
    <span class="eyebrow">Ready to Rise?</span>
    <h2 style="margin:16px 0 24px;">Find Your Cap Today</h2>
    <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
      <a href="<?= BASE_URL ?>/shop.php" class="btn btn--accent">Shop the Collection</a>
      <a href="<?= BASE_URL ?>/contact.php" class="btn btn--outline">Get in Touch</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
