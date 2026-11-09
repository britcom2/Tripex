<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Contact Us';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    list($ok, $errors) = handle_enquiry(null);
    if ($ok) {
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
      <div class="contact-intro"><span class="about-kicker">Start a conversation</span><h2>Tell us what you have in mind.</h2><p>For a product or project quote, our guided quote request helps us understand your materials, space and timing.</p><a class="btn btn-primary" href="<?= e(url('quote')) ?>">Build a quote request &rarr;</a></div>
      <h2 class="contact-form-heading">Or send us a message</h2>
      <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
      <form method="post" class="form">
        <?= csrf_field() ?>
        <div class="field-hp"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
        <div class="row">
          <label>Name *<input name="name" required value="<?= e($_POST['name'] ?? '') ?>"></label>
          <label>Email *<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
        </div>
        <div class="row">
          <label>Phone<input name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></label>
          <label>Subject<input name="subject" value="<?= e($_POST['subject'] ?? '') ?>"></label>
        </div>
        <label>Message *<textarea name="message" rows="6" required><?= e($_POST['message'] ?? '') ?></textarea></label>
        <button class="btn btn-primary" type="submit">Send Message</button>
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
