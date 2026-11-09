<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$edit = null;
if (isset($_GET['edit'])) {
    $edit = q('SELECT * FROM categories WHERE id = ?', [(int)$_GET['edit']])->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $act = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($act === 'delete' && $id) {
        $c = q('SELECT image FROM categories WHERE id = ?', [$id])->fetch();
        if ($c) {
            delete_upload($c['image']);
            q('DELETE FROM categories WHERE id = ?', [$id]);
            flash('success', 'Category deleted. Its products are kept but now have no category.');
        }
        redirect('admin/categories.php');
    }

    if ($act === 'save') {
        $name = trim((string)($_POST['name'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        $sort = (int)($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($name === '') {
            flash('error', 'Category name is required.');
            redirect('admin/categories.php' . ($id ? '?edit=' . $id : ''));
        }
        $image = null;
        $files = normalize_files('image');
        if ($files) {
            list($image, $err) = save_upload($files[0], 'cat', 1200);
            if ($err) {
                flash('error', $err);
            }
        }
        if ($id) {
            $old = q('SELECT image FROM categories WHERE id = ?', [$id])->fetch();
            $slug = unique_slug('categories', $name, $id);
            if ($image) {
                delete_upload($old['image'] ?? null);
                q('UPDATE categories SET name=?, slug=?, description=?, sort_order=?, is_active=?, image=? WHERE id=?', [$name, $slug, $desc ?: null, $sort, $active, $image, $id]);
            } else {
                q('UPDATE categories SET name=?, slug=?, description=?, sort_order=?, is_active=? WHERE id=?', [$name, $slug, $desc ?: null, $sort, $active, $id]);
            }
        } else {
            q('INSERT INTO categories (name, slug, description, sort_order, is_active, image) VALUES (?,?,?,?,?,?)', [$name, unique_slug('categories', $name), $desc ?: null, $sort, $active, $image]);
        }
        flash('success', 'Category saved.');
        redirect('admin/categories.php');
    }
}

$rows = q('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS n FROM categories c ORDER BY c.sort_order, c.name')->fetchAll();
$adminTitle = 'Categories';
include __DIR__ . '/_header.php';
?>
<div class="two">
  <div class="panel nopad">
    <table class="tbl wide">
      <thead><tr><th></th><th>Name</th><th>Products</th><th>Order</th><th class="r">Actions</th></tr></thead>
      <?php foreach ($rows as $c): ?>
        <tr>
          <td class="thumb"><img src="<?= e(img_url($c['image'])) ?>" alt=""></td>
          <td><strong><?= e($c['name']) ?></strong> <?= $c['is_active'] ? '' : '<span class="tag off">Hidden</span>' ?></td>
          <td><?= (int)$c['n'] ?></td>
          <td><?= (int)$c['sort_order'] ?></td>
          <td class="r">
            <a class="btn btn-sm" href="?edit=<?= (int)$c['id'] ?>">Edit</a>
            <form method="post" class="inline" data-confirm="Delete this category?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="muted center">No categories yet.</td></tr><?php endif; ?>
    </table>
  </div>
  <div class="panel">
    <h2><?= $edit ? 'Edit category' : 'Add category' ?></h2>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
      <label>Name *<input name="name" required value="<?= e($edit['name'] ?? '') ?>"></label>
      <label>Description<textarea name="description" rows="3"><?= e($edit['description'] ?? '') ?></textarea></label>
      <label>Sort order<input type="number" name="sort_order" value="<?= e($edit['sort_order'] ?? 0) ?>"></label>
      <label>Image <?php if (!empty($edit['image'])): ?><small class="muted">(upload to replace)</small><?php endif; ?><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
      <?php if (!empty($edit['image'])): ?><p><img class="preview" src="<?= e(img_url($edit['image'])) ?>" alt=""></p><?php endif; ?>
      <label class="check"><input type="checkbox" name="is_active" <?= (!$edit || $edit['is_active']) ? 'checked' : '' ?>> Visible on website</label>
      <button class="btn btn-primary" type="submit">Save category</button>
      <?php if ($edit): ?><a class="btn" href="categories.php">Cancel</a><?php endif; ?>
    </form>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
