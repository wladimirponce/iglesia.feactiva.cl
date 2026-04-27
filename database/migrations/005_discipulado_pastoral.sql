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
