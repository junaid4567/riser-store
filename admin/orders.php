<?php
require_once __DIR__ . '/auth.php';
requireAdminLogin();

$pageTitle = 'Orders';
$activeAdminNav = 'orders';

$statusFilter = $_GET['status'] ?? '';
$validStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

$sql = "SELECT * FROM orders";
$params = [];
if (in_array($statusFilter, $validStatuses, true)) {
    $sql .= " WHERE status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="admin-topbar">
  <h1>Orders</h1>
  <a href="<?= BASE_URL ?>/admin/export-orders.php" class="btn-small">⬇ Export CSV</a>
</div>

<div class="admin-card" style="display:flex; gap:10px; flex-wrap:wrap;">
  <a href="<?= BASE_URL ?>/admin/orders.php" class="btn-small <?= $statusFilter === '' ? 'danger' : '' ?>">All</a>
  <?php foreach ($validStatuses as $s): ?>
    <a href="<?= BASE_URL ?>/admin/orders.php?status=<?= e($s) ?>" class="btn-small <?= $statusFilter === $s ? 'danger' : '' ?>"><?= ucfirst($s) ?></a>
  <?php endforeach; ?>
</div>

<div class="admin-card">
  <?php if (empty($orders)): ?>
    <p class="text-mute">No orders found.</p>
  <?php else: ?>
    <div class="admin-table-scroll">
<table class="admin-table">
      <thead>
        <tr><th>Order #</th><th>Customer</th><th>Phone</th><th>City</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><?= e($o['order_number']) ?></td>
            <td><?= e($o['customer_name']) ?></td>
            <td><?= e($o['phone']) ?></td>
            <td><?= e($o['city']) ?></td>
            <td><?= formatPrice($o['total']) ?></td>
            <td><span class="pill pill--<?= e($o['status']) ?>"><?= e($o['status']) ?></span></td>
            <td><?= date('M j, Y g:ia', strtotime($o['created_at'])) ?></td>
            <td><a href="<?= BASE_URL ?>/admin/order-detail.php?id=<?= (int)$o['id'] ?>" class="btn-small">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
</div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
