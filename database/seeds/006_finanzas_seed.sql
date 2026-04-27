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
