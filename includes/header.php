<?php
$current = basename($_SERVER['SCRIPT_NAME']);
$siteName = setting('site_name', 'Tripex Carpets');
$fullTitle = isset($pageTitle) && $pageTitle ? $pageTitle . ' | ' . $siteName : $siteName . ' - ' . setting('tagline', 'Premium Flooring & Carpet Solutions');
$desc = isset($metaDesc) && $metaDesc ? $metaDesc : setting('meta_description', 'Premium carpets, carpet tiles, vinyl planks and architectural flooring surfaces in Kenya.');
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
<meta name="theme-color" content="#f5f1e8">
<meta property="og:title" content="<?= e($fullTitle) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=14">
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
    <a class="brand" href="<?= e(url()) ?>">
      <?php if ($logo): ?><img src="<?= e(img_url($logo)) ?>"<?= img_srcset_attr($logo, '180px') ?> alt="<?= e($siteName) ?>"><?php else: ?><span class="brand-mark">T</span><span class="brand-text"><?= e($siteName) ?></span><?php endif; ?>
    </a>
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
