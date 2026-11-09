<?php
require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Request a Quote';
$metaDesc = 'Build a flooring project quote request with products, quantities and project details.';
$allowedTypes = ['Residential', 'Office & corporate', 'Hotels & hospitality', 'Retail', 'Other'];
$allowedTimelines = ['As soon as possible', 'Within 1 month', '1–3 months', 'Planning ahead', 'Not sure yet'];
$basket = $_SESSION['quote_basket'] ?? [];
if (!is_array($basket)) {
    $basket = [];
}

$errors = [];
$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string)($_POST['action'] ?? '');
    $productId = (int)($_POST['product_id'] ?? 0);
    $removeProductId = (int)($_POST['remove_product_id'] ?? 0);

    if ($action === 'add' && $productId > 0) {
        $product = q('SELECT id FROM products WHERE id = ? AND is_active = 1 LIMIT 1', [$productId])->fetch();
        if ($product) {
            $basket[$productId] = $basket[$productId] ?? '';
            $_SESSION['quote_basket'] = $basket;
            flash('success', 'Product added to your quote list.');
        } else {
            flash('error', 'That product is no longer available to quote.');
        }
        redirect('quote.php');
    }

    if ($removeProductId > 0) {
        unset($basket[$removeProductId]);
        $_SESSION['quote_basket'] = $basket;
        redirect('quote.php');
    }

    if ($action === 'update' || $action === 'request_quote') {
        $postedAreas = $_POST['areas'] ?? [];
        if (is_array($postedAreas)) {
            foreach ($basket as $id => $area) {
                $value = trim((string)($postedAreas[$id] ?? ''));
                if ($value === '') {
                    $basket[$id] = '';
                } elseif (!is_numeric($value) || (float)$value <= 0 || (float)$value > 100000) {
                    $errors[] = 'Enter a valid quantity greater than zero and no more than 100,000, or leave it blank.';
                    break;
                } else {
                    $basket[$id] = (string)(float)$value;
                }
            }
        }
        $_SESSION['quote_basket'] = $basket;

        if ($action === 'update') {
            if (!$errors) {
                flash('success', 'Your quantities have been updated.');
                redirect('quote.php');
            }
        } elseif (!$basket) {
            $errors[] = 'Add at least one product to your quote list.';
        } else {
            $name = trim((string)($_POST['name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $location = trim((string)($_POST['location'] ?? ''));
            $type = trim((string)($_POST['project_type'] ?? ''));
            $timeline = trim((string)($_POST['timeline'] ?? ''));
            $installation = isset($_POST['installation']) ? 'Yes, please include installation' : 'Not requested';
            $notes = trim((string)($_POST['notes'] ?? ''));

            if ($name === '' || strlen($name) > 120) {
                $errors[] = 'Enter your name (up to 120 characters).';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid email address.';
            }
            if (strlen($phone) > 40 || strlen($location) > 190 || strlen($notes) > 3000) {
                $errors[] = 'Phone, location, or project notes exceed the allowed length.';
            }
            if ($type !== '' && !in_array($type, $allowedTypes, true)) {
                $errors[] = 'Choose a valid project type.';
            }
            if ($timeline !== '' && !in_array($timeline, $allowedTimelines, true)) {
                $errors[] = 'Choose a valid project timeline.';
            }

            $ids = array_map('intval', array_keys($basket));
            if (!$errors && $ids) {
                $marks = implode(',', array_fill(0, count($ids), '?'));
                $products = q(product_select() . " WHERE p.is_active = 1 AND p.id IN ($marks)", $ids)->fetchAll();
                $byId = [];
                foreach ($products as $product) {
                    $byId[(int)$product['id']] = $product;
                }
                $lines = [];
                foreach ($ids as $id) {
                    if (!isset($byId[$id])) {
                        unset($basket[$id]);
                        continue;
                    }
                    $product = $byId[$id];
                    $quantity = trim((string)($basket[$id] ?? ''));
                    $lines[] = '- ' . $product['name']
                        . ($product['sku'] ? ' (SKU: ' . $product['sku'] . ')' : '')
                        . ($quantity !== '' ? ' — quantity/area: ' . $quantity . ($product['unit'] ? ' ' . $product['unit'] : '') : ' — quantity/area to be confirmed');
                }

                if (!$lines) {
                    $errors[] = 'The selected products are no longer available. Please add products again.';
                } else {
                    $message = "FLOORING QUOTE REQUEST\n\n"
                        . "Project type: " . ($type ?: 'Not specified') . "\n"
                        . "Project location: " . ($location ?: 'Not specified') . "\n"
                        . "Target timeline: " . ($timeline ?: 'Not specified') . "\n"
                        . "Installation: " . $installation . "\n\n"
                        . "Products requested:\n" . implode("\n", $lines)
                        . "\n\nProject notes:\n" . ($notes ?: 'None provided');

                    if (strlen($message) > 5000) {
                        $errors[] = 'The quote request is too long. Shorten the project notes and try again.';
                    } else {
                        $_POST['name'] = $name;
                        $_POST['email'] = $email;
                        $_POST['phone'] = $phone;
                        $_POST['subject'] = 'Flooring quote request' . ($type ? ' — ' . $type : '');
                        $_POST['message'] = $message;
                        list($sent, $submitErrors) = handle_enquiry(null);
                        $errors = array_merge($errors, $submitErrors);
                        if ($sent) {
                            unset($_SESSION['quote_basket']);
                            flash('success', 'Your quote request has been received. Our team will be in touch shortly.');
                            redirect('quote.php?sent=1');
                        }
                    }
                }
                $_SESSION['quote_basket'] = $basket;
            }
        }
    }
}

$basket = $_SESSION['quote_basket'] ?? [];
$ids = array_map('intval', array_keys($basket));
$products = [];
if ($ids) {
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $rows = q(product_select() . " WHERE p.is_active = 1 AND p.id IN ($marks)", $ids)->fetchAll();
    $byId = [];
    foreach ($rows as $product) {
        $byId[(int)$product['id']] = $product;
    }
    foreach ($ids as $id) {
        if (isset($byId[$id])) {
            $products[] = $byId[$id];
        } else {
            unset($basket[$id]);
        }
    }
    $_SESSION['quote_basket'] = $basket;
}

include __DIR__ . '/includes/header.php';
?>
<section class="page-banner quote-banner"><div class="container"><span class="about-kicker">A considered start</span><h1>Build your quote request.</h1><p class="crumbs"><a href="<?= e(url()) ?>">Home</a> / Request a Quote</p></div></section>
<section class="section quote-section">
  <div class="container quote-layout">
    <div class="quote-main">
      <?= flash_render() ?>
      <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
      <div class="quote-section-heading"><span class="quote-step">01</span><div><h2>Your selected flooring</h2><p>Add products from the catalogue, and include quantities if you know them.</p></div></div>

      <form method="post" class="quote-form">
        <?= csrf_field() ?>
      <?php if ($products): ?>
        <div class="quote-list-form">
          <?php foreach ($products as $product): ?>
            <article class="quote-line">
              <img src="<?= e(img_url($product['image'])) ?>"<?= img_srcset_attr($product['image'], '92px') ?> alt="">
              <div class="quote-line-info"><span><?= e($product['cat_name'] ?: 'Flooring') ?></span><h3><?= e($product['name']) ?></h3><?php if ($product['sku']): ?><small>SKU <?= e($product['sku']) ?></small><?php endif; ?></div>
              <label>Quantity / area <small><?= e($product['unit'] ?: 'optional') ?></small><input type="number" min="0.01" max="100000" step="0.01" name="areas[<?= (int)$product['id'] ?>]" value="<?= e($basket[$product['id']] ?? '') ?>" placeholder="If known"></label>
              <button class="quote-remove" type="submit" name="remove_product_id" value="<?= (int)$product['id'] ?>" formnovalidate aria-label="Remove <?= e($product['name']) ?>">Remove</button>
            </article>
          <?php endforeach; ?>
          <button class="btn btn-outline quote-update" type="submit" name="action" value="update" formnovalidate>Update quantities</button>
        </div>
      <?php else: ?>
        <div class="quote-empty"><span aria-hidden="true">01</span><div><h3>Your quote list is empty</h3><p>Browse the catalogue and add the flooring products you are interested in.</p><a class="btn btn-outline" href="<?= e(url('products')) ?>">Explore products</a></div></div>
      <?php endif; ?>

      <div class="quote-section-heading quote-contact-heading"><span class="quote-step">02</span><div><h2>Tell us about your project</h2><p>Share a few details so our team can prepare a useful, tailored response.</p></div></div>
        <div class="row">
          <label>Your name *<input name="name" autocomplete="name" required maxlength="120" value="<?= e($_POST['name'] ?? '') ?>"></label>
          <label>Email address *<input type="email" name="email" autocomplete="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
          <label>Phone / WhatsApp<input name="phone" autocomplete="tel" maxlength="40" value="<?= e($_POST['phone'] ?? '') ?>"></label>
          <label>Project location<input name="location" autocomplete="address-level2" maxlength="190" placeholder="Town or neighbourhood" value="<?= e($_POST['location'] ?? '') ?>"></label>
        </div>
        <div class="row">
          <label>Space type<select name="project_type"><option value="">Select a space</option><?php foreach ($allowedTypes as $type): ?><option value="<?= e($type) ?>" <?= ($_POST['project_type'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></label>
          <label>When are you planning to start?<select name="timeline"><option value="">Select a timeline</option><?php foreach ($allowedTimelines as $timeline): ?><option value="<?= e($timeline) ?>" <?= ($_POST['timeline'] ?? '') === $timeline ? 'selected' : '' ?>><?= e($timeline) ?></option><?php endforeach; ?></select></label>
        </div>
        <label class="quote-install-option"><input type="checkbox" name="installation" value="1" <?= !empty($_POST['installation']) ? 'checked' : '' ?>><span>Please include professional installation in the quote</span></label>
        <label>Project notes<textarea name="notes" rows="4" maxlength="3000" placeholder="Room dimensions, preferred materials, plans, or anything else we should know"><?= e($_POST['notes'] ?? '') ?></textarea></label>
        <p class="quote-privacy">Your details will only be used to respond to this project enquiry.</p>
        <button class="btn btn-primary" type="submit" name="action" value="request_quote" <?= !$products ? 'disabled' : '' ?>>Send quote request <span aria-hidden="true">&rarr;</span></button>
      </form>
    </div>
    <aside class="quote-aside">
      <span class="quote-aside-mark">&#10003;</span><h2>A better quote starts with a little context.</h2>
      <p>Include a site location, approximate quantity, and whether you need installation. If you are still exploring, leave unknown details blank and we can guide you.</p>
      <div><strong>What happens next?</strong><ol><li>Our team reviews your materials and project details.</li><li>We follow up if measurements or site information would help.</li><li>You receive a clear quotation and recommended next steps.</li></ol></div>
      <?php if (setting('phone')): ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('phone'))) ?>">Prefer to talk? <?= e(setting('phone')) ?></a><?php endif; ?>
    </aside>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
