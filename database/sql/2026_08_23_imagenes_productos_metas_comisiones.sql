ALTER TABLE inventory_products
    ADD COLUMN image_path VARCHAR(255) NULL AFTER description;

CREATE TABLE IF NOT EXISTS commission_targets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    period_type VARCHAR(255) NOT NULL DEFAULT 'monthly',
    minimum_sales_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    minimum_commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status VARCHAR(255) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX commission_targets_scope_idx (company_id, branch_id, user_id, period_type),
    CONSTRAINT commission_targets_company_id_foreign FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT commission_targets_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    CONSTRAINT commission_targets_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

UPDATE company_plans
SET features = JSON_SET(features, '$.modules',
    CASE slug
        WHEN 'basico' THEN JSON_ARRAY('inicio', 'agenda', 'clientes', 'historia_clinica', 'servicios', 'caja', 'facturacion', 'deudas', 'reportes', 'sucursales')
        WHEN 'plus' THEN JSON_ARRAY('inicio', 'agenda', 'clientes', 'historia_clinica', 'servicios', 'caja', 'facturacion', 'deudas', 'reportes', 'comisiones', 'sucursales', 'usuarios', 'roles', 'inventario', 'inventario_operaciones', 'gastos', 'estadisticas')
        ELSE JSON_EXTRACT(features, '$.modules')
    END
)
WHERE slug IN ('basico', 'plus');

UPDATE roles
SET permissions = JSON_SET(
    COALESCE(permissions, JSON_OBJECT()),
    '$.comisiones',
    JSON_ARRAY('view', 'create', 'edit', 'delete')
)
WHERE slug = 'administrador';
