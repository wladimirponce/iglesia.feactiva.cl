-- FeActiva Iglesia SaaS
-- Migration: 006_whatsapp_agent_base
-- Scope: WhatsApp identity and agent base tables.

UPDATE auth_users
SET phone = CONCAT('+1000000', id)
WHERE phone IS NULL
   OR TRIM(phone) = '';

ALTER TABLE auth_users
    MODIFY phone VARCHAR(50) NOT NULL;

CREATE TABLE IF NOT EXISTS wa_conversations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    whatsapp_phone VARCHAR(50) NOT NULL,
    whatsapp_phone_normalized VARCHAR(20) NOT NULL,
    provider VARCHAR(50) NOT NULL DEFAULT 'whatsapp',
    provider_conversation_id VARCHAR(120) NULL,
    status ENUM('open','closed','archived') NOT NULL DEFAULT 'open',
    last_message_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_wa_conversations_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_wa_conversations_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    INDEX idx_wa_conversations_tenant_id (tenant_id),
    INDEX idx_wa_conversations_user_id (user_id),
    INDEX idx_wa_conversations_phone (tenant_id, whatsapp_phone_normalized),
    INDEX idx_wa_conversations_status (tenant_id, status),
    INDEX idx_wa_conversations_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wa_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    conversation_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    direction ENUM('inbound','outbound') NOT NULL,
    message_type ENUM('text','image','audio','video','document','interactive','system') NOT NULL DEFAULT 'text',
    provider_message_id VARCHAR(120) NULL,
    body TEXT NULL,
    payload JSON NULL,
    status ENUM('received','queued','sent','delivered','read','failed') NOT NULL DEFAULT 'received',
    sent_at DATETIME NULL,
    received_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_wa_messages_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_wa_messages_conversation
        FOREIGN KEY (conversation_id) REFERENCES wa_conversations(id),
    CONSTRAINT fk_wa_messages_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    INDEX idx_wa_messages_tenant_id (tenant_id),
    INDEX idx_wa_messages_conversation_id (conversation_id),
    INDEX idx_wa_messages_user_id (user_id),
    INDEX idx_wa_messages_direction (tenant_id, direction),
    INDEX idx_wa_messages_created_at (created_at),
    INDEX idx_wa_messages_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agent_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    conversation_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    request_type VARCHAR(80) NOT NULL,
    input_payload JSON NOT NULL,
    status ENUM('received','processing','completed','failed','cancelled') NOT NULL DEFAULT 'received',
    idempotency_key VARCHAR(120) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_agent_requests_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agent_requests_conversation
        FOREIGN KEY (conversation_id) REFERENCES wa_conversations(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agent_requests_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_agent_requests_idempotency (tenant_id, idempotency_key),
    INDEX idx_agent_requests_tenant_id (tenant_id),
    INDEX idx_agent_requests_conversation_id (conversation_id),
    INDEX idx_agent_requests_user_id (user_id),
    INDEX idx_agent_requests_status (tenant_id, status),
    INDEX idx_agent_requests_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agent_responses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    request_id BIGINT UNSIGNED NOT NULL,
    conversation_id BIGINT UNSIGNED NULL,
    output_payload JSON NOT NULL,
    status ENUM('created','sent','failed') NOT NULL DEFAULT 'created',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_agent_responses_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agent_responses_request
        FOREIGN KEY (request_id) REFERENCES agent_requests(id),
    CONSTRAINT fk_agent_responses_conversation
        FOREIGN KEY (conversation_id) REFERENCES wa_conversations(id)
        ON DELETE SET NULL,
    INDEX idx_agent_responses_tenant_id (tenant_id),
    INDEX idx_agent_responses_request_id (request_id),
    INDEX idx_agent_responses_conversation_id (conversation_id),
    INDEX idx_agent_responses_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agent_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    request_id BIGINT UNSIGNED NULL,
    response_id BIGINT UNSIGNED NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    action_code VARCHAR(120) NOT NULL,
    target_table VARCHAR(120) NULL,
    target_id BIGINT UNSIGNED NULL,
    input_payload JSON NULL,
    result_payload JSON NULL,
    status ENUM('pending','approved','executed','rejected','failed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    executed_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_agent_actions_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agent_actions_request
        FOREIGN KEY (request_id) REFERENCES agent_requests(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agent_actions_response
        FOREIGN KEY (response_id) REFERENCES agent_responses(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agent_actions_actor
        FOREIGN KEY (actor_user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    INDEX idx_agent_actions_tenant_id (tenant_id),
    INDEX idx_agent_actions_request_id (request_id),
    INDEX idx_agent_actions_response_id (response_id),
    INDEX idx_agent_actions_actor_user_id (actor_user_id),
    INDEX idx_agent_actions_status (tenant_id, status),
    INDEX idx_agent_actions_target (target_table, target_id),
    INDEX idx_agent_actions_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agent_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    request_id BIGINT UNSIGNED NULL,
    conversation_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(120) NOT NULL,
    event_description VARCHAR(255) NOT NULL,
    action VARCHAR(120) NOT NULL,
    result ENUM('success','failed','denied') NOT NULL,
    subject_type VARCHAR(80) NULL,
    subject_id BIGINT UNSIGNED NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_agent_audit_logs_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agent_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agent_audit_logs_request
        FOREIGN KEY (request_id) REFERENCES agent_requests(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agent_audit_logs_conversation
        FOREIGN KEY (conversation_id) REFERENCES wa_conversations(id)
        ON DELETE SET NULL,
    INDEX idx_agent_audit_logs_tenant_id (tenant_id),
    INDEX idx_agent_audit_logs_user_id (user_id),
    INDEX idx_agent_audit_logs_event_type (event_type),
    INDEX idx_agent_audit_logs_action (tenant_id, action),
    INDEX idx_agent_audit_logs_subject (subject_type, subject_id),
    INDEX idx_agent_audit_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
