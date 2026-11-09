<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();
$adminTitle = 'Dashboard';
$stats = [
    'Products'        => (int)q('SELECT COUNT(*) FROM products')->fetchColumn(),
    'Active products' => (int)q('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
    'Categories'      => (int)q('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'New enquiries'   => (int)q('SELECT COUNT(*) FROM enquiries WHERE is_read = 0')->fetchColumn(),
];
$recent = q('SELECT e.*, p.name AS product FROM enquiries e LEFT JOIN products p ON p.id = e.product_id ORDER BY e.id DESC LIMIT 6')->fetchAll();
$top = q('SELECT name, views FROM products ORDER BY views DESC LIMIT 5')->fetchAll();
include __DIR__ . '/_header.php';
?>
<div class="stats">
  <?php foreach ($stats as $label => $n): ?><div class="stat"><strong><?= $n ?></strong><span><?= e($label) ?></span></div><?php endforeach; ?>
</div>
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
