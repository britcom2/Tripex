<?php
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = rtrim(setting('canonical_url'), '/');
if ($base === '') {
    $base = $scheme . '://' . $host . rtrim(BASE_URL, '/');
}
$routes = ['', '/about', '/services', '/gallery', '/products', '/contact', '/quote'];
$urls = [];
foreach ($routes as $route) {
    $urls[] = ['loc' => $base . $route, 'changefreq' => 'weekly', 'priority' => $route === '' ? '1.0' : '0.7'];
}
try {
    $categories = q('SELECT slug FROM categories WHERE is_active = 1')->fetchAll();
    foreach ($categories as $category) {
        $urls[] = ['loc' => $base . '/category/' . rawurlencode($category['slug']), 'changefreq' => 'weekly', 'priority' => '0.7'];
    }
    $products = q('SELECT slug FROM products WHERE is_active = 1')->fetchAll();
    foreach ($products as $product) {
        $urls[] = ['loc' => $base . '/product/' . rawurlencode($product['slug']), 'changefreq' => 'weekly', 'priority' => '0.8'];
    }
} catch (Throwable $ex) {
}
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $item) {
    echo '  <url><loc>' . htmlspecialchars($item['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc><changefreq>' . $item['changefreq'] . '</changefreq><priority>' . $item['priority'] . '</priority></url>' . "\n";
}
echo '</urlset>';
