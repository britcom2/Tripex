<?php
// ---------- Basics ----------
function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path)
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

// ---------- Settings ----------
function setting(string $key, string $default = ''): string
{
    static $all = null;
    if ($all === null) {
        $all = [];
        try {
            foreach (q('SELECT skey, svalue FROM settings')->fetchAll() as $r) {
                $all[$r['skey']] = (string)$r['svalue'];
            }
        } catch (Throwable $ex) {
            $all = [];
        }
    }
    return (isset($all[$key]) && $all[$key] !== '') ? $all[$key] : $default;
}

function save_setting(string $key, string $value): void
{
    q('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', [$key, $value]);
}

// ---------- Security: CSRF, flash ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_ok(): bool
{
    return isset($_POST['_csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['_csrf']);
}

function csrf_require(): void
{
    if (!csrf_ok()) {
        http_response_code(400);
        exit('Invalid or expired form. Please go back, refresh the page and try again.');
    }
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function flash_render(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $out .= '<div class="alert alert-' . e($f[0]) . '">' . e($f[1]) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

// ---------- Auth ----------
function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function admin_inventory_schema_ready(): bool
{
    $requiredColumns = [
        'admins' => ['is_active'],
        'products' => ['stock_quantity', 'low_stock_threshold'],
    ];
    foreach ($requiredColumns as $table => $columns) {
        foreach ($columns as $column) {
            if (!(bool)q(
                'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, $column]
            )->fetchColumn()) {
                return false;
            }
        }
    }
    return (bool)q(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        ['product_stock_adjustments']
    )->fetchColumn();
}

function require_admin(bool $requireInventorySchema = true): void
{
    if (!admin_logged_in()) {
        redirect('admin/login.php');
    }
    $schemaReady = admin_inventory_schema_ready();
    if ($requireInventorySchema && !$schemaReady) {
        redirect('admin/upgrade.php');
    }
    if ($schemaReady) {
        $admin = q('SELECT is_active FROM admins WHERE id = ?', [(int)$_SESSION['admin_id']])->fetch();
        if (!$admin || !(int)$admin['is_active']) {
            unset($_SESSION['admin_id'], $_SESSION['admin_user']);
            session_regenerate_id(true);
            flash('error', 'This administrator account is inactive. Contact an active administrator.');
            redirect('admin/login.php');
        }
    }
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

// ---------- Text helpers ----------
function slugify(string $s): string
{
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($t !== false && $t !== '') {
        $s = $t;
    }
    $s = strtolower($s);
    $s = preg_replace('~[^a-z0-9]+~', '-', $s);
    $s = trim($s, '-');
    return $s !== '' ? $s : 'item';
}

function unique_slug(string $table, string $base, int $excludeId = 0): string
{
    $allowed = ['products', 'categories'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Bad table');
    }
    $slug = slugify($base);
    $i = 1;
    $candidate = $slug;
    while (true) {
        $st = q("SELECT id FROM `$table` WHERE slug = ? AND id <> ? LIMIT 1", [$candidate, $excludeId]);
        if (!$st->fetch()) {
            return $candidate;
        }
        $i++;
        $candidate = $slug . '-' . $i;
    }
}

function money($n): string
{
    return setting('currency', 'KES') . ' ' . number_format((float)$n, 0);
}

function price_html(array $p): string
{
    if ($p['price'] === null || (float)$p['price'] <= 0) {
        return '<span class="price quote">Request a quote</span>';
    }
    $unit = !empty($p['unit']) ? ' <small>/ ' . e($p['unit']) . '</small>' : '';
    $sale = $p['sale_price'];
    if ($sale !== null && (float)$sale > 0 && (float)$sale < (float)$p['price']) {
        return '<span class="price"><del>' . money($p['price']) . '</del> <strong>' . money($sale) . '</strong>' . $unit . '</span>';
    }
    return '<span class="price"><strong>' . money($p['price']) . '</strong>' . $unit . '</span>';
}

function stock_label(string $s): string
{
    $map = ['in_stock' => 'In stock', 'out_of_stock' => 'Out of stock', 'made_to_order' => 'Made to order'];
    return $map[$s] ?? 'In stock';
}

function parse_specs(?string $text): array
{
    $rows = [];
    foreach (preg_split('/\r\n|\r|\n/', (string)$text) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $pos = strpos($line, ':');
        if ($pos === false) {
            $rows[] = ['', $line];
        } else {
            $rows[] = [trim(substr($line, 0, $pos)), trim(substr($line, $pos + 1))];
        }
    }
    return $rows;
}

function excerpt(string $s, int $len = 140): string
{
    $s = trim(preg_replace('/\s+/', ' ', strip_tags($s)));
    if (function_exists('mb_strlen') && mb_strlen($s) > $len) {
        return mb_substr($s, 0, $len - 1) . '…';
    }
    return strlen($s) > $len ? substr($s, 0, $len - 1) . '…' : $s;
}

// ---------- Images ----------
function img_url(?string $file): string
{
    if (!$file) {
        return url('assets/img/placeholder.svg');
    }
    return url('uploads/' . $file);
}

function img_srcset(?string $file): string
{
    if (!$file || !preg_match('/^[A-Za-z0-9_.-]+$/', $file)) {
        return '';
    }
    $info = pathinfo($file);
    $set = [];
    foreach ([480, 960, 1280] as $width) {
        $variant = $info['filename'] . '_' . $width . 'w.' . ($info['extension'] ?? '');
        if (is_file(ROOT . '/uploads/' . $variant)) {
            $set[] = url('uploads/' . $variant) . ' ' . $width . 'w';
        }
    }
    return implode(', ', $set);
}

function img_srcset_attr(?string $file, string $sizes = '100vw'): string
{
    $srcset = img_srcset($file);
    return $srcset === '' ? '' : ' srcset="' . e($srcset) . '" sizes="' . e($sizes) . '"';
}

function generate_image_variants(string $file, string $extension, int $maxWidth): void
{
    if (!function_exists('imagecreatetruecolor')) {
        return;
    }
    $sourcePath = ROOT . '/uploads/' . $file;
    if ($extension === 'jpg' && function_exists('imagecreatefromjpeg')) {
        $source = @imagecreatefromjpeg($sourcePath);
    } elseif ($extension === 'png' && function_exists('imagecreatefrompng')) {
        $source = @imagecreatefrompng($sourcePath);
    } elseif (
        $extension === 'webp'
        && function_exists('imagecreatefromwebp')
        && function_exists('imagewebp')
    ) {
        $source = @imagecreatefromwebp($sourcePath);
    } else {
        return;
    }
    if (!$source) {
        return;
    }
    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    foreach ([480, 960, 1280] as $width) {
        if ($width >= $sourceWidth || $width >= $maxWidth) {
            continue;
        }
        $height = max(1, (int)round($sourceHeight * ($width / $sourceWidth)));
        $variant = imagecreatetruecolor($width, $height);
        if ($extension !== 'jpg') {
            imagealphablending($variant, false);
            imagesavealpha($variant, true);
            $transparent = imagecolorallocatealpha($variant, 0, 0, 0, 127);
            imagefill($variant, 0, 0, $transparent);
        }
        imagecopyresampled($variant, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
        $path = ROOT . '/uploads/' . pathinfo($file, PATHINFO_FILENAME) . '_' . $width . 'w.' . $extension;
        if ($extension === 'jpg') {
            $saved = imagejpeg($variant, $path, 82);
        } elseif ($extension === 'png') {
            $saved = imagepng($variant, $path, 7);
        } else {
            $saved = imagewebp($variant, $path, 82);
        }
        if ($saved) {
            @chmod($path, 0644);
        }
        imagedestroy($variant);
    }
    imagedestroy($source);
}

function normalize_files(string $key): array
{
    $out = [];
    if (empty($_FILES[$key])) {
        return $out;
    }
    $f = $_FILES[$key];
    if (!is_array($f['name'])) {
        return ($f['error'] === UPLOAD_ERR_NO_FILE) ? [] : [$f];
    }
    foreach ($f['name'] as $i => $name) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

/**
 * Validate and store an uploaded image. Returns [filename|null, error|null].
 */
function save_upload(array $f, string $prefix = 'img', int $maxWidth = 1600): array
{
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Upload failed (code ' . (int)$f['error'] . '). Check the file size limit on your hosting.'];
    }
    if ($f['size'] > 6 * 1024 * 1024) {
        return [null, $f['name'] . ' is larger than 6 MB.'];
    }
    $info = @getimagesize($f['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) {
        return [null, $f['name'] . ' is not a JPG, PNG or WEBP image.'];
    }
    $ext = $types[$info[2]];
    $dir = ROOT . '/uploads';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $name = $prefix . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    $done = false;
    if (function_exists('imagecreatefromjpeg') && $info[0] > 0) {
        $src = null;
        if ($ext === 'jpg') {
            $src = @imagecreatefromjpeg($f['tmp_name']);
        } elseif ($ext === 'png') {
            $src = @imagecreatefrompng($f['tmp_name']);
        } elseif ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
            $src = @imagecreatefromwebp($f['tmp_name']);
        }
        if ($src) {
            $w = imagesx($src);
            $h = imagesy($src);
            if ($w > $maxWidth) {
                $nh = (int)round($h * ($maxWidth / $w));
                $dst = imagecreatetruecolor($maxWidth, $nh);
                if ($ext !== 'jpg') {
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                }
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxWidth, $nh, $w, $h);
                imagedestroy($src);
                $src = $dst;
            }
            if ($ext === 'jpg') {
                $done = imagejpeg($src, $dest, 85);
            } elseif ($ext === 'png') {
                imagesavealpha($src, true);
                $done = imagepng($src, $dest, 6);
            } elseif (function_exists('imagewebp')) {
                $done = imagewebp($src, $dest, 85);
            }
            imagedestroy($src);
        }
    }
    if (!$done && !@move_uploaded_file($f['tmp_name'], $dest)) {
        return [null, 'Could not save ' . $f['name'] . '. Check that the uploads folder is writable.'];
    }
    @chmod($dest, 0644);
    generate_image_variants($name, $ext, $maxWidth);
    return [$name, null];
}

function delete_upload(?string $file): void
{
    if ($file && preg_match('/^[A-Za-z0-9_.-]+$/', $file)) {
        $p = ROOT . '/uploads/' . $file;
        if (is_file($p)) {
            @unlink($p);
        }
        $info = pathinfo($file);
        foreach ([480, 960, 1280] as $width) {
            $variant = ROOT . '/uploads/' . $info['filename'] . '_' . $width . 'w.' . ($info['extension'] ?? '');
            if (is_file($variant)) {
                @unlink($variant);
            }
        }
    }
}

// ---------- Pagination ----------
function pager(int $total, int $per, int $page): string
{
    $pages = (int)ceil($total / max(1, $per));
    if ($pages <= 1) {
        return '';
    }
    $qs = $_GET;
    $out = '<nav class="pager">';
    for ($i = 1; $i <= $pages; $i++) {
        $qs['page'] = $i;
        $out .= '<a class="' . ($i === $page ? 'active' : '') . '" href="?' . e(http_build_query($qs)) . '">' . $i . '</a>';
    }
    return $out . '</nav>';
}

// ---------- Product queries / cards ----------
function product_select(): string
{
    return 'SELECT p.*, c.name AS cat_name, c.slug AS cat_slug,
        (SELECT filename FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS image
        FROM products p LEFT JOIN categories c ON c.id = p.category_id';
}

function product_url(string $slug): string
{
    return url('product/' . $slug);
}

function product_card(array $p): string
{
    $badge = '';
    if ($p['sale_price'] !== null && (float)$p['sale_price'] > 0 && (float)$p['sale_price'] < (float)$p['price']) {
        $badge = '<span class="badge badge-sale">Offer</span>';
    } elseif ($p['stock_status'] === 'out_of_stock') {
        $badge = '<span class="badge badge-out">Out of stock</span>';
    }
    return '<article class="card product-card">
        <a class="card-img" href="' . e(product_url($p['slug'])) . '">' . $badge . '<img loading="lazy" decoding="async" src="' . e(img_url($p['image'])) . '"' . img_srcset_attr($p['image'], '(max-width: 600px) 48vw, (max-width: 1000px) 45vw, 25vw') . ' alt="' . e($p['name']) . '"></a>
        <div class="card-body">
            <span class="cat">' . e($p['cat_name'] ?? 'Flooring') . '</span>
            <h3><a href="' . e(product_url($p['slug'])) . '">' . e($p['name']) . '</a></h3>
            <p>' . e(excerpt((string)($p['short_description'] ?: $p['description']), 90)) . '</p>
            <div class="card-foot">' . price_html($p) . '<div class="card-actions"><a class="link-arrow" href="' . e(product_url($p['slug'])) . '">Details &rarr;</a><form method="post" action="' . e(url('quote.php')) . '" class="card-quote-form">' . csrf_field() . '<input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="' . (int)$p['id'] . '"><button type="submit">Add to quote</button></form></div></div>
        </div></article>';
}

// ---------- Enquiries ----------
/**
 * Process the enquiry form. Returns [success(bool), errors(array)].
 */
function handle_enquiry(?int $productId = null): array
{
    $errors = [];
    if (!csrf_ok()) {
        return [false, ['Your session expired. Please refresh the page and try again.']];
    }
    if (!empty($_POST['website'])) { // honeypot
        return [true, []];
    }
    if (!empty($_SESSION['last_enquiry']) && time() - $_SESSION['last_enquiry'] < 20) {
        return [false, ['Please wait a few seconds before sending another message.']];
    }
    $name    = trim((string)($_POST['name'] ?? ''));
    $email   = trim((string)($_POST['email'] ?? ''));
    $phone   = trim((string)($_POST['phone'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    if ($name === '' || strlen($name) > 120) {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($message) < 5) {
        $errors[] = 'Please enter a message.';
    }
    if (strlen($message) > 5000) {
        $errors[] = 'Your message is too long.';
    }
    if ($errors) {
        return [false, $errors];
    }
    q('INSERT INTO enquiries (product_id, name, email, phone, subject, message, ip) VALUES (?,?,?,?,?,?,?)',
        [$productId, $name, $email, $phone ?: null, substr($subject, 0, 190) ?: null, $message, client_ip()]);
    $_SESSION['last_enquiry'] = time();

    $to = setting('email');
    if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $body = "New website enquiry\n\nName: $name\nEmail: $email\nPhone: $phone\nSubject: $subject\n\n$message\n";
        $headers = 'From: ' . $to . "\r\nReply-To: " . str_replace(["\r", "\n"], '', $email) . "\r\nContent-Type: text/plain; charset=UTF-8";
        @mail($to, 'Website enquiry: ' . str_replace(["\r", "\n"], ' ', $subject ?: $name), $body, $headers);
    }
    return [true, []];
}
