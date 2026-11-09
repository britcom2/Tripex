<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$importResult = $_SESSION['product_csv_import'] ?? null;
unset($_SESSION['product_csv_import']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $id = (int)($_POST['id'] ?? 0);
    $act = (string)($_POST['action'] ?? '');
    if ($act === 'import_csv') {
        $imported = 0;
        $errors = [];
        $skipped = 0;
        $upload = $_FILES['csv_file'] ?? null;

        if (!$upload || $upload['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'The CSV file exceeds the server upload limit.',
                UPLOAD_ERR_FORM_SIZE => 'The CSV file exceeds the form upload limit.',
                UPLOAD_ERR_PARTIAL => 'The CSV file was only partially uploaded. Please try again.',
                UPLOAD_ERR_NO_FILE => 'Choose a CSV file to upload.',
            ];
            $errors[] = $upload ? ($uploadErrors[$upload['error']] ?? 'The CSV upload failed. Please try again.') : 'Choose a CSV file to upload.';
        } elseif (
            strtolower(pathinfo((string)$upload['name'], PATHINFO_EXTENSION)) !== 'csv'
            || (int)$upload['size'] > 5 * 1024 * 1024
            || !is_uploaded_file($upload['tmp_name'])
        ) {
            $errors[] = 'Upload a valid CSV file no larger than 5 MB.';
        } else {
            $handle = fopen($upload['tmp_name'], 'rb');
            if ($handle === false) {
                $errors[] = 'The uploaded CSV could not be read.';
            } else {
                $headers = fgetcsv($handle, 0, ',', '"', '\\');
                if ($headers === false) {
                    $errors[] = 'The CSV file is empty.';
                } else {
                    $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$headers[0]);
                    $headers = array_map(static function ($header): string {
                        $header = strtolower(trim((string)$header));
                        $header = preg_replace('/[\s-]+/', '_', $header);
                        return $header === 'product_name' ? 'name' : $header;
                    }, $headers);
                    if (in_array('', $headers, true)) {
                        $errors[] = 'The CSV header contains a blank column name. Remove empty columns and try again.';
                    } elseif (count($headers) !== count(array_unique($headers))) {
                        $errors[] = 'The CSV contains duplicate column names. Keep one of each column and try again.';
                    } elseif (!in_array('name', $headers, true)) {
                        $errors[] = 'The CSV is missing the product name column. Name it "name" or "product_name".';
                    } else {
                        $categories = q('SELECT id, name, slug FROM categories')->fetchAll();
                        $line = 1;
                        while (($values = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                            $line++;
                            if ($values === [null] || (count($values) === 1 && trim((string)$values[0]) === '')) {
                                continue;
                            }
                            if (count($values) !== count($headers)) {
                                $skipped++;
                                $errors[] = 'Line ' . $line . ': column count does not match the header.';
                                continue;
                            }

                            $row = array_combine($headers, $values);
                            $name = trim((string)($row['name'] ?? ''));
                            if ($name === '' || strlen($name) > 190) {
                                $skipped++;
                                $errors[] = 'Line ' . $line . ': product name is required and must be 190 characters or fewer.';
                                continue;
                            }

                            $categoryId = null;
                            $categoryValue = trim((string)($row['category'] ?? ''));
                            if ($categoryValue !== '') {
                                foreach ($categories as $category) {
                                    if (strcasecmp($category['slug'], $categoryValue) === 0 || strcasecmp($category['name'], $categoryValue) === 0) {
                                        $categoryId = (int)$category['id'];
                                        break;
                                    }
                                }
                                if ($categoryId === null) {
                                    $skipped++;
                                    $errors[] = 'Line ' . $line . ': category "' . $categoryValue . '" was not found.';
                                    continue;
                                }
                            }

                            $prices = [];
                            $invalidPrice = false;
                            foreach (['price', 'sale_price'] as $field) {
                                $value = trim((string)($row[$field] ?? ''));
                                if ($value !== '' && (!is_numeric($value) || (float)$value < 0)) {
                                    $invalidPrice = true;
                                    break;
                                }
                                $prices[$field] = $value === '' ? null : (float)$value;
                            }
                            if ($invalidPrice) {
                                $skipped++;
                                $errors[] = 'Line ' . $line . ': prices must be empty or non-negative numbers.';
                                continue;
                            }

                            $stock = trim((string)($row['stock_status'] ?? ''));
                            if ($stock === '') {
                                $stock = 'in_stock';
                            }
                            if (!in_array($stock, ['in_stock', 'out_of_stock', 'made_to_order'], true)) {
                                $skipped++;
                                $errors[] = 'Line ' . $line . ': stock_status must be in_stock, out_of_stock or made_to_order.';
                                continue;
                            }

                            $boolValues = ['1' => 1, '0' => 0, 'yes' => 1, 'no' => 0, 'true' => 1, 'false' => 0, 'active' => 1, 'inactive' => 0];
                            $activeValue = strtolower(trim((string)($row['is_active'] ?? '1')));
                            $featuredValue = strtolower(trim((string)($row['is_featured'] ?? '0')));
                            if (!array_key_exists($activeValue, $boolValues) || !array_key_exists($featuredValue, $boolValues)) {
                                $skipped++;
                                $errors[] = 'Line ' . $line . ': is_active and is_featured must be 1/0, yes/no or true/false.';
                                continue;
                            }

                            $slugValue = trim((string)($row['slug'] ?? ''));
                            $slug = $slugValue === '' ? unique_slug('products', $name) : slugify($slugValue);
                            if (strlen($slug) > 210 || q('SELECT id FROM products WHERE slug = ? LIMIT 1', [$slug])->fetch()) {
                                $skipped++;
                                $errors[] = 'Line ' . $line . ': the slug is too long or already belongs to a product.';
                                continue;
                            }

                            $sku = trim((string)($row['sku'] ?? ''));
                            $unit = trim((string)($row['unit'] ?? ''));
                            if (strlen($sku) > 60 || strlen($unit) > 30) {
                                $skipped++;
                                $errors[] = 'Line ' . $line . ': SKU must be 60 characters or fewer and unit 30 or fewer.';
                                continue;
                            }

                            try {
                                q(
                                    'INSERT INTO products (category_id, name, slug, sku, short_description, description, specs, price, sale_price, unit, stock_status, is_active, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                                    [
                                        $categoryId,
                                        $name,
                                        $slug,
                                        $sku === '' ? null : $sku,
                                        trim((string)($row['short_description'] ?? '')),
                                        trim((string)($row['description'] ?? '')),
                                        trim((string)($row['specs'] ?? '')),
                                        $prices['price'],
                                        $prices['sale_price'],
                                        $unit === '' ? null : $unit,
                                        $stock,
                                        $boolValues[$activeValue],
                                        $boolValues[$featuredValue],
                                    ]
                                );
                                $imported++;
                            } catch (PDOException $ex) {
                                $skipped++;
                                $errors[] = 'Line ' . $line . ': database rejected this row; check that its values are valid and do not duplicate an existing product.';
                            }
                        }
                    }
                }
                fclose($handle);
            }
        }

        $_SESSION['product_csv_import'] = [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 20),
            'more_errors' => max(0, count($errors) - 20),
        ];
        redirect('admin/products.php');
    }

    if ($id > 0) {
        if ($act === 'toggle_active') {
            q('UPDATE products SET is_active = 1 - is_active WHERE id = ?', [$id]);
            flash('success', 'Product visibility updated.');
        } elseif ($act === 'toggle_featured') {
            q('UPDATE products SET is_featured = 1 - is_featured WHERE id = ?', [$id]);
            flash('success', 'Featured status updated.');
        } elseif ($act === 'quick_price') {
            $price = trim((string)($_POST['price'] ?? ''));
            q('UPDATE products SET price = ? WHERE id = ?', [$price === '' ? null : max(0, (float)$price), $id]);
            flash('success', 'Price updated.');
        } elseif ($act === 'delete') {
            foreach (q('SELECT filename FROM product_images WHERE product_id = ?', [$id])->fetchAll() as $im) {
                delete_upload($im['filename']);
            }
            q('DELETE FROM products WHERE id = ?', [$id]);
            flash('success', 'Product deleted.');
        }
    }
    redirect('admin/products.php?' . http_build_query(array_diff_key($_GET, ['x' => 1])));
}

$search = trim((string)($_GET['q'] ?? ''));
$cat = (int)($_GET['cat'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 20;
$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($cat > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $cat;
}
$w = implode(' AND ', $where);
$total = (int)q("SELECT COUNT(*) FROM products p WHERE $w", $params)->fetchColumn();
$offset = ($page - 1) * $per;
$rows = q(product_select() . " WHERE $w ORDER BY p.id DESC LIMIT $per OFFSET $offset", $params)->fetchAll();
$cats = q('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$adminTitle = 'Products';
$adminActions = '<a class="btn" href="stock.php">Stock control</a> <a class="btn btn-primary" href="product_form.php">+ Add product</a>';
include __DIR__ . '/_header.php';
?>
<section class="panel csv-import">
  <div>
    <h2>Import products from CSV</h2>
    <p class="muted">Add products in bulk. Existing slugs are skipped; category can be a category name or slug. Maximum file size: 5 MB.</p>
    <p class="csv-columns"><strong>Columns:</strong> name, sku, category, slug, short_description, description, specs, price, sale_price, unit, stock_status, is_active, is_featured</p>
  </div>
  <div class="csv-actions">
    <a class="btn" href="product_csv_template.php">Download CSV template</a>
    <form method="post" enctype="multipart/form-data" class="csv-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="import_csv">
      <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
      <label class="sr-only" for="csv-file">Product CSV file</label>
      <input id="csv-file" type="file" name="csv_file" accept=".csv,text/csv" required>
      <button class="btn btn-primary" type="submit">Upload and import</button>
    </form>
  </div>
</section>
<?php if ($importResult): ?>
  <div class="alert <?= $importResult['skipped'] || $importResult['errors'] ? 'alert-error' : 'alert-success' ?>">
    Imported <?= (int)$importResult['imported'] ?> product<?= (int)$importResult['imported'] === 1 ? '' : 's' ?>; skipped <?= (int)$importResult['skipped'] ?>.
    <?php if (!empty($importResult['errors'])): ?>
      <ul class="csv-errors"><?php foreach ($importResult['errors'] as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul>
      <?php if (!empty($importResult['more_errors'])): ?><small><?= (int)$importResult['more_errors'] ?> more row error(s) not shown.</small><?php endif; ?>
    <?php endif; ?>
  </div>
<?php endif; ?>
<form class="filters" method="get">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name or SKU">
  <select name="cat"><option value="0">All categories</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
  <button class="btn btn-sm">Filter</button>
</form>
<div class="panel nopad">
<table class="tbl wide">
  <thead><tr><th></th><th>Product</th><th>Category</th><th>On hand</th><th>Price (<?= e(setting('currency', 'KES')) ?>)</th><th>Status</th><th class="r">Actions</th></tr></thead>
  <?php foreach ($rows as $p): ?>
    <tr>
      <td class="thumb"><img src="<?= e(img_url($p['image'])) ?>" alt=""></td>
      <td><strong><?= e($p['name']) ?></strong><br><small class="muted"><?= e($p['sku'] ?: '—') ?> · <?= e(stock_label($p['stock_status'])) ?></small></td>
      <td><?= e($p['cat_name'] ?: '—') ?></td>
      <td>
        <?php if ($p['stock_quantity'] === null): ?><span class="muted">Not tracked</span>
        <?php else: ?><?= number_format((float)$p['stock_quantity'], 2) ?><?= $p['unit'] ? ' ' . e($p['unit']) : '' ?>
          <?php if ($p['stock_quantity'] !== null && (float)$p['low_stock_threshold'] > 0 && (float)$p['stock_quantity'] <= (float)$p['low_stock_threshold']): ?><br><span class="stock-warning">Low stock</span><?php endif; ?>
        <?php endif; ?>
      </td>
      <td>
        <form method="post" class="inline-edit">
          <?= csrf_field() ?><input type="hidden" name="action" value="quick_price"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <input type="number" step="0.01" min="0" name="price" value="<?= $p['price'] !== null ? e($p['price']) : '' ?>" placeholder="Quote">
          <button class="btn btn-sm" title="Save price">Save</button>
        </form>
        <?php if ($p['unit']): ?><small class="muted">per <?= e($p['unit']) ?></small><?php endif; ?>
      </td>
      <td>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_active"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button class="tag <?= $p['is_active'] ? 'on' : 'off' ?>"><?= $p['is_active'] ? 'Visible' : 'Hidden' ?></button></form>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_featured"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button class="tag <?= $p['is_featured'] ? 'on' : 'off' ?>"><?= $p['is_featured'] ? '★ Featured' : '☆ Feature' ?></button></form>
      </td>
      <td class="r">
        <a class="btn btn-sm" href="product_form.php?id=<?= (int)$p['id'] ?>">Edit</a>
        <form method="post" class="inline" data-confirm="Delete this product and its images? This cannot be undone.">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="7" class="muted center">No products found.</td></tr><?php endif; ?>
</table>
</div>
<?= pager($total, $per, $page) ?>
<?php include __DIR__ . '/_footer.php'; ?>
