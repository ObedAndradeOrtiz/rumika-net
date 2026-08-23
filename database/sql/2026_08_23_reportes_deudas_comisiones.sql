ALTER TABLE branches
    ADD COLUMN product_commission_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER printer_bridge_url,
    ADD COLUMN product_commission_min_sale DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER product_commission_percent,
    ADD COLUMN service_commission_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER product_commission_min_sale,
    ADD COLUMN service_commission_min_sale DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER service_commission_percent;

ALTER TABLE inventory_products
    ADD COLUMN commission_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER minimum_stock;

ALTER TABLE services
    ADD COLUMN commission_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER duration_minutes;

ALTER TABLE treatment_payment_items
    ADD COLUMN commission_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER total,
    ADD COLUMN commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER commission_percent;

ALTER TABLE product_sale_items
    ADD COLUMN commission_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER total,
    ADD COLUMN commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER commission_percent;

UPDATE company_plans
SET features = JSON_SET(features, '$.modules',
    CASE slug
        WHEN 'basico' THEN JSON_ARRAY('inicio', 'agenda', 'clientes', 'historia_clinica', 'servicios', 'caja', 'facturacion', 'deudas', 'reportes', 'sucursales')
        WHEN 'plus' THEN JSON_ARRAY('inicio', 'agenda', 'clientes', 'historia_clinica', 'servicios', 'caja', 'facturacion', 'deudas', 'reportes', 'sucursales', 'usuarios', 'roles', 'inventario', 'inventario_operaciones', 'gastos', 'estadisticas')
        ELSE JSON_EXTRACT(features, '$.modules')
    END
)
WHERE slug IN ('basico', 'plus');

UPDATE roles
SET permissions = JSON_SET(
    JSON_SET(COALESCE(permissions, JSON_OBJECT()), '$.reportes', JSON_ARRAY('view')),
    '$.deudas',
    JSON_ARRAY('view', 'edit')
)
WHERE slug = 'administrador';

UPDATE roles
SET permissions = JSON_SET(
    JSON_SET(COALESCE(permissions, JSON_OBJECT()), '$.reportes', JSON_ARRAY('view')),
    '$.deudas',
    JSON_ARRAY('view')
)
WHERE slug = 'gerente';
