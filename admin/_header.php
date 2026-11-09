<?php
$cur = basename($_SERVER['SCRIPT_NAME']);
$unread = 0;
try {
    $unread = (int)q('SELECT COUNT(*) FROM enquiries WHERE is_read = 0')->fetchColumn();
} catch (Throwable $ex) {
}
$menu = [
    'index.php'       => ['Dashboard', ''],
    'products.php'    => ['Products', 'product_form.php'],
    'stock.php'       => ['Stock control', ''],
    'users.php'       => ['Staff accounts', ''],
    'categories.php'  => ['Categories', ''],
    'gallery.php'     => ['Gallery', ''],
    'enquiries.php'   => ['Enquiries', ''],
    'settings.php'    => ['Site Settings', ''],
    'account.php'     => ['My Account', ''],
];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($adminTitle ?? 'Admin') ?> | <?= e(setting('site_name', 'Tripex')) ?> Admin</title>
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>?v=2">
</head>
<body>
<aside class="side">
  <div class="logo"><?= e(setting('site_name', 'Tripex Carpets')) ?><small>Admin Panel</small></div>
  <nav>
    <?php foreach ($menu as $file => $m): ?>
      <a class="<?= ($cur === $file || ($file === 'products.php' && $cur === 'product_form.php') || ($file === 'stock.php' && $cur === 'upgrade.php')) ? 'active' : '' ?>" href="<?= e(url('admin/' . $file)) ?>">
        <?= e($m[0]) ?><?php if ($file === 'enquiries.php' && $unread): ?><span class="pill"><?= $unread ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="side-foot">
    <a href="<?= e(url()) ?>" target="_blank" rel="noopener">View website &nearr;</a>
    <a href="<?= e(url('admin/logout.php')) ?>">Log out</a>
  </div>
</aside>
<div class="content">
  <header class="topline"><h1><?= e($adminTitle ?? 'Admin') ?></h1><?= $adminActions ?? '' ?></header>
  <?= flash_render() ?>
