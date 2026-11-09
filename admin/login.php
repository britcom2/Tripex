<?php
require __DIR__ . '/../includes/bootstrap.php';
if (admin_logged_in()) {
    redirect('admin/index.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = client_ip();
    q('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    $recent = (int)q('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)', [$ip])->fetchColumn();
    if (!csrf_ok()) {
        $error = 'Session expired. Please try again.';
    } elseif ($recent >= 8) {
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } else {
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        $row = q('SELECT * FROM admins WHERE username = ? LIMIT 1', [$u])->fetch();
        if ($row && password_verify($p, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$row['id'];
            $_SESSION['admin_user'] = $row['username'];
            q('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
            redirect('admin/index.php');
        }
        q('INSERT INTO login_attempts (ip) VALUES (?)', [$ip]);
        $error = 'Incorrect username or password.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Admin Login</title>
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>?v=1">
</head>
<body class="login-body">
  <form method="post" class="login-card">
    <h1><?= e(setting('site_name', 'Tripex Carpets')) ?></h1>
    <p class="muted">Admin sign in</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label>Username<input name="username" autocomplete="username" required autofocus></label>
    <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn btn-primary" type="submit">Sign in</button>
    <p class="center"><a href="<?= e(url()) ?>">&larr; Back to website</a></p>
  </form>
</body>
</html>
