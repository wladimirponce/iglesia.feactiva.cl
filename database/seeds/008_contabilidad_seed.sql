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
