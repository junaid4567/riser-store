<?php
require_once __DIR__ . '/auth.php';
requireAdminLogin();

$pageTitle = 'Dashboard';
$activeAdminNav = 'dashboard';

// ---------------------------------------------------------------
// Core stats
// ---------------------------------------------------------------
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$totalOrders   = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$totalRevenue  = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();

// ---------------------------------------------------------------
// Period comparison: last 30 days vs the 30 days before that
// ---------------------------------------------------------------
function periodStats(PDO $pdo, $daysAgoStart, $daysAgoEnd) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS rev
        FROM orders
        WHERE status != 'cancelled'
          AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
          AND created_at <  DATE_SUB(CURDATE(), INTERVAL ? DAY)
    ");
    $stmt->execute([$daysAgoStart, $daysAgoEnd]);
    return $stmt->fetch();
}
$current30 = periodStats($pdo, 30, -1); // last 30 days inclusive of today
$previous  = periodStats($pdo, 60, 30); // the 30 days before that

function pctChange($now, $prev) {
    if ($prev == 0) return $now > 0 ? 100.0 : 0.0;
    return (($now - $prev) / $prev) * 100;
}
$orderTrend   = pctChange($current30['cnt'], $previous['cnt']);
$revenueTrend = pctChange($current30['rev'], $previous['rev']);
$avgOrderNow  = $current30['cnt'] > 0 ? $current30['rev'] / $current30['cnt'] : 0;
$avgOrderPrev = $previous['cnt'] > 0 ? $previous['rev'] / $previous['cnt'] : 0;
$avgOrderTrend = pctChange($avgOrderNow, $avgOrderPrev);

// ---------------------------------------------------------------
// 30-day revenue sparkline data
// ---------------------------------------------------------------
$stmt = $pdo->query("
    SELECT DATE(created_at) AS d, SUM(total) AS rev
    FROM orders
    WHERE status != 'cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(created_at)
");
$revByDate = [];
foreach ($stmt->fetchAll() as $r) { $revByDate[$r['d']] = (float)$r['rev']; }
$sparkValues = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $sparkValues[] = $revByDate[$d] ?? 0;
}
$sparkMax = max($sparkValues) ?: 1;

// ---------------------------------------------------------------
// Orders by day-of-week ("Most Day Active")
// ---------------------------------------------------------------
$stmt = $pdo->query("
    SELECT DAYOFWEEK(created_at) AS dow, COUNT(*) AS cnt
    FROM orders WHERE status != 'cancelled'
    GROUP BY DAYOFWEEK(created_at)
");
$dowCounts = array_fill(1, 7, 0);
foreach ($stmt->fetchAll() as $r) { $dowCounts[(int)$r['dow']] = (int)$r['cnt']; }
$dowLabels = [1=>'Sun',2=>'Mon',3=>'Tue',4=>'Wed',5=>'Thu',6=>'Fri',7=>'Sat'];
$dowMax = max($dowCounts) ?: 1;
$peakDow = array_search($dowMax, $dowCounts);

// ---------------------------------------------------------------
// Orders by province (top 3 + other) — "Customers" segmented bar
// ---------------------------------------------------------------
$stmt = $pdo->query("SELECT province, COUNT(*) AS cnt FROM orders GROUP BY province ORDER BY cnt DESC");
$provinceRows = $stmt->fetchAll();
$provinceTotal = array_sum(array_column($provinceRows, 'cnt')) ?: 1;
$provinceTop = array_slice($provinceRows, 0, 3);
$provinceOtherCount = $provinceTotal - array_sum(array_column($provinceTop, 'cnt'));
$segColors = ['#1c1c1c', '#E8432C', '#d8c4a0', '#c7c2b2'];

// ---------------------------------------------------------------
// Best-selling products (by quantity)
// ---------------------------------------------------------------
$bestSelling = $pdo->query("
    SELECT product_name, SUM(quantity) AS sold, SUM(line_total) AS revenue
    FROM order_items
    GROUP BY product_id, product_name
    ORDER BY sold DESC
    LIMIT 5
")->fetchAll();

// ---------------------------------------------------------------
// Repeat customer rate (by phone number)
// ---------------------------------------------------------------
$stmt = $pdo->query("SELECT phone, COUNT(*) AS cnt FROM orders GROUP BY phone");
$phoneRows = $stmt->fetchAll();
$totalCustomers = count($phoneRows);
$repeatCustomers = count(array_filter($phoneRows, fn($r) => $r['cnt'] > 1));
$repeatRate = $totalCustomers > 0 ? round(($repeatCustomers / $totalCustomers) * 100) : 0;

// ---------------------------------------------------------------
// Live Embroidery Customizer adoption — unique RISER metric
// ---------------------------------------------------------------
$totalItems = (int)$pdo->query("SELECT COUNT(*) FROM order_items")->fetchColumn();
$customItems = (int)$pdo->query("SELECT COUNT(*) FROM order_items WHERE custom_text IS NOT NULL AND custom_text != ''")->fetchColumn();
$embroAdoption = $totalItems > 0 ? round(($customItems / $totalItems) * 100) : 0;

$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 6")->fetchAll();

$lowStock = $pdo->query("
    SELECT pv.*, p.name AS product_name FROM product_variants pv
    JOIN products p ON p.id = pv.product_id
    WHERE pv.stock <= 5
    ORDER BY pv.stock ASC
    LIMIT 6
")->fetchAll();

include __DIR__ . '/includes/header.php';

function trendBadge($pct) {
    $dir = $pct >= 0 ? 'up' : 'down';
    $arrow = $pct >= 0 ? '&#9650;' : '&#9660;';
    return '<span class="stat-trend ' . $dir . '">' . $arrow . ' ' . number_format(abs($pct), 1) . '%</span>';
}
?>

<div class="admin-topbar">
  <div>
    <h1>Dashboard</h1>
    <span class="sub">Welcome back, <?= e($_SESSION['admin_username']) ?> — last 30 days vs. previous 30 days</span>
  </div>
  <a href="<?= BASE_URL ?>/admin/export-orders.php" class="export-btn">&#8595; Export Orders CSV</a>
</div>

<div class="admin-stats">
  <div class="admin-stat">
    <div class="label">Orders (30d)</div>
    <div class="value"><?= (int)$current30['cnt'] ?> <?= trendBadge($orderTrend) ?></div>
    <div class="sub">vs <?= (int)$previous['cnt'] ?> last period</div>
  </div>
  <div class="admin-stat">
    <div class="label">Revenue (30d)</div>
    <div class="value"><?= formatPrice($current30['rev']) ?></div>
    <div class="sub"><?= trendBadge($revenueTrend) ?> vs last period</div>
  </div>
  <div class="admin-stat">
    <div class="label">Avg Order Value</div>
    <div class="value"><?= formatPrice($avgOrderNow) ?></div>
    <div class="sub"><?= trendBadge($avgOrderTrend) ?> vs last period</div>
  </div>
  <div class="admin-stat">
    <div class="label">Pending Orders</div>
    <div class="value"><?= $pendingOrders ?></div>
    <div class="sub"><?= $totalProducts ?> active products</div>
  </div>
</div>

<div class="dash-grid">
  <div>
    <div class="widget-card">
      <div class="flex-between">
        <div>
          <h3>Total Profit</h3>
          <div class="big-num"><?= formatPrice($totalRevenue) ?></div>
          <div><?= trendBadge($revenueTrend) ?> <span class="text-mute" style="font-size:0.8rem;">vs last period</span></div>
        </div>
      </div>
      <?php
        $w = 700; $h = 140; $pad = 6;
        $pts = [];
        $n = count($sparkValues);
        foreach ($sparkValues as $i => $v) {
            $x = $pad + ($i / max(1, $n - 1)) * ($w - $pad * 2);
            $y = $h - $pad - ($v / $sparkMax) * ($h - $pad * 2);
            $pts[] = round($x, 1) . ',' . round($y, 1);
        }
        $linePath = 'M' . implode(' L', $pts);
        $areaPath = $linePath . " L{$w},{$h} L0,{$h} Z";
      ?>
      <svg viewBox="0 0 <?= $w ?> <?= $h ?>" style="width:100%; height:140px; margin-top:14px;" preserveAspectRatio="none">
        <path d="<?= $areaPath ?>" fill="rgba(232,67,44,0.10)" stroke="none"></path>
        <path d="<?= $linePath ?>" fill="none" stroke="#E8432C" stroke-width="2.5"></path>
      </svg>
      <div class="text-mute" style="font-family:var(--font-mono); font-size:0.7rem; display:flex; justify-content:space-between;">
        <span><?= date('M j', strtotime('-29 days')) ?></span><span>Today</span>
      </div>
    </div>

    <div class="widget-card">
      <div class="flex-between" style="margin-bottom:6px;">
        <h3>Best Selling Products</h3>
      </div>
      <?php if (empty($bestSelling)): ?>
        <p class="text-mute">No sales yet.</p>
      <?php else: ?>
        <div class="admin-table-scroll">
<table class="admin-table">
          <thead><tr><th>Product</th><th>Sold</th><th>Revenue</th></tr></thead>
          <tbody>
            <?php foreach ($bestSelling as $b): ?>
              <tr>
                <td><?= e($b['product_name']) ?></td>
                <td><?= (int)$b['sold'] ?> sold</td>
                <td><?= formatPrice($b['revenue']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
</div>
      <?php endif; ?>
    </div>

    <div class="widget-card">
      <h3>Orders by Region</h3>
      <div class="segbar">
        <?php foreach ($provinceTop as $i => $p): ?>
          <span style="width:<?= round(($p['cnt']/$provinceTotal)*100, 2) ?>%; background:<?= $segColors[$i] ?>;"></span>
        <?php endforeach; ?>
        <?php if ($provinceOtherCount > 0): ?>
          <span style="width:<?= round(($provinceOtherCount/$provinceTotal)*100, 2) ?>%; background:<?= $segColors[3] ?>;"></span>
        <?php endif; ?>
      </div>
      <div class="seg-legend">
        <?php foreach ($provinceTop as $i => $p): ?>
          <span><i style="background:<?= $segColors[$i] ?>;"></i><?= e($p['province']) ?> (<?= $p['cnt'] ?>)</span>
        <?php endforeach; ?>
        <?php if ($provinceOtherCount > 0): ?>
          <span><i style="background:<?= $segColors[3] ?>;"></i>Other (<?= $provinceOtherCount ?>)</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div>
    <div class="widget-card">
      <div class="flex-between"><h3>Most Day Active</h3></div>
      <div class="weekday-chart">
        <?php foreach ($dowLabels as $dow => $label): ?>
          <?php $count = $dowCounts[$dow]; $pctH = max(6, round(($count / $dowMax) * 100)); ?>
          <div class="bar-col <?= $dow === $peakDow ? 'peak' : '' ?>">
            <div class="bar <?= $dow === $peakDow ? 'peak' : '' ?>" style="height:<?= $pctH ?>%;" title="<?= $count ?> orders"></div>
            <div class="bar-label"><?= $label ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="widget-card">
      <h3>Repeat Customer Rate</h3>
      <div class="donut-wrap">
        <div class="donut" style="background: conic-gradient(#E8432C 0% <?= $repeatRate ?>%, #E9E4D6 <?= $repeatRate ?>% 100%);">
          <div class="donut-inner">
            <span class="pct"><?= $repeatRate ?>%</span>
            <span class="pct-sub"><?= $repeatCustomers ?> of <?= $totalCustomers ?> customers ordered again</span>
          </div>
        </div>
      </div>
    </div>

    <div class="widget-card">
      <h3>Store Insights</h3>
      <ul class="insight-list">
        <li><span class="dot"></span><span><strong><?= $embroAdoption ?>%</strong> of items ordered use the Live Embroidery Customizer.</span></li>
        <li><span class="dot"></span><span><strong><?= count($lowStock) ?></strong> variant<?= count($lowStock) === 1 ? '' : 's' ?> at 5 units or fewer in stock.</span></li>
        <li><span class="dot"></span><span>Most orders land on <strong><?= $dowLabels[$peakDow] ?></strong> — plan dispatch capacity around it.</span></li>
        <li><span class="dot"></span><span><strong><?= $pendingOrders ?></strong> order<?= $pendingOrders === 1 ? '' : 's' ?> waiting on confirmation right now.</span></li>
      </ul>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="flex-between" style="margin-bottom:18px;">
    <h3>Recent Orders</h3>
    <a href="<?= BASE_URL ?>/admin/orders.php" class="btn-small">View All</a>
  </div>
  <?php if (empty($recentOrders)): ?>
    <p class="text-mute">No orders yet.</p>
  <?php else: ?>
    <div class="admin-table-scroll">
<table class="admin-table">
      <thead>
        <tr><th>Order #</th><th>Customer</th><th>City</th><th>Total</th><th>Status</th><th>Date</th></tr>
      </thead>
      <tbody>
        <?php foreach ($recentOrders as $o): ?>
          <tr>
            <td><a href="<?= BASE_URL ?>/admin/order-detail.php?id=<?= (int)$o['id'] ?>"><?= e($o['order_number']) ?></a></td>
            <td><?= e($o['customer_name']) ?></td>
            <td><?= e($o['city']) ?></td>
            <td><?= formatPrice($o['total']) ?></td>
            <td><span class="pill pill--<?= e($o['status']) ?>"><?= e($o['status']) ?></span></td>
            <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
</div>
  <?php endif; ?>
</div>

<?php if (!empty($lowStock)): ?>
<div class="admin-card">
  <h3 style="margin-bottom:18px;">Low Stock Alert</h3>
  <div class="admin-table-scroll">
<table class="admin-table">
    <thead><tr><th>Product</th><th>Variant</th><th>Stock Left</th></tr></thead>
    <tbody>
      <?php foreach ($lowStock as $v): ?>
        <tr>
          <td><?= e($v['product_name']) ?></td>
          <td><?= e($v['size']) ?> / <?= e($v['color']) ?></td>
          <td><strong style="color:var(--riser-red-deep)"><?= (int)$v['stock'] ?></strong></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
