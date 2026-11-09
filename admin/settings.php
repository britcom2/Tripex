<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$textKeys = ['site_name', 'tagline', 'phone', 'email', 'whatsapp', 'address', 'hours', 'currency',
             'hero_title', 'hero_subtitle', 'about_text', 'footer_text', 'meta_description',
             'facebook', 'instagram', 'map_embed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    foreach ($textKeys as $k) {
        $val = trim((string)($_POST[$k] ?? ''));
        if ($k === 'map_embed' && preg_match('/src="([^"]+)"/', $val, $m)) {
            $val = html_entity_decode($m[1]); // accept the full Google Maps <iframe> code
        }
        if (in_array($k, ['facebook', 'instagram'], true) && $val !== '' && !preg_match('#^https?://#', $val)) {
            $val = 'https://' . $val;
        }
        save_setting($k, $val);
    }
    foreach (['hero_image' => ['hero', 2000], 'logo' => ['logo', 600]] as $key => $cfg) {
        if (!empty($_POST['remove_' . $key])) {
            delete_upload(setting($key));
            save_setting($key, '');
        }
        $files = normalize_files($key);
        if ($files) {
            list($file, $err) = save_upload($files[0], $cfg[0], $cfg[1]);
            if ($err) {
                flash('error', $err);
            } else {
                delete_upload(setting($key));
                save_setting($key, $file);
            }
        }
    }
    flash('success', 'Settings saved.');
    redirect('admin/settings.php');
}

$s = function ($k, $d = '') { return e(setting($k, $d)); };
$adminTitle = 'Site Settings';
include __DIR__ . '/_header.php';
?>
<form method="post" enctype="multipart/form-data" class="settings">
  <?= csrf_field() ?>
  <div class="panel">
    <h2>Company</h2>
    <div class="cols">
      <label>Company name<input name="site_name" value="<?= $s('site_name', 'Tripex Carpets') ?>"></label>
      <label>Tagline<input name="tagline" value="<?= $s('tagline', 'Premium Flooring & Carpet Solutions') ?>"></label>
      <label>Phone<input name="phone" value="<?= $s('phone') ?>"></label>
      <label>Email (enquiries are sent here)<input type="email" name="email" value="<?= $s('email') ?>"></label>
      <label>WhatsApp number <small class="muted">(with country code, e.g. 2547XXXXXXXX)</small><input name="whatsapp" value="<?= $s('whatsapp') ?>"></label>
      <label>Currency label<input name="currency" value="<?= $s('currency', 'KES') ?>"></label>
    </div>
    <label>Address<textarea name="address" rows="2"><?= $s('address') ?></textarea></label>
    <label>Opening hours<input name="hours" value="<?= $s('hours', 'Mon - Sat: 8:00 AM - 5:30 PM') ?>"></label>
  </div>
  <div class="panel">
    <h2>Homepage &amp; Branding</h2>
    <label>Hero headline<input name="hero_title" value="<?= $s('hero_title', 'Premium Flooring & Carpet Solutions') ?>"></label>
    <label>Hero sub-text<textarea name="hero_subtitle" rows="2"><?= $s('hero_subtitle', 'Carpet tiles, vinyl planks and architectural surfaces for homes, offices and commercial spaces.') ?></textarea></label>
    <div class="cols">
      <div>
        <label>Hero background image <small class="muted">(wide image, at least 1600px)</small><input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp"></label>
        <?php if (setting('hero_image')): ?><img class="preview" src="<?= e(img_url(setting('hero_image'))) ?>" alt=""><label class="check"><input type="checkbox" name="remove_hero_image"> Remove image</label><?php endif; ?>
      </div>
      <div>
        <label>Logo <small class="muted">(optional, replaces the text logo)</small><input type="file" name="logo" accept="image/jpeg,image/png,image/webp"></label>
        <?php if (setting('logo')): ?><img class="preview" src="<?= e(img_url(setting('logo'))) ?>" alt=""><label class="check"><input type="checkbox" name="remove_logo"> Remove logo</label><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="panel">
    <h2>Page content</h2>
    <label>About us text <small class="muted">Use separate lines and these section headings to organize the About page: Our Heritage, Our Mission, Our Vision, Our Core Values, Our Corporate Journey, Executive Leadership, International Standards Accreditations. List values as title/description pairs, timeline entries as year/title/description, and team members as role/name/description.</small><textarea name="about_text" rows="14"><?= $s('about_text') ?></textarea></label>
    <label>Footer blurb<textarea name="footer_text" rows="2"><?= $s('footer_text') ?></textarea></label>
    <label>SEO description <small class="muted">(shown in Google results)</small><textarea name="meta_description" rows="2"><?= $s('meta_description') ?></textarea></label>
  </div>
  <div class="panel">
    <h2>Social &amp; map</h2>
    <div class="cols">
      <label>Facebook URL<input name="facebook" value="<?= $s('facebook') ?>"></label>
      <label>Instagram URL<input name="instagram" value="<?= $s('instagram') ?>"></label>
    </div>
    <label>Google Maps embed <small class="muted">(Google Maps → Share → Embed a map → paste the iframe code)</small><textarea name="map_embed" rows="3"><?= $s('map_embed') ?></textarea></label>
  </div>
  <button class="btn btn-primary" type="submit">Save settings</button>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
