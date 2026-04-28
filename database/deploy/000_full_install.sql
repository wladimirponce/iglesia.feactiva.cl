-- FeActiva Iglesia SaaS full install SQL
-- Generated for local Docker / fresh database install.
-- Import into the selected database; this file does not create or drop databases.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- ============================================================
-- Source: database\migrations\001_core_saas.sql
-- ============================================================

-- FeActiva Iglesia SaaS
-- Migration: 001_core_saas
-- Scope: Core SaaS, Auth base, permissions and audit.

CREATE TABLE IF NOT EXISTS saas_countries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    default_currency VARCHAR(10) NOT NULL,
    default_timezone VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saas_currencies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    symbol VARCHAR(10) NULL,
    decimals TINYINT UNSIGNED NOT NULL DEFAULT 2,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saas_plans (
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

CREATE TABLE IF NOT EXISTS saas_tenants (
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

CREATE TABLE IF NOT EXISTS saas_modules (
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

CREATE TABLE IF NOT EXISTS saas_plan_modules (
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

CREATE TABLE IF NOT EXISTS saas_tenant_modules (
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

CREATE TABLE IF NOT EXISTS auth_users (
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

CREATE TABLE IF NOT EXISTS auth_user_tenants (
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

CREATE TABLE IF NOT EXISTS auth_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tenant_id BIGINT UNSIGNED NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auth_sessions_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_auth_sessions_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,
    INDEX idx_auth_sessions_user_id (user_id),
    INDEX idx_auth_sessions_tenant_id (tenant_id),
    INDEX idx_auth_sessions_expires_at (expires_at),
    INDEX idx_auth_sessions_revoked_at (revoked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auth_roles (
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

CREATE TABLE IF NOT EXISTS auth_permissions (
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

CREATE TABLE IF NOT EXISTS auth_role_permissions (
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

CREATE TABLE IF NOT EXISTS auth_user_roles (
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

CREATE TABLE IF NOT EXISTS saas_tenant_settings (
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

CREATE TABLE IF NOT EXISTS audit_logs (
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

-- ============================================================
-- Source: database\seeds\001_core_seed.sql
-- ============================================================

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

-- ============================================================
-- Source: database\seeds\002_dev_auth_seed.sql
-- ============================================================

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

-- ============================================================
-- Source: database\seeds\003_dev_permissions_seed.sql
-- ============================================================

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

-- ============================================================
-- Source: database\migrations\002_crm_personas.sql
-- ============================================================

-- FeActiva Iglesia SaaS
-- Migration: 002_crm_personas
-- Scope: CRM personas, familias, membresia, contactos y etiquetas.

CREATE TABLE IF NOT EXISTS crm_personas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    nombres VARCHAR(120) NOT NULL,
    apellidos VARCHAR(120) NOT NULL,
    nombre_preferido VARCHAR(120) NULL,

    tipo_documento VARCHAR(30) NULL,
    numero_documento VARCHAR(50) NULL,

    email VARCHAR(180) NULL,
    telefono VARCHAR(50) NULL,
    whatsapp VARCHAR(50) NULL,

    fecha_nacimiento DATE NULL,
    genero ENUM('masculino','femenino','otro','no_informa') NULL,
    estado_civil ENUM('soltero','casado','viudo','divorciado','separado','no_informa') NULL,

    direccion VARCHAR(255) NULL,
    ciudad VARCHAR(120) NULL,
    region VARCHAR(120) NULL,
    pais VARCHAR(80) NULL,

    estado_persona ENUM(
        'visita',
        'nuevo_asistente',
        'miembro',
        'lider',
        'servidor',
        'inactivo',
        'trasladado',
        'fallecido'
    ) NOT NULL DEFAULT 'visita',

    fecha_primer_contacto DATE NULL,
    fecha_ingreso DATE NULL,
    fecha_membresia DATE NULL,

    origen_contacto VARCHAR(120) NULL,
    observaciones_generales TEXT NULL,

    foto_url VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_crm_personas_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_crm_personas_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_crm_personas_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_crm_personas_deleted_by
        FOREIGN KEY (deleted_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_crm_persona_documento_tenant (tenant_id, tipo_documento, numero_documento),

    INDEX idx_crm_personas_tenant_id (tenant_id),
    INDEX idx_crm_personas_estado (estado_persona),
    INDEX idx_crm_personas_email (email),
    INDEX idx_crm_personas_telefono (telefono),
    INDEX idx_crm_personas_whatsapp (whatsapp),
    INDEX idx_crm_personas_nombres (nombres, apellidos),
    INDEX idx_crm_personas_deleted_at (deleted_at),
    INDEX idx_crm_personas_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crm_familias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    nombre_familia VARCHAR(180) NOT NULL,
    direccion VARCHAR(255) NULL,
    ciudad VARCHAR(120) NULL,
    region VARCHAR(120) NULL,
    pais VARCHAR(80) NULL,

    telefono_principal VARCHAR(50) NULL,
    email_principal VARCHAR(180) NULL,

    observaciones TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_crm_familias_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_crm_familias_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_crm_familias_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_crm_familias_tenant_id (tenant_id),
    INDEX idx_crm_familias_nombre (nombre_familia),
    INDEX idx_crm_familias_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crm_persona_familia (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    persona_id BIGINT UNSIGNED NOT NULL,
    familia_id BIGINT UNSIGNED NOT NULL,

    parentesco ENUM(
        'jefe_hogar',
        'conyuge',
        'hijo',
        'hija',
        'padre',
        'madre',
        'tutor',
        'hermano',
        'hermana',
        'abuelo',
        'abuela',
        'otro'
    ) NOT NULL DEFAULT 'otro',

    es_contacto_principal TINYINT(1) NOT NULL DEFAULT 0,
    vive_en_hogar TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_persona_familia_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_persona_familia_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_persona_familia_familia
        FOREIGN KEY (familia_id) REFERENCES crm_familias(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_persona_familia (tenant_id, persona_id, familia_id),

    INDEX idx_persona_familia_tenant_id (tenant_id),
    INDEX idx_persona_familia_persona_id (persona_id),
    INDEX idx_persona_familia_familia_id (familia_id),
    INDEX idx_persona_familia_parentesco (parentesco)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crm_estados_membresia (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    code VARCHAR(80) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion TEXT NULL,
    color VARCHAR(20) NULL,
    orden INT UNSIGNED NOT NULL DEFAULT 0,
    es_activo TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_estados_membresia_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_estado_membresia_tenant_code (tenant_id, code),
    INDEX idx_estados_membresia_tenant_id (tenant_id),
    INDEX idx_estados_membresia_es_activo (es_activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crm_historial_membresia (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    persona_id BIGINT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(80) NULL,
    estado_nuevo VARCHAR(80) NOT NULL,

    fecha_cambio DATE NOT NULL,
    motivo VARCHAR(255) NULL,
    observacion TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_historial_membresia_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_historial_membresia_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_historial_membresia_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_historial_membresia_tenant_id (tenant_id),
    INDEX idx_historial_membresia_persona_id (persona_id),
    INDEX idx_historial_membresia_fecha (fecha_cambio),
    INDEX idx_historial_membresia_estado_nuevo (estado_nuevo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crm_contactos_historial (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    persona_id BIGINT UNSIGNED NOT NULL,

    tipo_contacto ENUM(
        'llamada',
        'whatsapp',
        'email',
        'visita',
        'reunion',
        'mensaje_app',
        'otro'
    ) NOT NULL DEFAULT 'otro',

    fecha_contacto DATETIME NOT NULL,
    asunto VARCHAR(180) NULL,
    resumen TEXT NULL,
    resultado VARCHAR(180) NULL,
    requiere_seguimiento TINYINT(1) NOT NULL DEFAULT 0,
    fecha_seguimiento DATE NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_contactos_historial_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_contactos_historial_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_contactos_historial_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_contactos_historial_tenant_id (tenant_id),
    INDEX idx_contactos_historial_persona_id (persona_id),
    INDEX idx_contactos_historial_fecha (fecha_contacto),
    INDEX idx_contactos_historial_tipo (tipo_contacto),
    INDEX idx_contactos_historial_seguimiento (requiere_seguimiento, fecha_seguimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crm_etiquetas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    color VARCHAR(20) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_crm_etiquetas_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_crm_etiquetas_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_crm_etiquetas_tenant_nombre (tenant_id, nombre),
    INDEX idx_crm_etiquetas_tenant_id (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crm_persona_etiquetas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    persona_id BIGINT UNSIGNED NOT NULL,
    etiqueta_id BIGINT UNSIGNED NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_persona_etiquetas_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_persona_etiquetas_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_persona_etiquetas_etiqueta
        FOREIGN KEY (etiqueta_id) REFERENCES crm_etiquetas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_persona_etiquetas_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_persona_etiqueta (tenant_id, persona_id, etiqueta_id),
    INDEX idx_persona_etiquetas_tenant_id (tenant_id),
    INDEX idx_persona_etiquetas_persona_id (persona_id),
    INDEX idx_persona_etiquetas_etiqueta_id (etiqueta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Source: database\migrations\002b_add_tutor_to_parentesco.sql
-- ============================================================

-- FeActiva Iglesia SaaS
-- Migration: 002b_add_tutor_to_parentesco
-- Scope: Add tutor value to crm_persona_familia.parentesco enum for development databases.

ALTER TABLE crm_persona_familia
    MODIFY parentesco ENUM(
        'jefe_hogar',
        'conyuge',
        'hijo',
        'hija',
        'padre',
        'madre',
        'tutor',
        'hermano',
        'hermana',
        'abuelo',
        'abuela',
        'otro'
    ) NOT NULL DEFAULT 'otro';

-- ============================================================
-- Source: database\seeds\004_crm_seed.sql
-- ============================================================

-- FeActiva Iglesia SaaS
-- Seed: 004_crm_seed
-- Scope: Development CRM membership statuses for tenant_id = 1 only.
-- WARNING: Do not run in production.

INSERT INTO crm_estados_membresia
(tenant_id, code, nombre, descripcion, color, orden, es_activo)
VALUES
(1, 'visita', 'Visita', 'Persona que asistio o tuvo primer contacto.', '#94a3b8', 1, 1),
(1, 'nuevo_asistente', 'Nuevo asistente', 'Persona en proceso inicial de integracion.', '#38bdf8', 2, 1),
(1, 'miembro', 'Miembro', 'Persona reconocida como miembro de la iglesia.', '#22c55e', 3, 1),
(1, 'lider', 'Lider', 'Persona con responsabilidad de liderazgo.', '#a855f7', 4, 1),
(1, 'servidor', 'Servidor', 'Persona que participa activamente en algun servicio.', '#f59e0b', 5, 1),
(1, 'inactivo', 'Inactivo', 'Persona sin participacion reciente.', '#ef4444', 6, 1),
(1, 'trasladado', 'Trasladado', 'Persona trasladada a otra iglesia.', '#64748b', 7, 1),
(1, 'fallecido', 'Fallecido', 'Persona fallecida.', '#111827', 8, 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    color = VALUES(color),
    orden = VALUES(orden),
    es_activo = VALUES(es_activo);

-- ============================================================
-- Source: database\seeds\005_dev_crm_permissions_seed.sql
-- ============================================================

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

-- ============================================================
-- Source: database\migrations\003_finanzas_basicas.sql
-- ============================================================

-- FeActiva Iglesia SaaS
-- Migration: 003_finanzas_basicas
-- Scope: Finanzas basicas.

CREATE TABLE IF NOT EXISTS fin_cuentas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    nombre VARCHAR(150) NOT NULL,
    tipo ENUM('caja','banco','digital','otro') NOT NULL DEFAULT 'caja',
    banco VARCHAR(120) NULL,
    numero_cuenta VARCHAR(100) NULL,
    moneda VARCHAR(10) NOT NULL DEFAULT 'CLP',
    saldo_inicial DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    fecha_saldo_inicial DATE NULL,

    es_principal TINYINT(1) NOT NULL DEFAULT 0,
    es_activa TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_fin_cuentas_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_fin_cuentas_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_fin_cuentas_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_fin_cuentas_deleted_by
        FOREIGN KEY (deleted_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_fin_cuentas_tenant_id (tenant_id),
    INDEX idx_fin_cuentas_tipo (tipo),
    INDEX idx_fin_cuentas_activa (es_activa),
    INDEX idx_fin_cuentas_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fin_categorias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    tipo ENUM('ingreso','egreso') NOT NULL,
    codigo VARCHAR(50) NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    es_sistema TINYINT(1) NOT NULL DEFAULT 0,
    es_activa TINYINT(1) NOT NULL DEFAULT 1,
    orden INT UNSIGNED NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_fin_categorias_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_fin_categorias_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_fin_categorias_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_fin_categoria_tenant_codigo (tenant_id, codigo),
    INDEX idx_fin_categorias_tenant_id (tenant_id),
    INDEX idx_fin_categorias_tipo (tipo),
    INDEX idx_fin_categorias_activa (es_activa),
    INDEX idx_fin_categorias_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fin_centros_costo (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    codigo VARCHAR(50) NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    responsable_persona_id BIGINT UNSIGNED NULL,

    es_activo TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_fin_centros_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_fin_centros_responsable_persona
        FOREIGN KEY (responsable_persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_fin_centros_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_fin_centros_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_fin_centro_tenant_codigo (tenant_id, codigo),
    INDEX idx_fin_centros_tenant_id (tenant_id),
    INDEX idx_fin_centros_responsable (responsable_persona_id),
    INDEX idx_fin_centros_activo (es_activo),
    INDEX idx_fin_centros_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fin_campanas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    nombre VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,
    meta_monto DECIMAL(14,2) NULL,
    moneda VARCHAR(10) NOT NULL DEFAULT 'CLP',
    fecha_inicio DATE NULL,
    fecha_fin DATE NULL,
    estado ENUM('borrador','activa','cerrada','cancelada') NOT NULL DEFAULT 'borrador',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_fin_campanas_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_fin_campanas_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_fin_campanas_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_fin_campanas_tenant_id (tenant_id),
    INDEX idx_fin_campanas_estado (estado),
    INDEX idx_fin_campanas_fechas (fecha_inicio, fecha_fin),
    INDEX idx_fin_campanas_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fin_movimientos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    cuenta_id BIGINT UNSIGNED NOT NULL,
    categoria_id BIGINT UNSIGNED NOT NULL,
    centro_costo_id BIGINT UNSIGNED NULL,
    campana_id BIGINT UNSIGNED NULL,
    persona_id BIGINT UNSIGNED NULL,

    tipo ENUM('ingreso','egreso') NOT NULL,

    subtipo VARCHAR(80) NULL,
    descripcion VARCHAR(255) NOT NULL,
    monto DECIMAL(14,2) NOT NULL,
    moneda VARCHAR(10) NOT NULL DEFAULT 'CLP',

    fecha_movimiento DATE NOT NULL,
    fecha_contable DATE NOT NULL,

    medio_pago ENUM(
        'efectivo',
        'transferencia',
        'tarjeta_debito',
        'tarjeta_credito',
        'cheque',
        'paypal',
        'stripe',
        'flow',
        'mercadopago',
        'otro'
    ) NOT NULL DEFAULT 'efectivo',

    referencia_pago VARCHAR(150) NULL,

    estado ENUM('registrado','conciliado','anulado') NOT NULL DEFAULT 'registrado',

    observacion TEXT NULL,

    movimiento_anulacion_id BIGINT UNSIGNED NULL,
    motivo_anulacion TEXT NULL,
    anulado_at DATETIME NULL,
    anulado_by BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_fin_movimientos_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_fin_movimientos_cuenta
        FOREIGN KEY (cuenta_id) REFERENCES fin_cuentas(id),

    CONSTRAINT fk_fin_movimientos_categoria
        FOREIGN KEY (categoria_id) REFERENCES fin_categorias(id),

    CONSTRAINT fk_fin_movimientos_centro
        FOREIGN KEY (centro_costo_id) REFERENCES fin_centros_costo(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_fin_movimientos_campana
        FOREIGN KEY (campana_id) REFERENCES fin_campanas(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_fin_movimientos_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_fin_movimientos_anulacion
        FOREIGN KEY (movimiento_anulacion_id) REFERENCES fin_movimientos(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_fin_movimientos_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_fin_movimientos_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_fin_movimientos_anulado_by
        FOREIGN KEY (anulado_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CHECK (monto > 0),

    INDEX idx_fin_movimientos_tenant_id (tenant_id),
    INDEX idx_fin_movimientos_cuenta_id (cuenta_id),
    INDEX idx_fin_movimientos_categoria_id (categoria_id),
    INDEX idx_fin_movimientos_centro_id (centro_costo_id),
    INDEX idx_fin_movimientos_campana_id (campana_id),
    INDEX idx_fin_movimientos_persona_id (persona_id),
    INDEX idx_fin_movimientos_tipo (tipo),
    INDEX idx_fin_movimientos_estado (estado),
    INDEX idx_fin_movimientos_fecha_movimiento (fecha_movimiento),
    INDEX idx_fin_movimientos_fecha_contable (fecha_contable),
    INDEX idx_fin_movimientos_medio_pago (medio_pago),
    INDEX idx_fin_movimientos_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fin_documentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    movimiento_id BIGINT UNSIGNED NOT NULL,

    tipo_documento ENUM(
        'comprobante_ingreso',
        'comprobante_egreso',
        'boleta',
        'factura',
        'recibo',
        'transferencia',
        'cartola',
        'otro'
    ) NOT NULL DEFAULT 'otro',

    numero_documento VARCHAR(100) NULL,
    fecha_documento DATE NULL,
    archivo_url VARCHAR(255) NULL,
    archivo_nombre VARCHAR(180) NULL,
    archivo_mime VARCHAR(100) NULL,
    archivo_size BIGINT UNSIGNED NULL,

    descripcion TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_fin_documentos_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_fin_documentos_movimiento
        FOREIGN KEY (movimiento_id) REFERENCES fin_movimientos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_fin_documentos_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_fin_documentos_tenant_id (tenant_id),
    INDEX idx_fin_documentos_movimiento_id (movimiento_id),
    INDEX idx_fin_documentos_tipo (tipo_documento),
    INDEX idx_fin_documentos_fecha (fecha_documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fin_presupuestos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    nombre VARCHAR(180) NOT NULL,
    periodo_inicio DATE NOT NULL,
    periodo_fin DATE NOT NULL,
    moneda VARCHAR(10) NOT NULL DEFAULT 'CLP',
    estado ENUM('borrador','aprobado','cerrado','cancelado') NOT NULL DEFAULT 'borrador',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_fin_presupuestos_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_fin_presupuestos_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_fin_presupuestos_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_fin_presupuestos_tenant_id (tenant_id),
    INDEX idx_fin_presupuestos_periodo (periodo_inicio, periodo_fin),
    INDEX idx_fin_presupuestos_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fin_presupuesto_detalles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    presupuesto_id BIGINT UNSIGNED NOT NULL,
    categoria_id BIGINT UNSIGNED NOT NULL,
    centro_costo_id BIGINT UNSIGNED NULL,

    tipo ENUM('ingreso','egreso') NOT NULL,
    monto_presupuestado DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    observacion TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_presupuesto_detalles_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_presupuesto_detalles_presupuesto
        FOREIGN KEY (presupuesto_id) REFERENCES fin_presupuestos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_presupuesto_detalles_categoria
        FOREIGN KEY (categoria_id) REFERENCES fin_categorias(id),

    CONSTRAINT fk_presupuesto_detalles_centro
        FOREIGN KEY (centro_costo_id) REFERENCES fin_centros_costo(id)
        ON DELETE SET NULL,

    INDEX idx_presupuesto_detalles_tenant_id (tenant_id),
    INDEX idx_presupuesto_detalles_presupuesto_id (presupuesto_id),
    INDEX idx_presupuesto_detalles_categoria_id (categoria_id),
    INDEX idx_presupuesto_detalles_centro_id (centro_costo_id),
    INDEX idx_presupuesto_detalles_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Source: database\seeds\006_finanzas_seed.sql
-- ============================================================

-- FeActiva Iglesia SaaS
-- Seed: 006_finanzas_seed
-- Scope: Development finance catalogs for tenant_id = 1 only.
-- WARNING: Do not run in production.

START TRANSACTION;

INSERT INTO fin_categorias
(tenant_id, tipo, codigo, nombre, descripcion, es_sistema, es_activa, orden)
VALUES
(1, 'ingreso', 'diezmo', 'Diezmo', 'Ingresos por diezmos.', 1, 1, 1),
(1, 'ingreso', 'ofrenda', 'Ofrenda', 'Ingresos por ofrendas generales.', 1, 1, 2),
(1, 'ingreso', 'donacion', 'Donacion', 'Donaciones especiales.', 1, 1, 3),
(1, 'ingreso', 'misiones', 'Misiones', 'Aportes destinados a misiones.', 1, 1, 4),
(1, 'ingreso', 'campana', 'Campana especial', 'Aportes para campanas o proyectos especiales.', 1, 1, 5),
(1, 'ingreso', 'curso', 'Curso / formacion', 'Ingresos por cursos o formacion.', 1, 1, 6),
(1, 'ingreso', 'otro_ingreso', 'Otro ingreso', 'Otros ingresos.', 1, 1, 99),
(1, 'egreso', 'arriendo', 'Arriendo', 'Pago de arriendo o alquiler.', 1, 1, 1),
(1, 'egreso', 'servicios_basicos', 'Servicios basicos', 'Luz, agua, gas, internet y similares.', 1, 1, 2),
(1, 'egreso', 'sueldos_honorarios', 'Sueldos y honorarios', 'Pagos a personal, ministros o profesionales.', 1, 1, 3),
(1, 'egreso', 'ayuda_social', 'Ayuda social', 'Apoyo economico o material a personas o familias.', 1, 1, 4),
(1, 'egreso', 'misiones', 'Misiones', 'Gastos asociados a misiones.', 1, 1, 5),
(1, 'egreso', 'materiales', 'Materiales', 'Compra de materiales para ministerios o actividades.', 1, 1, 6),
(1, 'egreso', 'mantencion', 'Mantencion', 'Reparaciones y mantencion de infraestructura.', 1, 1, 7),
(1, 'egreso', 'administracion', 'Administracion', 'Gastos administrativos generales.', 1, 1, 8),
(1, 'egreso', 'otro_egreso', 'Otro egreso', 'Otros egresos.', 1, 1, 99)
ON DUPLICATE KEY UPDATE
    tipo = VALUES(tipo),
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    es_sistema = VALUES(es_sistema),
    es_activa = VALUES(es_activa),
    orden = VALUES(orden);

INSERT INTO fin_cuentas
(tenant_id, nombre, tipo, moneda, saldo_inicial, fecha_saldo_inicial, es_principal, es_activa, created_by)
SELECT
    1,
    'Caja principal',
    'caja',
    'CLP',
    0.00,
    CURDATE(),
    1,
    1,
    u.id
FROM auth_users u
WHERE u.email = 'admin@demo.test'
  AND NOT EXISTS (
      SELECT 1
      FROM fin_cuentas c
      WHERE c.tenant_id = 1
        AND c.nombre = 'Caja principal'
        AND c.deleted_at IS NULL
  )
LIMIT 1;

INSERT INTO fin_centros_costo
(tenant_id, codigo, nombre, descripcion, es_activo, created_by)
SELECT
    1,
    centros.codigo,
    centros.nombre,
    centros.descripcion,
    1,
    u.id
FROM auth_users u
INNER JOIN (
    SELECT 'general' AS codigo, 'General' AS nombre, 'Centro de costo general de la iglesia.' AS descripcion
    UNION ALL SELECT 'misiones', 'Misiones', 'Fondos y gastos asociados a misiones.'
    UNION ALL SELECT 'ayuda_social', 'Ayuda social', 'Fondos y gastos de ayuda social.'
    UNION ALL SELECT 'ninos', 'Ninos', 'Ministerio de ninos.'
    UNION ALL SELECT 'jovenes', 'Jovenes', 'Ministerio de jovenes.'
) centros
WHERE u.email = 'admin@demo.test'
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    es_activo = VALUES(es_activo),
    created_by = VALUES(created_by);

COMMIT;

-- ============================================================
-- Source: database\seeds\007_dev_finanzas_permissions_seed.sql
-- ============================================================

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

-- ============================================================
-- Source: database\migrations\004_contabilidad_formal.sql
-- ============================================================

-- FeActiva Iglesia SaaS
-- Migration: 004_contabilidad_formal
-- Scope: Contabilidad formal.

CREATE TABLE IF NOT EXISTS acct_configuracion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    pais_codigo VARCHAR(10) NOT NULL DEFAULT 'CL',
    moneda_base VARCHAR(10) NOT NULL DEFAULT 'CLP',
    norma_contable VARCHAR(50) NULL,
    periodo_inicio_mes TINYINT UNSIGNED NOT NULL DEFAULT 1,
    usa_centros_costo TINYINT(1) NOT NULL DEFAULT 1,
    requiere_aprobacion_asientos TINYINT(1) NOT NULL DEFAULT 1,
    numeracion_automatica TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_acct_config_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_acct_config_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_acct_config_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_acct_config_tenant (tenant_id),
    INDEX idx_acct_config_tenant_id (tenant_id),
    INDEX idx_acct_config_pais (pais_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acct_cuentas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,

    tipo ENUM(
        'activo',
        'pasivo',
        'patrimonio',
        'ingreso',
        'gasto',
        'orden'
    ) NOT NULL,

    naturaleza ENUM('deudora','acreedora') NOT NULL,

    cuenta_padre_id BIGINT UNSIGNED NULL,
    nivel TINYINT UNSIGNED NOT NULL DEFAULT 1,
    es_movimiento TINYINT(1) NOT NULL DEFAULT 1,
    es_sistema TINYINT(1) NOT NULL DEFAULT 0,
    es_activa TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_acct_cuentas_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_acct_cuentas_padre
        FOREIGN KEY (cuenta_padre_id) REFERENCES acct_cuentas(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_acct_cuentas_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_acct_cuentas_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_acct_cuenta_tenant_codigo (tenant_id, codigo),

    INDEX idx_acct_cuentas_tenant_id (tenant_id),
    INDEX idx_acct_cuentas_codigo (codigo),
    INDEX idx_acct_cuentas_tipo (tipo),
    INDEX idx_acct_cuentas_padre (cuenta_padre_id),
    INDEX idx_acct_cuentas_activa (es_activa),
    INDEX idx_acct_cuentas_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acct_periodos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    nombre VARCHAR(120) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado ENUM('abierto','cerrado','bloqueado') NOT NULL DEFAULT 'abierto',

    cerrado_at DATETIME NULL,
    cerrado_by BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_acct_periodos_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_acct_periodos_cerrado_by
        FOREIGN KEY (cerrado_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_acct_periodos_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_acct_periodos_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_acct_periodo_tenant_fechas (tenant_id, fecha_inicio, fecha_fin),
    INDEX idx_acct_periodos_tenant_id (tenant_id),
    INDEX idx_acct_periodos_estado (estado),
    INDEX idx_acct_periodos_fechas (fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acct_asientos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    periodo_id BIGINT UNSIGNED NULL,
    numero VARCHAR(80) NOT NULL,
    fecha_asiento DATE NOT NULL,
    descripcion VARCHAR(255) NOT NULL,

    origen ENUM(
        'manual',
        'finanzas',
        'ajuste',
        'reversa',
        'apertura',
        'cierre'
    ) NOT NULL DEFAULT 'manual',

    fin_movimiento_id BIGINT UNSIGNED NULL,
    asiento_reversado_id BIGINT UNSIGNED NULL,

    estado ENUM('borrador','aprobado','anulado') NOT NULL DEFAULT 'borrador',

    total_debe DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_haber DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    moneda VARCHAR(10) NOT NULL DEFAULT 'CLP',

    aprobado_at DATETIME NULL,
    aprobado_by BIGINT UNSIGNED NULL,

    anulado_at DATETIME NULL,
    anulado_by BIGINT UNSIGNED NULL,
    motivo_anulacion TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_acct_asientos_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_acct_asientos_periodo
        FOREIGN KEY (periodo_id) REFERENCES acct_periodos(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_acct_asientos_fin_movimiento
        FOREIGN KEY (fin_movimiento_id) REFERENCES fin_movimientos(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_acct_asientos_reversado
        FOREIGN KEY (asiento_reversado_id) REFERENCES acct_asientos(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_acct_asientos_aprobado_by
        FOREIGN KEY (aprobado_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_acct_asientos_anulado_by
        FOREIGN KEY (anulado_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_acct_asientos_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_acct_asientos_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_acct_asiento_tenant_numero (tenant_id, numero),

    INDEX idx_acct_asientos_tenant_id (tenant_id),
    INDEX idx_acct_asientos_periodo_id (periodo_id),
    INDEX idx_acct_asientos_fecha (fecha_asiento),
    INDEX idx_acct_asientos_estado (estado),
    INDEX idx_acct_asientos_origen (origen),
    INDEX idx_acct_asientos_fin_movimiento (fin_movimiento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acct_asiento_detalles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    asiento_id BIGINT UNSIGNED NOT NULL,
    cuenta_id BIGINT UNSIGNED NOT NULL,
    centro_costo_id BIGINT UNSIGNED NULL,

    descripcion VARCHAR(255) NULL,
    debe DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    haber DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    referencia VARCHAR(150) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_acct_detalles_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_acct_detalles_asiento
        FOREIGN KEY (asiento_id) REFERENCES acct_asientos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_acct_detalles_cuenta
        FOREIGN KEY (cuenta_id) REFERENCES acct_cuentas(id),

    CONSTRAINT fk_acct_detalles_centro
        FOREIGN KEY (centro_costo_id) REFERENCES fin_centros_costo(id)
        ON DELETE SET NULL,

    CHECK (debe >= 0),
    CHECK (haber >= 0),
    CHECK (
        (debe > 0 AND haber = 0)
        OR
        (haber > 0 AND debe = 0)
    ),

    INDEX idx_acct_detalles_tenant_id (tenant_id),
    INDEX idx_acct_detalles_asiento_id (asiento_id),
    INDEX idx_acct_detalles_cuenta_id (cuenta_id),
    INDEX idx_acct_detalles_centro_id (centro_costo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acct_mapeo_finanzas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    categoria_id BIGINT UNSIGNED NOT NULL,
    tipo_movimiento ENUM('ingreso','egreso') NOT NULL,

    cuenta_debe_id BIGINT UNSIGNED NOT NULL,
    cuenta_haber_id BIGINT UNSIGNED NOT NULL,

    descripcion VARCHAR(255) NULL,
    es_activo TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_mapeo_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_mapeo_categoria
        FOREIGN KEY (categoria_id) REFERENCES fin_categorias(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_mapeo_cuenta_debe
        FOREIGN KEY (cuenta_debe_id) REFERENCES acct_cuentas(id),

    CONSTRAINT fk_mapeo_cuenta_haber
        FOREIGN KEY (cuenta_haber_id) REFERENCES acct_cuentas(id),

    CONSTRAINT fk_mapeo_created_by
        FOREIGN KEY (created_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_mapeo_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_mapeo_finanzas_categoria_tipo (tenant_id, categoria_id, tipo_movimiento),

    INDEX idx_mapeo_tenant_id (tenant_id),
    INDEX idx_mapeo_categoria_id (categoria_id),
    INDEX idx_mapeo_activo (es_activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Source: database\seeds\008_contabilidad_seed.sql
-- ============================================================

-- FeActiva Iglesia SaaS
-- Seed: 008_contabilidad_seed
-- Scope: Development/base accounting configuration and chart of accounts for tenant_id = 1.

START TRANSACTION;

INSERT INTO acct_configuracion (
    tenant_id,
    pais_codigo,
    moneda_base,
    norma_contable,
    periodo_inicio_mes,
    usa_centros_costo,
    requiere_aprobacion_asientos,
    numeracion_automatica,
    created_by
) VALUES (
    1,
    'CL',
    'CLP',
    'GENERAL',
    1,
    1,
    1,
    1,
    1
)
ON DUPLICATE KEY UPDATE
    pais_codigo = VALUES(pais_codigo),
    moneda_base = VALUES(moneda_base),
    norma_contable = VALUES(norma_contable),
    periodo_inicio_mes = VALUES(periodo_inicio_mes),
    usa_centros_costo = VALUES(usa_centros_costo),
    requiere_aprobacion_asientos = VALUES(requiere_aprobacion_asientos),
    numeracion_automatica = VALUES(numeracion_automatica),
    updated_by = VALUES(created_by);

INSERT INTO acct_cuentas (
    tenant_id,
    codigo,
    nombre,
    tipo,
    naturaleza,
    cuenta_padre_id,
    nivel,
    es_movimiento,
    es_sistema,
    es_activa,
    created_by
) VALUES
(1, '1', 'Activos', 'activo', 'deudora', NULL, 1, 0, 1, 1, 1),
(1, '2', 'Pasivos', 'pasivo', 'acreedora', NULL, 1, 0, 1, 1, 1),
(1, '3', 'Patrimonio / Fondos', 'patrimonio', 'acreedora', NULL, 1, 0, 1, 1, 1),
(1, '4', 'Ingresos', 'ingreso', 'acreedora', NULL, 1, 0, 1, 1, 1),
(1, '5', 'Gastos', 'gasto', 'deudora', NULL, 1, 0, 1, 1, 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    tipo = VALUES(tipo),
    naturaleza = VALUES(naturaleza),
    cuenta_padre_id = VALUES(cuenta_padre_id),
    nivel = VALUES(nivel),
    es_movimiento = VALUES(es_movimiento),
    es_sistema = VALUES(es_sistema),
    es_activa = VALUES(es_activa),
    updated_by = VALUES(created_by),
    deleted_at = NULL;

INSERT INTO acct_cuentas (
    tenant_id,
    codigo,
    nombre,
    tipo,
    naturaleza,
    cuenta_padre_id,
    nivel,
    es_movimiento,
    es_sistema,
    es_activa,
    created_by
)
SELECT
    1,
    child.codigo,
    child.nombre,
    child.tipo,
    child.naturaleza,
    parent.id,
    2,
    1,
    1,
    1,
    1
FROM (
    SELECT '1' AS parent_codigo, '1.1' AS codigo, 'Caja' AS nombre, 'activo' AS tipo, 'deudora' AS naturaleza
    UNION ALL SELECT '1', '1.2', 'Bancos', 'activo', 'deudora'
    UNION ALL SELECT '1', '1.3', 'Cuentas por cobrar', 'activo', 'deudora'
    UNION ALL SELECT '2', '2.1', 'Cuentas por pagar', 'pasivo', 'acreedora'
    UNION ALL SELECT '2', '2.2', 'Obligaciones laborales', 'pasivo', 'acreedora'
    UNION ALL SELECT '3', '3.1', 'Fondo general', 'patrimonio', 'acreedora'
    UNION ALL SELECT '3', '3.2', 'Fondos restringidos', 'patrimonio', 'acreedora'
    UNION ALL SELECT '3', '3.3', 'Resultados acumulados', 'patrimonio', 'acreedora'
    UNION ALL SELECT '4', '4.1', 'Diezmos', 'ingreso', 'acreedora'
    UNION ALL SELECT '4', '4.2', 'Ofrendas', 'ingreso', 'acreedora'
    UNION ALL SELECT '4', '4.3', 'Donaciones', 'ingreso', 'acreedora'
    UNION ALL SELECT '4', '4.4', 'Ingresos por cursos', 'ingreso', 'acreedora'
    UNION ALL SELECT '4', '4.5', 'Otros ingresos', 'ingreso', 'acreedora'
    UNION ALL SELECT '5', '5.1', 'Arriendo', 'gasto', 'deudora'
    UNION ALL SELECT '5', '5.2', 'Servicios basicos', 'gasto', 'deudora'
    UNION ALL SELECT '5', '5.3', 'Sueldos y honorarios', 'gasto', 'deudora'
    UNION ALL SELECT '5', '5.4', 'Ayuda social', 'gasto', 'deudora'
    UNION ALL SELECT '5', '5.5', 'Misiones', 'gasto', 'deudora'
    UNION ALL SELECT '5', '5.6', 'Materiales', 'gasto', 'deudora'
    UNION ALL SELECT '5', '5.7', 'Administracion', 'gasto', 'deudora'
) child
INNER JOIN acct_cuentas parent
    ON parent.tenant_id = 1
    AND parent.codigo = child.parent_codigo
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    tipo = VALUES(tipo),
    naturaleza = VALUES(naturaleza),
    cuenta_padre_id = VALUES(cuenta_padre_id),
    nivel = VALUES(nivel),
    es_movimiento = VALUES(es_movimiento),
    es_sistema = VALUES(es_sistema),
    es_activa = VALUES(es_activa),
    updated_by = VALUES(created_by),
    deleted_at = NULL;

COMMIT;

-- ============================================================
-- Source: database\seeds\009_dev_contabilidad_permissions_seed.sql
-- ============================================================

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

-- ============================================================
-- Source: database\migrations\005_discipulado_pastoral.sql
-- ============================================================

-- FeActiva Iglesia SaaS
-- Migration: 005_discipulado_pastoral
-- Scope: Discipulado y Seguimiento Pastoral.

CREATE TABLE IF NOT EXISTS disc_rutas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    nombre VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,
    publico_objetivo VARCHAR(180) NULL,
    duracion_estimada_dias INT UNSIGNED NULL,
    es_activa TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_disc_rutas_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    INDEX idx_disc_rutas_tenant_id (tenant_id),
    INDEX idx_disc_rutas_activa (es_activa),
    INDEX idx_disc_rutas_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disc_etapas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    ruta_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,
    orden INT UNSIGNED NOT NULL DEFAULT 0,
    duracion_estimada_dias INT UNSIGNED NULL,
    es_obligatoria TINYINT(1) NOT NULL DEFAULT 1,
    es_activa TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_disc_etapas_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_disc_etapas_ruta
        FOREIGN KEY (ruta_id) REFERENCES disc_rutas(id)
        ON DELETE CASCADE,

    INDEX idx_disc_etapas_tenant_id (tenant_id),
    INDEX idx_disc_etapas_ruta_id (ruta_id),
    INDEX idx_disc_etapas_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disc_persona_rutas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    persona_id BIGINT UNSIGNED NOT NULL,
    ruta_id BIGINT UNSIGNED NOT NULL,
    mentor_persona_id BIGINT UNSIGNED NULL,

    estado ENUM('pendiente','en_progreso','completada','pausada','cancelada') NOT NULL DEFAULT 'pendiente',
    fecha_inicio DATE NULL,
    fecha_fin DATE NULL,
    porcentaje_avance DECIMAL(5,2) NOT NULL DEFAULT 0.00,

    observacion TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_disc_persona_rutas_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_disc_persona_rutas_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_disc_persona_rutas_ruta
        FOREIGN KEY (ruta_id) REFERENCES disc_rutas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_disc_persona_rutas_mentor
        FOREIGN KEY (mentor_persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_disc_persona_ruta (tenant_id, persona_id, ruta_id),

    INDEX idx_disc_persona_rutas_tenant_id (tenant_id),
    INDEX idx_disc_persona_rutas_persona_id (persona_id),
    INDEX idx_disc_persona_rutas_ruta_id (ruta_id),
    INDEX idx_disc_persona_rutas_mentor_id (mentor_persona_id),
    INDEX idx_disc_persona_rutas_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disc_persona_etapas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    persona_ruta_id BIGINT UNSIGNED NOT NULL,
    etapa_id BIGINT UNSIGNED NOT NULL,

    estado ENUM('pendiente','en_progreso','completada','omitida') NOT NULL DEFAULT 'pendiente',
    fecha_inicio DATE NULL,
    fecha_fin DATE NULL,
    nota_resultado VARCHAR(120) NULL,
    observacion TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_disc_persona_etapas_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_disc_persona_etapas_persona_ruta
        FOREIGN KEY (persona_ruta_id) REFERENCES disc_persona_rutas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_disc_persona_etapas_etapa
        FOREIGN KEY (etapa_id) REFERENCES disc_etapas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_disc_persona_etapas_updated_by
        FOREIGN KEY (updated_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_disc_persona_etapa (tenant_id, persona_ruta_id, etapa_id),

    INDEX idx_disc_persona_etapas_tenant_id (tenant_id),
    INDEX idx_disc_persona_etapas_persona_ruta_id (persona_ruta_id),
    INDEX idx_disc_persona_etapas_etapa_id (etapa_id),
    INDEX idx_disc_persona_etapas_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disc_mentorias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    persona_id BIGINT UNSIGNED NOT NULL,
    mentor_persona_id BIGINT UNSIGNED NOT NULL,
    persona_ruta_id BIGINT UNSIGNED NULL,

    fecha_mentoria DATETIME NOT NULL,
    modalidad ENUM('presencial','online','telefono','whatsapp','otro') NOT NULL DEFAULT 'presencial',
    tema VARCHAR(180) NULL,
    resumen TEXT NULL,
    acuerdos TEXT NULL,
    proxima_fecha DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_disc_mentorias_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_disc_mentorias_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_disc_mentorias_mentor
        FOREIGN KEY (mentor_persona_id) REFERENCES crm_personas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_disc_mentorias_persona_ruta
        FOREIGN KEY (persona_ruta_id) REFERENCES disc_persona_rutas(id)
        ON DELETE SET NULL,

    INDEX idx_disc_mentorias_tenant_id (tenant_id),
    INDEX idx_disc_mentorias_persona_id (persona_id),
    INDEX idx_disc_mentorias_mentor_id (mentor_persona_id),
    INDEX idx_disc_mentorias_fecha (fecha_mentoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disc_registros_espirituales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    persona_id BIGINT UNSIGNED NOT NULL,

    tipo ENUM(
        'conversion',
        'profesion_fe',
        'bautismo',
        'santa_cena',
        'recepcion_membresia',
        'presentacion_nino',
        'matrimonio',
        'otro'
    ) NOT NULL,

    fecha_evento DATE NOT NULL,
    lugar VARCHAR(180) NULL,
    ministro_responsable VARCHAR(180) NULL,
    observacion TEXT NULL,
    documento_url VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_disc_registros_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_disc_registros_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE CASCADE,

    INDEX idx_disc_registros_tenant_id (tenant_id),
    INDEX idx_disc_registros_persona_id (persona_id),
    INDEX idx_disc_registros_tipo (tipo),
    INDEX idx_disc_registros_fecha (fecha_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS past_casos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    persona_id BIGINT UNSIGNED NOT NULL,
    responsable_user_id BIGINT UNSIGNED NULL,

    tipo ENUM(
        'consejeria',
        'oracion',
        'visita',
        'crisis',
        'acompanamiento',
        'disciplinario',
        'otro'
    ) NOT NULL DEFAULT 'acompanamiento',

    titulo VARCHAR(180) NOT NULL,
    descripcion_general TEXT NULL,

    prioridad ENUM('baja','media','alta','critica') NOT NULL DEFAULT 'media',
    estado ENUM('abierto','en_seguimiento','cerrado','derivado') NOT NULL DEFAULT 'abierto',

    fecha_apertura DATE NOT NULL,
    fecha_cierre DATE NULL,

    es_confidencial TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_past_casos_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_past_casos_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_past_casos_responsable
        FOREIGN KEY (responsable_user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_past_casos_tenant_id (tenant_id),
    INDEX idx_past_casos_persona_id (persona_id),
    INDEX idx_past_casos_responsable (responsable_user_id),
    INDEX idx_past_casos_tipo (tipo),
    INDEX idx_past_casos_estado (estado),
    INDEX idx_past_casos_prioridad (prioridad),
    INDEX idx_past_casos_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS past_sesiones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    caso_id BIGINT UNSIGNED NOT NULL,
    persona_id BIGINT UNSIGNED NOT NULL,

    fecha_sesion DATETIME NOT NULL,
    modalidad ENUM('presencial','online','telefono','whatsapp','otro') NOT NULL DEFAULT 'presencial',

    resumen TEXT NULL,
    acuerdos TEXT NULL,
    proxima_accion TEXT NULL,
    proxima_fecha DATETIME NULL,

    es_confidencial TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_past_sesiones_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_past_sesiones_caso
        FOREIGN KEY (caso_id) REFERENCES past_casos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_past_sesiones_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE CASCADE,

    INDEX idx_past_sesiones_tenant_id (tenant_id),
    INDEX idx_past_sesiones_caso_id (caso_id),
    INDEX idx_past_sesiones_persona_id (persona_id),
    INDEX idx_past_sesiones_fecha (fecha_sesion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS past_solicitudes_oracion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    persona_id BIGINT UNSIGNED NULL,
    nombre_solicitante VARCHAR(180) NULL,
    contacto_solicitante VARCHAR(120) NULL,

    titulo VARCHAR(180) NOT NULL,
    detalle TEXT NULL,
    categoria VARCHAR(100) NULL,

    privacidad ENUM('privada','equipo_pastoral','publica') NOT NULL DEFAULT 'privada',
    estado ENUM('recibida','en_oracion','respondida','cerrada') NOT NULL DEFAULT 'recibida',

    fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_past_oracion_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_past_oracion_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,

    INDEX idx_past_oracion_tenant_id (tenant_id),
    INDEX idx_past_oracion_persona_id (persona_id),
    INDEX idx_past_oracion_estado (estado),
    INDEX idx_past_oracion_privacidad (privacidad),
    INDEX idx_past_oracion_fecha (fecha_solicitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS past_derivaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    caso_id BIGINT UNSIGNED NOT NULL,
    persona_id BIGINT UNSIGNED NOT NULL,

    derivado_a_user_id BIGINT UNSIGNED NULL,
    derivado_a_nombre VARCHAR(180) NULL,
    tipo_derivacion ENUM('pastor','psicologo','orientador','diacono','lider','externo','otro') NOT NULL DEFAULT 'pastor',

    motivo TEXT NOT NULL,
    fecha_derivacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente','aceptada','rechazada','cerrada') NOT NULL DEFAULT 'pendiente',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_past_derivaciones_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_past_derivaciones_caso
        FOREIGN KEY (caso_id) REFERENCES past_casos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_past_derivaciones_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_past_derivaciones_user
        FOREIGN KEY (derivado_a_user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_past_derivaciones_tenant_id (tenant_id),
    INDEX idx_past_derivaciones_caso_id (caso_id),
    INDEX idx_past_derivaciones_persona_id (persona_id),
    INDEX idx_past_derivaciones_user_id (derivado_a_user_id),
    INDEX idx_past_derivaciones_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Source: database\seeds\010_dev_discipulado_pastoral_permissions_seed.sql
-- ============================================================

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

-- ============================================================
-- Source: database\migrations\006_whatsapp_agent_base.sql
-- ============================================================

-- FeActiva Iglesia SaaS
-- Migration: 006_whatsapp_agent_base
-- Scope: WhatsApp identity and agent base tables.

UPDATE auth_users
SET phone = CONCAT('+1000000', id)
WHERE phone IS NULL
   OR TRIM(phone) = '';

ALTER TABLE auth_users
    MODIFY phone VARCHAR(50) NOT NULL;

CREATE TABLE IF NOT EXISTS wa_conversations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    whatsapp_phone VARCHAR(50) NOT NULL,
    whatsapp_phone_normalized VARCHAR(20) NOT NULL,
    provider VARCHAR(50) NOT NULL DEFAULT 'whatsapp',
    provider_conversation_id VARCHAR(120) NULL,
    status ENUM('open','closed','archived') NOT NULL DEFAULT 'open',
    last_message_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_wa_conversations_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_wa_conversations_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    INDEX idx_wa_conversations_tenant_id (tenant_id),
    INDEX idx_wa_conversations_user_id (user_id),
    INDEX idx_wa_conversations_phone (tenant_id, whatsapp_phone_normalized),
    INDEX idx_wa_conversations_status (tenant_id, status),
    INDEX idx_wa_conversations_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wa_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    conversation_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    direction ENUM('inbound','outbound') NOT NULL,
    message_type ENUM('text','image','audio','video','document','interactive','system') NOT NULL DEFAULT 'text',
    provider_message_id VARCHAR(120) NULL,
    body TEXT NULL,
    payload JSON NULL,
    status ENUM('received','queued','sent','delivered','read','failed') NOT NULL DEFAULT 'received',
    sent_at DATETIME NULL,
    received_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_wa_messages_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_wa_messages_conversation
        FOREIGN KEY (conversation_id) REFERENCES wa_conversations(id),
    CONSTRAINT fk_wa_messages_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    INDEX idx_wa_messages_tenant_id (tenant_id),
    INDEX idx_wa_messages_conversation_id (conversation_id),
    INDEX idx_wa_messages_user_id (user_id),
    INDEX idx_wa_messages_direction (tenant_id, direction),
    INDEX idx_wa_messages_created_at (created_at),
    INDEX idx_wa_messages_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agent_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    conversation_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    request_type VARCHAR(80) NOT NULL,
    input_payload JSON NOT NULL,
    status ENUM('received','processing','completed','failed','cancelled') NOT NULL DEFAULT 'received',
    idempotency_key VARCHAR(120) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_agent_requests_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agent_requests_conversation
        FOREIGN KEY (conversation_id) REFERENCES wa_conversations(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agent_requests_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_agent_requests_idempotency (tenant_id, idempotency_key),
    INDEX idx_agent_requests_tenant_id (tenant_id),
    INDEX idx_agent_requests_conversation_id (conversation_id),
    INDEX idx_agent_requests_user_id (user_id),
    INDEX idx_agent_requests_status (tenant_id, status),
    INDEX idx_agent_requests_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agent_responses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    request_id BIGINT UNSIGNED NOT NULL,
    conversation_id BIGINT UNSIGNED NULL,
    output_payload JSON NOT NULL,
    status ENUM('created','sent','failed') NOT NULL DEFAULT 'created',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_agent_responses_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agent_responses_request
        FOREIGN KEY (request_id) REFERENCES agent_requests(id),
    CONSTRAINT fk_agent_responses_conversation
        FOREIGN KEY (conversation_id) REFERENCES wa_conversations(id)
        ON DELETE SET NULL,
    INDEX idx_agent_responses_tenant_id (tenant_id),
    INDEX idx_agent_responses_request_id (request_id),
    INDEX idx_agent_responses_conversation_id (conversation_id),
    INDEX idx_agent_responses_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agent_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    request_id BIGINT UNSIGNED NULL,
    response_id BIGINT UNSIGNED NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    action_code VARCHAR(120) NOT NULL,
    target_table VARCHAR(120) NULL,
    target_id BIGINT UNSIGNED NULL,
    input_payload JSON NULL,
    result_payload JSON NULL,
    status ENUM('pending','approved','executed','rejected','failed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    executed_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_agent_actions_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agent_actions_request
        FOREIGN KEY (request_id) REFERENCES agent_requests(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agent_actions_response
        FOREIGN KEY (response_id) REFERENCES agent_responses(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agent_actions_actor
        FOREIGN KEY (actor_user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    INDEX idx_agent_actions_tenant_id (tenant_id),
    INDEX idx_agent_actions_request_id (request_id),
    INDEX idx_agent_actions_response_id (response_id),
    INDEX idx_agent_actions_actor_user_id (actor_user_id),
    INDEX idx_agent_actions_status (tenant_id, status),
    INDEX idx_agent_actions_target (target_table, target_id),
    INDEX idx_agent_actions_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agent_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    request_id BIGINT UNSIGNED NULL,
    conversation_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(120) NOT NULL,
    event_description VARCHAR(255) NOT NULL,
    action VARCHAR(120) NOT NULL,
    result ENUM('success','failed','denied') NOT NULL,
    subject_type VARCHAR(80) NULL,
    subject_id BIGINT UNSIGNED NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_agent_audit_logs_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),
    CONSTRAINT fk_agent_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agent_audit_logs_request
        FOREIGN KEY (request_id) REFERENCES agent_requests(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_agent_audit_logs_conversation
        FOREIGN KEY (conversation_id) REFERENCES wa_conversations(id)
        ON DELETE SET NULL,
    INDEX idx_agent_audit_logs_tenant_id (tenant_id),
    INDEX idx_agent_audit_logs_user_id (user_id),
    INDEX idx_agent_audit_logs_event_type (event_type),
    INDEX idx_agent_audit_logs_action (tenant_id, action),
    INDEX idx_agent_audit_logs_subject (subject_type, subject_id),
    INDEX idx_agent_audit_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Source: database\migrations\007_agent_core.sql
-- ============================================================

-- FeActiva Iglesia SaaS
-- Migration: 007_agent_core
-- Scope: Agent request processing core.

SET @db_name = DATABASE();

SET @column_exists = (
    SELECT COUNT(1)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'agent_requests'
      AND COLUMN_NAME = 'source'
);
SET @sql = IF(@column_exists = 0, 'ALTER TABLE agent_requests ADD COLUMN source VARCHAR(50) NULL AFTER user_id', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(1)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'agent_requests'
      AND COLUMN_NAME = 'input_text'
);
SET @sql = IF(@column_exists = 0, 'ALTER TABLE agent_requests ADD COLUMN input_text TEXT NULL AFTER input_payload', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(1)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'agent_requests'
      AND COLUMN_NAME = 'normalized_intent'
);
SET @sql = IF(@column_exists = 0, 'ALTER TABLE agent_requests ADD COLUMN normalized_intent VARCHAR(80) NULL AFTER input_text', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(1)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'agent_requests'
      AND INDEX_NAME = 'idx_agent_requests_source'
);
SET @sql = IF(@index_exists = 0, 'ALTER TABLE agent_requests ADD INDEX idx_agent_requests_source (tenant_id, source)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(1)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'agent_requests'
      AND INDEX_NAME = 'idx_agent_requests_intent'
);
SET @sql = IF(@index_exists = 0, 'ALTER TABLE agent_requests ADD INDEX idx_agent_requests_intent (tenant_id, normalized_intent)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(1)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'agent_responses'
      AND COLUMN_NAME = 'response_text'
);
SET @sql = IF(@column_exists = 0, 'ALTER TABLE agent_responses ADD COLUMN response_text TEXT NULL AFTER output_payload', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- Source: database\migrations\008_agent_tools.sql
-- ============================================================

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

-- ============================================================
-- Source: database\migrations\009_agenda_recordatorios.sql
-- ============================================================

-- FeActiva Iglesia SaaS
-- Migration: 009_agenda_recordatorios
-- Scope: Agenda reminders and agent action confirmation flag.

INSERT INTO saas_modules (code, name, description, module_group, is_core, is_active, sort_order)
VALUES ('agenda', 'Agenda y recordatorios', 'Recordatorios personales y ministeriales.', 'operativo', 0, 1, 40)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    module_group = VALUES(module_group),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order);

CREATE TABLE IF NOT EXISTS agenda_recordatorios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    persona_id BIGINT UNSIGNED NULL,
    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,
    fecha_hora DATETIME NOT NULL,
    estado ENUM('pendiente','completado','cancelado') NOT NULL DEFAULT 'pendiente',
    modulo_origen VARCHAR(80) NULL,
    referencia_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_agenda_recordatorios_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_agenda_recordatorios_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_agenda_recordatorios_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,
    INDEX idx_agenda_recordatorios_tenant_id (tenant_id),
    INDEX idx_agenda_recordatorios_user_id (user_id),
    INDEX idx_agenda_recordatorios_persona_id (persona_id),
    INDEX idx_agenda_recordatorios_fecha (tenant_id, fecha_hora),
    INDEX idx_agenda_recordatorios_estado (tenant_id, estado),
    INDEX idx_agenda_recordatorios_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE agent_actions
    ADD COLUMN IF NOT EXISTS requires_confirmation TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

INSERT INTO auth_permissions (module_id, code, name, description)
SELECT m.id, p.code, p.name, p.description
FROM saas_modules m
INNER JOIN (
    SELECT 'agenda.recordatorios.ver' AS code, 'Ver recordatorios' AS name, 'Permite ver recordatorios de agenda' AS description
    UNION ALL SELECT 'agenda.recordatorios.crear', 'Crear recordatorios', 'Permite crear recordatorios de agenda'
    UNION ALL SELECT 'agenda.recordatorios.editar', 'Editar recordatorios', 'Permite editar recordatorios de agenda'
    UNION ALL SELECT 'agenda.recordatorios.completar', 'Completar recordatorios', 'Permite completar recordatorios de agenda'
) p
WHERE m.code = 'agenda'
ON DUPLICATE KEY UPDATE
    module_id = VALUES(module_id),
    name = VALUES(name),
    description = VALUES(description);

INSERT INTO saas_tenant_modules (tenant_id, module_id, is_enabled, enabled_at)
SELECT 1, m.id, 1, UTC_TIMESTAMP()
FROM saas_modules m
WHERE m.code = 'agenda'
ON DUPLICATE KEY UPDATE
    is_enabled = VALUES(is_enabled),
    enabled_at = VALUES(enabled_at),
    disabled_at = NULL;

INSERT IGNORE INTO auth_role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM auth_roles r
INNER JOIN auth_permissions p
    ON p.code IN (
        'agenda.recordatorios.ver',
        'agenda.recordatorios.crear',
        'agenda.recordatorios.editar',
        'agenda.recordatorios.completar'
    )
WHERE r.code = 'admin_iglesia'
  AND r.tenant_id IS NULL
  AND r.deleted_at IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
