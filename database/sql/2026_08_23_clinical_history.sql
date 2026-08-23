CREATE TABLE `clinical_specialties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinical_specialties_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `clinical_specialties_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clinical_specialty_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `clinical_specialty_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinical_specialty_user_unique` (`clinical_specialty_id`,`user_id`),
  KEY `clinical_specialty_user_user_id_foreign` (`user_id`),
  CONSTRAINT `clinical_specialty_user_clinical_specialty_id_foreign` FOREIGN KEY (`clinical_specialty_id`) REFERENCES `clinical_specialties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_specialty_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clinical_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'ficha_inicial',
  `body` longtext NULL,
  `fields` json NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinical_templates_company_id_category_is_active_index` (`company_id`,`category`,`is_active`),
  CONSTRAINT `clinical_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clinical_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `appointment_id` bigint unsigned NULL,
  `appointment_service_id` bigint unsigned NULL,
  `service_id` bigint unsigned NULL,
  `clinical_template_id` bigint unsigned NULL,
  `created_by_user_id` bigint unsigned NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'ficha',
  `content` longtext NULL,
  `data` json NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinical_records_company_id_client_id_type_index` (`company_id`,`client_id`,`type`),
  KEY `clinical_records_appointment_id_foreign` (`appointment_id`),
  KEY `clinical_records_appointment_service_id_foreign` (`appointment_service_id`),
  KEY `clinical_records_service_id_foreign` (`service_id`),
  KEY `clinical_records_clinical_template_id_foreign` (`clinical_template_id`),
  KEY `clinical_records_created_by_user_id_foreign` (`created_by_user_id`),
  CONSTRAINT `clinical_records_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_records_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_records_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinical_records_appointment_service_id_foreign` FOREIGN KEY (`appointment_service_id`) REFERENCES `appointment_services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinical_records_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinical_records_clinical_template_id_foreign` FOREIGN KEY (`clinical_template_id`) REFERENCES `clinical_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinical_records_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clinical_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `appointment_id` bigint unsigned NULL,
  `appointment_service_id` bigint unsigned NULL,
  `service_id` bigint unsigned NULL,
  `clinical_record_id` bigint unsigned NULL,
  `uploaded_by_user_id` bigint unsigned NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NULL,
  `mime_type` varchar(255) NULL,
  `file_size` bigint unsigned NULL,
  `notes` text NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinical_documents_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `clinical_documents_appointment_id_foreign` (`appointment_id`),
  KEY `clinical_documents_appointment_service_id_foreign` (`appointment_service_id`),
  KEY `clinical_documents_service_id_foreign` (`service_id`),
  KEY `clinical_documents_clinical_record_id_foreign` (`clinical_record_id`),
  KEY `clinical_documents_uploaded_by_user_id_foreign` (`uploaded_by_user_id`),
  CONSTRAINT `clinical_documents_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_documents_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_documents_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinical_documents_appointment_service_id_foreign` FOREIGN KEY (`appointment_service_id`) REFERENCES `appointment_services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinical_documents_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinical_documents_clinical_record_id_foreign` FOREIGN KEY (`clinical_record_id`) REFERENCES `clinical_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinical_documents_uploaded_by_user_id_foreign` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clinical_prescriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `appointment_id` bigint unsigned NULL,
  `appointment_service_id` bigint unsigned NULL,
  `issued_by_user_id` bigint unsigned NULL,
  `title` varchar(255) NOT NULL DEFAULT 'Receta',
  `indications` longtext NOT NULL,
  `issued_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinical_prescriptions_company_id_client_id_issued_at_index` (`company_id`,`client_id`,`issued_at`),
  KEY `clinical_prescriptions_appointment_id_foreign` (`appointment_id`),
  KEY `clinical_prescriptions_appointment_service_id_foreign` (`appointment_service_id`),
  KEY `clinical_prescriptions_issued_by_user_id_foreign` (`issued_by_user_id`),
  CONSTRAINT `clinical_prescriptions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_prescriptions_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_prescriptions_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinical_prescriptions_appointment_service_id_foreign` FOREIGN KEY (`appointment_service_id`) REFERENCES `appointment_services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinical_prescriptions_issued_by_user_id_foreign` FOREIGN KEY (`issued_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clinical_patient_accesses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `granted_by_user_id` bigint unsigned NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 1,
  `can_create` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` timestamp NULL DEFAULT NULL,
  `reason` text NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinical_patient_access_unique` (`company_id`,`client_id`,`user_id`),
  KEY `clinical_patient_accesses_company_id_user_id_can_view_index` (`company_id`,`user_id`,`can_view`),
  KEY `clinical_patient_accesses_client_id_foreign` (`client_id`),
  KEY `clinical_patient_accesses_user_id_foreign` (`user_id`),
  KEY `clinical_patient_accesses_granted_by_user_id_foreign` (`granted_by_user_id`),
  CONSTRAINT `clinical_patient_accesses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_patient_accesses_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_patient_accesses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinical_patient_accesses_granted_by_user_id_foreign` FOREIGN KEY (`granted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE `roles`
SET `permissions` = JSON_SET(COALESCE(`permissions`, JSON_OBJECT()), '$.historia_clinica', JSON_ARRAY('view','create','edit','delete','view_full','manage_access'))
WHERE `slug` = 'administrador';

UPDATE `roles`
SET `permissions` = JSON_SET(COALESCE(`permissions`, JSON_OBJECT()), '$.historia_clinica', JSON_ARRAY('view','create','edit','view_full'))
WHERE `slug` = 'gerente';

UPDATE `roles`
SET `permissions` = JSON_SET(COALESCE(`permissions`, JSON_OBJECT()), '$.historia_clinica', JSON_ARRAY('view','create'))
WHERE `slug` IN ('recepcion','profesional');
