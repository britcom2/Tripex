<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Services';
$services = [
    ['Flooring Supply', 'A wide selection of carpets, carpet tiles, vinyl and other flooring surfaces to match your style and budget.'],
    ['Installation', 'Professional fitting for residential, office and commercial spaces, with careful surface preparation and clean finishing.'],
    ['Design Consultation', 'Practical guidance on colour, texture, pattern and layout so the floor works with the rest of your interior.'],
    ['Measurement & Quotation', 'Send us your floor area or plans and receive a clear quotation for materials and installation.'],
    ['Commercial Projects', 'Durable flooring for offices, hotels, schools, clinics, retail and other high-traffic areas.'],
    ['Maintenance & Aftercare', 'Advice on cleaning and caring for your floor so it keeps looking good for longer.'],
];
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner"><div class="container"><h1>Our Services</h1><p class="crumbs"><a href="<?= e(url()) ?>">Home</a> / Services</p></div></section>
<section class="section">
  <div class="container">
    <div class="grid grid-3">
      <?php foreach ($services as $i => $s): ?>
        <div class="service"><span class="num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span><h3><?= e($s[0]) ?></h3><p><?= e($s[1]) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="cta"><div class="container cta-inner"><div><h2>Need help choosing?</h2><p>Our team is ready to guide you.</p></div><a class="btn btn-primary" href="<?= e(url('contact')) ?>">Get a Quote</a></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
