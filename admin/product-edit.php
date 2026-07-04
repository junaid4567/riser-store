<?php
require_once __DIR__ . '/auth.php';
requireAdminLogin();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$pageTitle = $productId ? 'Edit Product' : 'Add Product';
$activeAdminNav = 'products';

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$product = [
    'name' => '', 'slug' => '', 'description' => '', 'price' => '', 'compare_price' => '',
    'category_id' => '', 'image' => '', 'is_featured' => 0, 'is_new_arrival' => 0, 'is_active' => 1,
];
$variants = [];
$errors = [];

if ($productId) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        header('Location: ' . BASE_URL . '/admin/products.php');
        exit;
    }
    $product = $existing;

    $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id");
    $stmt->execute([$productId]);
    $variants = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Your session expired. Please try saving again.';
    }

    $product['name'] = trim($_POST['name'] ?? '');
    $product['description'] = trim($_POST['description'] ?? '');
    $product['price'] = $_POST['price'] ?? '';
    $product['compare_price'] = $_POST['compare_price'] ?? '';
    $product['category_id'] = $_POST['category_id'] ?? '';
    $product['image'] = trim($_POST['image'] ?? '') ?: 'placeholder.jpg';
    $product['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
    $product['is_new_arrival'] = isset($_POST['is_new_arrival']) ? 1 : 0;
    $product['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    // ---------------------------------------------------------------
    // Direct product image upload — lets an admin pick a file from
    // their device instead of manually FTP-ing it and typing the
    // filename. Falls back to the manual filename field if no file
    // was chosen, so both workflows keep working.
    // ---------------------------------------------------------------
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['image'] = 'Upload failed. Please try a different image.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors['image'] = 'Image must be 5MB or smaller.';
        } else {
            // Verify the actual file content, not just the extension/browser-reported
            // type — this stops someone renaming a malicious file to .jpg.
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            if (!isset($allowed[$mime])) {
                $errors['image'] = 'Please upload a JPG, PNG, or WEBP image.';
            } else {
                $ext = $allowed[$mime];
                $uploadDir = __DIR__ . '/../images/products/';
                $safeName = slugify($product['name'] ?: 'product') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

                if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                    $errors['image'] = 'Server cannot save images right now (images/products/ is not writable).';
                } elseif (!move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {
                    $errors['image'] = 'Could not save the uploaded image. Please try again.';
                } else {
                    $product['image'] = $safeName;
                }
            }
        }
    }

    if ($product['name'] === '') $errors['name'] = 'Product name is required.';
    if (!is_numeric($product['price']) || $product['price'] <= 0) $errors['price'] = 'Enter a valid price.';

    $variantSizes = $_POST['variant_size'] ?? [];
    $variantColors = $_POST['variant_color'] ?? [];
    $variantHex = $_POST['variant_hex'] ?? [];
    $variantStock = $_POST['variant_stock'] ?? [];
    $variantSku = $_POST['variant_sku'] ?? [];

    $newVariants = [];
    foreach ($variantSizes as $i => $size) {
        $size = trim($size);
        $color = trim($variantColors[$i] ?? '');
        if ($size === '' && $color === '') continue; // skip empty rows
        $newVariants[] = [
            'size' => $size ?: 'One Size',
            'color' => $color ?: 'Default',
            'color_hex' => $variantHex[$i] ?: '#000000',
            'stock' => max(0, (int)($variantStock[$i] ?? 0)),
            'sku' => trim($variantSku[$i] ?? '') ?: null,
        ];
    }
    if (empty($newVariants)) {
        $errors['variants'] = 'Add at least one size/color variant.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $slug = slugify($product['name']);
            // Ensure unique slug
            $baseSlug = $slug;
            $n = 1;
            while (true) {
                $stmt = $pdo->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
                $stmt->execute([$slug, $productId ?? 0]);
                if (!$stmt->fetch()) break;
                $slug = $baseSlug . '-' . (++$n);
            }

            if ($productId) {
                $stmt = $pdo->prepare("
                    UPDATE products SET name=?, slug=?, description=?, price=?, compare_price=?, category_id=?, image=?, is_featured=?, is_new_arrival=?, is_active=?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $product['name'], $slug, $product['description'], $product['price'],
                    $product['compare_price'] ?: null, $product['category_id'] ?: null, $product['image'],
                    $product['is_featured'], $product['is_new_arrival'], $product['is_active'], $productId
                ]);
                // Replace variants entirely (simplest consistent approach)
                $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$productId]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, slug, description, price, compare_price, category_id, image, is_featured, is_new_arrival, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $product['name'], $slug, $product['description'], $product['price'],
                    $product['compare_price'] ?: null, $product['category_id'] ?: null, $product['image'],
                    $product['is_featured'], $product['is_new_arrival'], $product['is_active']
                ]);
                $productId = $pdo->lastInsertId();
            }

            $vStmt = $pdo->prepare("
                INSERT INTO product_variants (product_id, size, color, color_hex, stock, sku)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($newVariants as $v) {
                $vStmt->execute([$productId, $v['size'], $v['color'], $v['color_hex'], $v['stock'], $v['sku']]);
            }

            $pdo->commit();
            header('Location: ' . BASE_URL . '/admin/products.php?saved=1');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['general'] = 'Could not save product. ' . $e->getMessage();
        }
    } else {
        // repopulate variants array from POST for re-display on error
        $variants = $newVariants ?? [];
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="admin-topbar">
  <h1><?= $productId ? 'Edit Product' : 'Add New Product' ?></h1>
  <a href="<?= BASE_URL ?>/admin/products.php" class="btn-small">&larr; Back to Products</a>
</div>

<?php if (!empty($errors['general'])): ?><div class="alert alert--error"><?= e($errors['general']) ?></div><?php endif; ?>
<?php if (!empty($errors['variants'])): ?><div class="alert alert--error"><?= e($errors['variants']) ?></div><?php endif; ?>

<form method="POST" class="admin-form" enctype="multipart/form-data" action="<?= BASE_URL ?>/admin/product-edit.php<?= $productId ? '?id=' . (int)$productId : '' ?>">
  <?= csrfField() ?>
  <div class="admin-card">
    <h3 style="margin-bottom:18px;">Product Info</h3>
    <div class="form-grid">
      <div class="field full <?= isset($errors['name']) ? 'error' : '' ?>">
        <label for="name">Product Name *</label>
        <input type="text" id="name" name="name" value="<?= e($product['name']) ?>" required>
        <?php if (isset($errors['name'])): ?><span class="err-msg"><?= e($errors['name']) ?></span><?php endif; ?>
      </div>

      <div class="field full">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"><?= e($product['description']) ?></textarea>
      </div>

      <div class="field <?= isset($errors['price']) ? 'error' : '' ?>">
        <label for="price">Price (PKR) *</label>
        <input type="number" step="0.01" id="price" name="price" value="<?= e($product['price']) ?>" required>
        <?php if (isset($errors['price'])): ?><span class="err-msg"><?= e($errors['price']) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label for="compare_price">Compare-at Price (optional, for sale strike-through)</label>
        <input type="number" step="0.01" id="compare_price" name="compare_price" value="<?= e($product['compare_price']) ?>">
      </div>

      <div class="field">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
          <option value="">— None —</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field full <?= isset($errors['image']) ? 'error' : '' ?>">
        <label for="image_file">Product Image</label>
        <div class="image-upload">
          <div class="image-upload__preview" id="imagePreviewBox">
            <img id="imagePreviewImg"
                 src="<?= BASE_URL ?>/images/products/<?= e($product['image'] ?: 'placeholder.svg') ?>"
                 alt="Product image preview"
                 onerror="this.src='<?= BASE_URL ?>/images/placeholder.svg'">
          </div>
          <div class="image-upload__controls">
            <label for="image_file" class="btn-small image-upload__btn">Choose Image…</label>
            <input type="file" id="image_file" name="image_file" accept="image/jpeg,image/png,image/webp" style="display:none;">
            <span class="text-mute" style="font-size:0.76rem; font-family:var(--font-mono); display:block; margin-top:8px;">JPG, PNG, or WEBP — max 5MB. Uploading a new file replaces the image below.</span>
            <?php if (isset($errors['image'])): ?><span class="err-msg"><?= e($errors['image']) ?></span><?php endif; ?>

            <details style="margin-top:12px;">
              <summary class="text-mute" style="font-size:0.76rem; font-family:var(--font-mono); cursor:pointer;">Or set filename manually</summary>
              <input type="text" id="image" name="image" value="<?= e($product['image']) ?>" placeholder="e.g. classic-black.jpg" style="margin-top:8px;">
              <span class="text-mute" style="font-size:0.74rem; font-family:var(--font-mono);">Use this only if the file is already on the server in /images/products/.</span>
            </details>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex; gap:24px; margin-top:18px; flex-wrap:wrap;">
      <label style="display:flex; align-items:center; gap:8px;">
        <input type="checkbox" name="is_featured" <?= $product['is_featured'] ? 'checked' : '' ?>> Featured Product
      </label>
      <label style="display:flex; align-items:center; gap:8px;">
        <input type="checkbox" name="is_new_arrival" <?= $product['is_new_arrival'] ? 'checked' : '' ?>> New Arrival
      </label>
      <label style="display:flex; align-items:center; gap:8px;">
        <input type="checkbox" name="is_active" <?= $product['is_active'] ? 'checked' : '' ?>> Active (visible in store)
      </label>
    </div>
  </div>

  <div class="admin-card">
    <div class="flex-between" style="margin-bottom:18px;">
      <h3>Size / Color Variants</h3>
      <button type="button" class="btn-small" id="addVariantBtn">+ Add Variant</button>
    </div>

    <div id="variantRows">
      <?php
      $rowsToShow = !empty($variants) ? $variants : [['size' => 'One Size', 'color' => '', 'color_hex' => '#000000', 'stock' => 0, 'sku' => '']];
      foreach ($rowsToShow as $v):
      ?>
        <div class="variant-row">
          <div class="field">
            <label>Size</label>
            <input type="text" name="variant_size[]" value="<?= e($v['size']) ?>" placeholder="One Size">
          </div>
          <div class="field">
            <label>Color Name</label>
            <input type="text" name="variant_color[]" value="<?= e($v['color']) ?>" placeholder="Black">
          </div>
          <div class="field">
            <label>Color Hex</label>
            <input type="color" name="variant_hex[]" value="<?= e($v['color_hex'] ?: '#000000') ?>">
          </div>
          <div class="field">
            <label>Stock</label>
            <input type="number" name="variant_stock[]" value="<?= e($v['stock']) ?>" min="0">
          </div>
          <button type="button" class="btn-small danger js-remove-variant" style="height:fit-content;">Remove</button>
          <input type="hidden" name="variant_sku[]" value="<?= e($v['sku'] ?? '') ?>">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <button type="submit" class="btn">Save Product</button>
</form>

<script>
// Live preview of a chosen image file before upload
(function () {
  const input = document.getElementById('image_file');
  const previewImg = document.getElementById('imagePreviewImg');
  if (!input || !previewImg) return;
  input.addEventListener('change', function () {
    const file = input.files && input.files[0];
    if (!file) return;
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
      alert('Please choose a JPG, PNG, or WEBP image.');
      input.value = '';
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      alert('Image must be 5MB or smaller.');
      input.value = '';
      return;
    }
    const reader = new FileReader();
    reader.onload = e => { previewImg.src = e.target.result; };
    reader.readAsDataURL(file);
  });
})();

document.getElementById('addVariantBtn').addEventListener('click', function () {  const rows = document.getElementById('variantRows');
  const row = document.createElement('div');
  row.className = 'variant-row';
  row.innerHTML = `
    <div class="field"><label>Size</label><input type="text" name="variant_size[]" placeholder="One Size"></div>
    <div class="field"><label>Color Name</label><input type="text" name="variant_color[]" placeholder="Black"></div>
    <div class="field"><label>Color Hex</label><input type="color" name="variant_hex[]" value="#000000"></div>
    <div class="field"><label>Stock</label><input type="number" name="variant_stock[]" value="0" min="0"></div>
    <button type="button" class="btn-small danger js-remove-variant" style="height:fit-content;">Remove</button>
    <input type="hidden" name="variant_sku[]" value="">
  `;
  rows.appendChild(row);
});

document.getElementById('variantRows').addEventListener('click', function (e) {
  if (e.target.classList.contains('js-remove-variant')) {
    const rows = document.querySelectorAll('.variant-row');
    if (rows.length > 1) {
      e.target.closest('.variant-row').remove();
    } else {
      alert('At least one variant is required.');
    }
  }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
