<?php
/**
 * RISER Cap Store - Shared Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    // Harden the session cookie: HttpOnly blocks JS access (XSS mitigation),
    // SameSite=Lax blocks it being sent on cross-site form posts (CSRF
    // mitigation, on top of the token check), Secure is auto-enabled when
    // the site is served over HTTPS.
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';

// ---------------------------------------------------
// Formatting helpers
// ---------------------------------------------------
function formatPrice($amount) {
    return CURRENCY_SYMBOL . number_format((float)$amount, 0);
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    return $text ?: 'n-a';
}

// ---------------------------------------------------
// Attaches a compact color-options list to each product row, for the
// hover swatches + quick "Add to Cart" on product cards. One query for
// the whole batch (grid/shop page), not one query per card.
// Each product gets $product['colors'] = [
//   ['color' => 'Black', 'color_hex' => '#111', 'variant_id' => 12, 'in_stock' => true, 'multi_size' => false],
//   ...
// ]
// ---------------------------------------------------
function attachColorOptions(PDO $pdo, array $products) {
    if (empty($products)) return $products;

    $ids = array_unique(array_column($products, 'id'));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT product_id, id AS variant_id, color, color_hex, size, stock
        FROM product_variants
        WHERE product_id IN ($placeholders)
        ORDER BY color, size
    ");
    $stmt->execute($ids);

    // Group by product, then by color — first variant per color becomes the
    // one-tap "quick add" representative; flag multi_size if that color has
    // more than one size, so the UI can send the shopper to the product page
    // to pick a size instead of guessing.
    $byProduct = [];
    foreach ($stmt->fetchAll() as $row) {
        $pid = $row['product_id'];
        $color = $row['color'];
        if (!isset($byProduct[$pid][$color])) {
            $byProduct[$pid][$color] = [
                'color'      => $color,
                'color_hex'  => $row['color_hex'],
                'variant_id' => (int)$row['variant_id'],
                'in_stock'   => (int)$row['stock'] > 0,
                'size_count' => 0,
            ];
        }
        $byProduct[$pid][$color]['size_count']++;
        if ((int)$row['stock'] > 0) $byProduct[$pid][$color]['in_stock'] = true;
    }

    foreach ($products as &$product) {
        $colors = $byProduct[$product['id']] ?? [];
        $product['colors'] = array_map(function ($c) {
            $c['multi_size'] = $c['size_count'] > 1;
            unset($c['size_count']);
            return $c;
        }, array_values($colors));
    }
    unset($product);

    return $products;
}

// ---------------------------------------------------
// CSRF protection — used on all state-changing forms
// (checkout, contact, admin login, product delete, status update)
// ---------------------------------------------------
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function csrfVerify($token) {
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

// ---------------------------------------------------
// Simple login throttling (session-based) — slows brute force
// without needing extra DB tables.
// ---------------------------------------------------
function loginAttemptsRemaining() {
    $data = $_SESSION['login_throttle'] ?? null;
    if (!$data) return 5;
    if (time() - $data['first'] > 900) { // 15 min window resets
        unset($_SESSION['login_throttle']);
        return 5;
    }
    return max(0, 5 - $data['count']);
}

function registerFailedLogin() {
    if (empty($_SESSION['login_throttle']) || (time() - $_SESSION['login_throttle']['first']) > 900) {
        $_SESSION['login_throttle'] = ['count' => 0, 'first' => time()];
    }
    $_SESSION['login_throttle']['count']++;
}

function clearLoginThrottle() {
    unset($_SESSION['login_throttle']);
}

// ---------------------------------------------------
// SEO helpers — keyword-rich defaults targeting Karachi / Pakistan
// cap-store search terms. Pages can override via $pageDescription /
// $pageKeywords before including header.php.
// ---------------------------------------------------
function defaultMetaKeywords() {
    return 'caps in Karachi, caps in Pakistan, caps store in Pakistan, streetwear caps, embroidered caps, embroidered clothes, snapback caps Pakistan, cap brand Karachi';
}

function defaultMetaDescription() {
    return 'RISER is a Karachi-based streetwear caps store — shop embroidered caps, snapbacks, trucker caps and dad hats across Pakistan with Cash on Delivery nationwide.';
}

// ---------------------------------------------------
// Cart helpers (session-based, no login required)
// Cart structure: $_SESSION['cart'][cartKey] = [
//   'variant_id' => n, 'qty' => n,
//   'custom_text' => string|null, 'thread_color' => string|null
// ]
// cartKey is the variant_id (string) for plain items, so repeat adds merge,
// or "{variant_id}-c{random}" for a custom-embroidery line, since every
// custom order is a distinct production job and must not merge with others.
// ---------------------------------------------------
function getCart() {
    return $_SESSION['cart'] ?? [];
}

function cartCount() {
    $cart = getCart();
    $count = 0;
    foreach ($cart as $item) {
        $count += $item['qty'];
    }
    return $count;
}

function addToCart($variantId, $qty = 1, $customText = null, $threadColor = null) {
    $variantId = (int)$variantId;
    $qty = max(1, (int)$qty);

    // Sanitize custom embroidery text: letters, numbers, spaces only, max 12 chars
    $customText = trim((string)$customText);
    if ($customText !== '') {
        $customText = preg_replace('/[^A-Za-z0-9 ]/', '', $customText);
        $customText = trim(mb_substr($customText, 0, 12));
    }
    $threadColor = $threadColor ? preg_replace('/[^A-Za-z0-9 #]/', '', trim((string)$threadColor)) : null;

    if ($customText !== '') {
        // Always a new line — each custom embroidery cap is its own job.
        $key = $variantId . '-c' . bin2hex(random_bytes(4));
        $_SESSION['cart'][$key] = [
            'variant_id'   => $variantId,
            'qty'          => $qty,
            'custom_text'  => $customText,
            'thread_color' => $threadColor ?: null,
        ];
        return $key;
    }

    $key = (string)$variantId;
    if (!isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key] = ['variant_id' => $variantId, 'qty' => 0, 'custom_text' => null, 'thread_color' => null];
    }
    $_SESSION['cart'][$key]['qty'] += $qty;
    return $key;
}

function updateCartQty($cartKey, $qty) {
    $cartKey = (string)$cartKey;
    $qty = (int)$qty;
    if ($qty <= 0) {
        unset($_SESSION['cart'][$cartKey]);
    } elseif (isset($_SESSION['cart'][$cartKey])) {
        $_SESSION['cart'][$cartKey]['qty'] = $qty;
    }
}

function removeFromCart($cartKey) {
    unset($_SESSION['cart'][(string)$cartKey]);
}

function clearCart() {
    $_SESSION['cart'] = [];
}

/**
 * Returns full cart line items joined with product + variant data
 */
function getCartDetails(PDO $pdo) {
    $cart = getCart();
    if (empty($cart)) return [];

    $variantIds = [];
    foreach ($cart as $item) {
        $variantIds[(int)$item['variant_id']] = true;
    }
    $ids = array_keys($variantIds);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT pv.id AS variant_id, pv.size, pv.color, pv.color_hex, pv.stock,
               p.id AS product_id, p.name, p.slug, p.price, p.image
        FROM product_variants pv
        JOIN products p ON p.id = pv.product_id
        WHERE pv.id IN ($placeholders)
    ");
    $stmt->execute($ids);
    $byVariant = [];
    foreach ($stmt->fetchAll() as $row) {
        $byVariant[$row['variant_id']] = $row;
    }

    $details = [];
    foreach ($cart as $cartKey => $item) {
        $variantId = (int)$item['variant_id'];
        if (!isset($byVariant[$variantId])) continue; // variant deleted since added
        $row = $byVariant[$variantId];
        $row['cart_key']      = $cartKey;
        $row['qty']           = $item['qty'];
        $row['custom_text']   = $item['custom_text']   ?? null;
        $row['thread_color']  = $item['thread_color']  ?? null;
        $row['custom_fee']    = $row['custom_text'] ? CUSTOM_EMBROIDERY_FEE : 0;
        $row['line_total']    = ($row['price'] + $row['custom_fee']) * $item['qty'];
        $details[] = $row;
    }
    return $details;
}

function generateOrderNumber() {
    return 'RSR' . date('ymd') . strtoupper(substr(uniqid(), -5));
}
