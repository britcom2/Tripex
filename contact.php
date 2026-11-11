<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Contact Us';
$errors = [];
$whatsappAvailable = (bool)preg_match('/\d{7,}/', preg_replace('/\D/', '', setting('whatsapp')));
$selectedChannel = (($_POST['channel'] ?? $_GET['via'] ?? '') === 'whatsapp' && $whatsappAvailable) ? 'whatsapp' : 'website';
$product = null;
$productId = (int)($_GET['product_id'] ?? 0);
if ($productId > 0) {
  $product = q('SELECT id, name FROM products WHERE id = ? AND is_active = 1', [$productId])->fetch() ?: null;
  if (!$product) {
    $productId = 0;
  }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $productId = (int)($_POST['product_id'] ?? 0);
  if ($productId > 0) {
    $product = q('SELECT id, name FROM products WHERE id = ? AND is_active = 1', [$productId])->fetch() ?: null;
    if (!$product) {
      $errors[] = 'The selected product is no longer available.';
    }
  }
  $channel = $selectedChannel;
  if ($channel === 'whatsapp' && !preg_match('/\d{7,}/', preg_replace('/\D/', '', setting('whatsapp')))) {
    $errors[] = 'WhatsApp enquiries are unavailable right now. Please send a website enquiry instead.';
  }
  if ($channel === 'whatsapp' && strlen(preg_replace('/\D/', '', (string)($_POST['phone'] ?? ''))) < 7) {
    $errors[] = 'Please include a valid phone or WhatsApp number so we can reply.';
  }
  if ($product) {
    $_POST['subject'] = trim((string)($_POST['subject'] ?? '')) ?: 'Enquiry: ' . $product['name'];
  }
  if (!$errors) {
    list($ok, $errors) = handle_enquiry($productId ?: null);
  } else {
    $ok = false;
  }
    if ($ok) {
    if ($channel === 'whatsapp') {
      $name = trim((string)$_POST['name']);
      $subject = trim((string)($_POST['subject'] ?? 'Your enquiry'));
      $message = "Hello, I am {$name}. I sent an enquiry through the website.\n\n{$subject}\n" . trim((string)$_POST['message']);
      redirect(whatsapp_link(setting('whatsapp'), $message));
    }
        flash('success', 'Thank you! Your message has been sent. We will get back to you shortly.');
        redirect('contact');
    }
}
include __DIR__ . '/includes/header.php';
$map = setting('map_embed');
?>
<section class="page-banner"><div class="container"><span class="about-kicker">We are here to help</span><h1>Contact Us</h1><p class="crumbs"><a href="<?= e(url()) ?>">Home</a> / Contact</p></div></section>
<section class="section">
  <div class="container contact-grid">
    <div>
      <?= flash_render() ?>
      <?php if ($product): ?><div class="alert alert-success">Enquiring about: <strong><?= e($product['name']) ?></strong></div><?php endif; ?>
      <div class="contact-intro"><span class="about-kicker">Start a conversation</span><h2>Tell us what you have in mind.</h2><p>For a product or project quote, our guided quote request helps us understand your materials, space and timing.</p><a class="btn btn-primary" href="<?= e(url('quote')) ?>">Build a quote request &rarr;</a></div>
      <h2 class="contact-form-heading">Or send us a message</h2>
      <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
      <form method="post" class="form" data-enquiry-form>
        <?= csrf_field() ?>
        <?php if ($product): ?><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>"><?php endif; ?>
        <div class="field-hp"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
        <div class="row">
          <label>Name *<input name="name" required value="<?= e($_POST['name'] ?? '') ?>"></label>
          <label data-enquiry-email-field <?= $selectedChannel === 'whatsapp' ? 'hidden' : '' ?>>Email *<input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
        </div>
        <div class="row">
          <label>Phone<input name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></label>
          <label>Subject<input name="subject" value="<?= e($_POST['subject'] ?? ($product ? 'Enquiry: ' . $product['name'] : '')) ?>"></label>
        </div>
        <label>Message *<textarea name="message" rows="6" required><?= e($_POST['message'] ?? '') ?></textarea></label>
        <div class="account-form-actions">
          <?php if ($whatsappAvailable): ?><label class="enquiry-channel">Send via<select name="channel" data-enquiry-channel><option value="website" <?= $selectedChannel === 'website' ? 'selected' : '' ?>>Email</option><option value="whatsapp" <?= $selectedChannel === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option></select></label><?php else: ?><input type="hidden" name="channel" value="website"><?php endif; ?>
          <button class="btn btn-primary" type="submit">Send Message</button>
        </div>
      </form>
    </div>
    <aside class="contact-info">
      <h3>Get in touch</h3>
      <?php if (setting('address')): ?><p><strong>Visit us</strong><br><?= nl2br(e(setting('address'))) ?></p><?php endif; ?>
      <?php if (setting('phone')): ?><p><strong>Call</strong><br><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('phone'))) ?>"><?= e(setting('phone')) ?></a></p><?php endif; ?>
      <?php if (setting('email')): ?><p><strong>Email</strong><br><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p><?php endif; ?>
      <?php if (setting('hours')): ?><p><strong>Opening hours</strong><br><?= e(setting('hours')) ?></p><?php endif; ?>
    </aside>
  </div>
  <?php if ($map && preg_match('#^https://www\.google\.com/maps/embed#', $map)): ?>
    <div class="container mt"><iframe class="map" src="<?= e($map) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
