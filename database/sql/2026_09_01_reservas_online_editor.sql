CREATE TABLE IF NOT EXISTS booking_pages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    slug VARCHAR(255) NOT NULL,
    title VARCHAR(255) NULL,
    subtitle VARCHAR(255) NULL,
    hero_label VARCHAR(80) NULL,
    button_label VARCHAR(60) NULL,
    success_message VARCHAR(240) NULL,
    template VARCHAR(40) NOT NULL DEFAULT 'clean',
    mode VARCHAR(20) NOT NULL DEFAULT 'general',
    primary_color VARCHAR(20) NOT NULL DEFAULT '#008b7d',
    accent_color VARCHAR(20) NOT NULL DEFAULT '#dff7f2',
    background_color VARCHAR(20) NOT NULL DEFAULT '#f6f8fb',
    background_image_path VARCHAR(255) NULL,
    font_family VARCHAR(80) NOT NULL DEFAULT 'Figtree',
    icon_shape VARCHAR(20) NOT NULL DEFAULT 'rounded',
    available_from TIME NOT NULL DEFAULT '09:00:00',
    available_to TIME NOT NULL DEFAULT '18:00:00',
    slot_interval_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    max_appointments_per_slot SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    default_duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    min_days_ahead SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    max_days_ahead SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    show_prices TINYINT(1) NOT NULL DEFAULT 1,
    show_branch_cards TINYINT(1) NOT NULL DEFAULT 1,
    show_service_duration TINYINT(1) NOT NULL DEFAULT 1,
    show_company_logo TINYINT(1) NOT NULL DEFAULT 1,
    require_identity TINYINT(1) NOT NULL DEFAULT 0,
    require_email TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY booking_pages_slug_unique (slug),
    KEY booking_pages_company_active_index (company_id, is_active),
    CONSTRAINT booking_pages_company_id_foreign FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

DROP PROCEDURE IF EXISTS rumika_add_booking_column;
DELIMITER $$
CREATE PROCEDURE rumika_add_booking_column(
    IN column_name_param VARCHAR(64),
    IN column_sql_param TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'booking_pages'
            AND COLUMN_NAME = column_name_param
    ) THEN
        SET @rumika_sql = CONCAT('ALTER TABLE booking_pages ADD COLUMN ', column_sql_param);
        PREPARE rumika_stmt FROM @rumika_sql;
        EXECUTE rumika_stmt;
        DEALLOCATE PREPARE rumika_stmt;
    END IF;
END$$
DELIMITER ;

CALL rumika_add_booking_column('hero_label', 'hero_label VARCHAR(80) NULL AFTER subtitle');
CALL rumika_add_booking_column('button_label', 'button_label VARCHAR(60) NULL AFTER hero_label');
CALL rumika_add_booking_column('success_message', 'success_message VARCHAR(240) NULL AFTER button_label');
CALL rumika_add_booking_column('max_appointments_per_slot', 'max_appointments_per_slot SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER slot_interval_minutes');
CALL rumika_add_booking_column('min_days_ahead', 'min_days_ahead SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER default_duration_minutes');
CALL rumika_add_booking_column('max_days_ahead', 'max_days_ahead SMALLINT UNSIGNED NOT NULL DEFAULT 30 AFTER min_days_ahead');
CALL rumika_add_booking_column('show_branch_cards', 'show_branch_cards TINYINT(1) NOT NULL DEFAULT 1 AFTER show_prices');
CALL rumika_add_booking_column('show_service_duration', 'show_service_duration TINYINT(1) NOT NULL DEFAULT 1 AFTER show_branch_cards');
CALL rumika_add_booking_column('show_company_logo', 'show_company_logo TINYINT(1) NOT NULL DEFAULT 1 AFTER show_service_duration');
CALL rumika_add_booking_column('require_identity', 'require_identity TINYINT(1) NOT NULL DEFAULT 0 AFTER show_company_logo');
CALL rumika_add_booking_column('require_email', 'require_email TINYINT(1) NOT NULL DEFAULT 0 AFTER require_identity');

DROP PROCEDURE IF EXISTS rumika_add_booking_column;
