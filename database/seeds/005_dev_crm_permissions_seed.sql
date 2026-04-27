-- FeActiva Iglesia SaaS
-- Seed: 005_dev_crm_permissions_seed
-- Scope: Development CRM permissions for tenant_id = 1 only.
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
WHERE m.code = 'crm'
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
    SELECT 'crm.personas.ver' AS code, 'Ver personas' AS name, 'Permite ver personas del CRM' AS description
    UNION ALL SELECT 'crm.personas.crear', 'Crear personas', 'Permite crear personas'
    UNION ALL SELECT 'crm.personas.editar', 'Editar personas', 'Permite editar personas'
    UNION ALL SELECT 'crm.personas.eliminar', 'Eliminar personas', 'Permite eliminar logicamente personas'
    UNION ALL SELECT 'crm.contactos.ver', 'Ver contactos CRM', 'Permite ver historial de contactos'
    UNION ALL SELECT 'crm.contactos.crear', 'Crear contactos CRM', 'Permite crear historial de contactos'
    UNION ALL SELECT 'crm.etiquetas.ver', 'Ver etiquetas CRM', 'Permite ver etiquetas'
    UNION ALL SELECT 'crm.etiquetas.crear', 'Crear etiquetas CRM', 'Permite crear etiquetas'
    UNION ALL SELECT 'crm.etiquetas.editar', 'Editar etiquetas CRM', 'Permite editar etiquetas y asignarlas'
    UNION ALL SELECT 'crm.etiquetas.eliminar', 'Eliminar etiquetas CRM', 'Permite eliminar etiquetas'
    UNION ALL SELECT 'crm.familias.ver', 'Ver familias CRM', 'Permite ver familias'
    UNION ALL SELECT 'crm.familias.crear', 'Crear familias CRM', 'Permite crear familias'
    UNION ALL SELECT 'crm.familias.editar', 'Editar familias CRM', 'Permite editar familias y relaciones familiares'
) permissions
WHERE m.code = 'crm'
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
        'crm.personas.ver',
        'crm.personas.crear',
        'crm.personas.editar',
        'crm.personas.eliminar',
        'crm.contactos.ver',
        'crm.contactos.crear',
        'crm.etiquetas.ver',
        'crm.etiquetas.crear',
        'crm.etiquetas.editar',
        'crm.etiquetas.eliminar',
        'crm.familias.ver',
        'crm.familias.crear',
        'crm.familias.editar'
    )
WHERE r.code = 'admin_iglesia'
  AND r.tenant_id IS NULL
  AND r.deleted_at IS NULL;

COMMIT;
