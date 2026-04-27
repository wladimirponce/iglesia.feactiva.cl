# FEACTIVA IGLESIA SAAS — TABLAS CONTABILIDAD FORMAL

## 1. Objetivo

Definir el modelo contable formal de FeActiva Iglesia SaaS.

Este módulo gestiona:

- Plan de cuentas
- Asientos contables
- Doble partida
- Libro diario
- Libro mayor
- Balance
- Estado de resultados
- Flujo de caja
- Centros de costo contables
- Configuración contable por país
- Exportación para contador

---

## 2. Principios contables

1. Contabilidad depende de Finanzas.
2. Finanzas registra movimientos operativos.
3. Contabilidad registra hechos contables formales.
4. Todo asiento debe tener al menos dos líneas.
5. Todo asiento debe cuadrar:
   - Total debe = total haber.
6. Los asientos no deben eliminarse físicamente.
7. Los asientos aprobados no deben editarse.
8. Para corregir un asiento aprobado se debe crear asiento de reversa o ajuste.
9. El plan de cuentas debe ser configurable por tenant.
10. El sistema debe ser adaptable a Chile, Estados Unidos y otros países.

---

# 3. TABLAS CONTABLES

## 3.1 Configuración contable del tenant

```sql
CREATE TABLE acct_configuracion (
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
3.2 Plan de cuentas
CREATE TABLE acct_cuentas (
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
3.3 Períodos contables
CREATE TABLE acct_periodos (
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
3.4 Asientos contables
CREATE TABLE acct_asientos (
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
3.5 Detalle de asiento contable
CREATE TABLE acct_asiento_detalles (
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
3.6 Mapeo finanzas-contabilidad

Permite que movimientos financieros generen asientos contables automáticamente.

CREATE TABLE acct_mapeo_finanzas (
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
4. DATOS INICIALES RECOMENDADOS
4.1 Configuración contable inicial
INSERT INTO acct_configuracion
(tenant_id, pais_codigo, moneda_base, norma_contable, periodo_inicio_mes, usa_centros_costo, requiere_aprobacion_asientos, numeracion_automatica, created_by)
VALUES
(:tenant_id, :country_code, :currency_code, 'GENERAL', 1, 1, 1, 1, :user_id);
4.2 Plan de cuentas base simplificado

Este plan es base y debe poder modificarse por tenant.

INSERT INTO acct_cuentas
(tenant_id, codigo, nombre, tipo, naturaleza, cuenta_padre_id, nivel, es_movimiento, es_sistema, es_activa, created_by)
VALUES
(:tenant_id, '1', 'Activos', 'activo', 'deudora', NULL, 1, 0, 1, 1, :user_id),
(:tenant_id, '2', 'Pasivos', 'pasivo', 'acreedora', NULL, 1, 0, 1, 1, :user_id),
(:tenant_id, '3', 'Patrimonio / Fondos', 'patrimonio', 'acreedora', NULL, 1, 0, 1, 1, :user_id),
(:tenant_id, '4', 'Ingresos', 'ingreso', 'acreedora', NULL, 1, 0, 1, 1, :user_id),
(:tenant_id, '5', 'Gastos', 'gasto', 'deudora', NULL, 1, 0, 1, 1, :user_id);

Luego Codex debe insertar cuentas hijas obteniendo los IDs de las cuentas padre.

Ejemplo lógico:

-- Activos
-- 1.1 Caja
-- 1.2 Bancos
-- 1.3 Cuentas por cobrar

-- Pasivos
-- 2.1 Cuentas por pagar
-- 2.2 Obligaciones laborales

-- Patrimonio / Fondos
-- 3.1 Fondo general
-- 3.2 Fondos restringidos
-- 3.3 Resultados acumulados

-- Ingresos
-- 4.1 Diezmos
-- 4.2 Ofrendas
-- 4.3 Donaciones
-- 4.4 Ingresos por cursos
-- 4.5 Otros ingresos

-- Gastos
-- 5.1 Arriendo
-- 5.2 Servicios básicos
-- 5.3 Sueldos y honorarios
-- 5.4 Ayuda social
-- 5.5 Misiones
-- 5.6 Materiales
-- 5.7 Administración
5. CONSULTAS BASE
5.1 Libro diario
SELECT
    a.numero,
    a.fecha_asiento,
    a.descripcion AS asiento_descripcion,
    a.origen,
    a.estado,
    c.codigo AS cuenta_codigo,
    c.nombre AS cuenta_nombre,
    d.descripcion AS detalle_descripcion,
    d.debe,
    d.haber
FROM acct_asientos a
INNER JOIN acct_asiento_detalles d
    ON d.asiento_id = a.id
    AND d.tenant_id = a.tenant_id
INNER JOIN acct_cuentas c
    ON c.id = d.cuenta_id
    AND c.tenant_id = a.tenant_id
WHERE a.tenant_id = :tenant_id
  AND a.fecha_asiento BETWEEN :fecha_inicio AND :fecha_fin
  AND a.estado = 'aprobado'
ORDER BY a.fecha_asiento, a.numero, d.id;
5.2 Libro mayor por cuenta
SELECT
    c.codigo,
    c.nombre,
    a.fecha_asiento,
    a.numero,
    a.descripcion,
    d.debe,
    d.haber
FROM acct_asiento_detalles d
INNER JOIN acct_asientos a
    ON a.id = d.asiento_id
    AND a.tenant_id = d.tenant_id
INNER JOIN acct_cuentas c
    ON c.id = d.cuenta_id
    AND c.tenant_id = d.tenant_id
WHERE d.tenant_id = :tenant_id
  AND d.cuenta_id = :cuenta_id
  AND a.estado = 'aprobado'
  AND a.fecha_asiento BETWEEN :fecha_inicio AND :fecha_fin
ORDER BY a.fecha_asiento, a.numero;
5.3 Balance de comprobación
SELECT
    c.codigo,
    c.nombre,
    c.tipo,
    SUM(d.debe) AS total_debe,
    SUM(d.haber) AS total_haber,
    SUM(d.debe - d.haber) AS saldo_deudor,
    SUM(d.haber - d.debe) AS saldo_acreedor
FROM acct_asiento_detalles d
INNER JOIN acct_asientos a
    ON a.id = d.asiento_id
    AND a.tenant_id = d.tenant_id
INNER JOIN acct_cuentas c
    ON c.id = d.cuenta_id
    AND c.tenant_id = d.tenant_id
WHERE d.tenant_id = :tenant_id
  AND a.estado = 'aprobado'
  AND a.fecha_asiento BETWEEN :fecha_inicio AND :fecha_fin
GROUP BY c.id, c.codigo, c.nombre, c.tipo
ORDER BY c.codigo;
5.4 Estado de resultados
SELECT
    c.tipo,
    c.codigo,
    c.nombre,
    SUM(d.haber - d.debe) AS saldo
FROM acct_asiento_detalles d
INNER JOIN acct_asientos a
    ON a.id = d.asiento_id
    AND a.tenant_id = d.tenant_id
INNER JOIN acct_cuentas c
    ON c.id = d.cuenta_id
    AND c.tenant_id = d.tenant_id
WHERE d.tenant_id = :tenant_id
  AND a.estado = 'aprobado'
  AND c.tipo IN ('ingreso', 'gasto')
  AND a.fecha_asiento BETWEEN :fecha_inicio AND :fecha_fin
GROUP BY c.id, c.tipo, c.codigo, c.nombre
ORDER BY c.codigo;
6. ENDPOINTS ESPERADOS
6.1 Configuración contable
GET   /api/contabilidad/configuracion
PATCH /api/contabilidad/configuracion
6.2 Plan de cuentas
GET    /api/contabilidad/cuentas
GET    /api/contabilidad/cuentas/{id}
POST   /api/contabilidad/cuentas
PATCH  /api/contabilidad/cuentas/{id}
DELETE /api/contabilidad/cuentas/{id}
6.3 Períodos contables
GET   /api/contabilidad/periodos
POST  /api/contabilidad/periodos
PATCH /api/contabilidad/periodos/{id}
POST  /api/contabilidad/periodos/{id}/cerrar
POST  /api/contabilidad/periodos/{id}/abrir
6.4 Asientos
GET   /api/contabilidad/asientos
GET   /api/contabilidad/asientos/{id}
POST  /api/contabilidad/asientos
PATCH /api/contabilidad/asientos/{id}
POST  /api/contabilidad/asientos/{id}/aprobar
POST  /api/contabilidad/asientos/{id}/anular
POST  /api/contabilidad/asientos/{id}/reversar
6.5 Reportes contables
GET /api/contabilidad/reportes/libro-diario
GET /api/contabilidad/reportes/libro-mayor
GET /api/contabilidad/reportes/balance-comprobacion
GET /api/contabilidad/reportes/estado-resultados
GET /api/contabilidad/reportes/balance-general
GET /api/contabilidad/reportes/flujo-caja
6.6 Mapeo finanzas-contabilidad
GET   /api/contabilidad/mapeo-finanzas
POST  /api/contabilidad/mapeo-finanzas
PATCH /api/contabilidad/mapeo-finanzas/{id}
DELETE /api/contabilidad/mapeo-finanzas/{id}
7. PERMISOS REQUERIDOS
acct.configuracion.ver
acct.configuracion.editar

acct.cuentas.ver
acct.cuentas.crear
acct.cuentas.editar
acct.cuentas.eliminar

acct.periodos.ver
acct.periodos.crear
acct.periodos.editar
acct.periodos.cerrar
acct.periodos.abrir

acct.asientos.ver
acct.asientos.crear
acct.asientos.editar
acct.asientos.aprobar
acct.asientos.anular
acct.asientos.reversar

acct.reportes.ver
acct.reportes.exportar

acct.mapeo.ver
acct.mapeo.crear
acct.mapeo.editar
acct.mapeo.eliminar
8. REGLAS DE NEGOCIO
8.1 Plan de cuentas
Cada tenant tiene su propio plan de cuentas.
El plan base se puede cargar automáticamente al crear la iglesia.
Las cuentas padre no reciben movimientos si es_movimiento = 0.
Las cuentas con movimientos no deben eliminarse.
Las cuentas deben poder desactivarse.
El código contable debe ser único por tenant.
8.2 Asientos contables
Todo asiento debe pertenecer a un tenant.
Todo asiento debe tener número único por tenant.
Todo asiento debe tener fecha.
Todo asiento debe tener al menos dos líneas.
Todo asiento debe cuadrar antes de aprobarse.
Un asiento aprobado no se edita.
Un asiento aprobado solo se puede:
anular
reversar
ajustar con nuevo asiento
Un asiento en período cerrado no se puede modificar.
Todo asiento debe auditarse.
8.3 Doble partida

Antes de aprobar un asiento:

SELECT
    SUM(debe) AS total_debe,
    SUM(haber) AS total_haber
FROM acct_asiento_detalles
WHERE tenant_id = :tenant_id
  AND asiento_id = :asiento_id;

Validación obligatoria:

total_debe == total_haber
total_debe > 0
total_haber > 0
8.4 Asientos desde Finanzas

Si el módulo Contabilidad está activo:

Un movimiento financiero puede generar un asiento.
La generación debe usar acct_mapeo_finanzas.
Si no existe mapeo, el movimiento queda pendiente de contabilizar.
La anulación financiera debe generar reversa contable si el asiento fue aprobado.
Finanzas no debe editar directamente asientos contables.
8.5 Períodos contables
Los períodos pueden estar abiertos, cerrados o bloqueados.
No se pueden crear ni modificar asientos en períodos cerrados.
Solo usuarios autorizados pueden cerrar períodos.
Reabrir un período debe requerir permiso especial y auditoría.
8.6 Cumplimiento internacional

El sistema no debe codificar reglas rígidas de un solo país.

Debe permitir configurar:

País
Moneda base
Norma contable referencial
Período fiscal
Plan de cuentas local
Formatos de exportación

La exportación legal específica por país debe implementarse en módulos posteriores.

9. VALIDACIONES BACKEND

Antes de ejecutar cualquier endpoint de Contabilidad:

Validar usuario autenticado.
Validar tenant activo.
Validar módulo contabilidad activo.
Validar permiso requerido.
Validar que cuentas pertenezcan al tenant.
Validar que centros de costo pertenezcan al tenant.
Validar período abierto.
Validar doble partida.
Usar transacción para crear, aprobar, anular o reversar asientos.
Registrar auditoría.
Usar prepared statements.
No mostrar errores SQL al usuario final.
10. UI SUGERIDA
10.1 Menú Contabilidad
Resumen
Plan de cuentas
Asientos
Períodos
Mapeo Finanzas
Libro diario
Libro mayor
Balance
Estado de resultados
Flujo de caja
Configuración
10.2 Dashboard Contable

Indicadores:

Período actual
Asientos borrador
Asientos aprobados
Movimientos financieros pendientes de contabilizar
Total debe/haber del período
Resultado del período
Períodos abiertos
Alertas contables
11. AUDITORÍA

Debe auditarse:

Creación de cuenta contable
Edición de cuenta contable
Desactivación de cuenta
Creación de asiento
Edición de asiento borrador
Aprobación de asiento
Anulación de asiento
Reversa de asiento
Cierre de período
Reapertura de período
Cambios de configuración contable
Cambios en mapeo finanzas-contabilidad

Acciones sugeridas:

acct.cuenta.created
acct.cuenta.updated
acct.cuenta.disabled
acct.asiento.created
acct.asiento.updated
acct.asiento.approved
acct.asiento.cancelled
acct.asiento.reversed
acct.periodo.closed
acct.periodo.reopened
acct.config.updated
acct.mapeo.created
acct.mapeo.updated
12. CRITERIO DE ÉXITO

El módulo Contabilidad estará correctamente implementado cuando:

Cada iglesia tenga su propio plan de cuentas.
Se puedan crear asientos manuales.
Se puedan generar asientos desde Finanzas.
Todo asiento aprobado cuadre en debe y haber.
Se pueda consultar libro diario.
Se pueda consultar libro mayor.
Se pueda generar balance de comprobación.
Se pueda generar estado de resultados.
Se respeten tenant, permisos y módulo activo.
No se puedan modificar períodos cerrados.
Toda acción crítica quede auditada.