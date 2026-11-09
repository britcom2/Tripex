<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();
$adminTitle = 'Dashboard';
$stats = [
    'Products'        => (int)q('SELECT COUNT(*) FROM products')->fetchColumn(),
    'Active products' => (int)q('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
    'Categories'      => (int)q('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'New enquiries'   => (int)q('SELECT COUNT(*) FROM enquiries WHERE is_read = 0')->fetchColumn(),
    'Low-stock products' => (int)q('SELECT COUNT(*) FROM products WHERE stock_quantity IS NOT NULL AND low_stock_threshold > 0 AND stock_quantity <= low_stock_threshold')->fetchColumn(),
];
$recent = q('SELECT e.*, p.name AS product FROM enquiries e LEFT JOIN products p ON p.id = e.product_id ORDER BY e.id DESC LIMIT 6')->fetchAll();
$top = q('SELECT name, views FROM products ORDER BY views DESC LIMIT 5')->fetchAll();
$lowStock = q(
    'SELECT id, name, stock_quantity, low_stock_threshold, unit
     FROM products
     WHERE stock_quantity IS NOT NULL AND low_stock_threshold > 0 AND stock_quantity <= low_stock_threshold
     ORDER BY stock_quantity ASC, name
     LIMIT 8'
)->fetchAll();
$outOfStockCount = (int)q(
    'SELECT COUNT(*) FROM products
     WHERE stock_quantity IS NOT NULL AND low_stock_threshold > 0 AND stock_quantity <= 0'
)->fetchColumn();
include __DIR__ . '/_header.php';
?>
<div class="stats">
  <?php foreach ($stats as $label => $n): ?>
    <div class="stat <?= $label === 'Low-stock products' && $n > 0 ? 'stat-warning' : '' ?>">
      <strong><?= $n ?></strong>
      <span><?= e($label) ?></span>
      <?php if ($label === 'Low-stock products' && $n > 0): ?><a href="stock.php?low=1">Review stock &rarr;</a><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<section class="inventory-alert <?= $outOfStockCount > 0 ? 'inventory-alert-critical' : ($lowStock ? 'inventory-alert-warning' : 'inventory-alert-clear') ?>" <?= $lowStock ? 'role="alert"' : 'role="status"' ?>>
  <div class="inventory-alert-heading">
    <span class="inventory-alert-icon" aria-hidden="true"><?= $lowStock ? '!' : '&#10003;' ?></span>
    <div>
      <h2><?= $lowStock ? ($outOfStockCount > 0 ? 'Stock needs immediate attention' : 'Low-stock alert') : 'Inventory is healthy' ?></h2>
      <p>
        <?php if ($lowStock): ?>
          <?= count($lowStock) === 8 && $stats['Low-stock products'] > 8 ? 'Showing 8 of ' . $stats['Low-stock products'] . ' ' : $stats['Low-stock products'] . ' ' ?>
          product<?= $stats['Low-stock products'] === 1 ? ' is' : 's are' ?> at or below their alert threshold.
          <?php if ($outOfStockCount > 0): ?><strong><?= $outOfStockCount ?> out of stock.</strong><?php endif; ?>
        <?php else: ?>
          No tracked products are currently at or below their configured alert thresholds.
        <?php endif; ?>
      </p>
    </div>
    <a class="btn <?= $lowStock ? 'btn-primary' : '' ?>" href="stock.php?low=1">Open stock control &rarr;</a>
  </div>
  <?php if ($lowStock): ?>
    <div class="inventory-alert-list">
      <?php foreach ($lowStock as $item): ?>
        <?php $isOut = (float)$item['stock_quantity'] <= 0; ?>
        <div class="inventory-alert-item <?= $isOut ? 'inventory-alert-item-critical' : '' ?>">
          <div>
            <a href="product_form.php?id=<?= (int)$item['id'] ?>"><?= e($item['name']) ?></a>
            <small><?= $isOut ? 'Out of stock' : 'Low stock' ?> · Threshold <?= number_format((float)$item['low_stock_threshold'], 2) ?><?= $item['unit'] ? ' ' . e($item['unit']) : '' ?></small>
          </div>
          <strong><?= number_format((float)$item['stock_quantity'], 2) ?><?= $item['unit'] ? ' ' . e($item['unit']) : '' ?></strong>
          <a class="inventory-adjust-link" href="stock.php?low=1">Adjust stock</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<div class="two">
  <div class="panel">
    <h2>Latest enquiries</h2>
    <?php if ($recent): ?>
      <table class="tbl">
        <?php foreach ($recent as $r): ?>
          <tr class="<?= $r['is_read'] ? '' : 'unread' ?>">
            <td><a href="enquiries.php?view=<?= (int)$r['id'] ?>"><?= e($r['name']) ?></a><br><small class="muted"><?= e($r['product'] ?: ($r['subject'] ?: 'General enquiry')) ?></small></td>
            <td class="r"><small class="muted"><?= e(date('d M, H:i', strtotime($r['created_at']))) ?></small></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?><p class="muted">No enquiries yet.</p><?php endif; ?>
  </div>
  <div class="panel">
    <h2>Most viewed products</h2>
    <?php if ($top): ?>
      <table class="tbl"><?php foreach ($top as $t): ?><tr><td><?= e($t['name']) ?></td><td class="r"><?= (int)$t['views'] ?> views</td></tr><?php endforeach; ?></table>
    <?php else: ?><p class="muted">No products yet.</p><?php endif; ?>
    <p><a class="btn btn-primary btn-sm" href="product_form.php">+ Add product</a></p>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
