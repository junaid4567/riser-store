<?php
/**
 * RISER — Cart Actions (AJAX endpoint)
 * Handles add / update / remove operations on the session cart.
 * Always responds with JSON.
 */

require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? 'add';
$response = ['success' => false, 'message' => '', 'cartCount' => cartCount()];

// CSRF check on all mutating cart actions (token is issued in the page header)
if (!csrfVerify($_POST['csrf_token'] ?? '')) {
    $response['message'] = 'Your session expired. Please refresh the page and try again.';
    echo json_encode($response);
    exit;
}

try {
    if ($action === 'add') {
        $variantId   = (int)($_POST['variant_id'] ?? 0);
        $qty         = max(1, (int)($_POST['qty'] ?? 1));
        $customText  = trim((string)($_POST['custom_text'] ?? ''));
        $threadColor = trim((string)($_POST['thread_color'] ?? ''));

        if ($variantId <= 0) {
            $response['message'] = 'Please select a valid size and color.';
            echo json_encode($response);
            exit;
        }

        if ($customText !== '' && mb_strlen($customText) > 12) {
            $response['message'] = 'Custom embroidery text must be 12 characters or fewer.';
            echo json_encode($response);
            exit;
        }

        // Verify variant exists and has stock
        $stmt = $pdo->prepare("SELECT pv.*, p.name FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE pv.id = ?");
        $stmt->execute([$variantId]);
        $variant = $stmt->fetch();

        if (!$variant) {
            $response['message'] = 'This product variant no longer exists.';
            echo json_encode($response);
            exit;
        }

        $cart = getCart();
        $currentQtyInCart = 0;
        foreach ($cart as $item) {
            if ((int)$item['variant_id'] === $variantId) $currentQtyInCart += $item['qty'];
        }

        if (($currentQtyInCart + $qty) > $variant['stock']) {
            $response['message'] = 'Not enough stock available for this selection.';
            echo json_encode($response);
            exit;
        }

        addToCart($variantId, $qty, $customText ?: null, $threadColor ?: null);
        $response['success'] = true;
        $response['message'] = $customText !== ''
            ? $variant['name'] . ' with custom embroidery "' . htmlspecialchars($customText) . '" added to cart'
            : $variant['name'] . ' added to cart';
        $response['cartCount'] = cartCount();

    } elseif ($action === 'update') {
        $cartKey = (string)($_POST['cart_key'] ?? $_POST['variant_id'] ?? '');
        $qty = (int)($_POST['qty'] ?? 0);

        if ($qty > 0 && isset($_SESSION['cart'][$cartKey])) {
            // check stock against the underlying variant
            $stmt = $pdo->prepare("SELECT stock FROM product_variants WHERE id = ?");
            $stmt->execute([(int)$_SESSION['cart'][$cartKey]['variant_id']]);
            $variant = $stmt->fetch();
            if ($variant && $qty > $variant['stock']) {
                $qty = (int)$variant['stock'];
            }
        }

        updateCartQty($cartKey, $qty);
        $response['success'] = true;
        $response['message'] = $qty > 0 ? 'Cart updated' : 'Item removed from cart';
        $response['cartCount'] = cartCount();

    } elseif ($action === 'remove') {
        $cartKey = (string)($_POST['cart_key'] ?? $_POST['variant_id'] ?? '');
        removeFromCart($cartKey);
        $response['success'] = true;
        $response['message'] = 'Item removed from cart';
        $response['cartCount'] = cartCount();

    } else {
        $response['message'] = 'Unknown action.';
    }
} catch (Exception $e) {
    $response['message'] = 'Something went wrong. Please try again.';
}

echo json_encode($response);
