<?php
require __DIR__ . '/includes/bootstrap.php';

$cats = q('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();
$catSlug = trim((string)($_GET['cat'] ?? ''));
$search  = trim((string)($_GET['q'] ?? ''));
$sort    = (string)($_GET['sort'] ?? 'new');
$page    = max(1, (int)($_GET['page'] ?? 1));
$per     = 12;

$where = ['p.is_active = 1'];
$params = [];
$activeCat = null;
if ($catSlug !== '') {
    foreach ($cats as $c) {
        if ($c['slug'] === $catSlug) {
            $activeCat = $c;
        }
    }
    if ($activeCat) {
        $where[] = 'p.category_id = ?';
        $params[] = $activeCat['id'];
    }
}
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.short_description LIKE ? OR p.sku LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
$orders = [
    'new'        => 'p.created_at DESC',
    'price_asc'  => 'p.price IS NULL, p.price ASC',
    'price_desc' => 'p.price DESC',
    'name'       => 'p.name ASC',
];
$order = $orders[$sort] ?? $orders['new'];
$w = implode(' AND ', $where);

$total = (int)q("SELECT COUNT(*) FROM products p WHERE $w", $params)->fetchColumn();
$offset = ($page - 1) * $per;
$items = q(product_select() . " WHERE $w ORDER BY $order LIMIT $per OFFSET $offset", $params)->fetchAll();

$pageTitle = $activeCat ? $activeCat['name'] : 'Products';
$metaDesc = $activeCat && $activeCat['description'] ? excerpt($activeCat['description'], 160) : '';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
  <div class="container">
    <h1><?= e($activeCat ? $activeCat['name'] : 'Our Products') ?></h1>
    <p class="crumbs"><a href="<?= e(url()) ?>">Home</a> / <?= $activeCat ? '<a href="' . e(url('products')) . '">Products</a> / ' . e($activeCat['name']) : 'Products' ?></p>
  </div>
</section>
<section class="section">
  <div class="container shop">
    <aside class="sidebar">
      <h4>Categories</h4>
      <ul>
        <li><a class="<?= !$activeCat ? 'active' : '' ?>" href="<?= e(url('products')) ?>">All products</a></li>
        <?php foreach ($cats as $c): ?>
          <li><a class="<?= $activeCat && $activeCat['id'] == $c['id'] ? 'active' : '' ?>" href="<?= e(url('category/' . $c['slug'])) ?>"><?= e($c['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </aside>
    <div class="shop-main">
      <form class="toolbar" method="get" action="<?= e(url('products.php')) ?>">
        <?php if ($activeCat): ?><input type="hidden" name="cat" value="<?= e($activeCat['slug']) ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search products...">
        <select name="sort">
          <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option>
          <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
          <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Apply</button>
      </form>
      <nav class="category-chips" aria-label="Browse product categories">
        <a class="<?= !$activeCat ? 'active' : '' ?>" href="<?= e(url('products')) ?>">All products</a>
        <?php foreach ($cats as $c): ?><a class="<?= $activeCat && $activeCat['id'] == $c['id'] ? 'active' : '' ?>" href="<?= e(url('category/' . $c['slug'])) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
      </nav>
      <p class="muted"><?= $total ?> product<?= $total === 1 ? '' : 's' ?> found</p>
      <?php if ($items): ?>
        <div class="grid grid-3"><?php foreach ($items as $p) { echo product_card($p); } ?></div>
        <?= pager($total, $per, $page) ?>
      <?php else: ?>
        <div class="empty"><h3>No products found</h3><p>Try a different search or category, or <a href="<?= e(url('contact')) ?>">contact us</a> for help.</p></div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
