<?php
/**
 * RISER — Dynamic XML Sitemap
 * Lists static pages + every active product + every category for SEO crawling.
 */
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/xml; charset=utf-8');

$base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
        . ($_SERVER['HTTP_HOST'] ?? 'riser.pk') . BASE_URL;

$staticPages = [
    ['loc' => '/index.php', 'priority' => '1.0'],
    ['loc' => '/shop.php', 'priority' => '0.9'],
    ['loc' => '/featured.php', 'priority' => '0.7'],
    ['loc' => '/new-arrivals.php', 'priority' => '0.7'],
    ['loc' => '/about.php', 'priority' => '0.5'],
    ['loc' => '/contact.php', 'priority' => '0.5'],
];

$products = $pdo->query("SELECT slug, created_at FROM products WHERE is_active = 1")->fetchAll();
$categories = $pdo->query("SELECT slug FROM categories")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($staticPages as $page) {
    echo "  <url><loc>" . htmlspecialchars($base . $page['loc']) . "</loc><priority>{$page['priority']}</priority></url>\n";
}
foreach ($categories as $cat) {
    echo "  <url><loc>" . htmlspecialchars($base . '/shop.php?category=' . $cat['slug']) . "</loc><priority>0.6</priority></url>\n";
}
foreach ($products as $p) {
    $lastmod = date('Y-m-d', strtotime($p['created_at']));
    echo "  <url><loc>" . htmlspecialchars($base . '/product.php?slug=' . $p['slug']) . "</loc><lastmod>$lastmod</lastmod><priority>0.8</priority></url>\n";
}

echo '</urlset>';
