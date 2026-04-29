-- FeActiva Iglesia SaaS
-- Migration: 013_google_oauth_accounts
-- Scope: OAuth inicial para Google Calendar/Gmail.

CREATE TABLE IF NOT EXISTS google_oauth_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    provider ENUM('google') NOT NULL DEFAULT 'google',
    email VARCHAR(190) NULL,
    scopes_json JSON NOT NULL,
    access_token_encrypted TEXT NULL,
    refresh_token_encrypted TEXT NULL,
    token_expires_at DATETIME NULL,
    status ENUM('active','revoked','expired','error') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_google_oauth_accounts_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_google_oauth_accounts_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id),
    INDEX idx_google_oauth_accounts_user (tenant_id, user_id, provider, status),
    INDEX idx_google_oauth_accounts_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
