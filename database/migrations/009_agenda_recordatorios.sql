-- FeActiva Iglesia SaaS
-- Migration: 009_agenda_recordatorios
-- Scope: Agenda reminders and agent action confirmation flag.

INSERT INTO saas_modules (code, name, description, module_group, is_core, is_active, sort_order)
VALUES ('agenda', 'Agenda y recordatorios', 'Recordatorios personales y ministeriales.', 'operativo', 0, 1, 40)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    module_group = VALUES(module_group),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order);

CREATE TABLE IF NOT EXISTS agenda_recordatorios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    persona_id BIGINT UNSIGNED NULL,
    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,
    fecha_hora DATETIME NOT NULL,
    estado ENUM('pendiente','completado','cancelado') NOT NULL DEFAULT 'pendiente',
    modulo_origen VARCHAR(80) NULL,
    referencia_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_agenda_recordatorios_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_agenda_recordatorios_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_agenda_recordatorios_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,
    INDEX idx_agenda_recordatorios_tenant_id (tenant_id),
    INDEX idx_agenda_recordatorios_user_id (user_id),
    INDEX idx_agenda_recordatorios_persona_id (persona_id),
    INDEX idx_agenda_recordatorios_fecha (tenant_id, fecha_hora),
    INDEX idx_agenda_recordatorios_estado (tenant_id, estado),
    INDEX idx_agenda_recordatorios_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE agent_actions
    ADD COLUMN IF NOT EXISTS requires_confirmation TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT m.id, p.code, p.name, p.description
FROM saas_modules m
INNER JOIN (
    SELECT 'agenda.recordatorios.ver' AS code, 'Ver recordatorios' AS name, 'Permite ver recordatorios de agenda' AS description
    UNION ALL SELECT 'agenda.recordatorios.crear', 'Crear recordatorios', 'Permite crear recordatorios de agenda'
    UNION ALL SELECT 'agenda.recordatorios.editar', 'Editar recordatorios', 'Permite editar recordatorios de agenda'
    UNION ALL SELECT 'agenda.recordatorios.completar', 'Completar recordatorios', 'Permite completar recordatorios de agenda'
) p
WHERE m.code = 'agenda'
ON DUPLICATE KEY UPDATE
    module_id = VALUES(module_id),
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO saas_tenant_modules (tenant_id, module_id, is_enabled, enabled_at)
SELECT 1, m.id, 1, UTC_TIMESTAMP()
FROM saas_modules m
WHERE m.code = 'agenda'
ON DUPLICATE KEY UPDATE
    is_enabled = VALUES(is_enabled),
    enabled_at = VALUES(enabled_at),
    disabled_at = NULL;

INSERT IGNORE INTO auth_role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM auth_roles r
INNER JOIN auth_permissions p
    ON p.code IN (
        'agenda.recordatorios.ver',
        'agenda.recordatorios.crear',
        'agenda.recordatorios.editar',
        'agenda.recordatorios.completar'
    )
WHERE r.code = 'admin_iglesia'
  AND r.tenant_id IS NULL
  AND r.deleted_at IS NULL;
