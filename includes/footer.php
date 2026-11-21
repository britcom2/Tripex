</main>
<?php
$siteName = setting('site_name', 'Tripex Carpets');
$cats = [];
try {
    $cats = q('SELECT name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order, name LIMIT 8')->fetchAll();
} catch (Throwable $ex) {
}
$wa = whatsapp_link(setting('whatsapp'));
$socialLinks = [
    'Facebook' => setting('facebook'),
    'Instagram' => setting('instagram'),
    'TikTok' => setting('tiktok'),
    'YouTube' => setting('youtube'),
    'LinkedIn' => setting('linkedin'),
    'X' => setting('x'),
];
?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <h4><?= e($siteName) ?></h4>
      <p><?= e(setting('footer_text', 'Premium flooring, carpet tiles, vinyl planks and architectural surfaces for homes, offices and commercial spaces.')) ?></p>
      <div class="social">
        <?php foreach ($socialLinks as $label => $href): ?>
          <?php if ($href): ?><a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a><?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <div>
      <h4>Products</h4>
      <ul>
        <?php foreach ($cats as $c): ?><li><a href="<?= e(url('category/' . $c['slug'])) ?>"><?= e($c['name']) ?></a></li><?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h4>Company</h4>
      <ul>
        <li><a href="<?= e(url('about')) ?>">About Us</a></li>
        <li><a href="<?= e(url('services')) ?>">Services</a></li>
        <li><a href="<?= e(url('gallery')) ?>">Projects</a></li>
        <li><a href="<?= e(url('contact')) ?>">Contact</a></li>
      </ul>
    </div>
    <div>
      <h4>Contact</h4>
      <ul class="contact-list">
        <?php if (setting('address')): ?>
          <li class="footer-address"><span class="footer-contact-label">Address</span><?= nl2br(e(setting('address'))) ?></li>
        <?php endif; ?>
        <?php if (setting('phone') || setting('phone2') || setting('phone3')): ?>
          <li class="footer-phones"><span class="footer-contact-label">Phone</span>
            <?php foreach (['phone', 'phone2', 'phone3'] as $phoneKey): ?>
              <?php if (setting($phoneKey)): ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting($phoneKey))) ?>"><?= e(setting($phoneKey)) ?></a><?php endif; ?>
            <?php endforeach; ?>
          </li>
        <?php endif; ?>
        <?php if (setting('email')): ?>
          <li><span class="footer-contact-label">Email</span><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></li>
        <?php endif; ?>
        <?php if (setting('hours')): ?><li><?= e(setting('hours')) ?></li><?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="footer-bottom"><div class="container">&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</div></div>
</footer>
<?php if ($wa): ?>
<a class="wa-float" href="<?= e($wa) ?>" target="_blank" rel="noopener noreferrer" aria-label="Chat with us on WhatsApp">WhatsApp</a>
<?php endif; ?>
<nav class="mobile-actionbar" aria-label="Mobile navigation and quick contact">
  <a class="<?= $current === 'index.php' ? 'active' : '' ?>" href="<?= e(url()) ?>"><span class="mobile-nav-icon" aria-hidden="true">&#8962;</span><span class="mobile-nav-label">Home</span></a>
  <a class="<?= in_array($current, ['products.php', 'product.php', 'category.php'], true) ? 'active' : '' ?>" href="<?= e(url('products')) ?>"><span class="mobile-nav-icon" aria-hidden="true">&#9638;</span><span class="mobile-nav-label">Products</span></a>
  <a class="<?= $current === 'gallery.php' ? 'active' : '' ?>" href="<?= e(url('gallery')) ?>"><span class="mobile-nav-icon" aria-hidden="true">&#9635;</span><span class="mobile-nav-label">Projects</span></a>
  <?php if (setting('phone')): ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('phone'))) ?>"><span class="mobile-nav-icon" aria-hidden="true">&#9742;</span><span class="mobile-nav-label">Call</span></a><?php endif; ?>
  <?php if ($wa): ?><a href="<?= e($wa) ?>" target="_blank" rel="noopener noreferrer"><span class="mobile-nav-icon" aria-hidden="true">&#9993;</span><span class="mobile-nav-label">WhatsApp</span></a><?php endif; ?>
  <a class="mobile-action-quote <?= $current === 'quote.php' ? 'active' : '' ?>" href="<?= e(url('quote')) ?>"><span class="mobile-nav-icon" aria-hidden="true">&#9998;</span><span class="mobile-nav-label">Quote<?php if (!empty($quoteCount)): ?> (<?= $quoteCount ?>)<?php endif; ?></span></a>
</nav>
<script src="<?= e(url('assets/js/enquiry-channel.js')) ?>?v=3" defer></script>
</body>
</html>
