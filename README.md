# Tripex Carpets

Tripex Carpets is a product-catalogue and enquiry website for a carpet and flooring business. It gives customers a simple way to explore products, learn about the company and its services, and get in touch, while providing staff with an admin panel to manage the catalogue, inventory, content and enquiries.

The project is built with PHP and MySQL, without a PHP framework or a frontend build step.

## What the website offers

- Product catalogue: browse products by category, search by product name or SKU, and sort by newest, name or price.
- Product details: view descriptions, specifications, images, availability and pricing information.
- Quote-based sales: products can display a price, an offer price, or “Request a quote” when a price is not listed.
- Company information: public pages for the home page, about, services, gallery and contact details.
- Enquiries and contact: customers can submit website enquiries or continue a conversation through WhatsApp.
- Customer accounts: customers can register, sign in, submit enquiries and review their enquiry history.
- Responsive experience: the storefront adapts to mobile screens and includes mobile-friendly product galleries and quick contact actions.

## Admin panel

The `/admin/` area provides tools for managing the site:

- Dashboard: view product and category totals, recent enquiries, popular products and low-stock alerts.
- Products: create and edit listings, manage product images, descriptions, specifications, pricing, offers, availability, visibility and featured status. Prices can also be updated from the product list.
- Bulk product import: download a CSV template and import multiple products, with row-level feedback for skipped or invalid entries.
- Inventory: track stock quantities and alert thresholds, record stock adjustments with a reason, and review adjustment history.
- Categories: organise products and control category images and display order.
- Gallery: manage project photos and captions.
- Enquiries: review incoming messages, identify their channel, and follow up by email or WhatsApp.
- Staff accounts: create and manage administrator accounts.
- Site settings: update contact information, branding, homepage content, social links and map details.
- Account and system tools: update an administrator’s credentials and check site readiness.

## Project characteristics

- Server-rendered PHP pages backed by MySQL.
- No framework or frontend compilation step.
- Product and gallery images are managed through the admin panel; responsive image variants are generated when supported by the server.
- Enquiry notifications use PHP’s email functionality, while submitted enquiries are also retained in the admin panel.
- Includes protections such as prepared database queries, CSRF tokens, password hashing, login rate limiting and image-upload validation.

## Main areas of the codebase

| Path | Purpose |
|---|---|
| `index.php`, `products.php`, `product.php` | Homepage and product catalogue |
| `about.php`, `services.php`, `gallery.php`, `contact.php` | Company and contact pages |
| `account.php`, `quote.php` | Customer accounts and quote/enquiry flow |
| `admin/` | Administration and content management |
| `includes/` | Shared application bootstrap, helpers and page layout |
| `assets/` | Stylesheets and static assets |
| `uploads/` | Images managed through the site |
| `database/` | Database schema |

## Technical installation

These instructions are provider-neutral. They apply to any hosting environment that supports the requirements below; control-panel names, database host values, file-transfer methods and document-root paths vary by provider.

### Requirements

- PHP 7.4 or newer
- MySQL database
- PDO MySQL enabled
- GD enabled for image resizing and responsive variants
- `mbstring` and `fileinfo` enabled
- Permission for PHP to write `config.php` during setup and to save uploaded images in `uploads/`

### 1. Prepare the database

Using your hosting provider’s database management interface or command-line tools:

1. Create a MySQL database.
2. Create a database user with a strong password.
3. Grant that user access to the database with the privileges required to create and update tables.
4. Record the database host, name, username and password. Use the host value supplied by your provider; it is not always `localhost`.

### 2. Upload the project files

Upload the project files to the document root configured for your domain or application. The application root should contain files and directories such as:

- `index.php`
- `product.php`
- `products.php`
- `admin/`
- `includes/`
- `assets/`
- `uploads/`
- `database/`
- `install.php`

Preserve hidden files, including `.htaccess`, if your server uses Apache. If you install the site in a subdirectory, use that path when setting `BASE_URL` below.

### 3. Run the installer

Open the installer in a browser at the corresponding site URL, for example `https://example.com/install.php` or `https://example.com/store/install.php` for a subdirectory installation.

Enter the database details, choose an administrator username and a strong password, set the site name, and choose whether to add sample products. The installer creates the database tables, inserts default settings and sample categories, creates the administrator account, writes `config.php`, and creates the `uploads/` directory.

If `config.php` already exists and the configured database has no administrator account, the installer can offer a one-time form to create the first administrator. If an administrator already exists, use the admin login instead.

### 4. Manual configuration alternative

If the installer cannot create `config.php`, copy `config.sample.php` to `config.php` and enter the database credentials and base path:

```php
<?php
define('DB_HOST', 'database-host');
define('DB_NAME', 'database-name');
define('DB_USER', 'database-user');
define('DB_PASS', 'database-password');
define('BASE_URL', '');
define('DEBUG', false);
```

Set `BASE_URL` to an empty string when the site is at the domain root, or to the URL path of its subdirectory (for example, `/store`). Keep `DEBUG` disabled on a production site.

After creating the configuration file, import `database/schema.sql` using your database management tool. Then open `install.php` to create the first administrator if the database does not already have one.

### 5. Complete site setup

Sign in at `https://example.com/admin/` (adjust the URL if installed in a subdirectory). In Site Settings, add the company name, phone, email, address, WhatsApp number, logo, hero image, social links and map details. Then add or import product listings and check the dashboard for enquiries and stock alerts.

### 6. Secure the installation

Delete `install.php` from the live server as soon as setup is complete. Configure HTTPS using the method supported by your hosting provider. Confirm that the `uploads/` directory is writable by PHP and that uploaded files cannot be executed as scripts; the repository includes `.htaccess` rules for Apache-based servers.

### 7. Optional deployment automation

The repository may include deployment automation under `.github/workflows/`. Review the workflow before using it and configure it for your own hosting provider, deployment protocol, destination and repository secrets. Do not expose database credentials or other secrets in source control. The deployment process should preserve the live `config.php` and uploaded content, and should not remove files from the destination unless that behavior is intentional.

## Notes

- Images are resized on upload and responsive image variants are generated when supported by the server.
- The storefront includes mobile-friendly gallery navigation and quick actions for contact and quoting.
- PHP `mail()` is used for enquiry emails; delivery depends on the server’s mail configuration. Enquiries are also saved in the admin panel.
- Back up the database and `uploads/` folder regularly.

## Security and reliability

- Prepared SQL statements are used for database queries.
- CSRF validation is applied to forms.
- Passwords are hashed before storage.
- Upload validation checks file type and size.
- The application blocks PHP execution in `uploads/` through Apache server rules where supported.

This project is intended for PHP/MySQL hosting environments and has no frontend build pipeline.
