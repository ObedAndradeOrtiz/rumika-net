ALTER TABLE crm_conversations
    ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

CREATE INDEX crm_conversations_company_id_is_demo_index
    ON crm_conversations (company_id, is_demo);

CREATE TABLE crm_quick_replies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX crm_quick_replies_company_id_is_active_index (company_id, is_active),
    CONSTRAINT crm_quick_replies_company_id_foreign
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE whatsapp_templates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    whatsapp_channel_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(255) NOT NULL DEFAULT 'utility',
    language VARCHAR(12) NOT NULL DEFAULT 'es',
    body TEXT NOT NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY whatsapp_templates_company_id_name_language_unique (company_id, name, language),
    INDEX whatsapp_templates_company_id_status_index (company_id, status),
    INDEX whatsapp_templates_whatsapp_channel_id_foreign (whatsapp_channel_id),
    CONSTRAINT whatsapp_templates_company_id_foreign
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT whatsapp_templates_whatsapp_channel_id_foreign
        FOREIGN KEY (whatsapp_channel_id) REFERENCES whatsapp_channels(id) ON DELETE SET NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO crm_quick_replies (company_id, title, body, is_active, created_at, updated_at)
SELECT id, 'Confirmar cita', 'Hola, te escribimos para confirmar tu cita. Por favor indicanos si podras asistir.', 1, NOW(), NOW()
FROM companies c
WHERE NOT EXISTS (
    SELECT 1 FROM crm_quick_replies r WHERE r.company_id = c.id AND r.title = 'Confirmar cita'
);

INSERT INTO crm_quick_replies (company_id, title, body, is_active, created_at, updated_at)
SELECT id, 'Enviar ubicacion', 'Te compartimos la ubicacion de nuestra sucursal. Si necesitas ayuda para llegar, escribenos por este medio.', 1, NOW(), NOW()
FROM companies c
WHERE NOT EXISTS (
    SELECT 1 FROM crm_quick_replies r WHERE r.company_id = c.id AND r.title = 'Enviar ubicacion'
);

INSERT INTO crm_quick_replies (company_id, title, body, is_active, created_at, updated_at)
SELECT id, 'Reprogramar', 'Podemos ayudarte a reprogramar tu cita. Indicanos que fecha y horario te queda mejor.', 1, NOW(), NOW()
FROM companies c
WHERE NOT EXISTS (
    SELECT 1 FROM crm_quick_replies r WHERE r.company_id = c.id AND r.title = 'Reprogramar'
);

UPDATE company_plans
SET features = '{"trial_days":3,"blocked_after_trial":true,"modules":["inicio","agenda","clientes","historia_clinica","servicios","caja","facturacion","deudas","reportes","sucursales"],"limits":{"branches":1,"users":2,"clients":50,"products":50,"appointments_per_month":100},"notes":["Demo completo por 3 dias","Despues del demo queda bloqueado hasta activacion"]}',
    updated_at = NOW()
WHERE slug = 'free';
