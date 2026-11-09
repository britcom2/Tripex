<?php
require __DIR__ . '/includes/bootstrap.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$p = $slug !== '' ? q(product_select() . ' WHERE p.slug = ? AND p.is_active = 1 LIMIT 1', [$slug])->fetch() : false;
if (!$p) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    include __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container empty"><h1>Product not found</h1><p><a class="btn btn-primary" href="' . e(url('products')) . '">Back to products</a></p></div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$sent = false;
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST['subject'] = 'Enquiry: ' . $p['name'];
    list($sent, $errors) = handle_enquiry((int)$p['id']);
    if ($sent) {
        flash('success', 'Thank you! Your enquiry has been sent. We will get back to you shortly.');
        redirect('product/' . $p['slug'] . '#enquire');
    }
} else {
    q('UPDATE products SET views = views + 1 WHERE id = ?', [$p['id']]);
}

$images = q('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order, id', [$p['id']])->fetchAll();
$related = q(product_select() . ' WHERE p.is_active = 1 AND p.category_id <=> ? AND p.id <> ? ORDER BY RAND() LIMIT 4', [$p['category_id'], $p['id']])->fetchAll();
$specs = parse_specs($p['specs']);

$pageTitle = $p['name'];
$metaDesc = excerpt((string)($p['short_description'] ?: $p['description']), 160);
$ogImage = $images ? ((!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . img_url($images[0]['filename'])) : '';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
  <div class="container">
    <h1><?= e($p['name']) ?></h1>
    <p class="crumbs"><a href="<?= e(url()) ?>">Home</a> / <a href="<?= e(url('products')) ?>">Products</a><?php if ($p['cat_slug']): ?> / <a href="<?= e(url('category/' . $p['cat_slug'])) ?>"><?= e($p['cat_name']) ?></a><?php endif; ?></p>
  </div>
</section>
<section class="section">
  <div class="container">
    <?= flash_render() ?>
    <div class="product-detail">
      <div class="gallery">
        <div class="gallery-main" aria-label="Swipe to view product images">
          <div class="gallery-track">
            <?php if ($images): ?>
              <?php foreach ($images as $index => $im): ?>
                <figure class="gallery-slide" id="product-image-<?= $index + 1 ?>">
                  <img src="<?= e(img_url($im['filename'])) ?>"<?= img_srcset_attr($im['filename'], '(max-width: 700px) 92vw, 52vw') ?> alt="<?= e($p['name']) ?><?= $index ? ' image ' . ($index + 1) : '' ?>"<?= $index ? ' loading="lazy"' : ' fetchpriority="high"' ?>>
                </figure>
              <?php endforeach; ?>
            <?php else: ?>
              <figure class="gallery-slide"><img src="<?= e(img_url(null)) ?>" alt="<?= e($p['name']) ?>"></figure>
            <?php endif; ?>
          </div>
        </div>
        <?php if (count($images) > 1): ?>
          <div class="thumbs">
            <?php foreach ($images as $index => $im): ?>
              <a href="#product-image-<?= $index + 1 ?>" aria-label="View image <?= $index + 1 ?> of <?= count($images) ?>"><img loading="lazy" src="<?= e(img_url($im['filename'])) ?>"<?= img_srcset_attr($im['filename'], '78px') ?> alt=""></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="info">
        <?php if ($p['cat_name']): ?><span class="cat"><?= e($p['cat_name']) ?></span><?php endif; ?>
        <h2><?= e($p['name']) ?></h2>
        <div class="price-big"><?= price_html($p) ?></div>
        <p class="stock stock-<?= e($p['stock_status']) ?>"><?= e(stock_label($p['stock_status'])) ?><?= $p['sku'] ? ' &middot; SKU: ' . e($p['sku']) : '' ?></p>
        <?php if ($p['short_description']): ?><p class="lead"><?= e($p['short_description']) ?></p><?php endif; ?>
        <form method="post" action="<?= e(url('quote.php')) ?>" class="product-quote-add">
          <?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <button class="btn btn-primary" type="submit">Add to quote list</button>
        </form>
        <a class="btn btn-outline" href="#enquire">Ask about this product</a>
        <?php $wa = preg_replace('/\D/', '', setting('whatsapp')); if ($wa): ?>
          <a class="btn btn-outline" target="_blank" rel="noopener" href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode('Hello, I am interested in ' . $p['name']) ?>">WhatsApp</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="detail-tabs">
      <?php if ($p['description']): ?>
        <div><h3>Description</h3><div class="prose"><?= nl2br(e($p['description'])) ?></div></div>
      <?php endif; ?>
      <?php if ($specs): ?>
        <div><h3>Specifications</h3>
          <table class="specs"><?php foreach ($specs as $s): ?><tr><th><?= e($s[0]) ?></th><td><?= e($s[1]) ?></td></tr><?php endforeach; ?></table>
        </div>
      <?php endif; ?>
    </div>

    <div class="enquiry-box" id="enquire">
      <h3>Enquire about this product</h3>
      <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
      <form method="post" class="form">
        <?= csrf_field() ?>
        <div class="field-hp"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
        <div class="row">
          <label>Name *<input name="name" required value="<?= e($_POST['name'] ?? '') ?>"></label>
          <label>Email *<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
          <label>Phone<input name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></label>
        </div>
        <label>Message *<textarea name="message" rows="4" required><?= e($_POST['message'] ?? 'I would like a quote for ' . $p['name'] . '. Area needed (m²): ') ?></textarea></label>
        <button class="btn btn-primary" type="submit">Send Enquiry</button>
      </form>
    </div>

    <?php if ($related): ?>
      <div class="section-head mt"><h2>Related Products</h2></div>
      <div class="grid grid-4"><?php foreach ($related as $r) { echo product_card($r); } ?></div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
