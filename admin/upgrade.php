<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin(false);

$errors = [];
$updated = admin_inventory_schema_ready();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    try {
        $columnExists = static function (string $table, string $column): bool {
            return (bool)q(
                'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, $column]
            )->fetchColumn();
        };
        $tableExists = static function (string $table): bool {
            return (bool)q(
                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$table]
            )->fetchColumn();
        };

        if (!$columnExists('admins', 'is_active')) {
            db()->exec('ALTER TABLE admins ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER password_hash');
        }
        if (!$columnExists('products', 'stock_quantity')) {
            db()->exec('ALTER TABLE products ADD COLUMN stock_quantity DECIMAL(12,2) NULL DEFAULT NULL AFTER stock_status');
        }
        if (!$columnExists('products', 'low_stock_threshold')) {
            db()->exec('ALTER TABLE products ADD COLUMN low_stock_threshold DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER stock_quantity');
        }
        if (!$tableExists('customers')) {
            db()->exec(
                'CREATE TABLE customers (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(120) NOT NULL,
                    email VARCHAR(160) NOT NULL UNIQUE,
                    phone VARCHAR(40) NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
        }
        if (!$columnExists('enquiries', 'customer_id')) {
            db()->exec('ALTER TABLE enquiries ADD COLUMN customer_id INT UNSIGNED NULL AFTER product_id');
            db()->exec('ALTER TABLE enquiries ADD KEY idx_enquiry_customer (customer_id)');
            db()->exec('ALTER TABLE enquiries ADD CONSTRAINT fk_enquiry_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL');
        }
        if (!$columnExists('enquiries', 'source')) {
            db()->exec("ALTER TABLE enquiries ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT 'website' AFTER message");
        }
        $enquiryEmailNullable = (bool)q(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'enquiries' AND COLUMN_NAME = 'email' AND IS_NULLABLE = 'YES'"
        )->fetchColumn();
        if (!$enquiryEmailNullable) {
            db()->exec('ALTER TABLE enquiries MODIFY email VARCHAR(160) NULL');
        }

        db()->exec(
            'CREATE TABLE IF NOT EXISTS product_stock_adjustments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                admin_id INT UNSIGNED NULL,
                adjustment DECIMAL(12,2) NOT NULL,
                quantity_after DECIMAL(12,2) NOT NULL,
                reason VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_stock_product (product_id, created_at),
                KEY idx_stock_admin (admin_id),
                CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                CONSTRAINT fk_stock_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        if (!admin_inventory_schema_ready()) {
            throw new RuntimeException('The required database changes could not be verified.');
        }
        flash('success', 'Database upgrade complete. WhatsApp enquiries can now be submitted without an email address. Existing product stock counts remain untracked until you enter them.');
        redirect('admin/index.php');
    } catch (Throwable $ex) {
        $errors[] = 'Database upgrade failed: ' . $ex->getMessage();
    }
}

$adminTitle = 'Database upgrade';
include __DIR__ . '/_header.php';
?>
<section class="panel narrow">
  <?php if ($updated): ?>
    <div class="alert alert-success">The customer account, enquiry channel and inventory database updates are already installed.</div>
    <a class="btn btn-primary" href="index.php">Return to dashboard</a>
  <?php else: ?>
    <p>This one-time upgrade adds active staff accounts, product stock quantities and thresholds, an inventory adjustment history, and support for WhatsApp enquiries without an email address. Existing product stock counts will be left untracked until entered; the upgrade will not change product visibility or status.</p>
    <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post">
      <?= csrf_field() ?>
      <button class="btn btn-primary" type="submit">Upgrade database</button>
    </form>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/_footer.php'; ?>
