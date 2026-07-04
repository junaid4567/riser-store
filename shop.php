<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Shop All Caps';
$pageDescription = 'Browse the full RISER collection — streetwear caps, embroidered caps, snapbacks, trucker caps and dad hats. Caps store in Pakistan with nationwide Cash on Delivery.';
$activeNav = 'shop';

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// ---- Build filtered query ----
$where = ['p.is_active = 1'];
$params = [];

$selectedCategories = isset($_GET['category']) ? (array)$_GET['category'] : [];
$selectedCategories = array_filter($selectedCategories);

if (!empty($selectedCategories)) {
    $placeholders = implode(',', array_fill(0, count($selectedCategories), '?'));
    $where[] = "c.slug IN ($placeholders)";
    foreach ($selectedCategories as $slug) $params[] = $slug;
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $where[] = "p.name LIKE ?";
    $params[] = '%' . $search . '%';
}

$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
if ($minPrice !== null) { $where[] = "p.price >= ?"; $params[] = $minPrice; }
if ($maxPrice !== null) { $where[] = "p.price <= ?"; $params[] = $maxPrice; }

$sort = $_GET['sort'] ?? 'newest';
$orderBy = match ($sort) {
    'price_low'  => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name'       => 'p.name ASC',
    default      => 'p.created_at DESC',
};

$whereSql = implode(' AND ', $where);
$sql = "
    SELECT p.*, c.name AS cat_name, c.slug AS cat_slug
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE $whereSql
    ORDER BY $orderBy
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$products = attachColorOptions($pdo, $products);

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Home</a> / Shop</div>
  <h1>All Caps</h1>
</section>

<section class="section">
  <div class="shop-layout">
    <aside class="filters">
      <form method="GET" action="<?= BASE_URL ?>/shop.php" id="filterForm">
        <h4>Search</h4>
        <div class="filter-group">
          <input type="text" name="q" placeholder="Search caps..." value="<?= e($search) ?>"
                 style="width:100%; padding:10px 12px; border:2px solid var(--line-dark); font-family:var(--font-body);">
        </div>

        <h4>Category</h4>
        <div class="filter-group">
          <?php foreach ($categories as $cat): ?>
            <label>
              <input type="checkbox" name="category[]" value="<?= e($cat['slug']) ?>"
                <?= in_array($cat['slug'], $selectedCategories) ? 'checked' : '' ?>>
              <?= e($cat['name']) ?>
            </label>
          <?php endforeach; ?>
        </div>

        <h4>Price Range (PKR)</h4>
        <div class="filter-group" style="display:flex; gap:10px;">
          <input type="number" name="min_price" placeholder="Min" value="<?= e($minPrice ?? '') ?>"
                 style="width:100%; padding:10px; border:2px solid var(--line-dark);">
          <input type="number" name="max_price" placeholder="Max" value="<?= e($maxPrice ?? '') ?>"
                 style="width:100%; padding:10px; border:2px solid var(--line-dark);">
        </div>

        <div class="filter-group">
          <button type="submit" class="btn btn--full">Apply Filters</button>
          <a href="<?= BASE_URL ?>/shop.php" class="btn btn--outline btn--full" style="margin-top:10px;">Clear All</a>
        </div>
      </form>
    </aside>

    <div>
      <div class="toolbar">
        <span><?= count($products) ?> cap<?= count($products) !== 1 ? 's' : '' ?> found</span>
        <form method="GET" action="<?= BASE_URL ?>/shop.php" id="sortForm">
          <?php foreach ($selectedCategories as $c): ?><input type="hidden" name="category[]" value="<?= e($c) ?>"><?php endforeach; ?>
          <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif; ?>
          <?php if ($minPrice !== null): ?><input type="hidden" name="min_price" value="<?= e($minPrice) ?>"><?php endif; ?>
          <?php if ($maxPrice !== null): ?><input type="hidden" name="max_price" value="<?= e($maxPrice) ?>"><?php endif; ?>
          <select name="sort" onchange="document.getElementById('sortForm').submit()">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name: A-Z</option>
          </select>
        </form>
      </div>

      <?php if (empty($products)): ?>
        <div class="empty-state">
          No caps match your filters.<br><br>
          <a href="<?= BASE_URL ?>/shop.php" class="btn btn--outline">Clear Filters</a>
        </div>
      <?php else: ?>
        <div class="grid">
          <?php foreach ($products as $i => $p): ?>
            <?php include __DIR__ . '/includes/product-card.php'; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
