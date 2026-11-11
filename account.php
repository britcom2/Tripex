<?php
require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Customer Account';
$errors = [];
$customer = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'register') {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        if ($name === '' || strlen($name) > 120) {
            $errors[] = 'Please enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 160) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($phone) > 40) {
            $errors[] = 'Please enter a phone number of 40 characters or fewer.';
        }
        if (strlen($password) < 8 || strlen($password) > 4096) {
            $errors[] = 'Your password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'The passwords do not match.';
        }
        if (!$errors && q('SELECT id FROM customers WHERE email = ?', [$email])->fetch()) {
            $errors[] = 'An account already exists for that email address.';
        }
        if (!$errors) {
            try {
                q('INSERT INTO customers (name, email, phone, password_hash) VALUES (?, ?, ?, ?)', [
                    $name,
                    $email,
                    $phone ?: null,
                    password_hash($password, PASSWORD_DEFAULT),
                ]);
                session_regenerate_id(true);
                $_SESSION['customer_id'] = (int)db()->lastInsertId();
                flash('success', 'Your account is ready. You can now send and track enquiries.');
                redirect('account.php');
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') {
                    $errors[] = 'An account already exists for that email address.';
                } else {
                    throw $ex;
                }
            }
        }
    } elseif ($action === 'login') {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
      $ip = client_ip();
      q('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
      $recent = (int)q('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)', [$ip])->fetchColumn();
      if ($recent >= 8) {
        $errors[] = 'Too many sign-in attempts. Please wait 15 minutes and try again.';
      } else {
        $row = q('SELECT id, password_hash FROM customers WHERE email = ? LIMIT 1', [$email])->fetch();
        if ($row && password_verify($password, $row['password_hash'])) {
          session_regenerate_id(true);
          $_SESSION['customer_id'] = (int)$row['id'];
          q('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
          redirect('account.php');
        }
        q('INSERT INTO login_attempts (ip) VALUES (?)', [$ip]);
        $errors[] = 'The email address or password is incorrect.';
        }
    } elseif ($action === 'logout') {
        unset($_SESSION['customer_id']);
        session_regenerate_id(true);
        flash('success', 'You have signed out.');
        redirect('account.php');
    } elseif ($action === 'enquiry') {
        if (!customer_logged_in()) {
            redirect('account.php');
        }
        $customer = q('SELECT id, name, email, phone FROM customers WHERE id = ?', [(int)$_SESSION['customer_id']])->fetch();
        if (!$customer) {
            unset($_SESSION['customer_id']);
            redirect('account.php');
        }
        $channel = (($_POST['channel'] ?? '') === 'whatsapp') ? 'whatsapp' : 'website';
        if ($channel === 'whatsapp' && !preg_match('/\d{7,}/', preg_replace('/\D/', '', setting('whatsapp')))) {
            $errors[] = 'WhatsApp enquiries are unavailable right now. Please send a website enquiry instead.';
        }
        $enquiryPhone = trim((string)($_POST['phone'] ?? $customer['phone'] ?? ''));
        if ($channel === 'whatsapp' && strlen(preg_replace('/\D/', '', $enquiryPhone)) < 7) {
          $errors[] = 'Enter a valid phone or WhatsApp number so we can reply.';
        }
        if (!$errors) {
            $_POST['name'] = $customer['name'];
            $_POST['email'] = $customer['email'];
            $_POST['phone'] = $enquiryPhone;
            $_POST['subject'] = trim((string)($_POST['subject'] ?? ''));
            list($sent, $submitErrors) = handle_enquiry();
            $errors = array_merge($errors, $submitErrors);
            if ($sent) {
                if ($channel === 'whatsapp') {
                    $message = "Hello, I am {$customer['name']}. I submitted an enquiry through the website.\n\n" . trim((string)$_POST['subject']) . "\n" . trim((string)$_POST['message']);
                    redirect(whatsapp_link(setting('whatsapp'), $message));
                }
                flash('success', 'Your enquiry has been sent and added to your account.');
                redirect('account.php');
            }
        }
    }
}

if (customer_logged_in()) {
    $customer = q('SELECT id, name, email, phone FROM customers WHERE id = ?', [(int)$_SESSION['customer_id']])->fetch();
    if (!$customer) {
        unset($_SESSION['customer_id']);
        redirect('account.php');
    }
    $enquiries = q('SELECT id, subject, message, source, is_read, created_at FROM enquiries WHERE customer_id = ? ORDER BY id DESC LIMIT 50', [(int)$customer['id']])->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>
<section class="page-banner"><div class="container"><span class="about-kicker">Your enquiries</span><h1>Customer account</h1><p class="crumbs"><a href="<?= e(url()) ?>">Home</a> / Account</p></div></section>
<section class="section">
  <div class="container account-page">
    <?= flash_render() ?>
    <?php foreach ($errors as $error): ?><div class="alert alert-error" role="alert" tabindex="-1"><?= e($error) ?></div><?php endforeach; ?>
    <?php if ($customer): ?>
      <div class="account-heading">
        <div><h2>Hello, <?= e($customer['name']) ?></h2><p><?= e($customer['email']) ?><?= $customer['phone'] ? ' · ' . e($customer['phone']) : '' ?></p></div>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="btn btn-outline" type="submit">Sign out</button></form>
      </div>
      <div class="account-content">
        <section>
          <h2>Send an enquiry</h2>
          <form method="post" class="form enquiry-form" data-enquiry-form>
            <?= csrf_field() ?><input type="hidden" name="action" value="enquiry">
            <div class="enquiry-form-heading"><span class="enquiry-step">Quick enquiry</span><div><h3>What can we help with?</h3><p>We’ll follow up using your preferred channel.</p></div></div>
            <div class="row enquiry-fields">
              <label>Subject <span class="optional-mark">Optional</span><input name="subject" maxlength="190" value="<?= e($_POST['subject'] ?? '') ?>" placeholder="What can we help with?"></label>
              <label data-phone-field>Phone / WhatsApp <span data-phone-required class="required-mark" <?= (($_POST['channel'] ?? '') !== 'whatsapp') ? 'hidden' : '' ?>>*</span><input name="phone" autocomplete="tel" maxlength="40" value="<?= e($_POST['phone'] ?? $customer['phone']) ?>" placeholder="Include your country code" aria-describedby="enquiry-phone-help"><small class="field-help" id="enquiry-phone-help" data-phone-help <?= (($_POST['channel'] ?? '') !== 'whatsapp') ? 'hidden' : '' ?>>Use at least 7 digits; include your country code if possible.</small></label>
            </div>
            <label class="enquiry-message-field">Your message <span class="required-mark">*</span><textarea name="message" rows="5" maxlength="5000" required placeholder="Tell us what you have in mind..."><?= e($_POST['message'] ?? '') ?></textarea></label>
            <?php if (preg_match('/\d{7,}/', preg_replace('/\D/', '', setting('whatsapp')))): ?>
              <fieldset class="enquiry-channels"><legend>How would you like us to reply?</legend><div class="enquiry-channel-options">
                <label class="enquiry-channel-card"><input type="radio" name="channel" value="website" data-enquiry-channel aria-describedby="enquiry-channel-note" <?= (($_POST['channel'] ?? '') !== 'whatsapp') ? 'checked' : '' ?>><span class="enquiry-channel-icon" aria-hidden="true">&#9993;</span><span class="enquiry-channel-copy"><strong>Email</strong><small>Use your account email</small></span><span class="enquiry-channel-check" aria-hidden="true"></span></label>
                <label class="enquiry-channel-card enquiry-channel-whatsapp"><input type="radio" name="channel" value="whatsapp" data-enquiry-channel aria-describedby="enquiry-channel-note" <?= (($_POST['channel'] ?? '') === 'whatsapp') ? 'checked' : '' ?>><span class="enquiry-channel-icon" aria-hidden="true">&#10148;</span><span class="enquiry-channel-copy"><strong>WhatsApp</strong><small>Continue the conversation there</small></span><span class="enquiry-channel-check" aria-hidden="true"></span></label>
              </div></fieldset>
            <?php else: ?><input type="hidden" name="channel" value="website"><?php endif; ?>
            <p class="enquiry-channel-note" id="enquiry-channel-note" data-channel-note data-email-note="We’ll reply to the email address saved to your account." data-whatsapp-note="Add your WhatsApp number above. Your enquiry will be ready to send in WhatsApp." aria-live="polite"><?= (($_POST['channel'] ?? '') === 'whatsapp') ? 'Add your WhatsApp number above. Your enquiry will be ready to send in WhatsApp.' : 'We’ll reply to the email address saved to your account.' ?></p>
            <div class="enquiry-submit-row"><button class="btn btn-primary enquiry-submit" type="submit" data-enquiry-submit data-email-label="Send enquiry" data-whatsapp-label="Continue to WhatsApp"><span data-submit-label><?= (($_POST['channel'] ?? '') === 'whatsapp') ? 'Continue to WhatsApp' : 'Send enquiry' ?></span><span aria-hidden="true">&rarr;</span></button><span class="enquiry-submit-note"><span aria-hidden="true">&#128274;</span> Your details are only used to respond to you.</span></div>
          </form>
        </section>
        <section>
          <h2>Your enquiries</h2>
          <?php if ($enquiries): ?>
            <div class="account-enquiries">
              <?php foreach ($enquiries as $enquiry): ?>
                <article>
                  <div><strong><?= e($enquiry['subject'] ?: 'General enquiry') ?></strong><small><?= e(date('d M Y, H:i', strtotime($enquiry['created_at']))) ?> · <?= $enquiry['source'] === 'whatsapp' ? 'WhatsApp' : 'Website' ?> · <?= $enquiry['is_read'] ? 'Seen' : 'Received' ?></small></div>
                  <p><?= nl2br(e($enquiry['message'])) ?></p>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="muted">Your submitted enquiries will appear here.</p>
          <?php endif; ?>
        </section>
      </div>
    <?php else: ?>
      <div class="account-auth-grid">
        <section id="create-account">
          <h2>Create an account</h2>
          <p>Save your details and keep your enquiries together.</p>
          <form method="post" class="form">
            <?= csrf_field() ?><input type="hidden" name="action" value="register">
            <label>Name *<input name="name" autocomplete="name" maxlength="120" required value="<?= e($_POST['name'] ?? '') ?>"></label>
            <label>Email *<input type="email" name="email" autocomplete="email" maxlength="160" required value="<?= e($_POST['email'] ?? '') ?>"></label>
            <label>Phone / WhatsApp<input name="phone" autocomplete="tel" maxlength="40" value="<?= e($_POST['phone'] ?? '') ?>"></label>
            <label>Password <small>(at least 8 characters)</small><input type="password" name="password" autocomplete="new-password" minlength="8" required></label>
            <label>Confirm password<input type="password" name="confirm_password" autocomplete="new-password" minlength="8" required></label>
            <button class="btn btn-primary" type="submit">Create account</button>
          </form>
        </section>
        <section id="sign-in">
          <h2>Sign in</h2>
          <p>Already have an account? Continue to your enquiries.</p>
          <form method="post" class="form">
            <?= csrf_field() ?><input type="hidden" name="action" value="login">
            <label>Email<input type="email" name="email" autocomplete="email" required value="<?= e(($_POST['action'] ?? '') === 'login' ? ($_POST['email'] ?? '') : '') ?>"></label>
            <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
            <button class="btn btn-primary" type="submit">Sign in</button>
          </form>
        </section>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>