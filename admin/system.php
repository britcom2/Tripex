<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
$htaccess = is_readable(ROOT . '/.htaccess') ? file_get_contents(ROOT . '/.htaccess') : false;
$uploadProtectionConfigured = $htaccess !== false && preg_match('/RewriteRule\s+\^uploads\//i', $htaccess);
$installerPresent = is_file(ROOT . '/install.php');
$uploadsPath = ROOT . '/uploads';
$schemaReady = admin_inventory_schema_ready();
$uploadsReady = is_dir($uploadsPath) && is_writable($uploadsPath);
$gdAvailable = extension_loaded('gd');
$mailAvailable = function_exists('mail');
$release = null;
$releaseFile = ROOT . '/deployment.json';
if (is_readable($releaseFile)) {
  $releaseData = json_decode((string)file_get_contents($releaseFile), true);
  if (is_array($releaseData)
    && preg_match('/^[a-f0-9]{40}$/i', (string)($releaseData['commit'] ?? ''))
    && !empty($releaseData['deployed_at'])
    && strtotime((string)$releaseData['deployed_at']) !== false) {
    $release = $releaseData;
  }
}
$checks = [
    [
        'name' => 'PHP runtime',
        'ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'detail' => PHP_VERSION . ' (PHP 7.4+ supported; use a maintained version offered by Truehost)',
    ],
    [
        'name' => 'PDO MySQL',
        'ok' => extension_loaded('pdo_mysql'),
        'detail' => extension_loaded('pdo_mysql') ? 'Available' : 'Enable pdo_mysql in cPanel PHP Extensions',
    ],
    [
        'name' => 'Database schema',
        'ok' => $schemaReady,
        'detail' => $schemaReady ? 'Current schema is installed' : 'Run the Database upgrade from the admin panel',
    ],
    [
      'name' => 'Deployed revision',
      'ok' => $release !== null,
      'detail' => $release
        ? substr($release['commit'], 0, 12) . ' · ' . gmdate('d M Y, H:i', strtotime($release['deployed_at'])) . ' UTC'
        : 'No release marker found. Push to main and confirm the GitHub Actions deployment succeeds.',
    ],
    [
        'name' => 'Uploads directory',
        'ok' => $uploadsReady,
        'detail' => $uploadsReady
            ? 'Exists and writable'
            : 'Create uploads/ and allow PHP to write uploaded images',
    ],
    [
        'name' => 'GD image processing',
        'ok' => $gdAvailable,
        'detail' => $gdAvailable ? 'Available; image resizing is enabled' : 'Optional, but recommended for resizing uploaded images',
    ],
    [
        'name' => 'Upload script protection',
        'ok' => (bool)$uploadProtectionConfigured,
        'detail' => $uploadProtectionConfigured
            ? 'Root .htaccess blocks script execution in uploads/'
            : 'Deploy the project .htaccess with the uploads script-blocking rule',
    ],
    [
        'name' => 'HTTPS',
        'ok' => $isHttps,
        'detail' => $isHttps ? 'This request uses HTTPS' : 'Activate AutoSSL in cPanel before enabling HTTPS redirects',
    ],
    [
        'name' => 'Installer file',
        'ok' => !$installerPresent,
        'detail' => $installerPresent ? 'install.php is present; remove it from the server after setup' : 'Not present',
    ],
    [
        'name' => 'PHP mail',
        'ok' => $mailAvailable,
        'detail' => $mailAvailable ? 'Available; delivery still depends on cPanel mail configuration' : 'Unavailable; enquiries are saved, but email notifications will not send',
    ],
];

$adminTitle = 'System status';
include __DIR__ . '/_header.php';
?>
<section class="panel">
  <h2>Truehost hosting readiness</h2>
  <p class="muted">Use this checklist to diagnose common cPanel hosting and deployment issues. Upload limits currently read: <?= e(ini_get('upload_max_filesize')) ?> per file and <?= e(ini_get('post_max_size')) ?> per request.</p>
  <div class="panel nopad">
    <table class="tbl">
      <thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead>
      <tbody>
        <?php foreach ($checks as $check): ?>
          <tr>
            <td><strong><?= e($check['name']) ?></strong></td>
            <td><span class="tag <?= $check['ok'] ? 'on' : 'off' ?>"><?= $check['ok'] ? 'Ready' : 'Attention' ?></span></td>
            <td><?= e($check['detail']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
