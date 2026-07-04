<?php
require_once __DIR__ . '/auth.php';
requireAdminLogin();

$pageTitle = 'Products';
$activeAdminNav = 'products';

// Handle delete (POST only, CSRF-protected — prevents accidental/forced deletion via a crafted link)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    if (csrfVerify($_POST['csrf_token'] ?? '')) {
        $id = (int)$_POST['delete'];
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: ' . BASE_URL . '/admin/products.php?deleted=1');
    exit;
}

$products = $pdo->query("
    SELECT p.*, c.name AS cat_name,
           (SELECT COALESCE(SUM(stock),0) FROM product_variants WHERE product_id = p.id) AS total_stock
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.created_at DESC
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="admin-topbar">
  <h1>Products</h1>
  <a href="<?= BASE_URL ?>/admin/product-edit.php" class="btn">+ Add New Product</a>
</div>

<?php if (isset($_GET['deleted'])): ?>
  <div class="alert alert--success">Product deleted successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['saved'])): ?>
  <div class="alert alert--success">Product saved successfully.</div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-table-scroll">
<table class="admin-table">
    <thead>
      <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($products as $p): ?>
        <tr>
          <td><img src="<?= BASE_URL ?>/images/products/<?= e($p['image']) ?>" alt="" onerror="this.src='<?= BASE_URL ?>/images/placeholder.svg'"></td>
          <td>
            <?= e($p['name']) ?>
            <?php if ($p['is_featured']): ?><span class="pill pill--confirmed">Featured</span><?php endif; ?>
            <?php if ($p['is_new_arrival']): ?><span class="pill pill--delivered">New</span><?php endif; ?>
          </td>
          <td><?= e($p['cat_name'] ?? '—') ?></td>
          <td><?= formatPrice($p['price']) ?></td>
          <td><?= (int)$p['total_stock'] ?></td>
          <td><?= $p['is_active'] ? '<span class="pill pill--delivered">Active</span>' : '<span class="pill pill--cancelled">Hidden</span>' ?></td>
          <td>
            <div class="admin-btn-row">
              <a href="<?= BASE_URL ?>/admin/product-edit.php?id=<?= (int)$p['id'] ?>" class="btn-small">Edit</a>
              <form method="POST" action="<?= BASE_URL ?>/admin/products.php" style="display:inline;" onsubmit="return confirm('Delete this product permanently? This cannot be undone.');">
                <?= csrfField() ?>
                <input type="hidden" name="delete" value="<?= (int)$p['id'] ?>">
                <button type="submit" class="btn-small danger">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
