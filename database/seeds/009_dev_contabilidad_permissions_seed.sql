-- FeActiva Iglesia SaaS
-- Seed: 009_dev_contabilidad_permissions_seed
-- Scope: Development accounting permissions for tenant_id = 1 only.
-- WARNING: Do not run in production.

START TRANSACTION;

INSERT INTO saas_tenant_modules (
    tenant_id,
    module_id,
    is_enabled,
    enabled_at
)
SELECT
    1,
    m.id,
    1,
    UTC_TIMESTAMP()
FROM saas_modules m
WHERE m.code = 'contabilidad'
ON DUPLICATE KEY UPDATE
    is_enabled = VALUES(is_enabled),
    enabled_at = VALUES(enabled_at),
    disabled_at = NULL;

INSERT INTO auth_permissions (
    module_id,
    code,
    name,
    description
)
SELECT
    m.id,
    permissions.code,
    permissions.name,
    permissions.description
FROM saas_modules m
INNER JOIN (
    SELECT 'acct.configuracion.ver' AS code, 'Ver configuracion contable' AS name, 'Permite ver configuracion contable' AS description
    UNION ALL SELECT 'acct.configuracion.editar', 'Editar configuracion contable', 'Permite editar configuracion contable'
    UNION ALL SELECT 'acct.cuentas.ver', 'Ver cuentas contables', 'Permite ver el plan de cuentas contable'
    UNION ALL SELECT 'acct.cuentas.crear', 'Crear cuentas contables', 'Permite crear cuentas contables'
    UNION ALL SELECT 'acct.cuentas.editar', 'Editar cuentas contables', 'Permite editar cuentas contables'
    UNION ALL SELECT 'acct.periodos.ver', 'Ver periodos contables', 'Permite ver periodos contables'
    UNION ALL SELECT 'acct.periodos.crear', 'Crear periodos contables', 'Permite crear periodos contables'
    UNION ALL SELECT 'acct.periodos.cerrar', 'Cerrar periodos contables', 'Permite cerrar periodos contables'
    UNION ALL SELECT 'acct.asientos.ver', 'Ver asientos contables', 'Permite ver asientos contables'
    UNION ALL SELECT 'acct.asientos.crear', 'Crear asientos contables', 'Permite crear asientos contables'
    UNION ALL SELECT 'acct.asientos.aprobar', 'Aprobar asientos contables', 'Permite aprobar asientos contables'
    UNION ALL SELECT 'acct.asientos.anular', 'Anular asientos contables', 'Permite anular asientos contables'
    UNION ALL SELECT 'acct.asientos.reversar', 'Reversar asientos contables', 'Permite reversar asientos contables'
    UNION ALL SELECT 'acct.reportes.ver', 'Ver reportes contables', 'Permite ver reportes contables'
    UNION ALL SELECT 'acct.mapeo.ver', 'Ver mapeo finanzas-contabilidad', 'Permite ver mapeos finanzas-contabilidad'
    UNION ALL SELECT 'acct.mapeo.crear', 'Crear mapeo finanzas-contabilidad', 'Permite crear mapeos finanzas-contabilidad'
    UNION ALL SELECT 'acct.mapeo.editar', 'Editar mapeo finanzas-contabilidad', 'Permite editar mapeos finanzas-contabilidad'
) permissions
WHERE m.code = 'contabilidad'
ON DUPLICATE KEY UPDATE
    module_id = VALUES(module_id),
    name = VALUES(name),
    description = VALUES(description);

INSERT IGNORE INTO auth_role_permissions (
    role_id,
    permission_id
)
SELECT
    r.id,
    p.id
FROM auth_roles r
INNER JOIN auth_permissions p
    ON p.code IN (
        'acct.configuracion.ver',
        'acct.configuracion.editar',
        'acct.cuentas.ver',
        'acct.cuentas.crear',
        'acct.cuentas.editar',
        'acct.periodos.ver',
        'acct.periodos.crear',
        'acct.periodos.cerrar',
        'acct.asientos.ver',
        'acct.asientos.crear',
        'acct.asientos.aprobar',
        'acct.asientos.anular',
        'acct.asientos.reversar',
        'acct.reportes.ver',
        'acct.mapeo.ver',
        'acct.mapeo.crear',
        'acct.mapeo.editar'
    )
WHERE r.code = 'admin_iglesia'
  AND r.tenant_id IS NULL
  AND r.deleted_at IS NULL;

COMMIT;
