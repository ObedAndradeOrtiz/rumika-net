CREATE TABLE IF NOT EXISTS buyers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    full_name VARCHAR(255) NULL,
    nit VARCHAR(255) NULL,
    phone VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX buyers_company_status_idx (company_id, status),
    UNIQUE KEY buyers_company_nit_unique (company_id, nit),
    UNIQUE KEY buyers_company_phone_unique (company_id, phone),
    CONSTRAINT buyers_company_id_foreign FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS product_sales (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    buyer_id BIGINT UNSIGNED NULL,
    sold_by_user_id BIGINT UNSIGNED NULL,
    received_by_user_id BIGINT UNSIGNED NULL,
    buyer_name VARCHAR(255) NULL,
    buyer_nit VARCHAR(255) NULL,
    buyer_phone VARCHAR(255) NULL,
    buyer_email VARCHAR(255) NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    cash_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    qr_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    method VARCHAR(255) NOT NULL DEFAULT 'cash',
    invoice_requested TINYINT(1) NOT NULL DEFAULT 0,
    reference VARCHAR(255) NULL,
    notes TEXT NULL,
    sold_at DATETIME NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX product_sales_company_branch_sold_idx (company_id, branch_id, sold_at),
    INDEX product_sales_buyer_sold_idx (buyer_id, sold_at),
    CONSTRAINT product_sales_company_id_foreign FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT product_sales_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    CONSTRAINT product_sales_buyer_id_foreign FOREIGN KEY (buyer_id) REFERENCES buyers(id) ON DELETE SET NULL,
    CONSTRAINT product_sales_sold_by_user_id_foreign FOREIGN KEY (sold_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT product_sales_received_by_user_id_foreign FOREIGN KEY (received_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS product_sale_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_sale_id BIGINT UNSIGNED NOT NULL,
    inventory_product_id BIGINT UNSIGNED NOT NULL,
    inventory_product_batch_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    lot_code VARCHAR(255) NULL,
    quantity DECIMAL(12,2) NOT NULL DEFAULT 1,
    stock_quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
    pending_quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    missing_reason VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX product_sale_item_product_batch_idx (inventory_product_id, inventory_product_batch_id),
    CONSTRAINT product_sale_items_product_sale_id_foreign FOREIGN KEY (product_sale_id) REFERENCES product_sales(id) ON DELETE CASCADE,
    CONSTRAINT product_sale_items_inventory_product_id_foreign FOREIGN KEY (inventory_product_id) REFERENCES inventory_products(id) ON DELETE CASCADE,
    CONSTRAINT product_sale_items_inventory_product_batch_id_foreign FOREIGN KEY (inventory_product_batch_id) REFERENCES inventory_product_batches(id) ON DELETE SET NULL
);

UPDATE roles
SET permissions = JSON_SET(
    COALESCE(permissions, JSON_OBJECT()),
    '$.ventas_productos',
    CASE
        WHEN slug = 'administrador' THEN JSON_ARRAY('view', 'create', 'edit', 'delete')
        ELSE JSON_ARRAY('view', 'create', 'edit')
    END
)
WHERE slug IN ('administrador', 'gerente');
