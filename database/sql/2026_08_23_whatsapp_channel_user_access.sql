CREATE TABLE IF NOT EXISTS `user_whatsapp_channel` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `whatsapp_channel_id` BIGINT UNSIGNED NOT NULL,
  `assigned_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_whatsapp_channel_user_id_whatsapp_channel_id_unique` (`user_id`, `whatsapp_channel_id`),
  KEY `user_whatsapp_channel_whatsapp_channel_id_foreign` (`whatsapp_channel_id`),
  CONSTRAINT `user_whatsapp_channel_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_whatsapp_channel_whatsapp_channel_id_foreign` FOREIGN KEY (`whatsapp_channel_id`) REFERENCES `whatsapp_channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
