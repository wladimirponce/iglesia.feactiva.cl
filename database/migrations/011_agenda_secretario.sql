-- FeActiva Iglesia SaaS
-- Migration: 011_agenda_secretario
-- Scope: Agenda operativa y agente secretario.

INSERT INTO saas_modules (code, name, description, module_group, is_core, is_active, sort_order)
VALUES ('agenda', 'Agenda', 'Agenda, tareas, llamadas, reuniones y notificaciones programadas.', 'operativo', 0, 1, 40)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    is_active = 1,
    sort_order = VALUES(sort_order);

CREATE TABLE IF NOT EXISTS agenda_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    assigned_to_user_id BIGINT UNSIGNED NULL,
    persona_id BIGINT UNSIGNED NULL,
    familia_id BIGINT UNSIGNED NULL,
    tipo ENUM('reminder','call','meeting','whatsapp_send','task','followup') NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NULL,
    estado ENUM('pending','completed','cancelled','expired') NOT NULL DEFAULT 'pending',
    prioridad ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    modulo_origen VARCHAR(80) NULL,
    referencia_id BIGINT UNSIGNED NULL,
    requires_confirmation TINYINT(1) NOT NULL DEFAULT 0,
    confirmed_at DATETIME NULL,
    completed_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_agenda_items_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agenda_items_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agenda_items_assigned_to
        FOREIGN KEY (assigned_to_user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agenda_items_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agenda_items_familia
        FOREIGN KEY (familia_id) REFERENCES crm_familias(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agenda_items_deleted_by
        FOREIGN KEY (deleted_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    INDEX idx_agenda_items_tenant_fecha (tenant_id, fecha_inicio),
    INDEX idx_agenda_items_estado (tenant_id, estado),
    INDEX idx_agenda_items_persona (tenant_id, persona_id),
    INDEX idx_agenda_items_familia (tenant_id, familia_id),
    INDEX idx_agenda_items_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agenda_participants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    agenda_item_id BIGINT UNSIGNED NOT NULL,
    persona_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(180) NULL,
    rol ENUM('organizer','participant','recipient') NOT NULL DEFAULT 'participant',
    estado ENUM('pending','confirmed','declined','notified') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_agenda_participants_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agenda_participants_item
        FOREIGN KEY (agenda_item_id) REFERENCES agenda_items(id),
    CONSTRAINT fk_agenda_participants_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agenda_participants_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    INDEX idx_agenda_participants_item (agenda_item_id),
    INDEX idx_agenda_participants_persona (tenant_id, persona_id),
    INDEX idx_agenda_participants_user (tenant_id, user_id),
    INDEX idx_agenda_participants_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agenda_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    agenda_item_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('whatsapp','email','system') NOT NULL,
    recipient_type ENUM('user','persona','phone') NOT NULL,
    recipient_user_id BIGINT UNSIGNED NULL,
    recipient_persona_id BIGINT UNSIGNED NULL,
    recipient_phone VARCHAR(50) NULL,
    message_text TEXT NOT NULL,
    scheduled_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    status ENUM('scheduled','sent','failed','cancelled') NOT NULL DEFAULT 'scheduled',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_agenda_notifications_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agenda_notifications_item
        FOREIGN KEY (agenda_item_id) REFERENCES agenda_items(id),
    CONSTRAINT fk_agenda_notifications_user
        FOREIGN KEY (recipient_user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agenda_notifications_persona
        FOREIGN KEY (recipient_persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,
    INDEX idx_agenda_notifications_due (tenant_id, status, scheduled_at),
    INDEX idx_agenda_notifications_item (agenda_item_id),
    INDEX idx_agenda_notifications_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agenda_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    agenda_item_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(120) NOT NULL,
    event_description VARCHAR(255) NOT NULL,
    old_values_json JSON NULL,
    new_values_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_agenda_audit_logs_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agenda_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agenda_audit_logs_item
        FOREIGN KEY (agenda_item_id) REFERENCES agenda_items(id)
        ON DELETE SET NULL,
    INDEX idx_agenda_audit_logs_tenant (tenant_id, created_at),
    INDEX idx_agenda_audit_logs_event (event_type),
    INDEX idx_agenda_audit_logs_item (agenda_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
