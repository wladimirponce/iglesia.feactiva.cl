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
