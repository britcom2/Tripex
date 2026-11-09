<?php
// One-time web installer. DELETE THIS FILE after installation.
define('ROOT', __DIR__);
$adminSetup = false;
$alreadyInstalled = false;
$setupError = '';
$setupPdo = null;

if (is_file(ROOT . '/config.php')) {
    require ROOT . '/config.php';
    try {
        $setupPdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        $alreadyInstalled = (bool)$setupPdo->query('SELECT id FROM admins LIMIT 1')->fetch();
        $adminSetup = !$alreadyInstalled;
    } catch (Throwable $ex) {
        $setupError = 'Could not check the existing database. Verify the database settings in config.php and confirm that database/schema.sql has been imported.';
    }
}
session_start();
if (empty($_SESSION['inst'])) {
    $_SESSION['inst'] = bin2hex(random_bytes(16));
}

$errors = [];
$f = [
    'db_host' => 'localhost', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
    'site_name' => 'Tripex Carpets', 'admin_user' => 'admin', 'sample' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $_) {
        $f[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $adminPass = (string)($_POST['admin_pass'] ?? '');
    if (!hash_equals($_SESSION['inst'], (string)($_POST['_t'] ?? ''))) {
        $errors[] = 'Session expired, please reload the page.';
    }
    if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $f['admin_user'])) {
        $errors[] = 'Admin username must be 3–60 characters and use only letters, numbers, dots, underscores or hyphens.';
    }
    if (strlen($adminPass) < 8 || strlen($adminPass) > 4096) {
        $errors[] = 'Admin password must be between 8 and 4096 characters.';
    }
    if (!$adminSetup && !$alreadyInstalled && $setupError === '' && ($f['db_name'] === '' || $f['db_user'] === '')) {
        $errors[] = 'Database name and user are required.';
    }

    if (!$errors) {
        try {
            if ($adminSetup) {
                $lockName = 'tripex_admin_' . substr(hash('sha256', DB_NAME), 0, 40);
                $lock = $setupPdo->prepare('SELECT GET_LOCK(?, 10)');
                $lock->execute([$lockName]);
                if ((int)$lock->fetchColumn() !== 1) {
                    throw new RuntimeException('Could not obtain the admin setup lock. Please try again.');
                }

                try {
                    $setupPdo->beginTransaction();
                    if ($setupPdo->query('SELECT id FROM admins LIMIT 1 FOR UPDATE')->fetch()) {
                        throw new RuntimeException('An administrator account has already been created. Please sign in instead.');
                    }
                    $setupPdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')->execute([
                        $f['admin_user'],
                        password_hash($adminPass, PASSWORD_DEFAULT),
                    ]);
                    $setupPdo->commit();
                    $done = true;
                    $adminCreated = true;
                } catch (Throwable $ex) {
                    if ($setupPdo->inTransaction()) {
                        $setupPdo->rollBack();
                    }
                    throw $ex;
                } finally {
                    $release = $setupPdo->prepare('SELECT RELEASE_LOCK(?)');
                    $release->execute([$lockName]);
                }
            } elseif ($alreadyInstalled || $setupError !== '') {
                throw new RuntimeException('Admin setup is unavailable. Reload the page and check the existing database configuration.');
            } else {
            $pdo = new PDO('mysql:host=' . $f['db_host'] . ';dbname=' . $f['db_name'] . ';charset=utf8mb4', $f['db_user'], $_POST['db_pass'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $sql = file_get_contents(ROOT . '/database/schema.sql');
            foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
                $pdo->exec($stmt);
            }

            // Admin
            $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')->execute([$f['admin_user'], password_hash($adminPass, PASSWORD_DEFAULT)]);

            // Settings
            $defaults = [
                'site_name' => $f['site_name'] ?: 'Tripex Carpets',
                'tagline' => 'Premium Flooring & Carpet Solutions',
                'hero_title' => 'Premium Flooring & Carpet Solutions',
                'hero_subtitle' => 'Carpet tiles, vinyl planks and architectural surfaces for homes, offices and commercial spaces.',
                'hours' => 'Mon - Sat: 8:00 AM - 5:30 PM',
                'currency' => 'KES',
                'phone' => '', 'email' => '', 'address' => 'Nairobi, Kenya',
                'meta_description' => 'Premium flooring, carpet tiles, vinyl planks and architectural surfaces in Kenya.',
            ];
            $st = $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
            foreach ($defaults as $k => $v) {
                $st->execute([$k, $v]);
            }

            // Categories
            $cats = [
                ['Carpet Tiles', 'carpet-tiles', 'Modular carpet tiles for offices, retail and commercial interiors.'],
                ['Wall-to-Wall Carpets', 'wall-to-wall-carpets', 'Broadloom carpets for homes, hotels and offices.'],
                ['Vinyl Planks', 'vinyl-planks', 'Luxury vinyl and SPC planks with realistic wood and stone looks.'],
                ['Vinyl Sheets', 'vinyl-sheets', 'Affordable, easy-care vinyl flooring rolls.'],
                ['Laminate Flooring', 'laminate-flooring', 'Durable click-lock laminate in a range of finishes.'],
                ['Artificial Grass', 'artificial-grass', 'Soft, hard-wearing turf for gardens, balconies and play areas.'],
            ];
            $ci = $pdo->prepare('INSERT INTO categories (name, slug, description, sort_order) VALUES (?, ?, ?, ?)');
            $ids = [];
            foreach ($cats as $i => $c) {
                $ci->execute([$c[0], $c[1], $c[2], $i]);
                $ids[$c[1]] = (int)$pdo->lastInsertId();
            }

            // Optional sample products (no prices claimed: left as "Request a quote")
            if ($f['sample'] === '1') {
                $samples = [
                    ['Modular Carpet Tile', 'modular-carpet-tile', 'carpet-tiles', 'Durable 50cm x 50cm carpet tiles for offices and commercial spaces.', "Tile size: 50cm x 50cm\nUse: Commercial and residential", 'tile', 1],
                    ['Wall-to-Wall Carpet', 'wall-to-wall-carpet', 'wall-to-wall-carpets', 'Soft, comfortable broadloom carpet for living rooms and bedrooms.', "Roll width: 4m\nUse: Residential and hospitality", 'm²', 1],
                    ['Luxury Vinyl Plank', 'luxury-vinyl-plank', 'vinyl-planks', 'Wood-look vinyl planks that are water resistant and easy to maintain.', "Look: Wood\nInstallation: Click-lock or glue-down", 'm²', 1],
                    ['PVC Vinyl Floor Roll', 'pvc-vinyl-floor-roll', 'vinyl-sheets', 'Affordable vinyl flooring in a range of patterns.', "Material: PVC vinyl\nFormat: Roll", 'm²', 0],
                ];
                $pi = $pdo->prepare('INSERT INTO products (category_id, name, slug, short_description, description, specs, unit, is_featured) VALUES (?,?,?,?,?,?,?,?)');
                foreach ($samples as $s) {
                    $pi->execute([$ids[$s[2]], $s[0], $s[1], $s[3], $s[3] . ' Replace this sample with your own product details, photos and price from the admin panel.', $s[4], $s[5], $s[6]]);
                }
            }

            // Base URL (supports subfolder installs)
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            $cfg = "<?php\n// Generated by install.php\n"
                . "define('DB_HOST', " . var_export($f['db_host'], true) . ");\n"
                . "define('DB_NAME', " . var_export($f['db_name'], true) . ");\n"
                . "define('DB_USER', " . var_export($f['db_user'], true) . ");\n"
                . "define('DB_PASS', " . var_export((string)($_POST['db_pass'] ?? ''), true) . ");\n"
                . "define('BASE_URL', " . var_export($base, true) . ");\n"
                . "define('DEBUG', false);\n";
            if (file_put_contents(ROOT . '/config.php', $cfg) === false) {
                throw new RuntimeException('Could not write config.php. Make the site folder writable, or create config.php from config.sample.php by hand.');
            }
            @chmod(ROOT . '/config.php', 0640);
            @mkdir(ROOT . '/uploads', 0755, true);
            $done = true;
            }
        } catch (Throwable $ex) {
            $errors[] = 'Installation failed: ' . $ex->getMessage();
        }
    }
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Install</title>
<style>
body{font-family:system-ui,sans-serif;background:#f3f4f6;margin:0;padding:30px 16px;color:#111827}
.box{max-width:560px;margin:auto;background:#fff;padding:28px;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.08)}
h1{margin-top:0;font-size:1.5rem}h3{margin:22px 0 6px;font-size:1rem;border-top:1px solid #e5e7eb;padding-top:16px}
label{display:block;margin:10px 0;font-size:.9rem;font-weight:600}
input[type=text],input[type=password]{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;margin-top:4px;box-sizing:border-box;font-size:1rem}
button{background:#f59e0b;border:0;padding:12px 22px;border-radius:8px;font-weight:700;font-size:1rem;cursor:pointer}
.err{background:#fee2e2;color:#991b1b;padding:10px 12px;border-radius:8px;margin-bottom:10px}
.ok{background:#dcfce7;color:#166534;padding:14px;border-radius:8px}
small{font-weight:400;color:#6b7280}
</style></head><body><div class="box">
<?php if ($alreadyInstalled): ?>
  <h1>Already installed</h1>
  <p>The database already has an administrator account. For security, sign in at the admin panel and delete <code>install.php</code> from your server.</p>
  <p><a href="./">Go to website</a> &middot; <a href="admin/">Admin login</a></p>
<?php elseif ($setupError !== ''): ?>
  <h1>Could not check setup status</h1>
  <div class="err"><?= htmlspecialchars($setupError, ENT_QUOTES, 'UTF-8') ?></div>
  <p>After confirming the database configuration and schema, reload this page.</p>
<?php elseif (!empty($done)): ?>
  <h1>Installation complete</h1>
  <div class="ok"><?= !empty($adminCreated) ? 'Your admin account has been created.' : 'The database is ready and your admin account has been created.' ?></div>
  <p><strong>Important:</strong> delete <code>install.php</code> from your server now.</p>
  <p><a href="admin/login.php">Go to admin login &rarr;</a> &nbsp; <a href="./">View website</a></p>
<?php else: ?>
  <h1>Website Installer</h1>
  <?php if ($adminSetup): ?>
    <p>The site configuration is present, but this database has no administrator yet. Create the first admin account below.</p>
  <?php else: ?>
  <p>Enter the MySQL details you created in cPanel, and choose your admin login.</p>
  <?php endif; ?>
  <?php foreach ($errors as $er): ?><div class="err"><?= htmlspecialchars($er) ?></div><?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="_t" value="<?= htmlspecialchars($_SESSION['inst']) ?>">
    <?php if (!$adminSetup): ?>
    <h3 style="border:0;padding:0;margin-top:14px">Database</h3>
    <label>Host <small>(usually localhost)</small><input type="text" name="db_host" value="<?= htmlspecialchars($f['db_host']) ?>"></label>
    <label>Database name<input type="text" name="db_name" value="<?= htmlspecialchars($f['db_name']) ?>"></label>
    <label>Database user<input type="text" name="db_user" value="<?= htmlspecialchars($f['db_user']) ?>"></label>
    <label>Database password<input type="password" name="db_pass"></label>
    <?php endif; ?>
    <h3>Site &amp; admin</h3>
    <?php if (!$adminSetup): ?>
    <label>Company name<input type="text" name="site_name" value="<?= htmlspecialchars($f['site_name']) ?>"></label>
    <?php endif; ?>
    <label>Admin username<input type="text" name="admin_user" value="<?= htmlspecialchars($f['admin_user']) ?>"></label>
    <label>Admin password <small>(min. 8 characters)</small><input type="password" name="admin_pass"></label>
    <?php if (!$adminSetup): ?>
    <label><input type="checkbox" name="sample" value="1" <?= $f['sample'] === '1' ? 'checked' : '' ?>> Add a few sample products</label>
    <?php endif; ?>
    <button type="submit"><?= $adminSetup ? 'Create admin account' : 'Install' ?></button>
  </form>
<?php endif; ?>
</div></body></html>
