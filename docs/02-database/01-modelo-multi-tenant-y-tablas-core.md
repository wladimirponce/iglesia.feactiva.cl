# FEACTIVA IGLESIA SAAS — MODELO MULTI-TENANT Y TABLAS CORE

## 1. Objetivo

Definir el modelo base multi-tenant de FeActiva Iglesia SaaS y crear las tablas centrales necesarias para:

- Iglesias / tenants
- Planes SaaS
- Módulos activables
- Usuarios
- Roles
- Permisos
- Relación usuario-iglesia
- Auditoría
- Configuración base

---

## 2. Principios obligatorios

1. Cada iglesia es un `tenant`.
2. Ningún dato funcional debe existir sin `tenant_id`.
3. El `tenant_id` nunca debe venir confiado desde el frontend.
4. El usuario puede pertenecer a una o más iglesias.
5. Un usuario puede tener roles distintos en iglesias distintas.
6. Los módulos se activan por tenant.
7. La API debe validar siempre:
   - usuario autenticado
   - tenant activo
   - módulo activo
   - permiso requerido

---

## 3. SQL BASE

```sql
CREATE DATABASE IF NOT EXISTS feactiva
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE feactiva;
4. TABLAS CORE SAAS
4.1 Países
CREATE TABLE saas_countries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    default_currency VARCHAR(10) NOT NULL,
    default_timezone VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
4.2 Monedas
CREATE TABLE saas_currencies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    symbol VARCHAR(10) NULL,
    decimals TINYINT UNSIGNED NOT NULL DEFAULT 2,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
4.3 Planes SaaS
CREATE TABLE saas_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    monthly_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    annual_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    currency_code VARCHAR(10) NOT NULL DEFAULT 'USD',
    max_users INT UNSIGNED NULL,
    max_members INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
4.4 Iglesias / Tenants
CREATE TABLE saas_tenants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    legal_name VARCHAR(200) NULL,
    tax_id VARCHAR(50) NULL,
    country_code VARCHAR(10) NOT NULL DEFAULT 'CL',
    currency_code VARCHAR(10) NOT NULL DEFAULT 'CLP',
    timezone VARCHAR(100) NOT NULL DEFAULT 'America/Santiago',
    email VARCHAR(150) NULL,
    phone VARCHAR(50) NULL,
    address VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    status ENUM('active','inactive','suspended','trial') NOT NULL DEFAULT 'trial',
    trial_ends_at DATETIME NULL,
    subscription_ends_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_saas_tenants_plan
        FOREIGN KEY (plan_id) REFERENCES saas_plans(id),

    INDEX idx_saas_tenants_plan_id (plan_id),
    INDEX idx_saas_tenants_country_code (country_code),
    INDEX idx_saas_tenants_status (status),
    INDEX idx_saas_tenants_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
4.5 Módulos del sistema
CREATE TABLE saas_modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    module_group VARCHAR(50) NULL,
    is_core TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
4.6 Módulos incluidos por plan
CREATE TABLE saas_plan_modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_plan_modules_plan
        FOREIGN KEY (plan_id) REFERENCES saas_plans(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_plan_modules_module
        FOREIGN KEY (module_id) REFERENCES saas_modules(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_plan_module (plan_id, module_id),
    INDEX idx_plan_modules_plan_id (plan_id),
    INDEX idx_plan_modules_module_id (module_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
4.7 Módulos activos por iglesia
CREATE TABLE saas_tenant_modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    enabled_at DATETIME NULL,
    disabled_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tenant_modules_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_tenant_modules_module
        FOREIGN KEY (module_id) REFERENCES saas_modules(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_tenant_module (tenant_id, module_id),
    INDEX idx_tenant_modules_tenant_id (tenant_id),
    INDEX idx_tenant_modules_module_id (module_id),
    INDEX idx_tenant_modules_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
5. AUTENTICACIÓN Y PERMISOS
5.1 Usuarios globales
CREATE TABLE auth_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    avatar_url VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    INDEX idx_auth_users_email (email),
    INDEX idx_auth_users_active (is_active),
    INDEX idx_auth_users_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
5.2 Relación usuarios / tenants
CREATE TABLE auth_user_tenants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tenant_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active','inactive','invited') NOT NULL DEFAULT 'active',
    invited_at DATETIME NULL,
    accepted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_tenants_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_user_tenants_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_user_tenant (user_id, tenant_id),
    INDEX idx_user_tenants_user_id (user_id),
    INDEX idx_user_tenants_tenant_id (tenant_id),
    INDEX idx_user_tenants_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
5.3 Roles
CREATE TABLE auth_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_auth_roles_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_role_tenant_code (tenant_id, code),
    INDEX idx_auth_roles_tenant_id (tenant_id),
    INDEX idx_auth_roles_code (code),
    INDEX idx_auth_roles_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

Nota:

tenant_id NULL permite roles globales del sistema.
tenant_id NOT NULL permite roles personalizados por iglesia.
5.4 Permisos
CREATE TABLE auth_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id BIGINT UNSIGNED NULL,
    code VARCHAR(120) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_permissions_module
        FOREIGN KEY (module_id) REFERENCES saas_modules(id)
        ON DELETE SET NULL,

    INDEX idx_permissions_module_id (module_id),
    INDEX idx_permissions_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

Ejemplos de code:

crm.personas.ver
crm.personas.crear
crm.personas.editar
crm.personas.eliminar
fin.movimientos.ver
fin.movimientos.crear
fin.movimientos.anular
acct.asientos.crear
past.casos.ver
saas.modulos.activar
5.5 Permisos por rol
CREATE TABLE auth_role_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_role_permissions_role
        FOREIGN KEY (role_id) REFERENCES auth_roles(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_role_permissions_permission
        FOREIGN KEY (permission_id) REFERENCES auth_permissions(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_role_permission (role_id, permission_id),
    INDEX idx_role_permissions_role_id (role_id),
    INDEX idx_role_permissions_permission_id (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
5.6 Roles asignados a usuarios por tenant
CREATE TABLE auth_user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tenant_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_roles_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_user_roles_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_user_roles_role
        FOREIGN KEY (role_id) REFERENCES auth_roles(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_user_tenant_role (user_id, tenant_id, role_id),
    INDEX idx_user_roles_user_id (user_id),
    INDEX idx_user_roles_tenant_id (tenant_id),
    INDEX idx_user_roles_role_id (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
6. CONFIGURACIÓN POR TENANT
6.1 Configuración general
CREATE TABLE saas_tenant_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    setting_key VARCHAR(120) NOT NULL,
    setting_value TEXT NULL,
    setting_type ENUM('string','number','boolean','json','date') NOT NULL DEFAULT 'string',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tenant_settings_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_tenant_setting (tenant_id, setting_key),
    INDEX idx_tenant_settings_tenant_id (tenant_id),
    INDEX idx_tenant_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
7. AUDITORÍA
7.1 Logs de auditoría
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    module_code VARCHAR(50) NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(120) NULL,
    record_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_logs_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_audit_logs_tenant_id (tenant_id),
    INDEX idx_audit_logs_user_id (user_id),
    INDEX idx_audit_logs_module_code (module_code),
    INDEX idx_audit_logs_action (action),
    INDEX idx_audit_logs_table_record (table_name, record_id),
    INDEX idx_audit_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
8. DATOS INICIALES RECOMENDADOS
8.1 Países
INSERT INTO saas_countries (code, name, default_currency, default_timezone) VALUES
('CL', 'Chile', 'CLP', 'America/Santiago'),
('US', 'United States', 'USD', 'America/New_York'),
('AR', 'Argentina', 'ARS', 'America/Argentina/Buenos_Aires'),
('PE', 'Peru', 'PEN', 'America/Lima'),
('CO', 'Colombia', 'COP', 'America/Bogota'),
('MX', 'Mexico', 'MXN', 'America/Mexico_City');
8.2 Monedas
INSERT INTO saas_currencies (code, name, symbol, decimals) VALUES
('CLP', 'Peso chileno', '$', 0),
('USD', 'Dólar estadounidense', '$', 2),
('ARS', 'Peso argentino', '$', 2),
('PEN', 'Sol peruano', 'S/', 2),
('COP', 'Peso colombiano', '$', 0),
('MXN', 'Peso mexicano', '$', 2);
8.3 Módulos
INSERT INTO saas_modules (code, name, description, module_group, is_core, sort_order) VALUES
('core', 'Core SaaS', 'Gestión base multi-tenant, planes y configuración.', 'core', 1, 1),
('auth', 'Usuarios y Seguridad', 'Usuarios, roles, permisos y sesiones.', 'core', 1, 2),
('crm', 'CRM Personas', 'Gestión de personas, familias, miembros y visitas.', 'personas', 0, 10),
('discipulado', 'Discipulado', 'Rutas de discipulado y crecimiento espiritual.', 'ministerial', 0, 20),
('pastoral', 'Seguimiento Pastoral', 'Consejería, visitas, oración y acompañamiento.', 'ministerial', 0, 30),
('ministerios', 'Ministerios', 'Gestión de ministerios, equipos y servidores.', 'ministerial', 0, 40),
('comunicacion', 'Comunicación', 'Mensajes, plantillas, campañas y notificaciones.', 'operativo', 0, 50),
('finanzas', 'Finanzas', 'Ingresos, egresos, caja, banco y presupuestos.', 'financiero', 0, 60),
('contabilidad', 'Contabilidad', 'Plan de cuentas, asientos y estados financieros.', 'financiero', 0, 70),
('reportes', 'Reportes', 'Reportes ministeriales, financieros y operativos.', 'analitica', 0, 80),
('legal', 'Cumplimiento Legal', 'Auditoría, normativa, protección de datos y exportaciones.', 'legal', 0, 90);
8.4 Planes SaaS
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
('basic', 'Básico', 'CRM básico para iglesias pequeñas.', 19.00, 190.00, 'USD', 3, 150, 1),
('standard', 'Estándar', 'CRM, discipulado, comunicación y finanzas básicas.', 49.00, 490.00, 'USD', 10, 500, 1),
('premium', 'Premium', 'Gestión integral con contabilidad, reportes y cumplimiento.', 99.00, 990.00, 'USD', 30, 2000, 1),
('enterprise', 'Enterprise', 'Plan avanzado para iglesias grandes o redes de iglesias.', 0.00, 0.00, 'USD', NULL, NULL, 1);
8.5 Roles base globales
INSERT INTO auth_roles (tenant_id, code, name, description, is_system) VALUES
(NULL, 'super_admin', 'Super Admin FeActiva', 'Administrador global de la plataforma.', 1),
(NULL, 'admin_iglesia', 'Administrador de Iglesia', 'Administrador principal de una iglesia.', 1),
(NULL, 'pastor_principal', 'Pastor Principal', 'Acceso pastoral y administrativo amplio.', 1),
(NULL, 'pastor_asistente', 'Pastor Asistente', 'Acceso pastoral limitado.', 1),
(NULL, 'tesorero', 'Tesorero', 'Gestión financiera de la iglesia.', 1),
(NULL, 'contador', 'Contador', 'Gestión contable y reportes financieros.', 1),
(NULL, 'lider_ministerio', 'Líder de Ministerio', 'Gestión de equipos y actividades ministeriales.', 1),
(NULL, 'mentor', 'Mentor / Discipulador', 'Seguimiento de personas en discipulado.', 1),
(NULL, 'miembro', 'Miembro', 'Usuario miembro de la iglesia.', 1),
(NULL, 'usuario_app', 'Usuario App', 'Usuario general de la app FeActiva.', 1);
8.6 Permisos base
INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'crm.personas.ver', 'Ver personas', 'Permite ver personas del CRM'
FROM saas_modules WHERE code = 'crm';

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'crm.personas.crear', 'Crear personas', 'Permite crear personas'
FROM saas_modules WHERE code = 'crm';

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'crm.personas.editar', 'Editar personas', 'Permite editar personas'
FROM saas_modules WHERE code = 'crm';

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'crm.personas.eliminar', 'Eliminar personas', 'Permite eliminar lógicamente personas'
FROM saas_modules WHERE code = 'crm';

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'fin.movimientos.ver', 'Ver movimientos financieros', 'Permite ver ingresos y egresos'
FROM saas_modules WHERE code = 'finanzas';

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'fin.movimientos.crear', 'Crear movimientos financieros', 'Permite registrar ingresos y egresos'
FROM saas_modules WHERE code = 'finanzas';

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'fin.movimientos.anular', 'Anular movimientos financieros', 'Permite anular movimientos financieros'
FROM saas_modules WHERE code = 'finanzas';

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'acct.asientos.ver', 'Ver asientos contables', 'Permite ver asientos contables'
FROM saas_modules WHERE code = 'contabilidad';

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'acct.asientos.crear', 'Crear asientos contables', 'Permite crear asientos contables'
FROM saas_modules WHERE code = 'contabilidad';

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'past.casos.ver', 'Ver casos pastorales', 'Permite ver casos pastorales según autorización'
FROM saas_modules WHERE code = 'pastoral';

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'past.casos.crear', 'Crear casos pastorales', 'Permite crear casos pastorales'
FROM saas_modules WHERE code = 'pastoral';

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT id, 'saas.modulos.activar', 'Activar módulos', 'Permite activar o desactivar módulos por iglesia'
FROM saas_modules WHERE code = 'core';
9. CONSULTAS DE VALIDACIÓN PARA BACKEND
9.1 Verificar que un usuario pertenece al tenant
SELECT 1
FROM auth_user_tenants
WHERE user_id = :user_id
  AND tenant_id = :tenant_id
  AND status = 'active'
LIMIT 1;
9.2 Verificar que un módulo está activo para el tenant
SELECT 1
FROM saas_tenant_modules tm
INNER JOIN saas_modules m ON m.id = tm.module_id
WHERE tm.tenant_id = :tenant_id
  AND m.code = :module_code
  AND tm.is_enabled = 1
  AND m.is_active = 1
LIMIT 1;
9.3 Verificar permiso de usuario
SELECT 1
FROM auth_user_roles ur
INNER JOIN auth_role_permissions rp ON rp.role_id = ur.role_id
INNER JOIN auth_permissions p ON p.id = rp.permission_id
WHERE ur.user_id = :user_id
  AND ur.tenant_id = :tenant_id
  AND p.code = :permission_code
LIMIT 1;
10. REGLAS PARA CODEX

Codex debe implementar este modelo respetando estas reglas:

No modificar nombres de tablas sin autorización.
No eliminar tenant_id en tablas funcionales.
No usar eliminación física para registros críticos.
No confiar en tenant_id enviado desde frontend.
Toda consulta funcional debe filtrar por tenant_id.
Toda acción crítica debe registrar auditoría.
Toda operación financiera debe ser transaccional.
Las contraseñas deben guardarse con password_hash.
Nunca guardar contraseñas en texto plano.
Nunca mostrar errores SQL al usuario.
Usar prepared statements siempre.
Validar módulo activo antes de permitir operación.
Validar permiso antes de permitir operación.
Mantener el sistema modular.
Mantener compatibilidad con PHP, MySQL y arquitectura incremental.
11. CRITERIO DE ÉXITO

Este modelo estará correctamente implementado cuando:

Puedan existir múltiples iglesias en la misma base.
Cada iglesia vea solo sus datos.
Un usuario pueda pertenecer a más de una iglesia.
Un usuario pueda tener roles diferentes según la iglesia.
Los módulos puedan activarse/desactivarse por iglesia.
Las acciones críticas queden auditadas.
Los permisos sean granulares.
El sistema pueda crecer hacia CRM, Finanzas, Contabilidad y Comunicación sin rehacer el núcleo.