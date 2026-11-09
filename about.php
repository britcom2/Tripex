<?php
require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'About Us';
$metaDesc = 'Learn about Tripex, our architectural flooring expertise, company journey, team and standards.';

$source = trim(setting('about_text', "Tripex Carpets supplies premium flooring solutions including carpet tiles, wall-to-wall carpets, vinyl planks and architectural surfaces.\n\nWe help homeowners, designers, contractors and businesses choose durable, good-looking flooring that suits their space and budget."));
$sectionNames = [
    'our heritage' => 'heritage',
    'our mission' => 'mission',
    'our vision' => 'vision',
    'our core values' => 'values',
    'our corporate journey' => 'journey',
    'executive leadership' => 'leadership',
    'international standards accreditations' => 'accreditations',
];
$sections = [];
$current = 'intro';
foreach (preg_split('/\R/', $source) as $line) {
    $line = trim($line);
    if ($line === '') {
        continue;
    }
    $key = strtolower($line);
    if (isset($sectionNames[$key])) {
        $current = $sectionNames[$key];
        $sections[$current] = [];
    } else {
        $sections[$current][] = $line;
    }
}

$intro = $sections['intro'] ?? [];
$heritage = $sections['heritage'] ?? [];
$mission = $sections['mission'] ?? [];
$vision = $sections['vision'] ?? [];
$values = $sections['values'] ?? [];
$journey = $sections['journey'] ?? [];
$leaders = $sections['leadership'] ?? [];
$accreditations = $sections['accreditations'] ?? [];

$storyTitle = count($heritage) > 1 ? array_shift($heritage) : 'Flooring expertise, built around people';
$storyText = $heritage ?: $intro;
if (!$storyText) {
    $storyText = ['We help homeowners, designers, contractors and businesses choose durable, considered flooring for the spaces they use every day.'];
}

$valueCards = [];
for ($i = 0; $i < count($values); $i += 2) {
    $valueCards[] = ['title' => $values[$i], 'text' => $values[$i + 1] ?? ''];
}
$milestones = [];
for ($i = 0; $i < count($journey); $i += 3) {
    $milestones[] = [
        'year' => $journey[$i],
        'title' => $journey[$i + 1] ?? '',
        'text' => $journey[$i + 2] ?? '',
    ];
}
$team = [];
$leadershipIntro = '';
if (!empty($leaders)) {
    $leadershipIntro = array_shift($leaders);
}
for ($i = 0; $i < count($leaders); $i += 3) {
    $team[] = [
        'role' => $leaders[$i],
        'name' => $leaders[$i + 1] ?? '',
        'text' => $leaders[$i + 2] ?? '',
    ];
}
$accreditationIntro = '';
if (count($accreditations) > 1) {
    $last = end($accreditations);
    if (strlen($last) > 80) {
        $accreditationIntro = array_pop($accreditations);
    }
}

$photos = q('SELECT title, filename FROM gallery ORDER BY sort_order, id DESC LIMIT 6')->fetchAll();
$storyPhotoCaption = $photos[0]['title'] ?? '';
$imageSlots = [
    ['image' => $photos[0]['filename'] ?? null, 'placeholder' => 'about-heritage.svg', 'alt' => 'Flooring craftsmanship and material detail'],
    ['image' => $photos[1]['filename'] ?? null, 'placeholder' => 'about-interior.svg', 'alt' => 'A finished interior flooring project'],
    ['image' => $photos[2]['filename'] ?? null, 'placeholder' => 'about-materials.svg', 'alt' => 'Flooring materials and finishes'],
];
$workSlots = [
    ['image' => $photos[3]['filename'] ?? null, 'placeholder' => 'about-interior.svg', 'alt' => 'Tripex flooring project'],
    ['image' => $photos[4]['filename'] ?? null, 'placeholder' => 'about-heritage.svg', 'alt' => 'Flooring installation detail'],
    ['image' => $photos[5]['filename'] ?? null, 'placeholder' => 'about-materials.svg', 'alt' => 'Material and finish selection'],
];

include __DIR__ . '/includes/header.php';
?>
<section class="page-banner about-banner">
  <div class="container">
    <span class="eyebrow">The Tripex story</span>
    <h1>Spaces made to last.</h1>
    <p class="about-banner-copy">Thoughtful materials, skilled installation and a lasting commitment to the spaces where life happens.</p>
    <p class="crumbs"><a href="<?= e(url()) ?>">Home</a> / About</p>
  </div>
</section>

<section class="section about-story">
  <div class="container about-story-grid">
    <div class="about-photo-collage" aria-label="Tripex project photography">
      <figure class="about-photo about-photo-main">
        <img fetchpriority="high" src="<?= e($imageSlots[0]['image'] ? img_url($imageSlots[0]['image']) : url('assets/img/' . $imageSlots[0]['placeholder'])) ?>"<?= img_srcset_attr($imageSlots[0]['image'], '(max-width: 600px) 90vw, 45vw') ?> alt="<?= e($imageSlots[0]['alt']) ?>">
        <figcaption><?= e($storyPhotoCaption ?: 'Materials chosen with purpose') ?></figcaption>
      </figure>
      <figure class="about-photo about-photo-small">
        <img loading="lazy" decoding="async" src="<?= e($imageSlots[1]['image'] ? img_url($imageSlots[1]['image']) : url('assets/img/' . $imageSlots[1]['placeholder'])) ?>"<?= img_srcset_attr($imageSlots[1]['image'], '35vw') ?> alt="<?= e($imageSlots[1]['alt']) ?>">
      </figure>
      <figure class="about-photo about-photo-detail">
        <img loading="lazy" decoding="async" src="<?= e($imageSlots[2]['image'] ? img_url($imageSlots[2]['image']) : url('assets/img/' . $imageSlots[2]['placeholder'])) ?>"<?= img_srcset_attr($imageSlots[2]['image'], '35vw') ?> alt="<?= e($imageSlots[2]['alt']) ?>">
      </figure>
      <span class="about-photo-note">Made for everyday living</span>
    </div>
    <div class="about-story-copy">
      <span class="about-kicker">Our heritage</span>
      <h2><?= e($storyTitle) ?></h2>
      <?php foreach ($storyText as $paragraph): ?><p><?= e($paragraph) ?></p><?php endforeach; ?>
      <div class="about-story-facts">
        <div><span>01</span><strong>Material expertise</strong></div>
        <div><span>02</span><strong>Careful installation</strong></div>
        <div><span>03</span><strong>Service that stays</strong></div>
      </div>
    </div>
  </div>
</section>

<section class="section about-work">
  <div class="container">
    <div class="section-head"><span class="about-kicker">Made for real spaces</span><h2>Details that make a difference.</h2><p>A few glimpses of the materials and spaces that shape our work.</p></div>
    <div class="about-work-grid">
      <?php foreach ($workSlots as $index => $slot): ?>
        <figure class="about-work-card">
          <img loading="lazy" decoding="async" src="<?= e($slot['image'] ? img_url($slot['image']) : url('assets/img/' . $slot['placeholder'])) ?>"<?= img_srcset_attr($slot['image'], '(max-width: 600px) 90vw, 33vw') ?> alt="<?= e($slot['alt']) ?>">
          <figcaption><span><?= sprintf('%02d', $index + 1) ?></span><?= e(($photos[$index + 3]['title'] ?? '') ?: 'Project detail') ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
    <?php if (!$photos): ?><p class="about-gallery-note">Project photo placeholders are ready. Add your own images in Admin → Gallery to show your work here.</p><?php endif; ?>
  </div>
</section>

<?php if ($mission || $vision): ?>
<section class="section section-alt about-purpose">
  <div class="container">
    <div class="section-head"><span class="about-kicker">What guides us</span><h2>Purpose in every detail.</h2></div>
    <div class="about-purpose-grid">
      <?php if ($mission): ?><article class="about-purpose-card"><span class="about-card-index">01</span><h3>Our Mission</h3><?php foreach ($mission as $paragraph): ?><p><?= e($paragraph) ?></p><?php endforeach; ?></article><?php endif; ?>
      <?php if ($vision): ?><article class="about-purpose-card"><span class="about-card-index">02</span><h3>Our Vision</h3><?php foreach ($vision as $paragraph): ?><p><?= e($paragraph) ?></p><?php endforeach; ?></article><?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($valueCards): ?>
<section class="section about-values">
  <div class="container">
    <div class="section-head"><span class="about-kicker">Our core values</span><h2>Good work starts with good principles.</h2></div>
    <div class="about-value-grid">
      <?php foreach ($valueCards as $index => $value): ?>
        <article class="about-value-card">
          <span class="about-value-number"><?= sprintf('%02d', $index + 1) ?></span>
          <h3><?= e($value['title']) ?></h3>
          <p><?= e($value['text']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($milestones): ?>
<section class="section section-alt about-journey">
  <div class="container">
    <div class="section-head"><span class="about-kicker">Our corporate journey</span><h2>Growing with every project.</h2></div>
    <div class="about-timeline">
      <?php foreach ($milestones as $milestone): ?>
        <article class="about-milestone">
          <span class="about-year"><?= e($milestone['year']) ?></span>
          <div><h3><?= e($milestone['title']) ?></h3><p><?= e($milestone['text']) ?></p></div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($team): ?>
<section class="section about-team">
  <div class="container">
    <div class="section-head"><span class="about-kicker">Executive leadership</span><h2>People behind the precision.</h2><?php if ($leadershipIntro): ?><p><?= e($leadershipIntro) ?></p><?php endif; ?></div>
    <div class="about-team-grid">
      <?php foreach ($team as $member): ?>
        <article class="about-team-card">
          <div class="about-team-photo"><img loading="lazy" src="<?= e(url('assets/img/about-team.svg')) ?>" alt="Portrait placeholder for <?= e($member['name']) ?>"><span>Portrait placeholder</span></div>
          <div class="about-team-copy"><span><?= e($member['role']) ?></span><h3><?= e($member['name']) ?></h3><p><?= e($member['text']) ?></p></div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($accreditations): ?>
<section class="about-standards">
  <div class="container about-standards-inner">
    <div><span class="about-kicker">International standards</span><h2>Quality you can trust.</h2><?php if ($accreditationIntro): ?><p><?= e($accreditationIntro) ?></p><?php endif; ?></div>
    <div class="about-standard-list"><?php foreach ($accreditations as $standard): ?><span><?= e($standard) ?></span><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<section class="cta"><div class="container cta-inner"><div><h2>Let's talk about your space</h2><p>Tell us what you are planning and our team will help you find the right solution.</p></div><a class="btn btn-primary" href="<?= e(url('contact')) ?>">Contact Us</a></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
