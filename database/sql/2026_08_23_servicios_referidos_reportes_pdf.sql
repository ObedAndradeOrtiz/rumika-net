ALTER TABLE appointment_services
    ADD COLUMN referred_by_user_id BIGINT UNSIGNED NULL AFTER performed_by_user_id,
    ADD CONSTRAINT appointment_services_referred_by_user_id_foreign
        FOREIGN KEY (referred_by_user_id) REFERENCES users(id) ON DELETE SET NULL;
