# FEACTIVA IGLESIA SAAS — TABLAS FINANZAS BÁSICAS

## 1. Objetivo

Definir el modelo de datos del módulo Finanzas de FeActiva Iglesia SaaS.

Este módulo gestiona:

- Ingresos
- Egresos
- Diezmos
- Ofrendas
- Donaciones
- Campañas
- Caja
- Bancos
- Centros de costo
- Comprobantes
- Anulación de movimientos

---

## 2. Principios

1. Finanzas pertenece siempre a un `tenant_id`.
2. Finanzas puede funcionar sin Contabilidad.
3. Finanzas puede relacionarse opcionalmente con CRM mediante `persona_id`.
4. Un movimiento financiero no se borra físicamente: se anula.
5. Toda operación financiera debe ser auditable.
6. Toda operación crítica debe ejecutarse dentro de transacción.
7. Todo monto debe registrarse con moneda.
8. Todo movimiento debe tener fecha real y fecha contable.

---

# 3. TABLAS FINANCIERAS

## 3.1 Cuentas financieras

Representa caja, bancos o cuentas internas.

```sql
CREATE TABLE fin_cuentas (
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
3.2 Categorías financieras

Clasifica ingresos y egresos.

CREATE TABLE fin_categorias (
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
3.3 Centros de costo

Permite separar fondos por ministerio, proyecto o área.

CREATE TABLE fin_centros_costo (
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
3.4 Campañas financieras

Para campañas especiales: construcción, misiones, ayuda social, etc.

CREATE TABLE fin_campanas (
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
3.5 Movimientos financieros

Tabla central del módulo.

CREATE TABLE fin_movimientos (
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
3.6 Documentos financieros

Adjuntos o referencias de comprobantes.

CREATE TABLE fin_documentos (
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
3.7 Presupuestos
CREATE TABLE fin_presupuestos (
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
3.8 Detalle de presupuesto
CREATE TABLE fin_presupuesto_detalles (
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
4. DATOS INICIALES RECOMENDADOS

Codex debe crear estas categorías al crear una nueva iglesia.

4.1 Categorías de ingreso
INSERT INTO fin_categorias
(tenant_id, tipo, codigo, nombre, descripcion, es_sistema, es_activa, orden)
VALUES
(:tenant_id, 'ingreso', 'diezmo', 'Diezmo', 'Ingresos por diezmos.', 1, 1, 1),
(:tenant_id, 'ingreso', 'ofrenda', 'Ofrenda', 'Ingresos por ofrendas generales.', 1, 1, 2),
(:tenant_id, 'ingreso', 'donacion', 'Donación', 'Donaciones especiales.', 1, 1, 3),
(:tenant_id, 'ingreso', 'misiones', 'Misiones', 'Aportes destinados a misiones.', 1, 1, 4),
(:tenant_id, 'ingreso', 'campana', 'Campaña especial', 'Aportes para campañas o proyectos especiales.', 1, 1, 5),
(:tenant_id, 'ingreso', 'curso', 'Curso / formación', 'Ingresos por cursos o formación.', 1, 1, 6),
(:tenant_id, 'ingreso', 'otro_ingreso', 'Otro ingreso', 'Otros ingresos.', 1, 1, 99);
4.2 Categorías de egreso
INSERT INTO fin_categorias
(tenant_id, tipo, codigo, nombre, descripcion, es_sistema, es_activa, orden)
VALUES
(:tenant_id, 'egreso', 'arriendo', 'Arriendo', 'Pago de arriendo o alquiler.', 1, 1, 1),
(:tenant_id, 'egreso', 'servicios_basicos', 'Servicios básicos', 'Luz, agua, gas, internet y similares.', 1, 1, 2),
(:tenant_id, 'egreso', 'sueldos_honorarios', 'Sueldos y honorarios', 'Pagos a personal, ministros o profesionales.', 1, 1, 3),
(:tenant_id, 'egreso', 'ayuda_social', 'Ayuda social', 'Apoyo económico o material a personas o familias.', 1, 1, 4),
(:tenant_id, 'egreso', 'misiones', 'Misiones', 'Gastos asociados a misiones.', 1, 1, 5),
(:tenant_id, 'egreso', 'materiales', 'Materiales', 'Compra de materiales para ministerios o actividades.', 1, 1, 6),
(:tenant_id, 'egreso', 'mantencion', 'Mantención', 'Reparaciones y mantención de infraestructura.', 1, 1, 7),
(:tenant_id, 'egreso', 'administracion', 'Administración', 'Gastos administrativos generales.', 1, 1, 8),
(:tenant_id, 'egreso', 'otro_egreso', 'Otro egreso', 'Otros egresos.', 1, 1, 99);
4.3 Cuenta caja principal
INSERT INTO fin_cuentas
(tenant_id, nombre, tipo, moneda, saldo_inicial, fecha_saldo_inicial, es_principal, es_activa, created_by)
VALUES
(:tenant_id, 'Caja principal', 'caja', :currency_code, 0.00, CURDATE(), 1, 1, :user_id);
4.4 Centros de costo iniciales
INSERT INTO fin_centros_costo
(tenant_id, codigo, nombre, descripcion, es_activo, created_by)
VALUES
(:tenant_id, 'general', 'General', 'Centro de costo general de la iglesia.', 1, :user_id),
(:tenant_id, 'misiones', 'Misiones', 'Fondos y gastos asociados a misiones.', 1, :user_id),
(:tenant_id, 'ayuda_social', 'Ayuda social', 'Fondos y gastos de ayuda social.', 1, :user_id),
(:tenant_id, 'ninos', 'Niños', 'Ministerio de niños.', 1, :user_id),
(:tenant_id, 'jovenes', 'Jóvenes', 'Ministerio de jóvenes.', 1, :user_id);
5. CONSULTAS BASE
5.1 Listar movimientos
SELECT
    m.id,
    m.tipo,
    m.subtipo,
    m.descripcion,
    m.monto,
    m.moneda,
    m.fecha_movimiento,
    m.fecha_contable,
    m.medio_pago,
    m.estado,
    c.nombre AS cuenta_nombre,
    cat.nombre AS categoria_nombre,
    cc.nombre AS centro_costo_nombre,
    CONCAT(p.nombres, ' ', p.apellidos) AS persona_nombre
FROM fin_movimientos m
INNER JOIN fin_cuentas c
    ON c.id = m.cuenta_id
    AND c.tenant_id = m.tenant_id
INNER JOIN fin_categorias cat
    ON cat.id = m.categoria_id
    AND cat.tenant_id = m.tenant_id
LEFT JOIN fin_centros_costo cc
    ON cc.id = m.centro_costo_id
    AND cc.tenant_id = m.tenant_id
LEFT JOIN crm_personas p
    ON p.id = m.persona_id
    AND p.tenant_id = m.tenant_id
WHERE m.tenant_id = :tenant_id
  AND m.estado <> 'anulado'
ORDER BY m.fecha_movimiento DESC, m.id DESC
LIMIT :limit OFFSET :offset;
5.2 Resumen ingresos/egresos por período
SELECT
    tipo,
    moneda,
    SUM(monto) AS total
FROM fin_movimientos
WHERE tenant_id = :tenant_id
  AND estado <> 'anulado'
  AND fecha_movimiento BETWEEN :fecha_inicio AND :fecha_fin
GROUP BY tipo, moneda;
5.3 Saldo por cuenta
SELECT
    c.id,
    c.nombre,
    c.tipo,
    c.moneda,
    c.saldo_inicial
    + COALESCE(SUM(
        CASE
            WHEN m.tipo = 'ingreso' AND m.estado <> 'anulado' THEN m.monto
            WHEN m.tipo = 'egreso' AND m.estado <> 'anulado' THEN -m.monto
            ELSE 0
        END
    ), 0) AS saldo_actual
FROM fin_cuentas c
LEFT JOIN fin_movimientos m
    ON m.cuenta_id = c.id
    AND m.tenant_id = c.tenant_id
WHERE c.tenant_id = :tenant_id
  AND c.deleted_at IS NULL
GROUP BY c.id, c.nombre, c.tipo, c.moneda, c.saldo_inicial
ORDER BY c.nombre;
5.4 Donaciones por persona
SELECT
    p.id,
    CONCAT(p.nombres, ' ', p.apellidos) AS persona,
    SUM(m.monto) AS total_donado,
    COUNT(m.id) AS cantidad_movimientos
FROM fin_movimientos m
INNER JOIN crm_personas p
    ON p.id = m.persona_id
    AND p.tenant_id = m.tenant_id
WHERE m.tenant_id = :tenant_id
  AND m.tipo = 'ingreso'
  AND m.estado <> 'anulado'
  AND m.fecha_movimiento BETWEEN :fecha_inicio AND :fecha_fin
GROUP BY p.id, p.nombres, p.apellidos
ORDER BY total_donado DESC;
6. ENDPOINTS ESPERADOS
6.1 Cuentas
GET    /api/finanzas/cuentas
POST   /api/finanzas/cuentas
PATCH  /api/finanzas/cuentas/{id}
DELETE /api/finanzas/cuentas/{id}
6.2 Categorías
GET    /api/finanzas/categorias
POST   /api/finanzas/categorias
PATCH  /api/finanzas/categorias/{id}
DELETE /api/finanzas/categorias/{id}
6.3 Centros de costo
GET    /api/finanzas/centros-costo
POST   /api/finanzas/centros-costo
PATCH  /api/finanzas/centros-costo/{id}
DELETE /api/finanzas/centros-costo/{id}
6.4 Movimientos
GET    /api/finanzas/movimientos
GET    /api/finanzas/movimientos/{id}
POST   /api/finanzas/movimientos
PATCH  /api/finanzas/movimientos/{id}
POST   /api/finanzas/movimientos/{id}/anular
6.5 Documentos
GET    /api/finanzas/movimientos/{id}/documentos
POST   /api/finanzas/movimientos/{id}/documentos
DELETE /api/finanzas/documentos/{id}
6.6 Presupuestos
GET    /api/finanzas/presupuestos
POST   /api/finanzas/presupuestos
PATCH  /api/finanzas/presupuestos/{id}
DELETE /api/finanzas/presupuestos/{id}
GET    /api/finanzas/presupuestos/{id}/detalles
POST   /api/finanzas/presupuestos/{id}/detalles
PATCH  /api/finanzas/presupuestos/{id}/detalles/{detalle_id}
DELETE /api/finanzas/presupuestos/{id}/detalles/{detalle_id}
7. PERMISOS REQUERIDOS
fin.cuentas.ver
fin.cuentas.crear
fin.cuentas.editar
fin.cuentas.eliminar

fin.categorias.ver
fin.categorias.crear
fin.categorias.editar
fin.categorias.eliminar

fin.centros_costo.ver
fin.centros_costo.crear
fin.centros_costo.editar
fin.centros_costo.eliminar

fin.movimientos.ver
fin.movimientos.crear
fin.movimientos.editar
fin.movimientos.anular

fin.documentos.ver
fin.documentos.crear
fin.documentos.eliminar

fin.presupuestos.ver
fin.presupuestos.crear
fin.presupuestos.editar
fin.presupuestos.eliminar
8. REGLAS DE NEGOCIO
8.1 Movimientos financieros
Todo movimiento debe tener cuenta.
Todo movimiento debe tener categoría.
Todo movimiento debe tener tipo: ingreso o egreso.
Todo monto debe ser mayor que cero.
Todo movimiento debe tener fecha de movimiento y fecha contable.
No se permite eliminar movimientos financieros.
Para corregir un movimiento, se debe anular y crear uno nuevo.
Toda anulación debe registrar:
motivo
usuario
fecha
Si el movimiento tiene documentos asociados, deben mantenerse.
Si el módulo Contabilidad está activo, la creación de movimientos podrá disparar asiento contable según configuración futura.
8.2 Ingresos

Subtipos recomendados:

diezmo
ofrenda
donacion
campana
misiones
curso
otro

La persona donante es opcional.

8.3 Egresos

Subtipos recomendados:

arriendo
servicios_basicos
sueldos_honorarios
ayuda_social
misiones
materiales
mantencion
administracion
otro
8.4 Cuentas
Cada tenant debe tener al menos una cuenta financiera.
Puede existir una cuenta principal.
No se puede eliminar físicamente una cuenta con movimientos.
Si una cuenta ya no se usa, debe marcarse inactiva.
8.5 Presupuestos
Un presupuesto pertenece a un período.
Puede tener líneas por categoría y centro de costo.
El presupuesto no bloquea movimientos; solo permite comparación.
Un presupuesto aprobado no debe modificarse sin permiso especial.
9. VALIDACIONES BACKEND

Antes de ejecutar cualquier endpoint de Finanzas:

Validar usuario autenticado.
Validar tenant activo.
Validar módulo finanzas activo.
Validar permiso requerido.
Validar que cuenta, categoría, centro de costo, campaña y persona pertenezcan al mismo tenant.
Validar monto positivo.
Validar moneda.
Usar transacción para crear, editar o anular movimientos.
Usar prepared statements.
Registrar auditoría.
10. UI SUGERIDA
10.1 Menú Finanzas
Resumen
Ingresos
Egresos
Cuentas
Centros de costo
Campañas
Presupuestos
Comprobantes
Reportes
10.2 Dashboard Finanzas

Indicadores:

Ingresos del mes
Egresos del mes
Saldo neto
Saldo por cuenta
Donaciones por categoría
Gastos por centro de costo
Presupuesto vs real
Movimientos recientes
10.3 Formularios
Registro de ingreso

Campos:

Cuenta
Categoría
Persona, opcional
Centro de costo
Campaña
Monto
Moneda
Fecha movimiento
Fecha contable
Medio de pago
Referencia
Comprobante
Observación
Registro de egreso

Campos:

Cuenta
Categoría
Centro de costo
Monto
Moneda
Fecha movimiento
Fecha contable
Medio de pago
Referencia
Documento
Observación
11. AUDITORÍA

Debe auditarse:

Creación de cuenta
Edición de cuenta
Desactivación de cuenta
Creación de categoría
Edición de categoría
Creación de movimiento
Edición de movimiento
Anulación de movimiento
Subida de documento
Eliminación de documento
Creación/aprobación/cierre de presupuesto

Ejemplo de acciones:

fin.cuenta.created
fin.cuenta.updated
fin.cuenta.disabled
fin.movimiento.created
fin.movimiento.updated
fin.movimiento.cancelled
fin.documento.uploaded
fin.presupuesto.created
fin.presupuesto.approved
12. CRITERIO DE ÉXITO

El módulo Finanzas estará correctamente implementado cuando:

Se puedan registrar ingresos.
Se puedan registrar egresos.
Se puedan clasificar por categoría.
Se puedan asociar a cuenta financiera.
Se puedan asociar a centro de costo.
Se puedan asociar a persona del CRM si corresponde.
Se pueda anular un movimiento sin eliminarlo.
Se pueda calcular saldo por cuenta.
Se puedan adjuntar comprobantes.
Se puedan crear presupuestos.
Se respeten tenant, permisos y módulos activos.
Toda acción crítica quede auditada.