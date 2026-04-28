-- NO USAR EN PRODUCCION
-- Seed DEV: permisos Agenda Secretario para tenant_id = 1 y rol admin_iglesia.

INSERT INTO saas_modules (code, name, description, module_group, is_core, is_active, sort_order)
VALUES ('agenda', 'Agenda', 'Agenda, tareas, llamadas, reuniones y notificaciones programadas.', 'operativo', 0, 1, 40)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    is_active = 1;

INSERT INTO saas_tenant_modules (tenant_id, module_id, is_enabled)
SELECT 1, m.id, 1
FROM saas_modules m
WHERE m.code = 'agenda'
ON DUPLICATE KEY UPDATE is_enabled = 1;

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT m.id, p.code, p.name, p.description
FROM saas_modules m
INNER JOIN (
    SELECT 'agenda.items.ver' AS code, 'Ver agenda' AS name, 'Permite ver items de agenda' AS description
    UNION ALL SELECT 'agenda.items.crear', 'Crear agenda', 'Permite crear items de agenda'
    UNION ALL SELECT 'agenda.items.editar', 'Editar agenda', 'Permite editar items de agenda'
    UNION ALL SELECT 'agenda.items.cancelar', 'Cancelar agenda', 'Permite cancelar items de agenda'
    UNION ALL SELECT 'agenda.items.completar', 'Completar agenda', 'Permite completar items de agenda'
    UNION ALL SELECT 'agenda.notifications.ver', 'Ver notificaciones agenda', 'Permite ver notificaciones de agenda'
    UNION ALL SELECT 'agenda.notifications.crear', 'Crear notificaciones agenda', 'Permite crear notificaciones de agenda'
    UNION ALL SELECT 'agenda.notifications.enviar', 'Enviar notificaciones agenda', 'Permite enviar notificaciones de agenda'
    UNION ALL SELECT 'agenda.notifications.cancelar', 'Cancelar notificaciones agenda', 'Permite cancelar notificaciones de agenda'
) p
WHERE m.code = 'agenda'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

INSERT IGNORE INTO auth_role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM auth_roles r
INNER JOIN auth_permissions p
WHERE r.code = 'admin_iglesia'
  AND p.code IN (
    'agenda.items.ver',
    'agenda.items.crear',
    'agenda.items.editar',
    'agenda.items.cancelar',
    'agenda.items.completar',
    'agenda.notifications.ver',
    'agenda.notifications.crear',
    'agenda.notifications.enviar',
    'agenda.notifications.cancelar'
  );
