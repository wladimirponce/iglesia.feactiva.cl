-- FeActiva Iglesia SaaS
-- Seed: 003_dev_permissions_seed
-- Scope: Development permissions for auth middleware tests only.
-- WARNING: Do not run in production.

START TRANSACTION;

INSERT INTO auth_permissions (
    module_id,
    code,
    name,
    description
)
SELECT
    m.id,
    'auth.usuarios.ver',
    'Ver usuarios',
    'Permite ver usuarios del tenant.'
FROM saas_modules m
WHERE m.code = 'auth'
ON DUPLICATE KEY UPDATE
    module_id = VALUES(module_id),
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO saas_tenant_modules (
    tenant_id,
    module_id,
    is_enabled,
    enabled_at
)
SELECT
    t.id,
    m.id,
    1,
    UTC_TIMESTAMP()
FROM saas_tenants t
INNER JOIN saas_modules m
    ON m.code = 'auth'
WHERE t.name = 'Iglesia Demo'
  AND t.tax_id = 'DEMO-DEV'
  AND t.deleted_at IS NULL
ON DUPLICATE KEY UPDATE
    is_enabled = VALUES(is_enabled),
    enabled_at = VALUES(enabled_at),
    disabled_at = NULL;

INSERT IGNORE INTO auth_role_permissions (
    role_id,
    permission_id
)
SELECT
    r.id,
    p.id
FROM auth_roles r
INNER JOIN auth_permissions p
    ON p.code = 'auth.usuarios.ver'
WHERE r.code = 'admin_iglesia'
  AND r.tenant_id IS NULL
  AND r.deleted_at IS NULL
ORDER BY r.id ASC
LIMIT 1;

COMMIT;
