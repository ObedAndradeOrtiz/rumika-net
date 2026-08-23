INSERT INTO business_types (name, slug, description, enabled_modules, created_at, updated_at)
VALUES
('Farmacia', 'farmacia', 'Venta directa, compradores por NIT, inventario por lotes y vencimientos.', '["clientes","ventas_productos","inventario","caja","facturacion"]', NOW(), NOW()),
('Tienda', 'tienda', 'Venta comercial, compradores, inventario, caja y facturacion.', '["clientes","ventas_productos","inventario","caja","facturacion"]', NOW(), NOW())
ON DUPLICATE KEY UPDATE
name = VALUES(name),
description = VALUES(description),
enabled_modules = VALUES(enabled_modules),
updated_at = NOW();

ALTER TABLE treatment_payments
    ADD COLUMN invoice_nit VARCHAR(40) NULL AFTER invoice_requested,
    ADD COLUMN invoice_name VARCHAR(180) NULL AFTER invoice_nit,
    ADD COLUMN invoice_status VARCHAR(30) NOT NULL DEFAULT 'not_requested' AFTER invoice_name,
    ADD COLUMN invoiced_at TIMESTAMP NULL AFTER invoice_status,
    ADD COLUMN invoiced_by_user_id BIGINT UNSIGNED NULL AFTER invoiced_at;

ALTER TABLE treatment_payments
    ADD CONSTRAINT treatment_payments_invoiced_by_user_id_foreign
    FOREIGN KEY (invoiced_by_user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE product_sales
    ADD COLUMN invoice_status VARCHAR(30) NOT NULL DEFAULT 'not_requested' AFTER invoice_requested,
    ADD COLUMN invoiced_at TIMESTAMP NULL AFTER invoice_status,
    ADD COLUMN invoiced_by_user_id BIGINT UNSIGNED NULL AFTER invoiced_at;

ALTER TABLE product_sales
    ADD CONSTRAINT product_sales_invoiced_by_user_id_foreign
    FOREIGN KEY (invoiced_by_user_id) REFERENCES users(id) ON DELETE SET NULL;

UPDATE treatment_payments
SET invoice_status = 'pending'
WHERE invoice_requested = 1 AND invoice_status = 'not_requested';

UPDATE product_sales
SET invoice_status = 'pending'
WHERE invoice_requested = 1 AND invoice_status = 'not_requested';

UPDATE company_plans
SET features = JSON_SET(features, '$.modules',
    CASE slug
        WHEN 'basico' THEN JSON_ARRAY('inicio', 'agenda', 'clientes', 'historia_clinica', 'servicios', 'caja', 'facturacion', 'sucursales')
        WHEN 'plus' THEN JSON_ARRAY('inicio', 'agenda', 'clientes', 'historia_clinica', 'servicios', 'caja', 'facturacion', 'sucursales', 'usuarios', 'roles', 'inventario', 'inventario_operaciones', 'gastos', 'estadisticas')
        ELSE JSON_EXTRACT(features, '$.modules')
    END
)
WHERE slug IN ('basico', 'plus');

UPDATE roles
SET permissions = JSON_SET(COALESCE(permissions, JSON_OBJECT()), '$.facturacion', JSON_ARRAY('view', 'edit'))
WHERE slug = 'administrador';

UPDATE roles
SET permissions = JSON_SET(COALESCE(permissions, JSON_OBJECT()), '$.facturacion', JSON_ARRAY('view'))
WHERE slug = 'gerente';
