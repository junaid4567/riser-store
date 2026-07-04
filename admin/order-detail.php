<?php
require_once __DIR__ . '/auth.php';
requireAdminLogin();

$orderId = (int)($_GET['id'] ?? 0);
$pageTitle = 'Order Detail';
$activeAdminNav = 'orders';

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

$validStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'] ?? '';
    if (csrfVerify($_POST['csrf_token'] ?? '') && in_array($newStatus, $validStatuses, true)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        $order['status'] = $newStatus;
    }
}

$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="admin-topbar">
  <h1>Order #<?= e($order['order_number']) ?></h1>
  <a href="<?= BASE_URL ?>/admin/orders.php" class="btn-small">&larr; Back to Orders</a>
</div>

<div class="admin-card">
  <div class="flex-between" style="margin-bottom:18px; flex-wrap:wrap; gap:14px;">
    <span class="pill pill--<?= e($order['status']) ?>" style="font-size:0.8rem; padding:6px 14px;"><?= e($order['status']) ?></span>
    <form method="POST" style="display:flex; gap:10px;">
      <?= csrfField() ?>
      <select name="status" style="border:1px solid var(--line-dark); padding:8px 12px; font-family:var(--font-mono); font-size:0.82rem;">
        <?php foreach ($validStatuses as $s): ?>
          <option value="<?= e($s) ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-small">Update Status</button>
    </form>
  </div>

  <div class="form-grid">
    <div>
      <h4 style="font-family:var(--font-mono); font-size:0.75rem; text-transform:uppercase; color:var(--mute); margin-bottom:6px;">Customer</h4>
      <p><?= e($order['customer_name']) ?></p>
      <p><?= e($order['phone']) ?></p>
      <?php if ($order['email']): ?><p><?= e($order['email']) ?></p><?php endif; ?>
    </div>
    <div>
      <h4 style="font-family:var(--font-mono); font-size:0.75rem; text-transform:uppercase; color:var(--mute); margin-bottom:6px;">Delivery Address</h4>
      <p><?= e($order['address']) ?></p>
      <p><?= e($order['city']) ?>, <?= e($order['province']) ?> <?= e($order['postal_code']) ?></p>
    </div>
    <?php if ($order['notes']): ?>
    <div class="full">
      <h4 style="font-family:var(--font-mono); font-size:0.75rem; text-transform:uppercase; color:var(--mute); margin-bottom:6px;">Order Notes</h4>
      <p><?= nl2br(e($order['notes'])) ?></p>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="admin-card">
  <h3 style="margin-bottom:18px;">Items Ordered</h3>
  <div class="admin-table-scroll">
<table class="admin-table">
    <thead><tr><th>Product</th><th>Size / Color</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= e($item['product_name']) ?><?php if (!empty($item['custom_text'])): ?><br><span class="text-mute" style="font-family:var(--font-mono); font-size:0.72rem;">Embroidered: "<?= e($item['custom_text']) ?>"</span><?php endif; ?></td>
          <td><?= e($item['size']) ?> / <?= e($item['color']) ?></td>
          <td><?= formatPrice($item['price']) ?></td>
          <td><?= (int)$item['quantity'] ?></td>
          <td><?= formatPrice($item['line_total']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

  <div style="max-width:300px; margin-left:auto; margin-top:20px;">
    <div class="summary-row"><span>Subtotal</span><span><?= formatPrice($order['subtotal']) ?></span></div>
    <div class="summary-row"><span>Shipping</span><span><?= formatPrice($order['shipping_fee']) ?></span></div>
    <div class="summary-row total"><span>Total (COD)</span><span><?= formatPrice($order['total']) ?></span></div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
