<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="tripex-products-template.csv"');

$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, [
    'name',
    'sku',
    'category',
    'slug',
    'short_description',
    'description',
    'specs',
    'price',
    'sale_price',
    'unit',
    'stock_status',
    'is_active',
    'is_featured',
]);
fputcsv($output, [
    'Example carpet tile',
    'CT-001',
    'carpet-tiles',
    '',
    'Durable modular carpet tile',
    'Product description goes here',
    "Size: 50cm x 50cm\nUse: Commercial",
    '',
    '',
    'tile',
    'in_stock',
    '1',
    '0',
]);
fclose($output);
