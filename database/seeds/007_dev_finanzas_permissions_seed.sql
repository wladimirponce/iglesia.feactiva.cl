-- FeActiva Iglesia SaaS
-- Seed: 007_dev_finanzas_permissions_seed
-- Scope: Development finance permissions for tenant_id = 1 only.
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
WHERE m.code = 'finanzas'
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
    SELECT 'fin.cuentas.ver' AS code, 'Ver cuentas financieras' AS name, 'Permite ver cuentas financieras' AS description
    UNION ALL SELECT 'fin.cuentas.crear', 'Crear cuentas financieras', 'Permite crear cuentas financieras'
    UNION ALL SELECT 'fin.cuentas.editar', 'Editar cuentas financieras', 'Permite editar cuentas financieras'
    UNION ALL SELECT 'fin.cuentas.eliminar', 'Eliminar cuentas financieras', 'Permite eliminar logicamente cuentas financieras'
    UNION ALL SELECT 'fin.categorias.ver', 'Ver categorias financieras', 'Permite ver categorias financieras'
    UNION ALL SELECT 'fin.categorias.crear', 'Crear categorias financieras', 'Permite crear categorias financieras'
    UNION ALL SELECT 'fin.categorias.editar', 'Editar categorias financieras', 'Permite editar categorias financieras'
    UNION ALL SELECT 'fin.categorias.eliminar', 'Eliminar categorias financieras', 'Permite eliminar categorias financieras'
    UNION ALL SELECT 'fin.centros_costo.ver', 'Ver centros de costo', 'Permite ver centros de costo'
    UNION ALL SELECT 'fin.centros_costo.crear', 'Crear centros de costo', 'Permite crear centros de costo'
    UNION ALL SELECT 'fin.centros_costo.editar', 'Editar centros de costo', 'Permite editar centros de costo'
    UNION ALL SELECT 'fin.centros_costo.eliminar', 'Eliminar centros de costo', 'Permite eliminar centros de costo'
    UNION ALL SELECT 'fin.movimientos.ver', 'Ver movimientos financieros', 'Permite ver movimientos financieros'
    UNION ALL SELECT 'fin.movimientos.crear', 'Crear movimientos financieros', 'Permite crear movimientos financieros'
    UNION ALL SELECT 'fin.movimientos.editar', 'Editar movimientos financieros', 'Permite editar movimientos financieros'
    UNION ALL SELECT 'fin.movimientos.anular', 'Anular movimientos financieros', 'Permite anular movimientos financieros'
    UNION ALL SELECT 'fin.documentos.ver', 'Ver documentos financieros', 'Permite ver documentos financieros'
    UNION ALL SELECT 'fin.documentos.crear', 'Crear documentos financieros', 'Permite crear documentos financieros'
    UNION ALL SELECT 'fin.documentos.eliminar', 'Eliminar documentos financieros', 'Permite eliminar documentos financieros'
    UNION ALL SELECT 'fin.reportes.ver', 'Ver reportes financieros', 'Permite ver reportes financieros'
    UNION ALL SELECT 'fin.presupuestos.ver', 'Ver presupuestos', 'Permite ver presupuestos'
    UNION ALL SELECT 'fin.presupuestos.crear', 'Crear presupuestos', 'Permite crear presupuestos'
    UNION ALL SELECT 'fin.presupuestos.editar', 'Editar presupuestos', 'Permite editar presupuestos'
    UNION ALL SELECT 'fin.presupuestos.eliminar', 'Eliminar presupuestos', 'Permite eliminar presupuestos'
) permissions
WHERE m.code = 'finanzas'
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
        'fin.cuentas.ver',
        'fin.cuentas.crear',
        'fin.cuentas.editar',
        'fin.cuentas.eliminar',
        'fin.categorias.ver',
        'fin.categorias.crear',
        'fin.categorias.editar',
        'fin.categorias.eliminar',
        'fin.centros_costo.ver',
        'fin.centros_costo.crear',
        'fin.centros_costo.editar',
        'fin.centros_costo.eliminar',
        'fin.movimientos.ver',
        'fin.movimientos.crear',
        'fin.movimientos.editar',
        'fin.movimientos.anular',
        'fin.documentos.ver',
        'fin.documentos.crear',
        'fin.documentos.eliminar',
        'fin.reportes.ver',
        'fin.presupuestos.ver',
        'fin.presupuestos.crear',
        'fin.presupuestos.editar',
        'fin.presupuestos.eliminar'
    )
WHERE r.code = 'admin_iglesia'
  AND r.tenant_id IS NULL
  AND r.deleted_at IS NULL;

COMMIT;
