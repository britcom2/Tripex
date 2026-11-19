<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$textKeys = ['site_name', 'tagline', 'phone', 'email', 'whatsapp', 'address', 'hours', 'currency',
             'hero_title', 'hero_subtitle', 'about_text', 'footer_text', 'meta_description',
             'facebook', 'instagram', 'tiktok', 'youtube', 'linkedin', 'x', 'map_embed'];
$socialHosts = [
    'facebook' => ['facebook.com', 'fb.com'],
    'instagram' => ['instagram.com'],
    'tiktok' => ['tiktok.com'],
    'youtube' => ['youtube.com', 'youtu.be'],
    'linkedin' => ['linkedin.com', 'lnkd.in'],
    'x' => ['x.com', 'twitter.com'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $settingsValid = true;
    foreach ($textKeys as $k) {
        $val = trim((string)($_POST[$k] ?? ''));
        if ($k === 'map_embed' && preg_match('/src="([^"]+)"/', $val, $m)) {
            $val = html_entity_decode($m[1]); // accept the full Google Maps <iframe> code
        }
        if (isset($socialHosts[$k]) && $val !== '') {
            if (!preg_match('#^https?://#i', $val)) {
                $val = 'https://' . $val;
            }
            $parts = parse_url($val);
            $host = is_array($parts) ? strtolower($parts['host'] ?? '') : '';
            $hostAllowed = false;
            foreach ($socialHosts[$k] as $allowedHost) {
                if ($host === $allowedHost || substr($host, -strlen('.' . $allowedHost)) === '.' . $allowedHost) {
                    $hostAllowed = true;
                    break;
                }
            }
            if (
                !is_array($parts)
                || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
                || isset($parts['user'])
                || isset($parts['pass'])
                || isset($parts['port'])
                || !$hostAllowed
                || !filter_var($val, FILTER_VALIDATE_URL)
            ) {
                flash('error', ucfirst($k) . ' must be a valid link from its official platform.');
                $settingsValid = false;
                continue;
            }
            $val = preg_replace('#^http://#i', 'https://', $val);
        }
        save_setting($k, $val);
    }
    $videoLines = preg_split('/\r\n|\r|\n/', trim((string)($_POST['tiktok_videos'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
    $videoLines = array_map('trim', $videoLines);
    $videoLines = array_values(array_filter($videoLines, static function ($line) {
        return $line !== '';
    }));
    $videosValid = count($videoLines) <= 3;
    foreach ($videoLines as $videoUrl) {
        if (tiktok_video_id($videoUrl) === null) {
            $videosValid = false;
            break;
        }
    }
    if ($videosValid) {
        save_setting('tiktok_videos', implode("\n", $videoLines));
    } else {
        flash('error', 'Add up to three full TikTok video URLs (tiktok.com/@name/video/123...). Existing previews were not changed.');
        $settingsValid = false;
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
                $settingsValid = false;
            } else {
                delete_upload(setting($key));
                save_setting($key, $file);
            }
        }
    }
    if ($settingsValid) {
        flash('success', 'Settings saved.');
    }
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
    <label>About us text <small class="muted">Use separate lines and these section headings to organize the About page: Our Heritage, Our Mission, Our Vision, Our Core Values, Our Corporate Journey and International Standards Accreditations. List values as title/description pairs and timeline entries as year/title/description. Executive leadership content is retained for existing records but is not displayed.</small><textarea name="about_text" rows="14"><?= $s('about_text') ?></textarea></label>
    <label>Footer blurb<textarea name="footer_text" rows="2"><?= $s('footer_text') ?></textarea></label>
    <label>SEO description <small class="muted">(shown in Google results)</small><textarea name="meta_description" rows="2"><?= $s('meta_description') ?></textarea></label>
  </div>
  <div class="panel">
    <h2>Social &amp; map</h2>
    <div class="cols">
      <label>Facebook URL<input name="facebook" value="<?= $s('facebook') ?>" placeholder="https://facebook.com/your-page"></label>
      <label>Instagram URL<input name="instagram" value="<?= $s('instagram') ?>" placeholder="https://instagram.com/your-profile"></label>
      <label>TikTok URL<input name="tiktok" value="<?= $s('tiktok') ?>" placeholder="https://www.tiktok.com/@your-profile"></label>
      <label>YouTube URL<input name="youtube" value="<?= $s('youtube') ?>" placeholder="https://www.youtube.com/@your-channel"></label>
      <label>LinkedIn URL<input name="linkedin" value="<?= $s('linkedin') ?>" placeholder="https://www.linkedin.com/company/your-company"></label>
      <label>X / Twitter URL<input name="x" value="<?= $s('x') ?>" placeholder="https://x.com/your-profile"></label>
    </div>
    <label>TikTok video URLs <small class="muted">(up to 3 full public video links, one per line; previews appear on the homepage)</small><textarea name="tiktok_videos" rows="4" placeholder="https://www.tiktok.com/@your-profile/video/1234567890123456789"><?= $s('tiktok_videos') ?></textarea></label>
    <label>Google Maps embed <small class="muted">(Google Maps → Share → Embed a map → paste the iframe code)</small><textarea name="map_embed" rows="3"><?= $s('map_embed') ?></textarea></label>
  </div>
  <button class="btn btn-primary" type="submit">Save settings</button>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
