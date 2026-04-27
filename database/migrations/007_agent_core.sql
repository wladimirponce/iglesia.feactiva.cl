-- FeActiva Iglesia SaaS
-- Migration: 007_agent_core
-- Scope: Agent request processing core.

SET @db_name = DATABASE();

SET @column_exists = (
    SELECT COUNT(1)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'agent_requests'
      AND COLUMN_NAME = 'source'
);
SET @sql = IF(@column_exists = 0, 'ALTER TABLE agent_requests ADD COLUMN source VARCHAR(50) NULL AFTER user_id', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(1)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'agent_requests'
      AND COLUMN_NAME = 'input_text'
);
SET @sql = IF(@column_exists = 0, 'ALTER TABLE agent_requests ADD COLUMN input_text TEXT NULL AFTER input_payload', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(1)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'agent_requests'
      AND COLUMN_NAME = 'normalized_intent'
);
SET @sql = IF(@column_exists = 0, 'ALTER TABLE agent_requests ADD COLUMN normalized_intent VARCHAR(80) NULL AFTER input_text', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(1)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'agent_requests'
      AND INDEX_NAME = 'idx_agent_requests_source'
);
SET @sql = IF(@index_exists = 0, 'ALTER TABLE agent_requests ADD INDEX idx_agent_requests_source (tenant_id, source)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(1)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'agent_requests'
      AND INDEX_NAME = 'idx_agent_requests_intent'
);
SET @sql = IF(@index_exists = 0, 'ALTER TABLE agent_requests ADD INDEX idx_agent_requests_intent (tenant_id, normalized_intent)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(1)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'agent_responses'
      AND COLUMN_NAME = 'response_text'
);
SET @sql = IF(@column_exists = 0, 'ALTER TABLE agent_responses ADD COLUMN response_text TEXT NULL AFTER output_payload', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
