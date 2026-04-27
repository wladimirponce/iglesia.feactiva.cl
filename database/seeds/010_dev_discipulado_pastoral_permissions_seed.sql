-- FeActiva Iglesia SaaS
-- Seed: 010_dev_discipulado_pastoral_permissions_seed
-- Scope: Development discipulado and pastoral permissions for tenant_id = 1 only.
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
WHERE m.code IN ('discipulado', 'pastoral')
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
    SELECT 'discipulado' AS module_code, 'disc.rutas.ver' AS code, 'Ver rutas de discipulado' AS name, 'Permite ver rutas de discipulado' AS description
    UNION ALL SELECT 'discipulado', 'disc.rutas.crear', 'Crear rutas de discipulado', 'Permite crear rutas de discipulado'
    UNION ALL SELECT 'discipulado', 'disc.rutas.editar', 'Editar rutas de discipulado', 'Permite editar rutas de discipulado'
    UNION ALL SELECT 'discipulado', 'disc.rutas.eliminar', 'Eliminar rutas de discipulado', 'Permite eliminar logicamente rutas de discipulado'
    UNION ALL SELECT 'discipulado', 'disc.avance.ver', 'Ver avance de discipulado', 'Permite ver avance de discipulado'
    UNION ALL SELECT 'discipulado', 'disc.avance.editar', 'Editar avance de discipulado', 'Permite editar avance de discipulado'
    UNION ALL SELECT 'discipulado', 'disc.mentorias.ver', 'Ver mentorias', 'Permite ver mentorias de discipulado'
    UNION ALL SELECT 'discipulado', 'disc.mentorias.crear', 'Crear mentorias', 'Permite registrar mentorias de discipulado'
    UNION ALL SELECT 'discipulado', 'disc.registros.ver', 'Ver registros espirituales', 'Permite ver registros espirituales'
    UNION ALL SELECT 'discipulado', 'disc.registros.crear', 'Crear registros espirituales', 'Permite crear registros espirituales'
    UNION ALL SELECT 'pastoral', 'past.casos.ver', 'Ver casos pastorales', 'Permite ver casos pastorales'
    UNION ALL SELECT 'pastoral', 'past.casos.ver_confidencial', 'Ver casos pastorales confidenciales', 'Permite ver casos pastorales confidenciales'
    UNION ALL SELECT 'pastoral', 'past.casos.crear', 'Crear casos pastorales', 'Permite crear casos pastorales'
    UNION ALL SELECT 'pastoral', 'past.casos.editar', 'Editar casos pastorales', 'Permite editar casos pastorales'
    UNION ALL SELECT 'pastoral', 'past.casos.cerrar', 'Cerrar casos pastorales', 'Permite cerrar casos pastorales'
    UNION ALL SELECT 'pastoral', 'past.sesiones.ver', 'Ver sesiones pastorales', 'Permite ver sesiones pastorales'
    UNION ALL SELECT 'pastoral', 'past.sesiones.crear', 'Crear sesiones pastorales', 'Permite crear sesiones pastorales'
    UNION ALL SELECT 'pastoral', 'past.oracion.ver', 'Ver solicitudes de oracion', 'Permite ver solicitudes de oracion'
    UNION ALL SELECT 'pastoral', 'past.oracion.crear', 'Crear solicitudes de oracion', 'Permite crear solicitudes de oracion'
    UNION ALL SELECT 'pastoral', 'past.oracion.editar', 'Editar solicitudes de oracion', 'Permite editar solicitudes de oracion'
    UNION ALL SELECT 'pastoral', 'past.derivaciones.crear', 'Crear derivaciones pastorales', 'Permite crear derivaciones pastorales'
) permissions
    ON permissions.module_code = m.code
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
        'disc.rutas.ver',
        'disc.rutas.crear',
        'disc.rutas.editar',
        'disc.rutas.eliminar',
        'disc.avance.ver',
        'disc.avance.editar',
        'disc.mentorias.ver',
        'disc.mentorias.crear',
        'disc.registros.ver',
        'disc.registros.crear',
        'past.casos.ver',
        'past.casos.ver_confidencial',
        'past.casos.crear',
        'past.casos.editar',
        'past.casos.cerrar',
        'past.sesiones.ver',
        'past.sesiones.crear',
        'past.oracion.ver',
        'past.oracion.crear',
        'past.oracion.editar',
        'past.derivaciones.crear'
    )
WHERE r.code = 'admin_iglesia'
  AND r.tenant_id IS NULL
  AND r.deleted_at IS NULL;

COMMIT;
