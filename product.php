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
$whatsappAvailable = (bool)preg_match('/\d{7,}/', preg_replace('/\D/', '', setting('whatsapp')));
$selectedChannel = (($_POST['channel'] ?? '') === 'whatsapp' && $whatsappAvailable) ? 'whatsapp' : 'website';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST['subject'] = 'Enquiry: ' . $p['name'];
  $channel = $selectedChannel;
  if ($channel === 'whatsapp' && !preg_match('/\d{7,}/', preg_replace('/\D/', '', setting('whatsapp')))) {
    $errors[] = 'WhatsApp enquiries are unavailable right now. Please send a website enquiry instead.';
    $sent = false;
  } elseif ($channel === 'whatsapp' && strlen(preg_replace('/\D/', '', (string)($_POST['phone'] ?? ''))) < 7) {
    $errors[] = 'Please include a valid phone or WhatsApp number so we can reply.';
    $sent = false;
  } else {
    list($sent, $errors) = handle_enquiry((int)$p['id']);
  }
    if ($sent) {
    if ($channel === 'whatsapp') {
      $name = trim((string)$_POST['name']);
      $message = "Hello, I am {$name}. I sent an enquiry about {$p['name']} through the website.\n\n" . trim((string)$_POST['message']);
      redirect(whatsapp_link(setting('whatsapp'), $message));
    }
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
          <a class="btn btn-outline" href="<?= e(url('contact.php?via=whatsapp&product_id=' . (int)$p['id'])) ?>">Enquire via WhatsApp</a>
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
      <form method="post" class="form" data-enquiry-form>
        <?= csrf_field() ?>
        <div class="field-hp"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
        <div class="row">
          <label>Name *<input name="name" required value="<?= e($_POST['name'] ?? '') ?>"></label>
          <label data-enquiry-email-field <?= $selectedChannel === 'whatsapp' ? 'hidden' : '' ?>>Email *<input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
          <label>Phone<input name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></label>
        </div>
        <label>Message *<textarea name="message" rows="4" required><?= e($_POST['message'] ?? 'I would like a quote for ' . $p['name'] . '. Area needed (m²): ') ?></textarea></label>
        <div class="account-form-actions">
          <?php if ($whatsappAvailable): ?><label class="enquiry-channel">Send via<select name="channel" data-enquiry-channel><option value="website" <?= $selectedChannel === 'website' ? 'selected' : '' ?>>Email</option><option value="whatsapp" <?= $selectedChannel === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option></select></label><?php else: ?><input type="hidden" name="channel" value="website"><?php endif; ?>
          <button class="btn btn-primary" type="submit">Send Enquiry</button>
        </div>
      </form>
    </div>

    <?php if ($related): ?>
      <div class="section-head mt"><h2>Related Products</h2></div>
      <div class="grid grid-4"><?php foreach ($related as $r) { echo product_card($r); } ?></div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
