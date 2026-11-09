# Tripex Carpets – Website & Admin Panel

Plain PHP + MySQL (no frameworks, no build step), made for cPanel shared hosting such as Truehost.
Requires PHP 7.4 or newer with PDO MySQL and GD (both standard on Truehost).

## Deploy on Truehost (cPanel)

1. **Create the database**
   cPanel → *MySQL® Databases* → create a database, create a user with a strong password, and add the user to the database with **All Privileges**.
   Note the full names (cPanel adds a prefix, e.g. `myacct_tripex`).

2. **Upload the files**
   cPanel → *File Manager* → open `public_html` (or the folder of your domain/subdomain, e.g. `tripex.yourdomain.com`).
   Upload `tripex-site.zip` and **Extract** it so that `index.php`, `admin/`, `includes/` etc. sit directly inside that folder.
   Make sure hidden files (`.htaccess`) were extracted (File Manager → Settings → *Show Hidden Files*).

3. **Run the installer**
   Open `https://yourdomain.com/install.php`, enter the database details, company name and the admin username/password, then click **Install**.

4. **Delete `install.php`** from the server when it finishes.

5. **Log in** at `https://yourdomain.com/admin/` and fill in *Site Settings* (phone, email, address, WhatsApp, hero image, logo, map).

6. **PHP version**: cPanel → *Select PHP Version* → choose 8.1 or 8.2 and make sure `pdo_mysql`, `gd`, `mbstring`, `fileinfo` are ticked.

7. **HTTPS**: once the free SSL (AutoSSL) is active, open `.htaccess` and remove the `#` in front of the three "Force HTTPS" lines.

If the installer cannot write `config.php`, copy `config.sample.php` to `config.php`, fill it in, and import `database/schema.sql` through phpMyAdmin, then create your admin user via the installer or phpMyAdmin.

## GitHub Actions deployment to Truehost

The workflow at `.github/workflows/deploy-truehost.yml` deploys each push to `main` over FTPS. In the GitHub repository, open **Settings → Secrets and variables → Actions** and add these repository secrets:

| Secret | Value |
|---|---|
| `TRUEHOST_FTP_SERVER` | FTP server hostname from Truehost/cPanel (not necessarily the website hostname) |
| `TRUEHOST_FTP_USERNAME` | FTP account username |
| `TRUEHOST_FTP_PASSWORD` | FTP account password |
| `TRUEHOST_FTP_SERVER_DIR` | Destination directory relative to the FTP account home. The current expected value is `tripex.splentainsights.com/`; verify the correct path in cPanel and keep the trailing slash. |
| `TRUEHOST_FTP_PORT` | FTPS port from Truehost. Common explicit FTPS port is `21`; confirm with the provider. |
| `TRUEHOST_FTP_PROTOCOL` | `ftps` for explicit FTPS; use only the protocol confirmed by Truehost. |

The workflow deliberately does not upload `config.php`, `install.php`, or `uploads/`, and does not clear the destination before deployment. Configure the development site's `config.php`, database, and writable uploads directory on the server separately. Remove any existing installer from the server after setup. Never add hosting credentials or `config.php` to Git.

## Admin panel (`/admin/`)

| Section | What you can do |
|---|---|
| Dashboard | Counts, latest enquiries, most viewed products |
| Products | Add / edit / delete, multiple images (choose the main one), price, offer price, unit, stock status, low-stock threshold, specs, visible & featured toggles, **quick price edit in the list**, and CSV import |
| Stock control | Record signed stock movements with a required reason, review the adjustment history, and filter products below their alert thresholds |
| Staff accounts | Create admin accounts, reset passwords, disable/reactivate accounts, and delete disabled accounts. All staff accounts currently have full admin access. |
| Categories | Add / edit / delete with image and display order |
| Gallery | Upload project photos with captions |
| Enquiries | Read, reply by email, delete (also emailed to the address in settings) |
| Site Settings | Company details, hero text & image, logo, about text, social links, Google Map |
| My Account | Change username / password |

Leave a product's price empty to show **"Request a quote"** instead of a price.

To bulk add products, open **Admin → Products**, download the CSV template, fill it in and upload it. The importer accepts category names or slugs, leaves empty prices as quote-only, and skips invalid rows with line-by-line feedback. CSV files are limited to 5 MB. Products with a duplicate slug are skipped.

Customers can add products from the catalogue to a quote list and submit a project request at `/quote`. The request captures optional quantities, site type and location, timeline, installation preference and project notes, and appears in **Admin → Enquiries**. The homepage features an automatically rotating, keyboard-accessible project slideshow. On mobile, a fixed bottom dock provides Home, Products, Projects, Call, WhatsApp and Quote shortcuts (contact shortcuts appear when configured).

On existing installations, deploy the update and sign in to the admin panel; the site will direct an administrator to the one-time **Database upgrade** page. Run it before using staff accounts or stock control. Existing product stock counts remain untracked until you record opening quantities in **Admin → Stock control**. New installations include the updated schema automatically.

## Notes

- Images are resized on upload and responsive 480/960/1280 px variants are generated when the source dimensions allow (JPG/PNG/WEBP, 6 MB each; GD recommended). If an upload fails, check the PHP limits `upload_max_filesize` / `post_max_size` in *Select PHP Version → Options*.
- Storefront images use responsive source sets, mobile product galleries support swipe, and mobile visitors get a bottom navigation dock with quick contact and quote actions. Static assets and uploaded images use long-lived cache headers; CSS uses a versioned URL when updated. Apache `mod_deflate` enables response compression, while `mod_headers` applies the long-lived cache policy.
- The storefront uses system and serif font stacks so it renders immediately without waiting for external font services.
- Enquiry emails use PHP `mail()`. If they don't arrive, create a mailbox in cPanel (e.g. `info@yourdomain.com`) and use it as the Email in Site Settings. Enquiries are always saved in the admin panel regardless.
- Back up regularly: the database (phpMyAdmin → Export) and the `uploads/` folder.
- Security built in: prepared SQL statements, CSRF tokens on every form, hashed passwords, login rate-limiting, upload validation, PHP execution blocked in `uploads/`, `includes/`, `database/` and `config.php` protected.

## Structure

```
index.php products.php product.php services.php gallery.php about.php contact.php   public pages
admin/        admin panel
includes/     bootstrap, helpers, header/footer
assets/       css, placeholder image
uploads/      images uploaded from the admin
database/     schema.sql
install.php   one-time installer (delete after use)
```
