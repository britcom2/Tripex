<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$me = q('SELECT * FROM admins WHERE id = ?', [(int)$_SESSION['admin_id']])->fetch();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $username = trim((string)($_POST['username'] ?? ''));
    $current  = (string)($_POST['current_password'] ?? '');
    $new      = (string)($_POST['new_password'] ?? '');
    $confirm  = (string)($_POST['confirm_password'] ?? '');

    if (!password_verify($current, $me['password_hash'])) {
        $errors[] = 'Your current password is incorrect.';
    }
    if ($username === '' || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }
    if (q('SELECT id FROM admins WHERE username = ? AND id <> ?', [$username, $me['id']])->fetch()) {
        $errors[] = 'That username is already taken.';
    }
    if ($new !== '') {
        if (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($new !== $confirm) {
            $errors[] = 'New password and confirmation do not match.';
        }
    }
    if (!$errors) {
        if ($new !== '') {
            q('UPDATE admins SET username = ?, password_hash = ? WHERE id = ?', [$username, password_hash($new, PASSWORD_DEFAULT), $me['id']]);
        } else {
            q('UPDATE admins SET username = ? WHERE id = ?', [$username, $me['id']]);
        }
        $_SESSION['admin_user'] = $username;
        flash('success', 'Account updated.');
        redirect('admin/account.php');
    }
}
$adminTitle = 'My Account';
include __DIR__ . '/_header.php';
?>
<div class="panel narrow">
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Username<input name="username" required value="<?= e($_POST['username'] ?? $me['username']) ?>"></label>
    <label>New password <small class="muted">(leave empty to keep the current one)</small><input type="password" name="new_password" autocomplete="new-password"></label>
    <label>Confirm new password<input type="password" name="confirm_password" autocomplete="new-password"></label>
    <hr>
    <label>Current password (required to save)<input type="password" name="current_password" required autocomplete="current-password"></label>
    <button class="btn btn-primary" type="submit">Save changes</button>
  </form>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
