<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $productId = (int)($_POST['product_id'] ?? 0);
    $deltaInput = trim((string)($_POST['adjustment'] ?? ''));
    $reason = trim((string)($_POST['reason'] ?? ''));
    $errors = [];

    if ($productId < 1) {
        $errors[] = 'Choose a valid product.';
    }
    if ($deltaInput === '' || !is_numeric($deltaInput) || !is_finite((float)$deltaInput) || (float)$deltaInput === 0.0 || abs((float)$deltaInput) > 10000000 || abs((float)$deltaInput - round((float)$deltaInput, 2)) > 0.000001) {
        $errors[] = 'Enter a stock change between -10,000,000 and 10,000,000, excluding zero.';
    }
    if ($reason === '' || strlen($reason) > 255) {
        $errors[] = 'Enter a reason for the adjustment (up to 255 characters).';
    }

    if (!$errors) {
        $delta = round((float)$deltaInput, 2);
        try {
            db()->beginTransaction();
            $product = q(
                'SELECT stock_quantity, stock_status FROM products WHERE id = ? FOR UPDATE',
                [$productId]
            )->fetch();
            if (!$product) {
                throw new RuntimeException('Product not found. Refresh the inventory list and try again.');
            }
            $before = $product['stock_quantity'] === null ? 0.0 : (float)$product['stock_quantity'];
            $after = round($before + $delta, 2);
            if ($after < 0) {
                throw new RuntimeException('This adjustment would make stock negative. Check the quantity and try again.');
            }

            $status = $product['stock_status'];
            if ($status !== 'made_to_order') {
                $status = $after > 0 ? 'in_stock' : 'out_of_stock';
            }
            q('UPDATE products SET stock_quantity = ?, stock_status = ? WHERE id = ?', [
                number_format($after, 2, '.', ''),
                $status,
                $productId,
            ]);
            q(
                'INSERT INTO product_stock_adjustments (product_id, admin_id, adjustment, quantity_after, reason) VALUES (?, ?, ?, ?, ?)',
                [$productId, (int)$_SESSION['admin_id'], number_format($delta, 2, '.', ''), number_format($after, 2, '.', ''), $reason]
            );
            db()->commit();
            flash('success', 'Stock updated and adjustment recorded.');
            redirect('admin/stock.php' . (!empty($_GET['low']) ? '?low=1' : ''));
        } catch (Throwable $ex) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = $ex->getMessage();
        }
    }
    foreach ($errors as $error) {
        flash('error', $error);
    }
    redirect('admin/stock.php' . (!empty($_GET['low']) ? '?low=1' : ''));
}

$lowOnly = (string)($_GET['low'] ?? '') === '1';
$search = trim((string)($_GET['q'] ?? ''));
$where = [];
$params = [];
if ($lowOnly) {
    $where[] = 'p.stock_quantity IS NOT NULL AND p.low_stock_threshold > 0 AND p.stock_quantity <= p.low_stock_threshold';
}
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$filter = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$products = q(
    "SELECT p.id, p.name, p.sku, p.unit, p.stock_quantity, p.low_stock_threshold, p.stock_status
     FROM products p $filter ORDER BY p.name LIMIT 250",
    $params
)->fetchAll();
$lowCount = (int)q(
    'SELECT COUNT(*) FROM products WHERE stock_quantity IS NOT NULL AND low_stock_threshold > 0 AND stock_quantity <= low_stock_threshold'
)->fetchColumn();
$history = q(
    'SELECT a.*, p.name AS product_name, p.sku, ad.username
     FROM product_stock_adjustments a
     JOIN products p ON p.id = a.product_id
     LEFT JOIN admins ad ON ad.id = a.admin_id
     ORDER BY a.id DESC LIMIT 50'
)->fetchAll();

$adminTitle = 'Stock control';
include __DIR__ . '/_header.php';
?>
<section class="panel stock-intro">
  <div><h2>Inventory overview</h2><p class="muted">Record stock received, issued, or counted. Every adjustment is saved with the signed change, new balance, reason, and staff account.</p></div>
  <a class="btn <?= $lowOnly ? '' : 'btn-primary' ?>" href="stock.php">All products</a>
  <a class="btn <?= $lowOnly ? 'btn-primary' : '' ?>" href="stock.php?low=1">Low stock (<?= $lowCount ?>)</a>
</section>
<form class="filters" method="get">
  <?php if ($lowOnly): ?><input type="hidden" name="low" value="1"><?php endif; ?>
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search product or SKU">
  <button class="btn btn-sm" type="submit">Search</button>
</form>
<div class="panel nopad">
  <table class="tbl wide stock-table">
    <thead><tr><th>Product</th><th>On hand</th><th>Low-stock alert</th><th>Status</th><th>Adjust stock</th></tr></thead>
    <tbody>
      <?php foreach ($products as $product): ?>
        <?php $isLow = $product['stock_quantity'] !== null && (float)$product['low_stock_threshold'] > 0 && (float)$product['stock_quantity'] <= (float)$product['low_stock_threshold']; ?>
        <tr>
          <td><strong><?= e($product['name']) ?></strong><br><small class="muted"><?= e($product['sku'] ?: 'No SKU') ?><?= $product['unit'] ? ' · ' . e($product['unit']) : '' ?></small></td>
          <td><?= $product['stock_quantity'] === null ? '<span class="muted">Not tracked</span>' : number_format((float)$product['stock_quantity'], 2) . ($product['unit'] ? ' ' . e($product['unit']) : '') ?><?php if ($isLow): ?><br><span class="stock-warning">Low stock</span><?php endif; ?></td>
          <td><?= (float)$product['low_stock_threshold'] > 0 ? number_format((float)$product['low_stock_threshold'], 2) : '<span class="muted">Not set</span>' ?></td>
          <td><?= e(stock_label($product['stock_status'])) ?></td>
          <td>
            <form method="post" class="stock-adjust-form">
              <?= csrf_field() ?>
              <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
              <label class="sr-only" for="adjustment-<?= (int)$product['id'] ?>">Signed stock change for <?= e($product['name']) ?></label>
              <input id="adjustment-<?= (int)$product['id'] ?>" type="number" step="0.01" min="-10000000" max="10000000" name="adjustment" required placeholder="+ received / - issued">
              <label class="sr-only" for="reason-<?= (int)$product['id'] ?>">Reason for stock change</label>
              <input id="reason-<?= (int)$product['id'] ?>" name="reason" maxlength="255" required placeholder="Reason">
              <button class="btn btn-sm" type="submit">Record</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$products): ?><tr><td colspan="5" class="center muted">No matching products found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<section class="panel stock-history">
  <h2>Recent adjustment history</h2>
  <div class="stock-history-scroll">
    <table class="tbl">
      <thead><tr><th>Date</th><th>Product</th><th>Change</th><th>Balance after</th><th>Reason</th><th>Recorded by</th></tr></thead>
      <tbody>
        <?php foreach ($history as $entry): ?>
          <tr>
            <td><?= e(date('d M Y H:i', strtotime($entry['created_at']))) ?></td>
            <td><?= e($entry['product_name']) ?></td>
            <td class="<?= (float)$entry['adjustment'] > 0 ? 'stock-positive' : 'stock-negative' ?>"><?= (float)$entry['adjustment'] > 0 ? '+' : '' ?><?= number_format((float)$entry['adjustment'], 2) ?></td>
            <td><?= number_format((float)$entry['quantity_after'], 2) ?></td>
            <td><?= e($entry['reason']) ?></td>
            <td><?= e($entry['username'] ?: 'Former account') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$history): ?><tr><td colspan="6" class="center muted">No stock adjustments recorded yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
