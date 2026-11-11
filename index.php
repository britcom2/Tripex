<?php
require __DIR__ . '/includes/bootstrap.php';

$pageTitle = '';
$metaDesc = setting('meta_description', 'Premium carpet tiles, luxury carpets and architectural flooring supplied and installed by Tripex in Kenya.');
$categories = q(
    'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) AS product_count
     FROM categories c WHERE c.is_active = 1 ORDER BY c.sort_order, c.name LIMIT 8'
)->fetchAll();
$photos = q('SELECT title, filename FROM gallery ORDER BY sort_order, id DESC LIMIT 12')->fetchAll();
$projectPhotoOffset = count($photos) >= 11 ? 6 : 0;
$featured = q(product_select() . ' WHERE p.is_active = 1 AND p.is_featured = 1 ORDER BY p.updated_at DESC LIMIT 4')->fetchAll();
if (!$featured) {
    $featured = q(product_select() . ' WHERE p.is_active = 1 ORDER BY p.created_at DESC LIMIT 4')->fetchAll();
}
$heroPhoto = setting('hero_image');
$heroTitle = setting('hero_title', "Beautiful underfoot.\nBuilt to perform.");
$heroTitleParts = preg_split('/\s*&\s*/', $heroTitle, 2);
$heroSubtitle = setting(
    'hero_subtitle',
    'Premium carpets, carpet tiles and resilient surfaces for distinctive homes, workplaces and hospitality spaces across Kenya.'
);

$photoAt = static function (int $index) use ($photos): string {
    $fallbacks = ['about-interior.svg', 'about-heritage.svg', 'about-materials.svg'];
    if (isset($photos[$index]['filename'])) {
        return img_url($photos[$index]['filename']);
    }
    return url('assets/img/' . $fallbacks[$index % count($fallbacks)]);
};
$photoTitle = static function (int $index, string $fallback) use ($photos): string {
    return !empty($photos[$index]['title']) ? $photos[$index]['title'] : $fallback;
};
$heroSlides = [
    [
        'image' => $heroPhoto ? img_url($heroPhoto) : $photoAt(0),
        'filename' => $heroPhoto ?: ($photos[0]['filename'] ?? null),
        'alt' => $heroPhoto ? 'Featured Tripex flooring project' : $photoTitle(0, 'Carpet flooring project'),
        'eyebrow' => 'Architectural flooring, thoughtfully delivered',
        'title' => $heroTitleParts[0],
        'emphasis' => $heroTitleParts[1] ?? '',
        'subtitle' => $heroSubtitle,
    ],
    [
        'image' => $photoAt(1),
        'filename' => $photos[1]['filename'] ?? null,
        'alt' => $photoTitle(1, 'Interior flooring detail'),
        'eyebrow' => 'Materials selected with purpose',
        'title' => 'Made for living.',
        'emphasis' => 'Ready for real life.',
        'subtitle' => 'Discover distinctive carpet, vinyl and timber-look finishes selected for comfort, character and lasting performance.',
    ],
    [
        'image' => $photoAt(2),
        'filename' => $photos[2]['filename'] ?? null,
        'alt' => $photoTitle(2, 'Flooring material detail'),
        'eyebrow' => 'From first measure to final fit',
        'title' => 'A better finish.',
        'emphasis' => 'Every detail considered.',
        'subtitle' => 'Bring your project to life with material guidance, accurate site measurement and careful installation across Kenya.',
    ],
];

$ranges = [
    ['name' => 'Carpet tiles', 'detail' => 'Modular comfort for busy commercial spaces.', 'photo' => 0],
    ['name' => 'Luxury carpets', 'detail' => 'Soft, considered finishes for hospitality and home.', 'photo' => 1],
    ['name' => 'SPC & vinyl flooring', 'detail' => 'Resilient surfaces with the look of natural timber.', 'photo' => 2],
    ['name' => 'Architectural surfaces', 'detail' => 'Thoughtful materials, prepared and fitted with care.', 'photo' => 3],
];
$services = [
    ['number' => '01', 'title' => 'Material selection', 'text' => 'Compare textures, finishes and performance to find a surface suited to the way your space is used.'],
    ['number' => '02', 'title' => 'Site measurement', 'text' => 'Plan your project with site measurements and a clear quotation for materials and installation.'],
    ['number' => '03', 'title' => 'Precision installation', 'text' => 'Careful preparation, accurate layouts and considered finishing from experienced installers.'],
];

include __DIR__ . '/includes/header.php';
?>
<section class="home-hero" data-home-slider aria-label="Featured Tripex flooring projects">
  <div class="home-hero-slides">
    <?php foreach ($heroSlides as $index => $slide): ?>
      <article class="home-hero-slide" data-home-slide aria-roledescription="slide" aria-label="<?= ($index + 1) ?> of <?= count($heroSlides) ?>" aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>" <?= $index > 0 ? 'hidden' : '' ?>>
        <div class="home-hero-background"><img <?= $index === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?> decoding="async" src="<?= e($slide['image']) ?>"<?= img_srcset_attr($slide['filename'], '100vw') ?> alt="<?= e($slide['alt']) ?>"></div>
        <div class="container home-hero-content">
          <div class="home-hero-copy">
            <span class="home-eyebrow"><span></span><?= e($slide['eyebrow']) ?></span>
            <h1><?php if ($slide['emphasis'] !== ''): ?><?= e($slide['title']) ?><br><em><?= e($slide['emphasis']) ?></em><?php else: ?><?= nl2br(e($slide['title'])) ?><?php endif; ?></h1>
            <p><?= e($slide['subtitle']) ?></p>
            <div class="hero-actions">
              <a class="btn btn-primary" href="<?= e(url('products')) ?>">Explore our collection</a>
              <a class="home-text-link" href="<?= e(url('contact')) ?>">Plan your project <span aria-hidden="true">&rarr;</span></a>
            </div>
            <div class="home-hero-note"><span class="home-note-mark">&#10003;</span><span>From thoughtful selection to a precise final fit.</span></div>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <div class="container home-hero-controls" aria-label="Hero slideshow controls">
    <button type="button" class="home-hero-arrow" data-slide-previous aria-label="Previous slide">&larr;</button>
    <div class="home-hero-dots">
      <?php foreach ($heroSlides as $index => $slide): ?>
        <button type="button" data-slide-to="<?= $index ?>" aria-label="Show slide <?= $index + 1 ?>" <?= $index === 0 ? 'aria-current="true"' : '' ?>></button>
      <?php endforeach; ?>
    </div>
    <button type="button" class="home-hero-arrow" data-slide-next aria-label="Next slide">&rarr;</button>
    <span class="sr-only" data-slider-status aria-live="polite">Slide 1 of <?= count($heroSlides) ?></span>
  </div>
</section>

<section class="home-proof" aria-label="Tripex project highlights">
  <div class="container home-proof-grid">
    <div><strong>2012</strong><span>Serving Kenya's flooring needs</span></div>
    <div><strong>1,500+</strong><span>Residential &amp; corporate installations</span></div>
    <div><strong>1.2M <small>m²</small></strong><span>Flooring supplied and installed</span></div>
    <div><strong>East Africa</strong><span>Projects delivered across the region</span></div>
  </div>
</section>

<section class="section home-ranges">
  <div class="container">
    <div class="home-section-heading">
      <div><span class="home-kicker">The collection</span><h2>Surfaces for every setting.</h2><p>Explore materials selected for their character, comfort and everyday performance.</p></div>
      <a class="home-text-link" href="<?= e(url('products')) ?>">Browse all products <span aria-hidden="true">&rarr;</span></a>
    </div>
    <?php if ($categories): ?>
      <div class="home-range-grid">
        <?php foreach (array_slice($categories, 0, 4) as $index => $category): ?>
          <a class="home-range-card" href="<?= e(url('category/' . $category['slug'])) ?>">
            <img loading="lazy" decoding="async" src="<?= e($category['image'] ? img_url($category['image']) : $photoAt($index)) ?>"<?= img_srcset_attr($category['image'] ?: ($photos[$index]['filename'] ?? null), '(max-width: 520px) 48vw, 25vw') ?> alt="<?= e($category['name']) ?>">
            <span class="home-range-overlay"><small><?= sprintf('%02d', $index + 1) ?> / COLLECTION</small><strong><?= e($category['name']) ?></strong><span><?= (int)$category['product_count'] ?> product<?= (int)$category['product_count'] === 1 ? '' : 's' ?> <b aria-hidden="true">&rarr;</b></span></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="home-range-grid">
        <?php foreach ($ranges as $index => $range): ?>
          <a class="home-range-card" href="<?= e(url('products')) ?>">
            <img loading="lazy" decoding="async" src="<?= e($photoAt($range['photo'])) ?>"<?= img_srcset_attr($photos[$range['photo']]['filename'] ?? null, '(max-width: 520px) 48vw, 25vw') ?> alt="<?= e($range['name'] . ' flooring selection') ?>">
            <span class="home-range-overlay"><small><?= sprintf('%02d', $index + 1) ?> / COLLECTION</small><strong><?= e($range['name']) ?></strong><span><?= e($range['detail']) ?> <b aria-hidden="true">&rarr;</b></span></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section home-story section-alt">
  <div class="container home-story-grid">
    <div class="home-story-visual">
      <img loading="lazy" decoding="async" src="<?= e($photoAt(4)) ?>"<?= img_srcset_attr($photos[4]['filename'] ?? null, '(max-width: 760px) 90vw, 45vw') ?> alt="<?= e($photoTitle(4, 'Tripex flooring installation')) ?>">
      <div class="home-story-inset"><img loading="lazy" decoding="async" src="<?= e($photoAt(5)) ?>"<?= img_srcset_attr($photos[5]['filename'] ?? null, '40vw') ?> alt="<?= e($photoTitle(5, 'Flooring craftsmanship')) ?>"></div>
      <span class="home-story-caption">Thoughtful details. Lasting finish.</span>
    </div>
    <div class="home-story-copy">
      <span class="home-kicker">The Tripex difference</span>
      <h2>Spaces deserve more than a surface.</h2>
      <p>We bring material knowledge, precise installation and dependable project support together, helping architects, developers and homeowners get more from every space.</p>
      <ul class="home-check-list">
        <li><span>&#10003;</span> Solutions for residential, commercial and hospitality projects</li>
        <li><span>&#10003;</span> Careful site preparation and accurate installation</li>
        <li><span>&#10003;</span> Practical guidance from selection through aftercare</li>
      </ul>
      <a class="btn btn-outline" href="<?= e(url('about')) ?>">Discover our story</a>
    </div>
  </div>
</section>

<?php if ($featured): ?>
<section class="section home-featured">
  <div class="container">
    <div class="home-section-heading">
      <div><span class="home-kicker">Considered choices</span><h2>Selected for your next project.</h2><p>Explore a few of the products in our collection.</p></div>
      <a class="home-text-link" href="<?= e(url('products')) ?>">View the collection <span aria-hidden="true">&rarr;</span></a>
    </div>
    <div class="grid grid-4"><?php foreach ($featured as $product) { echo product_card($product); } ?></div>
  </div>
</section>
<?php endif; ?>

<section class="section home-services section-alt">
  <div class="container">
    <div class="home-section-heading">
      <div><span class="home-kicker">From first idea to final fit</span><h2>Good work, at every step.</h2><p>A considered process helps every project come together beautifully.</p></div>
      <a class="home-text-link" href="<?= e(url('services')) ?>">Explore our services <span aria-hidden="true">&rarr;</span></a>
    </div>
    <div class="home-service-grid">
      <?php foreach ($services as $service): ?>
        <article class="home-service-card"><span><?= e($service['number']) ?></span><h3><?= e($service['title']) ?></h3><p><?= e($service['text']) ?></p><a href="<?= e(url('services')) ?>" aria-label="Learn about <?= e($service['title']) ?>">&rarr;</a></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section home-projects">
  <div class="container">
    <div class="home-section-heading">
      <div><span class="home-kicker">A closer look</span><h2>Materials meet real life.</h2><p>Discover the details, textures and spaces behind our work.</p></div>
      <a class="home-text-link" href="<?= e(url('gallery')) ?>">See projects <span aria-hidden="true">&rarr;</span></a>
    </div>
    <div class="home-project-grid">
      <?php for ($i = 0; $i < 5; $i++): ?>
        <?php $projectPhotoIndex = $projectPhotoOffset + $i; ?>
        <a class="home-project-photo home-project-photo-<?= $i + 1 ?>" href="<?= e(url('gallery')) ?>">
          <img loading="lazy" decoding="async" src="<?= e($photoAt($projectPhotoIndex)) ?>"<?= img_srcset_attr($photos[$projectPhotoIndex]['filename'] ?? null, '(max-width: 520px) 90vw, 35vw') ?> alt="<?= e($photoTitle($projectPhotoIndex, 'Tripex architectural flooring project')) ?>">
          <?php if (!empty($photos[$projectPhotoIndex]['title'])): ?><span><?= e($photos[$projectPhotoIndex]['title']) ?></span><?php endif; ?>
        </a>
      <?php endfor; ?>
    </div>
  </div>
</section>

<section class="home-purpose">
  <div class="container home-purpose-grid">
    <article><span class="home-kicker">Our mission</span><h2>To empower architects and selective developers with world-class tiling, carpeting, and wood structures that outperform normal wear-rating baselines via superior laying accuracy.</h2></article>
    <article><span class="home-kicker">Our vision</span><h2>To be established as the absolute regional reference for architectural surfaces, synonymous with meticulous high-end residential finish and B2B logistical reliability.</h2><a class="home-text-link" href="<?= e(url('about')) ?>">More about Tripex <span aria-hidden="true">&rarr;</span></a></article>
  </div>
</section>

<section class="home-cta">
  <div class="container home-cta-inner">
    <div><span class="home-kicker">Let's make a good space great</span><h2>Have a project in mind?</h2><p>Talk to us about materials, measurements and a clear path to installation.</p></div>
    <div><a class="btn btn-primary" href="<?= e(url('contact')) ?>">Talk to our team</a><a class="home-cta-secondary" href="<?= e(url('products')) ?>">Browse products &rarr;</a></div>
  </div>
</section>
<script defer src="<?= e(url('assets/js/home-slider.js')) ?>?v=1"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
