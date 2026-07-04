<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Featured Caps';
$pageDescription = 'Hand-picked embroidered caps from RISER — Karachi\'s streetwear caps store. Shop featured snapbacks and trucker caps with Cash on Delivery across Pakistan.';
$activeNav = 'featured';

$products = $pdo->query("
    SELECT p.*, c.name AS cat_name FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_featured = 1 AND p.is_active = 1
    ORDER BY p.created_at DESC
")->fetchAll();
$products = attachColorOptions($pdo, $products);

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Home</a> / Featured</div>
  <h1>Featured Caps</h1>
  <p style="color:var(--mute-on-dark); margin-top:14px; max-width:50ch;">Our hand-picked standouts — the styles that define RISER this season.</p>
</section>

<section class="section">
  <?php if (empty($products)): ?>
    <div class="empty-state">No featured caps right now — check back soon.</div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($products as $i => $p): ?>
        <?php include __DIR__ . '/includes/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
