CREATE TABLE IF NOT EXISTS `whatsapp_channels` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(255) NOT NULL,
  `phone_number` VARCHAR(255) NULL,
  `phone_number_id` VARCHAR(255) NOT NULL,
  `waba_id` VARCHAR(255) NULL,
  `api_version` VARCHAR(255) NOT NULL DEFAULT 'v23.0',
  `access_token` TEXT NOT NULL,
  `verify_token` TEXT NULL,
  `audio_converter_api_key` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_channels_phone_number_id_unique` (`phone_number_id`),
  KEY `whatsapp_channels_company_id_is_active_index` (`company_id`, `is_active`),
  KEY `whatsapp_channels_branch_id_is_active_index` (`branch_id`, `is_active`),
  CONSTRAINT `whatsapp_channels_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_channels_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contacts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `client_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(255) NULL,
  `phone` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `last_interaction_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_contacts_company_id_phone_unique` (`company_id`, `phone`),
  KEY `crm_contacts_company_id_last_interaction_at_index` (`company_id`, `last_interaction_at`),
  KEY `crm_contacts_client_id_foreign` (`client_id`),
  CONSTRAINT `crm_contacts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_contacts_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_conversations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `whatsapp_channel_id` BIGINT UNSIGNED NOT NULL,
  `crm_contact_id` BIGINT UNSIGNED NOT NULL,
  `client_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'open',
  `unread_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_message` TEXT NULL,
  `last_message_at` TIMESTAMP NULL,
  `last_customer_message_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `crm_conversations_company_id_status_last_message_at_index` (`company_id`, `status`, `last_message_at`),
  KEY `crm_conversations_whatsapp_channel_id_last_message_at_index` (`whatsapp_channel_id`, `last_message_at`),
  KEY `crm_conversations_crm_contact_id_foreign` (`crm_contact_id`),
  KEY `crm_conversations_client_id_foreign` (`client_id`),
  CONSTRAINT `crm_conversations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_conversations_whatsapp_channel_id_foreign` FOREIGN KEY (`whatsapp_channel_id`) REFERENCES `whatsapp_channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_conversations_crm_contact_id_foreign` FOREIGN KEY (`crm_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_conversations_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `crm_conversation_id` BIGINT UNSIGNED NOT NULL,
  `whatsapp_channel_id` BIGINT UNSIGNED NOT NULL,
  `crm_contact_id` BIGINT UNSIGNED NOT NULL,
  `wamid` VARCHAR(255) NULL,
  `direction` VARCHAR(255) NOT NULL,
  `type` VARCHAR(255) NOT NULL DEFAULT 'text',
  `body` TEXT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'received',
  `media_id` VARCHAR(255) NULL,
  `media_url` VARCHAR(255) NULL,
  `media_mime_type` VARCHAR(255) NULL,
  `media_filename` VARCHAR(255) NULL,
  `raw_payload` JSON NULL,
  `message_at` TIMESTAMP NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `reply_to_wamid` VARCHAR(255) NULL,
  `reply_preview` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_messages_wamid_unique` (`wamid`),
  KEY `crm_messages_company_id_message_at_index` (`company_id`, `message_at`),
  KEY `crm_messages_crm_conversation_id_message_at_index` (`crm_conversation_id`, `message_at`),
  KEY `crm_messages_whatsapp_channel_id_foreign` (`whatsapp_channel_id`),
  KEY `crm_messages_crm_contact_id_foreign` (`crm_contact_id`),
  CONSTRAINT `crm_messages_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_messages_crm_conversation_id_foreign` FOREIGN KEY (`crm_conversation_id`) REFERENCES `crm_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_messages_whatsapp_channel_id_foreign` FOREIGN KEY (`whatsapp_channel_id`) REFERENCES `whatsapp_channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_messages_crm_contact_id_foreign` FOREIGN KEY (`crm_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE `company_plans`
SET `features` = JSON_SET(`features`, '$.modules', JSON_ARRAY('inicio', 'agenda', 'clientes', 'historia_clinica', 'servicios', 'caja', 'facturacion', 'deudas', 'reportes', 'comisiones', 'sucursales', 'usuarios', 'roles', 'inventario', 'inventario_operaciones', 'gastos', 'estadisticas', 'crm'))
WHERE `slug` = 'plus';

UPDATE `roles`
SET `permissions` = JSON_SET(COALESCE(`permissions`, JSON_OBJECT()), '$.crm', JSON_ARRAY('view', 'create', 'edit', 'delete'))
WHERE `slug` IN ('administrador', 'admin', 'owner', 'super_admin', 'super-administrador');

UPDATE `roles`
SET `permissions` = JSON_SET(COALESCE(`permissions`, JSON_OBJECT()), '$.crm', JSON_ARRAY('view', 'create', 'edit'))
WHERE `slug` IN ('gerente', 'recepcion');
