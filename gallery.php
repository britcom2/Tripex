<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Projects';
$items = q('SELECT * FROM gallery ORDER BY sort_order, id DESC')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner"><div class="container"><h1>Projects Gallery</h1><p class="crumbs"><a href="<?= e(url()) ?>">Home</a> / Projects</p></div></section>
<section class="section">
  <div class="container">
    <?php if ($items): ?>
      <div class="masonry">
        <?php foreach ($items as $g): ?>
          <figure><a href="<?= e(img_url($g['filename'])) ?>" target="_blank" rel="noopener"><img loading="lazy" decoding="async" src="<?= e(img_url($g['filename'])) ?>"<?= img_srcset_attr($g['filename'], '(max-width: 700px) 92vw, 33vw') ?> alt="<?= e($g['title']) ?>"></a><?php if ($g['title']): ?><figcaption><?= e($g['title']) ?></figcaption><?php endif; ?></figure>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty"><h3>Projects coming soon</h3><p>Check back shortly to see our recent work.</p></div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
