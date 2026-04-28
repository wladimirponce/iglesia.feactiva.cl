-- FeActiva Iglesia SaaS
-- Migration: 012_agenda_multichannel_secretary
-- Scope: Agenda multicanal, Google Calendar placeholder y media WhatsApp.

ALTER TABLE agenda_notifications
    MODIFY recipient_type ENUM('user','persona','phone','email') NOT NULL;

ALTER TABLE agenda_notifications
    ADD COLUMN IF NOT EXISTS recipient_email VARCHAR(180) NULL AFTER recipient_phone,
    ADD COLUMN IF NOT EXISTS provider VARCHAR(80) NULL AFTER channel,
    ADD COLUMN IF NOT EXISTS provider_message_id VARCHAR(160) NULL AFTER provider,
    ADD COLUMN IF NOT EXISTS requires_credentials TINYINT(1) NOT NULL DEFAULT 1 AFTER last_error,
    ADD COLUMN IF NOT EXISTS ready_for_delivery_at DATETIME NULL AFTER requires_credentials;

CREATE INDEX IF NOT EXISTS idx_agenda_notifications_email ON agenda_notifications (tenant_id, recipient_email);
CREATE INDEX IF NOT EXISTS idx_agenda_notifications_provider ON agenda_notifications (tenant_id, provider, provider_message_id);

CREATE TABLE IF NOT EXISTS google_calendar_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    provider_account_id VARCHAR(180) NULL,
    email VARCHAR(180) NOT NULL,
    display_name VARCHAR(180) NULL,
    calendar_id VARCHAR(180) NULL,
    access_token_encrypted TEXT NULL,
    refresh_token_encrypted TEXT NULL,
    token_expires_at DATETIME NULL,
    status ENUM('pending_oauth','active','revoked','error') NOT NULL DEFAULT 'pending_oauth',
    last_sync_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_google_calendar_accounts_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_google_calendar_accounts_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    INDEX idx_google_calendar_accounts_tenant (tenant_id, status),
    INDEX idx_google_calendar_accounts_user (tenant_id, user_id),
    INDEX idx_google_calendar_accounts_email (tenant_id, email),
    INDEX idx_google_calendar_accounts_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS google_calendar_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    agenda_item_id BIGINT UNSIGNED NOT NULL,
    google_calendar_account_id BIGINT UNSIGNED NULL,
    google_calendar_id VARCHAR(180) NULL,
    google_event_id VARCHAR(180) NULL,
    sync_status ENUM('pending','synced','failed','cancelled') NOT NULL DEFAULT 'pending',
    last_sync_error TEXT NULL,
    synced_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_google_calendar_events_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_google_calendar_events_item
        FOREIGN KEY (agenda_item_id) REFERENCES agenda_items(id),
    CONSTRAINT fk_google_calendar_events_account
        FOREIGN KEY (google_calendar_account_id) REFERENCES google_calendar_accounts(id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_google_calendar_event (tenant_id, google_calendar_account_id, google_event_id),
    INDEX idx_google_calendar_events_item (agenda_item_id),
    INDEX idx_google_calendar_events_status (tenant_id, sync_status),
    INDEX idx_google_calendar_events_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE wa_messages
    ADD COLUMN IF NOT EXISTS media_url VARCHAR(500) NULL AFTER body,
    ADD COLUMN IF NOT EXISTS transcription_text TEXT NULL AFTER media_url,
    ADD COLUMN IF NOT EXISTS transcription_status ENUM('not_required','pending','completed','failed') NOT NULL DEFAULT 'not_required' AFTER transcription_text,
    ADD COLUMN IF NOT EXISTS response_mode ENUM('text','audio') NOT NULL DEFAULT 'text' AFTER transcription_status;

CREATE INDEX IF NOT EXISTS idx_wa_messages_type ON wa_messages (tenant_id, message_type);
CREATE INDEX IF NOT EXISTS idx_wa_messages_response_mode ON wa_messages (tenant_id, response_mode);

CREATE TABLE IF NOT EXISTS agenda_provider_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    agenda_item_id BIGINT UNSIGNED NULL,
    agenda_notification_id BIGINT UNSIGNED NULL,
    provider VARCHAR(80) NOT NULL,
    event_type VARCHAR(120) NOT NULL,
    event_description VARCHAR(255) NOT NULL,
    request_json JSON NULL,
    response_json JSON NULL,
    result ENUM('success','failed','skipped') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_agenda_provider_audit_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agenda_provider_audit_item
        FOREIGN KEY (agenda_item_id) REFERENCES agenda_items(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agenda_provider_audit_notification
        FOREIGN KEY (agenda_notification_id) REFERENCES agenda_notifications(id)
        ON DELETE SET NULL,
    INDEX idx_agenda_provider_audit_tenant (tenant_id, created_at),
    INDEX idx_agenda_provider_audit_provider (tenant_id, provider),
    INDEX idx_agenda_provider_audit_event (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
