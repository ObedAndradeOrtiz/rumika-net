CREATE TABLE IF NOT EXISTS `company_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) NULL,
  `monthly_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
  `features` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_plans_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `company_plans`
  (`name`, `slug`, `description`, `monthly_price`, `currency`, `features`, `is_active`, `sort_order`, `created_at`, `updated_at`)
VALUES
  ('Free', 'free', 'Plan inicial para probar Rumika.', 0.00, 'USD', JSON_ARRAY('Acceso basico', 'Una empresa', 'Configuracion inicial'), 1, 1, NOW(), NOW()),
  ('Basico', 'basico', 'Operaciones esenciales para negocios pequenos.', 30.00, 'USD', JSON_ARRAY('Agenda', 'Clientes', 'Servicios'), 1, 2, NOW(), NOW()),
  ('Plus', 'plus', 'Gestion completa para equipos en crecimiento.', 60.00, 'USD', JSON_ARRAY('Agenda', 'Clientes', 'Inventario', 'Caja'), 1, 3, NOW(), NOW()),
  ('Empresa', 'empresa', 'Control avanzado para varias sucursales.', 90.00, 'USD', JSON_ARRAY('Sucursales', 'Roles', 'Finanzas', 'Registros'), 1, 4, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `monthly_price` = VALUES(`monthly_price`),
  `currency` = VALUES(`currency`),
  `features` = VALUES(`features`),
  `is_active` = VALUES(`is_active`),
  `sort_order` = VALUES(`sort_order`),
  `updated_at` = NOW();

ALTER TABLE `companies`
  ADD COLUMN `company_plan_id` BIGINT UNSIGNED NULL AFTER `logo_path`,
  ADD INDEX `companies_company_plan_id_foreign` (`company_plan_id`);

UPDATE `companies`
SET `company_plan_id` = (SELECT `id` FROM `company_plans` WHERE `slug` = 'free' LIMIT 1)
WHERE `company_plan_id` IS NULL;

ALTER TABLE `companies`
  ADD CONSTRAINT `companies_company_plan_id_foreign`
  FOREIGN KEY (`company_plan_id`) REFERENCES `company_plans` (`id`)
  ON DELETE SET NULL;

ALTER TABLE `users`
  ADD COLUMN `firebase_uid` VARCHAR(255) NULL AFTER `email_verified_at`,
  ADD COLUMN `auth_provider` VARCHAR(255) NULL AFTER `firebase_uid`,
  ADD UNIQUE KEY `users_firebase_uid_unique` (`firebase_uid`);
