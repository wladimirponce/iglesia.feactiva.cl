-- FeActiva Iglesia SaaS
-- Migration: 013_ensure_wa_messages_columns
-- Scope: Ensure all wa_messages columns added by 012 migrations are present.
--        Safe to run even if columns already exist (ADD COLUMN IF NOT EXISTS).

ALTER TABLE wa_messages
    ADD COLUMN IF NOT EXISTS media_url TEXT NULL AFTER body,
    ADD COLUMN IF NOT EXISTS transcription_text TEXT NULL AFTER media_url,
    ADD COLUMN IF NOT EXISTS transcription_status
        ENUM('not_required','pending','completed','failed')
        NOT NULL DEFAULT 'not_required' AFTER transcription_text,
    ADD COLUMN IF NOT EXISTS response_mode
        ENUM('text','audio')
        NOT NULL DEFAULT 'text' AFTER transcription_status,
    ADD COLUMN IF NOT EXISTS audio_response_url TEXT NULL AFTER response_mode;

CREATE INDEX IF NOT EXISTS idx_wa_messages_response_mode
    ON wa_messages (tenant_id, response_mode);

CREATE INDEX IF NOT EXISTS idx_wa_messages_type
    ON wa_messages (tenant_id, message_type);
