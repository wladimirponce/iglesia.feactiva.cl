-- FeActiva Iglesia SaaS
-- Migration: 008_agent_tools
-- Scope: Agent tool execution audit compatibility.

ALTER TABLE agent_actions
    MODIFY status ENUM('success','failed','blocked','pending','approved','executed','rejected') NOT NULL DEFAULT 'pending';

ALTER TABLE agent_actions
    ADD COLUMN IF NOT EXISTS action_name VARCHAR(120) NULL AFTER actor_user_id,
    ADD COLUMN IF NOT EXISTS module_code VARCHAR(80) NULL AFTER action_name,
    ADD COLUMN IF NOT EXISTS input_json JSON NULL AFTER module_code,
    ADD COLUMN IF NOT EXISTS output_json JSON NULL AFTER input_json;

CREATE INDEX IF NOT EXISTS idx_agent_actions_action_name ON agent_actions (tenant_id, action_name);
CREATE INDEX IF NOT EXISTS idx_agent_actions_module_code ON agent_actions (tenant_id, module_code);
