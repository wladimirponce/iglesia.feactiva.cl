-- FeActiva Iglesia SaaS
-- Migration: 010_agent_sql_skills
-- Scope: Read-only SQL Skill Studio for the agent.

ALTER TABLE agent_audit_logs
    MODIFY result ENUM('success','failed','denied','blocked') NOT NULL;

CREATE TABLE IF NOT EXISTS agent_sql_skills (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    module_code VARCHAR(80) NOT NULL,
    required_permission VARCHAR(120) NOT NULL,
    sql_template TEXT NOT NULL,
    parameters_json JSON NULL,
    status ENUM('pending_approval','approved','rejected','deprecated') NOT NULL DEFAULT 'pending_approval',
    created_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_agent_sql_skills_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agent_sql_skills_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agent_sql_skills_approved_by
        FOREIGN KEY (approved_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    INDEX idx_agent_sql_skills_tenant_status (tenant_id, status),
    INDEX idx_agent_sql_skills_module (tenant_id, module_code),
    INDEX idx_agent_sql_skills_permission (tenant_id, required_permission),
    INDEX idx_agent_sql_skills_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agent_sql_skill_executions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    skill_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    parameters_json JSON NULL,
    result_summary_json JSON NULL,
    status ENUM('success','failed','blocked') NOT NULL,
    error_code VARCHAR(120) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_agent_sql_skill_executions_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agent_sql_skill_executions_skill
        FOREIGN KEY (skill_id) REFERENCES agent_sql_skills(id),
    CONSTRAINT fk_agent_sql_skill_executions_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    INDEX idx_agent_sql_skill_executions_tenant (tenant_id, created_at),
    INDEX idx_agent_sql_skill_executions_skill (skill_id, created_at),
    INDEX idx_agent_sql_skill_executions_status (tenant_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
