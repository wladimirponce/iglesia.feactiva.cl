-- FeActiva Iglesia SaaS
-- Migration: 012_agent_conversation_state_and_drafts
-- Scope: Stateful WhatsApp secretary conversations and outbound drafts.

CREATE TABLE IF NOT EXISTS agent_conversation_state (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    phone VARCHAR(30) NOT NULL,
    conversation_id BIGINT UNSIGNED NULL,
    state_key VARCHAR(120) NOT NULL,
    state_json JSON NOT NULL,
    status ENUM('active','completed','cancelled','expired') NOT NULL DEFAULT 'active',
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_agent_conversation_state_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agent_conversation_state_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agent_conversation_state_conversation
        FOREIGN KEY (conversation_id) REFERENCES wa_conversations(id)
        ON DELETE SET NULL,
    INDEX idx_agent_conversation_state_phone (phone, status),
    INDEX idx_agent_conversation_state_tenant (tenant_id, status),
    INDEX idx_agent_conversation_state_expires (status, expires_at),
    INDEX idx_agent_conversation_state_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS outbound_message_drafts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    conversation_id BIGINT UNSIGNED NULL,
    recipient_phone VARCHAR(30) NULL,
    recipient_persona_id BIGINT UNSIGNED NULL,
    channel ENUM('whatsapp','email') NOT NULL DEFAULT 'whatsapp',
    original_text TEXT NOT NULL,
    draft_text TEXT NOT NULL,
    improved_text TEXT NULL,
    status ENUM('draft','waiting_confirmation','approved','sent','cancelled') NOT NULL DEFAULT 'draft',
    approved_at DATETIME NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_outbound_message_drafts_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_outbound_message_drafts_user
        FOREIGN KEY (created_by_user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_outbound_message_drafts_conversation
        FOREIGN KEY (conversation_id) REFERENCES wa_conversations(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_outbound_message_drafts_persona
        FOREIGN KEY (recipient_persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,
    INDEX idx_outbound_message_drafts_tenant (tenant_id, status),
    INDEX idx_outbound_message_drafts_conversation (conversation_id, status),
    INDEX idx_outbound_message_drafts_recipient_phone (recipient_phone),
    INDEX idx_outbound_message_drafts_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
