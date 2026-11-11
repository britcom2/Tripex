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

The /admin/ area provides tools for managing the site:

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
| index.php, products.php, product.php | Homepage and product catalogue |
| about.php, services.php, gallery.php, contact.php | Company and contact pages |
| account.php, quote.php | Customer accounts and quote/enquiry flow |
| admin/ | Administration and content management |
| includes/ | Shared application bootstrap, helpers and page layout |
| assets/ | Stylesheets and static assets |
| uploads/ | Images managed through the site |
| database/ | Database schema |

## Technical installation

### Requirements

- PHP 7.4 or newer
- MySQL database
- PDO MySQL enabled
- GD enabled for image resizing and responsive variants
- mbstring and fileinfo enabled
- Write access to the project root for config.php and uploads/ on first install

For cPanel / Truehost, make sure the PHP version selected in the hosting control panel includes these extensions:

- pdo_mysql
- gd
- mbstring
- fileinfo

### 1. Prepare the database

Create a MySQL database and database user in cPanel:

1. Open MySQL® Databases.
2. Create a database, for example: myacct_tripex
3. Create a database user with a strong password.
4. Add the user to the database with All Privileges.
5. Note the database host, database name, username and password.

If you are using cPanel, the host is usually localhost and the database name is typically prefixed by your account name.

### 2. Upload the project files

Upload the project files to your web root or the domain/subdomain folder you want to use, for example:

- public_html/
- public_html/tripex/
- or a subdomain directory

The root should contain files such as:

- index.php
- product.php
- products.php
- admin/
- includes/
- assets/
- uploads/
- database/
- install.php

### 3. Create the configuration file

The easiest route is to use the built-in installer, which creates config.php for you.

If you prefer to do it manually:

1. Copy config.sample.php to config.php
2. Update the values:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('BASE_URL', '');
define('DEBUG', false);
```

Notes:

- If the site is installed in a subfolder, set BASE_URL to the folder path, for example `/tripex`
- If the site is in the domain root, leave BASE_URL as an empty string

### 4. Import the database schema

You can either:

- let the web installer create the full schema automatically, or
- import database/schema.sql manually through phpMyAdmin

The schema includes the tables required for:

- admin accounts
- categories and products
- product images and stock adjustments
- customer accounts
- enquiries
- site settings
- login attempts

### 5. Run the installer

Open the installer in your browser:

- https://yourdomain.com/install.php

Then:

1. Enter the MySQL database details
2. Choose your admin username and password
3. Optionally enable sample products
4. Click Install

The installer will:

- create the database tables from database/schema.sql if needed
- insert the default settings
- create the first administrator account
- generate config.php
- create the uploads directory

### 6. Finish setup in the admin panel

After installation:

1. Log in to https://yourdomain.com/admin/
2. Go to Site Settings
3. Fill in:
   - company name
   - phone
   - email
   - address
   - WhatsApp number
   - hero image
   - logo
   - map
4. Add categories and products
5. Review the dashboard and stock alerts

### 7. Remove the installer

For security, delete install.php after setup is complete.

The application will warn you if the database already contains an administrator and the installer should not be left active on the live server.

### 8. cPanel and hosting notes

- Make sure the uploads/ directory is writable by the PHP process
- Confirm that PDO MySQL and GD are enabled in the selected PHP version
- If the site is served over HTTPS, enable SSL in cPanel and then update the site to force HTTPS in your .htaccess rules if needed
- If email is not working, create an email account in cPanel and use that address in the Site Settings email field

### 9. Manual recovery if config cannot be written

If the installer cannot write config.php, do this manually:

1. Copy config.sample.php to config.php
2. Fill in your database credentials and BASE_URL
3. Import database/schema.sql via phpMyAdmin
4. Open install.php again to create the first admin account if needed

### 10. GitHub Actions deployment (optional)

The repository includes a GitHub Actions workflow for deploying the site over FTPS to a Truehost cPanel account.

Configure these repository secrets under GitHub:

- TRUEHOST_FTP_SERVER
- TRUEHOST_FTP_USERNAME
- TRUEHOST_FTP_PASSWORD
- TRUEHOST_FTP_SERVER_DIR
- TRUEHOST_FTP_PORT
- TRUEHOST_FTP_PROTOCOL

The deployment workflow is designed to avoid uploading config.php, install.php and uploads/, so those files must be maintained manually or separately on the target server.

## Notes

- The site is intentionally lightweight and does not depend on a framework.
- Images are resized on upload and responsive image variants are generated when supported by the server.
- The storefront includes mobile-friendly gallery navigation and quick actions for contact and quoting.
- PHP mail() is used for enquiry emails; if mail is not arriving, make sure the configured sender email exists in cPanel.
- Back up the database and uploads/ folder regularly.

## Security and reliability

- Prepared SQL statements are used for database queries.
- CSRF validation is applied to forms.
- Passwords are hashed before storage.
- Upload validation checks file type and size.
- The application blocks PHP execution in uploads/ via server rules.

This project is intended for shared hosting environments with straightforward PHP/MySQL deployment and no build pipeline.
