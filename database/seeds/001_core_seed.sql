-- FeActiva Iglesia SaaS
-- Seed: 001_core_seed
-- No real users, passwords or production credentials are inserted here.

INSERT INTO saas_countries (code, name, default_currency, default_timezone) VALUES
('CL', 'Chile', 'CLP', 'America/Santiago'),
('US', 'United States', 'USD', 'America/New_York'),
('AR', 'Argentina', 'ARS', 'America/Argentina/Buenos_Aires'),
('PE', 'Peru', 'PEN', 'America/Lima'),
('CO', 'Colombia', 'COP', 'America/Bogota'),
('MX', 'Mexico', 'MXN', 'America/Mexico_City')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    default_currency = VALUES(default_currency),
    default_timezone = VALUES(default_timezone);

INSERT INTO saas_currencies (code, name, symbol, decimals) VALUES
('CLP', 'Peso chileno', '$', 0),
('USD', 'Dolar estadounidense', '$', 2),
('ARS', 'Peso argentino', '$', 2),
('PEN', 'Sol peruano', 'S/', 2),
('COP', 'Peso colombiano', '$', 0),
('MXN', 'Peso mexicano', '$', 2)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    symbol = VALUES(symbol),
    decimals = VALUES(decimals);

INSERT INTO saas_modules (code, name, description, module_group, is_core, sort_order) VALUES
('core', 'Core SaaS', 'Gestion base multi-tenant, planes y configuracion.', 'core', 1, 1),
('auth', 'Usuarios y Seguridad', 'Usuarios, roles, permisos y sesiones.', 'core', 1, 2),
('crm', 'CRM Personas', 'Gestion de personas, familias, miembros y visitas.', 'personas', 0, 10),
('discipulado', 'Discipulado', 'Rutas de discipulado y crecimiento espiritual.', 'ministerial', 0, 20),
('pastoral', 'Seguimiento Pastoral', 'Consejeria, visitas, oracion y acompanamiento.', 'ministerial', 0, 30),
('ministerios', 'Ministerios', 'Gestion de ministerios, equipos y servidores.', 'ministerial', 0, 40),
('comunicacion', 'Comunicacion', 'Mensajes, plantillas, campanas y notificaciones.', 'operativo', 0, 50),
('finanzas', 'Finanzas', 'Ingresos, egresos, caja, banco y presupuestos.', 'financiero', 0, 60),
('contabilidad', 'Contabilidad', 'Plan de cuentas, asientos y estados financieros.', 'financiero', 0, 70),
('reportes', 'Reportes', 'Reportes ministeriales, financieros y operativos.', 'analitica', 0, 80),
('legal', 'Cumplimiento Legal', 'Auditoria, normativa, proteccion de datos y exportaciones.', 'legal', 0, 90)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    module_group = VALUES(module_group),
    is_core = VALUES(is_core),
    sort_order = VALUES(sort_order);

INSERT INTO saas_plans (
    code,
    name,
    description,
    monthly_price,
    annual_price,
    currency_code,
    max_users,
    max_members,
    is_active
) VALUES
('basic', 'Basico', 'CRM basico para iglesias pequenas.', 19.00, 190.00, 'USD', 3, 150, 1),
('standard', 'Estandar', 'CRM, discipulado, comunicacion y finanzas basicas.', 49.00, 490.00, 'USD', 10, 500, 1),
('premium', 'Premium', 'Gestion integral con contabilidad, reportes y cumplimiento.', 99.00, 990.00, 'USD', 30, 2000, 1),
('enterprise', 'Enterprise', 'Plan avanzado para iglesias grandes o redes de iglesias.', 0.00, 0.00, 'USD', NULL, NULL, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    monthly_price = VALUES(monthly_price),
    annual_price = VALUES(annual_price),
    currency_code = VALUES(currency_code),
    max_users = VALUES(max_users),
    max_members = VALUES(max_members),
    is_active = VALUES(is_active);

INSERT IGNORE INTO auth_roles (tenant_id, code, name, description, is_system) VALUES
(NULL, 'super_admin', 'Super Admin FeActiva', 'Administrador global de la plataforma.', 1),
(NULL, 'admin_iglesia', 'Administrador de Iglesia', 'Administrador principal de una iglesia.', 1),
(NULL, 'pastor_principal', 'Pastor Principal', 'Acceso pastoral y administrativo amplio.', 1),
(NULL, 'pastor_asistente', 'Pastor Asistente', 'Acceso pastoral limitado.', 1),
(NULL, 'tesorero', 'Tesorero', 'Gestion financiera de la iglesia.', 1),
(NULL, 'contador', 'Contador', 'Gestion contable y reportes financieros.', 1),
(NULL, 'lider_ministerio', 'Lider de Ministerio', 'Gestion de equipos y actividades ministeriales.', 1),
(NULL, 'mentor', 'Mentor / Discipulador', 'Seguimiento de personas en discipulado.', 1),
(NULL, 'miembro', 'Miembro', 'Usuario miembro de la iglesia.', 1),
(NULL, 'usuario_app', 'Usuario App', 'Usuario general de la app FeActiva.', 1);

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'saas.modulos.activar', 'Activar modulos', 'Permite activar o desactivar modulos por iglesia'
FROM saas_modules WHERE code = 'core'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'crm.personas.ver', 'Ver personas', 'Permite ver personas del CRM'
FROM saas_modules WHERE code = 'crm'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'crm.personas.crear', 'Crear personas', 'Permite crear personas'
FROM saas_modules WHERE code = 'crm'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'crm.personas.editar', 'Editar personas', 'Permite editar personas'
FROM saas_modules WHERE code = 'crm'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'crm.personas.eliminar', 'Eliminar personas', 'Permite eliminar logicamente personas'
FROM saas_modules WHERE code = 'crm'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'fin.movimientos.ver', 'Ver movimientos financieros', 'Permite ver ingresos y egresos'
FROM saas_modules WHERE code = 'finanzas'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'fin.movimientos.crear', 'Crear movimientos financieros', 'Permite registrar ingresos y egresos'
FROM saas_modules WHERE code = 'finanzas'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'fin.movimientos.anular', 'Anular movimientos financieros', 'Permite anular movimientos financieros'
FROM saas_modules WHERE code = 'finanzas'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'acct.asientos.ver', 'Ver asientos contables', 'Permite ver asientos contables'
FROM saas_modules WHERE code = 'contabilidad'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'acct.asientos.crear', 'Crear asientos contables', 'Permite crear asientos contables'
FROM saas_modules WHERE code = 'contabilidad'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'past.casos.ver', 'Ver casos pastorales', 'Permite ver casos pastorales segun autorizacion'
FROM saas_modules WHERE code = 'pastoral'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);
