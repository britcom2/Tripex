# Tripex Carpets

Tripex Carpets is a product-catalogue and enquiry website for a carpet and flooring business. It gives customers a simple way to explore products, learn about the company and its services, and get in touch, while providing staff with an admin panel to manage the catalogue, inventory, content and enquiries.

The project is built with PHP and MySQL, without a PHP framework or a frontend build step.

## What the website offers

- **Product catalogue:** Browse products by category, search by product name or SKU, and sort by newest, name or price.
- **Product details:** View descriptions, specifications, images, availability and pricing information.
- **Quote-based sales:** Products can display a price, an offer price, or “Request a quote” when a price is not listed. The site supports enquiries rather than an online checkout.
- **Company information:** Public pages for the home page, about, services, gallery and contact details.
- **Enquiries and contact:** Customers can submit website enquiries or continue a conversation through WhatsApp. Enquiries are stored for staff to follow up.
- **Customer accounts:** Customers can register, sign in, submit enquiries and review their enquiry history.
- **Responsive experience:** The storefront adapts to mobile screens and includes mobile-friendly product galleries and quick contact actions.

## Admin panel

The `/admin/` area provides tools for managing the site:

- **Dashboard:** View product and category totals, recent enquiries, popular products and low-stock alerts.
- **Products:** Create and edit listings, manage product images, descriptions, specifications, pricing, offers, availability, visibility and featured status. Prices can also be updated from the product list.
- **Bulk product import:** Download a CSV template and import multiple products, with row-level feedback for skipped or invalid entries.
- **Inventory:** Track stock quantities and alert thresholds, record stock adjustments with a reason, and review adjustment history.
- **Categories:** Organize products and control category images and display order.
- **Gallery:** Manage project photos and captions.
- **Enquiries:** Review incoming messages, identify their channel, and follow up by email or WhatsApp.
- **Staff accounts:** Create and manage administrator accounts.
- **Site settings:** Update company contact information, branding, homepage content, social links and map details.
- **Account and system tools:** Update an administrator's credentials and check site readiness. Existing installations can use the admin database-upgrade flow when an update requires it.

## Project characteristics

- Server-rendered PHP pages backed by MySQL.
- No framework or frontend compilation step.
- Product and gallery images are managed through the admin panel; responsive image variants are generated when supported by the server.
- Enquiry notifications use PHP's email functionality, while submitted enquiries are also retained in the admin panel.
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

Technical installation, configuration and deployment instructions will be documented separately.
