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
      <div class="enquiry-page-intro"><span class="about-kicker">A considered start</span><h2>Let’s find the right surface for your space.</h2><p>Share a few details and our flooring team will help you choose the right finish, quantity and timeline.</p></div>
      <?php foreach ($errors as $er): ?><div class="alert alert-error" role="alert" tabindex="-1"><?= e($er) ?></div><?php endforeach; ?>
      <form method="post" class="form enquiry-form contact-enquiry-form" data-enquiry-form>
        <?= csrf_field() ?>
        <?php if ($product): ?><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>"><?php endif; ?>
        <div class="field-hp"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
        <div class="enquiry-form-heading"><span class="enquiry-step">Quick enquiry</span><div><h2>Tell us a little about it</h2><p>We’ll get back to you using your preferred channel.</p></div></div>
        <div class="row enquiry-fields">
          <label>Your name <span class="required-mark">*</span><input name="name" autocomplete="name" maxlength="120" required value="<?= e($_POST['name'] ?? '') ?>" placeholder="e.g. Alex"></label>
          <label data-enquiry-email-field <?= $selectedChannel === 'whatsapp' ? 'hidden' : '' ?>>Email address <span class="required-mark">*</span><input type="email" name="email" autocomplete="email" maxlength="160" value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com"></label>
          <label data-phone-field>Phone / WhatsApp <span data-phone-required class="required-mark" <?= $selectedChannel !== 'whatsapp' ? 'hidden' : '' ?>>*</span><input name="phone" autocomplete="tel" maxlength="40" value="<?= e($_POST['phone'] ?? '') ?>" placeholder="+254 7XX XXX XXX" aria-describedby="enquiry-phone-help"><small class="field-help" id="enquiry-phone-help" data-phone-help <?= $selectedChannel !== 'whatsapp' ? 'hidden' : '' ?>>Format: +254 7XX XXX XXX.</small></label>
          <label>Subject <span class="optional-mark">Optional</span><input name="subject" maxlength="190" value="<?= e($_POST['subject'] ?? ($product ? 'Enquiry: ' . $product['name'] : '')) ?>" placeholder="What can we help with?"></label>
        </div>
        <label class="enquiry-message-field">Your message <span class="required-mark">*</span><textarea name="message" rows="5" maxlength="5000" required placeholder="Tell us about the room, product or project you have in mind..."><?= e($_POST['message'] ?? '') ?></textarea></label>
        <?php if ($whatsappAvailable): ?>
          <fieldset class="enquiry-channels">
            <legend>How would you like us to reply?</legend>
            <div class="enquiry-channel-options">
              <label class="enquiry-channel-card">
                <input type="radio" name="channel" value="website" data-enquiry-channel aria-describedby="enquiry-channel-note" <?= $selectedChannel === 'website' ? 'checked' : '' ?>>
                <span class="enquiry-channel-icon" aria-hidden="true">&#9993;</span>
                <span class="enquiry-channel-copy"><strong>Email</strong><small>A personal reply to your inbox</small></span>
                <span class="enquiry-channel-check" aria-hidden="true"></span>
              </label>
              <label class="enquiry-channel-card enquiry-channel-whatsapp">
                <input type="radio" name="channel" value="whatsapp" data-enquiry-channel aria-describedby="enquiry-channel-note" <?= $selectedChannel === 'whatsapp' ? 'checked' : '' ?>>
                <span class="enquiry-channel-icon" aria-hidden="true">&#10148;</span>
                <span class="enquiry-channel-copy"><strong>WhatsApp</strong><small>Continue the conversation there</small></span>
                <span class="enquiry-channel-check" aria-hidden="true"></span>
              </label>
            </div>
          </fieldset>
        <?php else: ?><input type="hidden" name="channel" value="website"><?php endif; ?>
        <p class="enquiry-channel-note" id="enquiry-channel-note" data-channel-note data-email-note="We’ll use your email address to reply to this enquiry." data-whatsapp-note="Add your WhatsApp number in the Kenya format (+254 7XX XXX XXX).">We’ll use your email address to reply to this enquiry.</p>
        <div class="enquiry-submit-row">
          <button class="btn btn-primary enquiry-submit" type="submit" data-enquiry-submit data-email-label="Send enquiry" data-whatsapp-label="Continue to WhatsApp"><span data-submit-label><?= $selectedChannel === 'whatsapp' ? 'Continue to WhatsApp' : 'Send enquiry' ?></span></button>
          <span class="enquiry-submit-note"><span aria-hidden="true">&#128274;</span> Your details are only used to respond to you.</span>
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
