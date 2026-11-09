<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$p = null;
if ($id > 0) {
    $p = q('SELECT * FROM products WHERE id = ?', [$id])->fetch();
    if (!$p) {
        flash('error', 'Product not found.');
        redirect('admin/products.php');
    }
}
$cats = q('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $name  = trim((string)($_POST['name'] ?? ''));
    $catId = (int)($_POST['category_id'] ?? 0) ?: null;
    $price = trim((string)($_POST['price'] ?? ''));
    $sale  = trim((string)($_POST['sale_price'] ?? ''));
    $data = [
        'name'              => $name,
        'category_id'       => $catId,
        'sku'               => trim((string)($_POST['sku'] ?? '')) ?: null,
        'short_description' => substr(trim((string)($_POST['short_description'] ?? '')), 0, 255) ?: null,
        'description'       => trim((string)($_POST['description'] ?? '')) ?: null,
        'specs'             => trim((string)($_POST['specs'] ?? '')) ?: null,
        'price'             => $price === '' ? null : max(0, (float)$price),
        'sale_price'        => $sale === '' ? null : max(0, (float)$sale),
        'unit'              => substr(trim((string)($_POST['unit'] ?? '')), 0, 30) ?: null,
        'stock_status'      => in_array($_POST['stock_status'] ?? '', ['in_stock', 'out_of_stock', 'made_to_order'], true) ? $_POST['stock_status'] : 'in_stock',
        'is_active'         => isset($_POST['is_active']) ? 1 : 0,
        'is_featured'       => isset($_POST['is_featured']) ? 1 : 0,
    ];
    if ($name === '') {
        $errors[] = 'Product name is required.';
    }
    if ($data['sale_price'] !== null && $data['price'] !== null && $data['sale_price'] >= $data['price']) {
        $errors[] = 'Offer price must be lower than the regular price (or leave it empty).';
    }

    if (!$errors) {
        if ($p) {
            $data['slug'] = unique_slug('products', $name, $id);
            $set = implode(', ', array_map(function ($k) { return "`$k` = ?"; }, array_keys($data)));
            q("UPDATE products SET $set WHERE id = ?", array_merge(array_values($data), [$id]));
        } else {
            $data['slug'] = unique_slug('products', $name);
            $cols = implode(', ', array_map(function ($k) { return "`$k`"; }, array_keys($data)));
            $ph = implode(', ', array_fill(0, count($data), '?'));
            q("INSERT INTO products ($cols) VALUES ($ph)", array_values($data));
            $id = (int)db()->lastInsertId();
        }

        // Delete selected images
        foreach ((array)($_POST['delete_img'] ?? []) as $imgId) {
            $im = q('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [(int)$imgId, $id])->fetch();
            if ($im) {
                delete_upload($im['filename']);
                q('DELETE FROM product_images WHERE id = ?', [$im['id']]);
            }
        }
        // Upload new images
        $imgErr = [];
        foreach (normalize_files('images') as $f) {
            list($file, $err) = save_upload($f, 'p' . $id);
            if ($err) {
                $imgErr[] = $err;
                continue;
            }
            q('INSERT INTO product_images (product_id, filename, sort_order) VALUES (?,?,?)', [$id, $file, (int)q('SELECT COALESCE(MAX(sort_order),0)+1 FROM product_images WHERE product_id = ?', [$id])->fetchColumn()]);
        }
        // Primary image
        $primary = (int)($_POST['primary_img'] ?? 0);
        if ($primary > 0 && q('SELECT id FROM product_images WHERE id = ? AND product_id = ?', [$primary, $id])->fetch()) {
            q('UPDATE product_images SET is_primary = 0 WHERE product_id = ?', [$id]);
            q('UPDATE product_images SET is_primary = 1 WHERE id = ?', [$primary]);
        }
        if (!q('SELECT id FROM product_images WHERE product_id = ? AND is_primary = 1', [$id])->fetch()) {
            $first = q('SELECT id FROM product_images WHERE product_id = ? ORDER BY sort_order, id LIMIT 1', [$id])->fetchColumn();
            if ($first) {
                q('UPDATE product_images SET is_primary = 1 WHERE id = ?', [$first]);
            }
        }
        foreach ($imgErr as $m) {
            flash('error', $m);
        }
        flash('success', 'Product saved.');
        redirect('admin/product_form.php?id=' . $id);
    }
    $p = array_merge($p ?: [], $data);
}

$images = $id ? q('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order, id', [$id])->fetchAll() : [];
$v = function ($k, $d = '') use ($p) { return e($p[$k] ?? $d); };
$adminTitle = $id ? 'Edit product' : 'Add product';
$adminActions = '<a class="btn" href="products.php">&larr; All products</a>';
include __DIR__ . '/_header.php';
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="form-grid">
  <?= csrf_field() ?>
  <div class="main-col">
    <div class="panel">
      <label>Product name *<input name="name" required value="<?= $v('name') ?>"></label>
      <label>Short description <small class="muted">(shown on product cards)</small><input name="short_description" maxlength="255" value="<?= $v('short_description') ?>"></label>
      <label>Full description<textarea name="description" rows="7"><?= $v('description') ?></textarea></label>
      <label>Specifications <small class="muted">(one per line, as “Name: Value”)</small>
        <textarea name="specs" rows="6" placeholder="Material: Nylon&#10;Tile size: 50cm x 50cm&#10;Thickness: 6mm"><?= $v('specs') ?></textarea></label>
    </div>
    <div class="panel">
      <h2>Images</h2>
      <?php if ($images): ?>
        <div class="img-grid">
          <?php foreach ($images as $im): ?>
            <div class="img-item">
              <img src="<?= e(img_url($im['filename'])) ?>" alt="">
              <label class="radio"><input type="radio" name="primary_img" value="<?= (int)$im['id'] ?>" <?= $im['is_primary'] ? 'checked' : '' ?>> Main</label>
              <label class="check del"><input type="checkbox" name="delete_img[]" value="<?= (int)$im['id'] ?>"> Delete</label>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <label>Upload images <small class="muted">(JPG, PNG or WEBP, up to 6 MB each; you can select several)</small>
        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple></label>
    </div>
  </div>
  <div class="side-col">
    <div class="panel">
      <h2>Publish</h2>
      <label class="check"><input type="checkbox" name="is_active" <?= (!$p || !empty($p['is_active'])) ? 'checked' : '' ?>> Visible on website</label>
      <label class="check"><input type="checkbox" name="is_featured" <?= !empty($p['is_featured']) ? 'checked' : '' ?>> Featured on homepage</label>
      <button class="btn btn-primary block" type="submit">Save product</button>
    </div>
    <div class="panel">
      <h2>Pricing</h2>
      <label>Price (<?= e(setting('currency', 'KES')) ?>)<input type="number" step="0.01" min="0" name="price" value="<?= $v('price') ?>" placeholder="Leave empty for “Request a quote”"></label>
      <label>Offer price<input type="number" step="0.01" min="0" name="sale_price" value="<?= $v('sale_price') ?>"></label>
      <label>Unit <small class="muted">(e.g. m², tile, roll)</small><input name="unit" value="<?= $v('unit') ?>" list="units"></label>
      <datalist id="units"><option>m²</option><option>tile</option><option>box</option><option>roll</option><option>piece</option><option>plank</option></datalist>
    </div>
    <div class="panel">
      <h2>Details</h2>
      <label>Category
        <select name="category_id"><option value="0">— None —</option>
          <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($p['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select></label>
      <label>SKU / Code<input name="sku" value="<?= $v('sku') ?>"></label>
      <label>Stock status
        <select name="stock_status">
          <?php foreach (['in_stock' => 'In stock', 'made_to_order' => 'Made to order', 'out_of_stock' => 'Out of stock'] as $k => $l): ?>
            <option value="<?= $k ?>" <?= ($p['stock_status'] ?? 'in_stock') === $k ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select></label>
    </div>
  </div>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
