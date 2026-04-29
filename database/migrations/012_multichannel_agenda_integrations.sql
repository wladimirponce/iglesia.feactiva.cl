-- FeActiva Iglesia SaaS
-- Migration: 012_multichannel_agenda_integrations
-- Scope: Agenda multicanal, calendario externo y soporte de audio WhatsApp.

ALTER TABLE agenda_notifications
    MODIFY recipient_type ENUM('user','persona','phone','email') NOT NULL;

ALTER TABLE agenda_notifications
    ADD COLUMN IF NOT EXISTS recipient_email VARCHAR(180) NULL AFTER recipient_phone,
    ADD COLUMN IF NOT EXISTS external_provider VARCHAR(60) NULL AFTER channel,
    ADD COLUMN IF NOT EXISTS external_message_id VARCHAR(180) NULL AFTER external_provider,
    ADD COLUMN IF NOT EXISTS delivery_status ENUM('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending' AFTER status,
    ADD COLUMN IF NOT EXISTS delivery_response_json JSON NULL AFTER delivery_status;

CREATE INDEX IF NOT EXISTS idx_agenda_notifications_external
    ON agenda_notifications (tenant_id, external_provider, external_message_id);
CREATE INDEX IF NOT EXISTS idx_agenda_notifications_delivery
    ON agenda_notifications (tenant_id, delivery_status, scheduled_at);
CREATE INDEX IF NOT EXISTS idx_agenda_notifications_recipient_email
    ON agenda_notifications (tenant_id, recipient_email);

CREATE TABLE IF NOT EXISTS calendar_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    provider ENUM('google') NOT NULL,
    calendar_id VARCHAR(190) NOT NULL DEFAULT 'primary',
    email VARCHAR(190) NOT NULL,
    access_token_encrypted TEXT NULL,
    refresh_token_encrypted TEXT NULL,
    token_expires_at DATETIME NULL,
    status ENUM('active','revoked','expired') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_calendar_accounts_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_calendar_accounts_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    INDEX idx_calendar_accounts_tenant (tenant_id, provider, status),
    INDEX idx_calendar_accounts_user (tenant_id, user_id),
    INDEX idx_calendar_accounts_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendar_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    agenda_item_id BIGINT UNSIGNED NOT NULL,
    calendar_account_id BIGINT UNSIGNED NOT NULL,
    provider ENUM('google') NOT NULL,
    external_event_id VARCHAR(190) NULL,
    sync_status ENUM('pending','synced','failed','cancelled') NOT NULL DEFAULT 'pending',
    last_error TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_calendar_events_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_calendar_events_agenda_item
        FOREIGN KEY (agenda_item_id) REFERENCES agenda_items(id),
    CONSTRAINT fk_calendar_events_account
        FOREIGN KEY (calendar_account_id) REFERENCES calendar_accounts(id),
    INDEX idx_calendar_events_item (tenant_id, agenda_item_id),
    INDEX idx_calendar_events_account (tenant_id, calendar_account_id),
    INDEX idx_calendar_events_status (tenant_id, sync_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE wa_messages
    ADD COLUMN IF NOT EXISTS media_url TEXT NULL AFTER body,
    ADD COLUMN IF NOT EXISTS transcription_text TEXT NULL AFTER media_url,
    ADD COLUMN IF NOT EXISTS transcription_status ENUM('not_required','pending','completed','failed') NOT NULL DEFAULT 'not_required' AFTER transcription_text,
    ADD COLUMN IF NOT EXISTS response_mode ENUM('text','audio') NOT NULL DEFAULT 'text' AFTER transcription_status,
    ADD COLUMN IF NOT EXISTS audio_response_url TEXT NULL AFTER response_mode;

CREATE INDEX IF NOT EXISTS idx_wa_messages_response_mode
    ON wa_messages (tenant_id, response_mode);
