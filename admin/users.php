<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'create') {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $errors = [];
        if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
            $errors[] = 'Username must be 3–60 characters and use only letters, numbers, dots, underscores or hyphens.';
        }
        if (strlen($password) < 8 || strlen($password) > 4096) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!$errors && q('SELECT id FROM admins WHERE username = ?', [$username])->fetch()) {
            $errors[] = 'That username is already in use.';
        }
        if (!$errors) {
            try {
                q('INSERT INTO admins (username, password_hash, is_active) VALUES (?, ?, 1)', [
                    $username,
                    password_hash($password, PASSWORD_DEFAULT),
                ]);
                flash('success', 'Administrator account created. It has full admin access.');
                redirect('admin/users.php');
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') {
                    $errors[] = 'That username is already in use.';
                } else {
                    throw $ex;
                }
            }
        }
        foreach ($errors as $error) {
            flash('error', $error);
        }
        redirect('admin/users.php');
    }

    if ($id > 0) {
        $target = q('SELECT id, username, is_active FROM admins WHERE id = ?', [$id])->fetch();
        if (!$target) {
            flash('error', 'Administrator account not found.');
            redirect('admin/users.php');
        }
        $currentId = (int)$_SESSION['admin_id'];

        if ($action === 'toggle_active') {
            try {
                db()->beginTransaction();
                $lockedAdmins = q('SELECT id, is_active FROM admins ORDER BY id FOR UPDATE')->fetchAll();
                $activeCount = 0;
                $targetIsActive = null;
                foreach ($lockedAdmins as $lockedAdmin) {
                    if ((int)$lockedAdmin['is_active'] === 1) {
                        $activeCount++;
                    }
                    if ((int)$lockedAdmin['id'] === $id) {
                        $targetIsActive = (int)$lockedAdmin['is_active'] === 1;
                    }
                }
                if ($targetIsActive === null) {
                    throw new RuntimeException('Administrator account not found.');
                }
                if ($id === $currentId && $targetIsActive) {
                    flash('error', 'You cannot deactivate your own account.');
                } elseif ($targetIsActive && $activeCount <= 1) {
                    flash('error', 'The last active administrator cannot be deactivated.');
                } else {
                    q('UPDATE admins SET is_active = 1 - is_active WHERE id = ?', [$id]);
                    flash('success', 'Administrator account status updated.');
                }
                db()->commit();
            } catch (Throwable $ex) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }
                throw $ex;
            }
        } elseif ($action === 'reset_password') {
            $password = (string)($_POST['password'] ?? '');
            if (strlen($password) < 8 || strlen($password) > 4096) {
                flash('error', 'New password must be at least 8 characters.');
            } else {
                q('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
                flash('success', 'Password updated for ' . $target['username'] . '.');
            }
        } elseif ($action === 'delete') {
            if ($id === $currentId) {
                flash('error', 'You cannot delete your own account.');
            } elseif ((int)$target['is_active'] === 1) {
                flash('error', 'Deactivate this account before deleting it.');
            } else {
                q('DELETE FROM admins WHERE id = ?', [$id]);
                flash('success', 'Inactive administrator account deleted.');
            }
        }
    }
    redirect('admin/users.php');
}

$admins = q('SELECT id, username, is_active, created_at FROM admins ORDER BY created_at, id')->fetchAll();
$adminTitle = 'Staff accounts';
include __DIR__ . '/_header.php';
?>
<section class="panel">
  <h2>Add administrator</h2>
  <p class="muted">Every staff account currently has full access to the admin panel. Share credentials securely and ask staff to change their password after signing in.</p>
  <form method="post" class="user-create-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <label>Username<input name="username" minlength="3" maxlength="60" pattern="[A-Za-z0-9._-]{3,60}" autocomplete="off" required></label>
    <label>Temporary password<input type="password" name="password" minlength="8" maxlength="4096" autocomplete="new-password" required></label>
    <button class="btn btn-primary" type="submit">Create account</button>
  </form>
</section>
<div class="panel nopad">
  <table class="tbl wide user-table">
    <thead><tr><th>Username</th><th>Status</th><th>Created</th><th>Manage account</th></tr></thead>
    <tbody>
      <?php foreach ($admins as $admin): ?>
        <?php $isSelf = (int)$admin['id'] === (int)$_SESSION['admin_id']; ?>
        <tr>
          <td><strong><?= e($admin['username']) ?></strong><?= $isSelf ? ' <small class="muted">(you)</small>' : '' ?></td>
          <td><span class="tag <?= $admin['is_active'] ? 'on' : 'off' ?>"><?= $admin['is_active'] ? 'Active' : 'Inactive' ?></span></td>
          <td><?= e(date('d M Y', strtotime($admin['created_at']))) ?></td>
          <td>
            <div class="user-actions">
              <form method="post" class="user-inline-form">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$admin['id'] ?>"><input type="hidden" name="action" value="reset_password">
                <label class="sr-only" for="password-<?= (int)$admin['id'] ?>">New password for <?= e($admin['username']) ?></label>
                <input id="password-<?= (int)$admin['id'] ?>" type="password" name="password" minlength="8" maxlength="4096" autocomplete="new-password" placeholder="New password" required>
                <button class="btn btn-sm" type="submit">Reset</button>
              </form>
              <?php if (!$isSelf): ?>
                <form method="post" class="inline" data-confirm="<?= $admin['is_active'] ? 'Deactivate this account? It will be signed out on its next request.' : 'Reactivate this account?' ?>">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$admin['id'] ?>"><input type="hidden" name="action" value="toggle_active">
                  <button class="btn btn-sm" type="submit"><?= $admin['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                </form>
                <?php if (!$admin['is_active']): ?>
                  <form method="post" class="inline" data-confirm="Permanently delete this inactive administrator account?">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$admin['id'] ?>"><input type="hidden" name="action" value="delete">
                    <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
