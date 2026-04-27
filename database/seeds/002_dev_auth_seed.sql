-- FeActiva Iglesia SaaS
-- Seed: 002_dev_auth_seed
-- Scope: Development auth data only.
-- WARNING: Do not run in production.

START TRANSACTION;

INSERT INTO saas_tenants (
    plan_id,
    name,
    legal_name,
    tax_id,
    country_code,
    currency_code,
    timezone,
    email,
    status
)
SELECT
    p.id,
    'Iglesia Demo',
    'Iglesia Demo',
    'DEMO-DEV',
    'CL',
    'CLP',
    'America/Santiago',
    'admin@demo.test',
    'trial'
FROM saas_plans p
WHERE p.code = 'basic'
  AND NOT EXISTS (
      SELECT 1
      FROM saas_tenants t
      WHERE t.name = 'Iglesia Demo'
        AND t.tax_id = 'DEMO-DEV'
        AND t.deleted_at IS NULL
  )
LIMIT 1;

INSERT INTO auth_users (
    name,
    email,
    password_hash,
    phone,
    is_active,
    email_verified_at
) VALUES (
    'Admin Demo',
    'admin@demo.test',
    '$2y$10$.hkZ69sxpqs77Xx4S1SIgeOV/c12Kk6r/eK9vRhsfoTsw3I9/VUR6',
    '+56900000000',
    1,
    UTC_TIMESTAMP()
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password_hash = VALUES(password_hash),
    phone = VALUES(phone),
    is_active = VALUES(is_active),
    email_verified_at = VALUES(email_verified_at),
    deleted_at = NULL;

INSERT INTO auth_user_tenants (
    user_id,
    tenant_id,
    status,
    accepted_at
)
SELECT
    u.id,
    t.id,
    'active',
    UTC_TIMESTAMP()
FROM auth_users u
INNER JOIN saas_tenants t
    ON t.name = 'Iglesia Demo'
    AND t.tax_id = 'DEMO-DEV'
    AND t.deleted_at IS NULL
WHERE u.email = 'admin@demo.test'
ON DUPLICATE KEY UPDATE
    status = VALUES(status),
    accepted_at = VALUES(accepted_at);

INSERT INTO auth_roles (
    tenant_id,
    code,
    name,
    description,
    is_system
)
SELECT
    NULL,
    'admin_iglesia',
    'Administrador de Iglesia',
    'Administrador principal de una iglesia.',
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM auth_roles r
    WHERE r.tenant_id IS NULL
      AND r.code = 'admin_iglesia'
      AND r.deleted_at IS NULL
);

INSERT IGNORE INTO auth_user_roles (
    user_id,
    tenant_id,
    role_id
)
SELECT
    u.id,
    t.id,
    r.id
FROM auth_users u
INNER JOIN saas_tenants t
    ON t.name = 'Iglesia Demo'
    AND t.tax_id = 'DEMO-DEV'
    AND t.deleted_at IS NULL
INNER JOIN auth_roles r
    ON r.code = 'admin_iglesia'
    AND r.tenant_id IS NULL
    AND r.deleted_at IS NULL
WHERE u.email = 'admin@demo.test'
ORDER BY r.id ASC
LIMIT 1;

COMMIT;
