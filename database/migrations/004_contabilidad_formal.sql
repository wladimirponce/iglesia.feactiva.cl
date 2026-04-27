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
