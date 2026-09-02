DROP PROCEDURE IF EXISTS rumika_add_booking_source_to_appointments;

DELIMITER $$
CREATE PROCEDURE rumika_add_booking_source_to_appointments()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'appointments'
          AND COLUMN_NAME = 'booking_source'
    ) THEN
        ALTER TABLE appointments
            ADD COLUMN booking_source VARCHAR(30) NOT NULL DEFAULT 'manual' AFTER status;
    END IF;
END$$
DELIMITER ;

CALL rumika_add_booking_source_to_appointments();

DROP PROCEDURE IF EXISTS rumika_add_booking_source_to_appointments;

UPDATE appointments
SET booking_source = 'manual'
WHERE booking_source IS NULL OR booking_source = '';
