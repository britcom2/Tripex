<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $act = (string)($_POST['action'] ?? '');
    if ($act === 'upload') {
        $title = substr(trim((string)($_POST['title'] ?? '')), 0, 190);
        $n = 0;
        foreach (normalize_files('images') as $f) {
            list($file, $err) = save_upload($f, 'gal');
            if ($err) {
                flash('error', $err);
                continue;
            }
            q('INSERT INTO gallery (title, filename) VALUES (?, ?)', [$title ?: null, $file]);
            $n++;
        }
        if ($n) {
            flash('success', $n . ' image(s) added.');
        }
    } elseif ($act === 'delete') {
        $g = q('SELECT filename FROM gallery WHERE id = ?', [(int)$_POST['id']])->fetch();
        if ($g) {
            delete_upload($g['filename']);
            q('DELETE FROM gallery WHERE id = ?', [(int)$_POST['id']]);
            flash('success', 'Image deleted.');
        }
    } elseif ($act === 'title') {
        q('UPDATE gallery SET title = ? WHERE id = ?', [substr(trim((string)$_POST['title']), 0, 190) ?: null, (int)$_POST['id']]);
        flash('success', 'Caption updated.');
    }
    redirect('admin/gallery.php');
}

$items = q('SELECT * FROM gallery ORDER BY sort_order, id DESC')->fetchAll();
$adminTitle = 'Projects Gallery';
include __DIR__ . '/_header.php';
?>
<div class="panel">
  <h2>Add images</h2>
  <form method="post" enctype="multipart/form-data" class="inline-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="upload">
    <label>Caption (optional)<input name="title" maxlength="190"></label>
    <label>Images<input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required></label>
    <button class="btn btn-primary" type="submit">Upload</button>
  </form>
</div>
<div class="img-grid big">
  <?php foreach ($items as $g): ?>
    <div class="img-item">
      <img src="<?= e(img_url($g['filename'])) ?>" alt="">
      <form method="post" class="cap"><?= csrf_field() ?><input type="hidden" name="action" value="title"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
        <input name="title" value="<?= e($g['title']) ?>" placeholder="Caption"><button class="btn btn-sm">Save</button></form>
      <form method="post" data-confirm="Delete this image?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
    </div>
  <?php endforeach; ?>
  <?php if (!$items): ?><p class="muted">No gallery images yet.</p><?php endif; ?>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
