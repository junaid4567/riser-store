<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'New Arrivals';
$pageDescription = 'Just dropped — new embroidered caps and snapbacks from RISER, a streetwear caps store in Karachi shipping nationwide across Pakistan.';
$activeNav = 'new';

$products = $pdo->query("
    SELECT p.*, c.name AS cat_name FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_new_arrival = 1 AND p.is_active = 1
    ORDER BY p.created_at DESC
")->fetchAll();
$products = attachColorOptions($pdo, $products);

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Home</a> / New Arrivals</div>
  <h1>New Arrivals</h1>
  <p style="color:var(--mute-on-dark); margin-top:14px; max-width:50ch;">Fresh off the embroidery machine. The latest drops from RISER.</p>
</section>

<section class="section">
  <?php if (empty($products)): ?>
    <div class="empty-state">No new arrivals right now — check back soon.</div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($products as $i => $p): ?>
        <?php include __DIR__ . '/includes/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
