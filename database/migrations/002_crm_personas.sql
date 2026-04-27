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
