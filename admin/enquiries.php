<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $id = (int)($_POST['id'] ?? 0);
    $act = (string)($_POST['action'] ?? '');
    if ($act === 'delete' && $id) {
        q('DELETE FROM enquiries WHERE id = ?', [$id]);
        flash('success', 'Enquiry deleted.');
    } elseif ($act === 'unread' && $id) {
        q('UPDATE enquiries SET is_read = 0 WHERE id = ?', [$id]);
    } elseif ($act === 'all_read') {
        q('UPDATE enquiries SET is_read = 1');
        flash('success', 'All enquiries marked as read.');
    }
    redirect('admin/enquiries.php');
}

$view = null;
if (isset($_GET['view'])) {
    $view = q('SELECT e.*, p.name AS product, p.slug AS pslug FROM enquiries e LEFT JOIN products p ON p.id = e.product_id WHERE e.id = ?', [(int)$_GET['view']])->fetch() ?: null;
    if ($view && !$view['is_read']) {
        q('UPDATE enquiries SET is_read = 1 WHERE id = ?', [$view['id']]);
    }
}
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 25;
$total = (int)q('SELECT COUNT(*) FROM enquiries')->fetchColumn();
$offset = ($page - 1) * $per;
$rows = q("SELECT e.*, p.name AS product FROM enquiries e LEFT JOIN products p ON p.id = e.product_id ORDER BY e.id DESC LIMIT $per OFFSET $offset")->fetchAll();

$adminTitle = 'Enquiries';
$adminActions = '<form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="action" value="all_read"><button class="btn">Mark all as read</button></form>';
include __DIR__ . '/_header.php';
?>
<?php if ($view): ?>
  <div class="panel">
    <h2><?= e($view['subject'] ?: 'Enquiry from ' . $view['name']) ?></h2>
    <p class="muted"><?= e(date('d M Y, H:i', strtotime($view['created_at']))) ?><?= $view['product'] ? ' · Product: <a href="' . e(product_url($view['pslug'])) . '" target="_blank" rel="noopener">' . e($view['product']) . '</a>' : '' ?></p>
    <p><strong><?= e($view['name']) ?></strong><?php if ($view['email']): ?> &lt;<a href="mailto:<?= e($view['email']) ?>"><?= e($view['email']) ?></a>&gt;<?php else: ?> · No email provided<?php endif; ?><?= $view['phone'] ? ' · <a href="tel:' . e($view['phone']) . '">' . e($view['phone']) . '</a>' : '' ?></p>
    <div class="msg"><?= nl2br(e($view['message'])) ?></div>
    <p>
      <?php if ($view['email']): ?><a class="btn btn-primary" href="mailto:<?= e($view['email']) ?>?subject=<?= rawurlencode('Re: ' . ($view['subject'] ?: 'Your enquiry')) ?>">Reply by email</a><?php endif; ?>
      <?php if ($view['phone']): ?>
        <?php $whatsappReply = whatsapp_link((string)$view['phone'], 'Hello ' . $view['name'] . ', thanks for your enquiry' . ($view['subject'] ? ' about ' . $view['subject'] : '') . '.'); ?>
        <?php if ($whatsappReply): ?><a class="btn" href="<?= e($whatsappReply) ?>" target="_blank" rel="noopener">Reply via WhatsApp</a><?php endif; ?>
      <?php endif; ?>
      <a class="btn" href="enquiries.php">Back</a>
      <form method="post" class="inline" data-confirm="Delete this enquiry?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$view['id'] ?>"><button class="btn btn-danger">Delete</button></form>
    </p>
  </div>
<?php endif; ?>
<div class="panel nopad">
  <table class="tbl wide">
    <thead><tr><th>From</th><th>About</th><th>Contact</th><th>Channel</th><th>Received</th><th class="r"></th></tr></thead>
    <?php foreach ($rows as $r): ?>
      <tr class="<?= $r['is_read'] ? '' : 'unread' ?>">
        <td><a href="?view=<?= (int)$r['id'] ?>"><strong><?= e($r['name']) ?></strong></a></td>
        <td><?= e($r['product'] ?: ($r['subject'] ?: 'General enquiry')) ?><br><small class="muted"><?= e(excerpt($r['message'], 70)) ?></small></td>
        <td><?= e($r['email'] ?: 'No email') ?><br><small class="muted"><?= e($r['phone']) ?></small></td>
        <td><?= $r['source'] === 'whatsapp' ? 'WhatsApp' : 'Website' ?></td>
        <td><small><?= e(date('d M Y, H:i', strtotime($r['created_at']))) ?></small></td>
        <td class="r"><a class="btn btn-sm" href="?view=<?= (int)$r['id'] ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="muted center">No enquiries yet.</td></tr><?php endif; ?>
  </table>
</div>
<?= pager($total, $per, $page) ?>
<?php include __DIR__ . '/_footer.php'; ?>
