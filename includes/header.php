<?php
$current = basename($_SERVER['SCRIPT_NAME']);
$siteName = setting('site_name', 'Tripex Carpets');
$tagline = setting('tagline', 'Premium Flooring & Carpet Solutions');
$fullTitle = isset($pageTitle) && $pageTitle ? $pageTitle . ' | ' . $siteName : (setting('seo_title') ?: $siteName . ' - ' . $tagline);
$desc = isset($metaDesc) && $metaDesc ? $metaDesc : setting('meta_description', 'Premium carpets, carpet tiles, vinyl planks and architectural flooring surfaces in Kenya.');
$canonicalBase = rtrim(setting('canonical_url'), '/');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$requestHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$routeSuffix = [
  'index.php' => '',
  'products.php' => '/products',
  'services.php' => '/services',
  'gallery.php' => '/gallery',
  'about.php' => '/about',
  'contact.php' => '/contact',
  'quote.php' => '/quote',
  'account.php' => '/account',
];
$canonical = $canonicalBase !== ''
  ? $canonicalBase . ($routeSuffix[$current] ?? strtok($_SERVER['REQUEST_URI'] ?? '/', '?'))
  : $scheme . '://' . $requestHost . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$ogImageUrl = !empty($ogImage) ? $ogImage : setting('og_image');
if ($ogImageUrl === '' && $logo) {
  $ogImageUrl = $scheme . '://' . $requestHost . img_url(setting('logo'));
}
$socialSameAs = array_values(array_filter([
  setting('facebook'), setting('instagram'), setting('tiktok'), setting('youtube'), setting('linkedin'), setting('x')
]));
$googleRating = trim(setting('google_rating'));
$googleReviewCount = trim(setting('google_review_count'));
$business = [
  '@context' => 'https://schema.org',
  '@type' => 'HomeAndConstructionBusiness',
  '@id' => $canonical . '#business',
  'name' => $siteName,
  'description' => $desc,
  'url' => $canonical,
  'telephone' => array_values(array_filter([setting('phone'), setting('phone2'), setting('phone3')])),
  'email' => setting('email'),
  'address' => [
    '@type' => 'PostalAddress',
    'streetAddress' => setting('address'),
    'addressLocality' => 'Nairobi',
    'postalCode' => '10538-00100',
    'addressCountry' => 'KE',
  ],
];
if ($ogImageUrl !== '') $business['image'] = $ogImageUrl;
if ($socialSameAs) $business['sameAs'] = $socialSameAs;
if ($googleRating !== '' && $googleReviewCount !== '') {
  $business['aggregateRating'] = [
    '@type' => 'AggregateRating',
    'ratingValue' => $googleRating,
    'reviewCount' => $googleReviewCount,
  ];
}
$nav = [
    'index.php'    => ['Home', 'index.php'],
    'products.php' => ['Products', 'products'],
    'services.php' => ['Services', 'services'],
    'gallery.php'  => ['Projects', 'gallery'],
    'about.php'    => ['About', 'about'],
    'account.php'  => ['Account', 'account.php'],
    'contact.php'  => ['Contact', 'contact'],
];
$logo = setting('logo');
$quoteBasket = $_SESSION['quote_basket'] ?? [];
$quoteCount = is_array($quoteBasket) ? count($quoteBasket) : 0;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<?php if (setting('seo_keywords')): ?><meta name="keywords" content="<?= e(setting('seo_keywords')) ?>"><?php endif; ?>
<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
<meta name="theme-color" content="#f5f1e8">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:title" content="<?= e($fullTitle) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<?php if ($ogImageUrl !== ''): ?><meta property="og:image" content="<?= e($ogImageUrl) ?>"><?php endif; ?>
<meta name="twitter:card" content="<?= $ogImageUrl !== '' ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= e($fullTitle) ?>">
<meta name="twitter:description" content="<?= e($desc) ?>">
<?php if ($ogImageUrl !== ''): ?><meta name="twitter:image" content="<?= e($ogImageUrl) ?>"><?php endif; ?>
<script type="application/ld+json"><?= json_encode($business, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=22">
</head>
<body>
<div class="topbar">
  <div class="container">
    <div>
      <?php if (setting('phone')): ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('phone'))) ?>">&#9742; <?= e(setting('phone')) ?></a><?php endif; ?>
      <?php if (setting('email')): ?><a href="mailto:<?= e(setting('email')) ?>">&#9993; <?= e(setting('email')) ?></a><?php endif; ?>
    </div>
    <div class="hide-sm"><?= e(setting('hours')) ?></div>
  </div>
</div>
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="<?= e(url()) ?>" aria-label="<?= e($siteName) ?>">
      <?php if ($logo): ?><img src="<?= e(img_url($logo)) ?>"<?= img_srcset_attr($logo, '180px') ?> alt=""><?php else: ?><span class="brand-mark" aria-hidden="true">T</span><?php endif; ?>
      <span class="brand-copy<?= $logo ? ' brand-copy-mobile' : '' ?>">
        <span class="brand-text"><?= e($siteName) ?></span>
        <small class="brand-tagline"><?= e($tagline) ?></small>
      </span>
    </a>
    <?php if (setting('phone')): ?><a class="mobile-header-call" href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('phone'))) ?>" aria-label="Call <?= e(setting('phone')) ?>"><span aria-hidden="true">&#9742;</span><span>Call</span></a><?php endif; ?>
    <button class="nav-toggle" aria-label="Menu" onclick="document.body.classList.toggle('nav-open')"><span></span><span></span><span></span></button>
    <nav class="main-nav">
      <?php foreach ($nav as $file => $n): ?>
        <?php if ($file === 'account.php'): ?>
          <details class="account-menu">
            <summary class="account-menu-button <?= $current === $file ? 'active' : '' ?>">Account</summary>
            <div class="account-menu-popup">
              <?php if (customer_logged_in()): ?>
                <a href="<?= e(url('account.php')) ?>">My account</a>
              <?php else: ?>
                <a href="<?= e(url('account.php#sign-in')) ?>">Sign in</a>
                <a href="<?= e(url('account.php#create-account')) ?>">Create account</a>
              <?php endif; ?>
            </div>
          </details>
        <?php else: ?>
          <a class="<?= $current === $file ? 'active' : '' ?>" href="<?= e($file === 'index.php' ? url() : url($n[1])) ?>"><?= e($n[0]) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
      <a class="btn btn-primary btn-sm" href="<?= e(url('quote')) ?>">Request a Quote<?php if ($quoteCount): ?> <span class="quote-count"><?= $quoteCount ?></span><?php endif; ?></a>
    </nav>
  </div>
</header>
<main>
