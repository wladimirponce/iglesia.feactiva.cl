# FEACTIVA IGLESIA SAAS — REPORTES, BI Y CUMPLIMIENTO LEGAL

## 1. Objetivo

Definir el modelo base para:

- Reportes operativos
- Reportes financieros
- Reportes contables
- Reportes ministeriales
- BI básico
- Exportaciones
- Cumplimiento legal
- Protección de datos
- Auditoría avanzada

Este módulo debe leer información de otros módulos, no modificar datos operativos.

---

# 2. PRINCIPIOS

1. Reportes no debe alterar información original.
2. Todo reporte debe respetar tenant_id.
3. Todo reporte debe validar permisos.
4. Toda exportación sensible debe auditarse.
5. Cumplimiento legal debe ser configurable por país.
6. Los reportes deben poder exportarse a PDF, Excel o CSV.
7. Las normas legales específicas no deben quedar quemadas en el código principal.
8. El sistema debe permitir adaptación para Chile, Estados Unidos y otros países.

---

# 3. TABLAS REPORTES Y BI

## 3.1 Catálogo de reportes

```sql
CREATE TABLE rep_reportes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(120) NOT NULL UNIQUE,
    nombre VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,
    modulo_codigo VARCHAR(50) NOT NULL,
    tipo ENUM('operativo','financiero','contable','ministerial','legal','bi') NOT NULL,
    requiere_permiso VARCHAR(120) NULL,
    es_activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_rep_reportes_modulo (modulo_codigo),
    INDEX idx_rep_reportes_tipo (tipo),
    INDEX idx_rep_reportes_activo (es_activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
3.2 Reportes favoritos por usuario
CREATE TABLE rep_reportes_favoritos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    reporte_id BIGINT UNSIGNED NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_rep_favoritos_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_rep_favoritos_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_rep_favoritos_reporte
        FOREIGN KEY (reporte_id) REFERENCES rep_reportes(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_rep_favorito (tenant_id, user_id, reporte_id),

    INDEX idx_rep_favoritos_tenant_id (tenant_id),
    INDEX idx_rep_favoritos_user_id (user_id),
    INDEX idx_rep_favoritos_reporte_id (reporte_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
3.3 Historial de generación de reportes
CREATE TABLE rep_reportes_historial (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    reporte_id BIGINT UNSIGNED NULL,

    codigo_reporte VARCHAR(120) NOT NULL,
    nombre_reporte VARCHAR(180) NOT NULL,

    filtros JSON NULL,
    formato ENUM('pantalla','pdf','excel','csv') NOT NULL DEFAULT 'pantalla',

    archivo_url VARCHAR(255) NULL,
    total_registros INT UNSIGNED NULL,

    generado_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_rep_historial_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_rep_historial_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_rep_historial_reporte
        FOREIGN KEY (reporte_id) REFERENCES rep_reportes(id)
        ON DELETE SET NULL,

    INDEX idx_rep_historial_tenant_id (tenant_id),
    INDEX idx_rep_historial_user_id (user_id),
    INDEX idx_rep_historial_codigo (codigo_reporte),
    INDEX idx_rep_historial_generado_at (generado_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
4. TABLAS CUMPLIMIENTO LEGAL
4.1 Configuración legal por tenant
CREATE TABLE legal_configuracion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    pais_codigo VARCHAR(10) NOT NULL DEFAULT 'CL',
    tipo_entidad ENUM(
        'iglesia',
        'corporacion',
        'fundacion',
        'asociacion',
        'nonprofit',
        'otro'
    ) NOT NULL DEFAULT 'iglesia',

    nombre_legal VARCHAR(200) NULL,
    identificador_tributario VARCHAR(80) NULL,

    requiere_consentimiento_datos TINYINT(1) NOT NULL DEFAULT 1,
    politica_privacidad_url VARCHAR(255) NULL,

    retencion_datos_anios INT UNSIGNED NULL,
    permite_exportacion_datos TINYINT(1) NOT NULL DEFAULT 1,
    permite_eliminacion_solicitada TINYINT(1) NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_legal_config_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_legal_config_tenant (tenant_id),

    INDEX idx_legal_config_tenant_id (tenant_id),
    INDEX idx_legal_config_pais (pais_codigo),
    INDEX idx_legal_config_tipo_entidad (tipo_entidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
4.2 Consentimientos de datos personales
CREATE TABLE legal_consentimientos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    persona_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,

    tipo_consentimiento ENUM(
        'tratamiento_datos',
        'comunicaciones',
        'uso_imagen',
        'datos_sensibles',
        'menores_edad',
        'otro'
    ) NOT NULL,

    estado ENUM('otorgado','revocado','pendiente') NOT NULL DEFAULT 'pendiente',

    descripcion TEXT NULL,
    fecha_otorgado DATETIME NULL,
    fecha_revocado DATETIME NULL,
    origen ENUM('app','web','manual','documento','otro') NOT NULL DEFAULT 'manual',

    documento_url VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_legal_consent_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_legal_consent_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_legal_consent_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_legal_consent_tenant_id (tenant_id),
    INDEX idx_legal_consent_persona_id (persona_id),
    INDEX idx_legal_consent_user_id (user_id),
    INDEX idx_legal_consent_tipo (tipo_consentimiento),
    INDEX idx_legal_consent_estado (estado),
    INDEX idx_legal_consent_fecha (fecha_otorgado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
4.3 Solicitudes de derechos de datos
CREATE TABLE legal_solicitudes_datos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    persona_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,

    tipo_solicitud ENUM(
        'acceso',
        'rectificacion',
        'eliminacion',
        'portabilidad',
        'oposicion',
        'revocacion_consentimiento',
        'otro'
    ) NOT NULL,

    estado ENUM('recibida','en_revision','resuelta','rechazada','cerrada') NOT NULL DEFAULT 'recibida',

    descripcion TEXT NULL,
    respuesta TEXT NULL,

    fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta DATETIME NULL,
    resuelto_by BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_legal_solicitudes_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_legal_solicitudes_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_legal_solicitudes_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_legal_solicitudes_resuelto_by
        FOREIGN KEY (resuelto_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_legal_solicitudes_tenant_id (tenant_id),
    INDEX idx_legal_solicitudes_persona_id (persona_id),
    INDEX idx_legal_solicitudes_user_id (user_id),
    INDEX idx_legal_solicitudes_tipo (tipo_solicitud),
    INDEX idx_legal_solicitudes_estado (estado),
    INDEX idx_legal_solicitudes_fecha (fecha_solicitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
4.4 Exportaciones legales
CREATE TABLE legal_exportaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    tipo_exportacion ENUM(
        'personas',
        'finanzas',
        'contabilidad',
        'auditoria',
        'consentimientos',
        'datos_personales',
        'otro'
    ) NOT NULL,

    formato ENUM('pdf','excel','csv','json') NOT NULL DEFAULT 'pdf',

    filtros JSON NULL,
    archivo_url VARCHAR(255) NULL,

    estado ENUM('generada','descargada','expirada','fallida') NOT NULL DEFAULT 'generada',

    generado_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    descargado_at DATETIME NULL,
    expira_at DATETIME NULL,

    generado_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_legal_export_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_legal_export_user
        FOREIGN KEY (generado_by) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_legal_export_tenant_id (tenant_id),
    INDEX idx_legal_export_tipo (tipo_exportacion),
    INDEX idx_legal_export_formato (formato),
    INDEX idx_legal_export_estado (estado),
    INDEX idx_legal_export_generado_at (generado_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
5. AUDITORÍA AVANZADA
5.1 Accesos a datos sensibles
CREATE TABLE audit_sensitive_access (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,

    modulo_codigo VARCHAR(50) NOT NULL,
    recurso_tipo VARCHAR(120) NOT NULL,
    recurso_id BIGINT UNSIGNED NULL,

    accion ENUM('view','export','download','print','share') NOT NULL DEFAULT 'view',

    motivo VARCHAR(255) NULL,

    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_sensitive_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_audit_sensitive_user
        FOREIGN KEY (user_id) REFERENCES auth_users(id)
        ON DELETE SET NULL,

    INDEX idx_audit_sensitive_tenant_id (tenant_id),
    INDEX idx_audit_sensitive_user_id (user_id),
    INDEX idx_audit_sensitive_modulo (modulo_codigo),
    INDEX idx_audit_sensitive_recurso (recurso_tipo, recurso_id),
    INDEX idx_audit_sensitive_accion (accion),
    INDEX idx_audit_sensitive_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
6. DATOS INICIALES RECOMENDADOS
6.1 Catálogo de reportes base
INSERT INTO rep_reportes
(codigo, nombre, descripcion, modulo_codigo, tipo, requiere_permiso)
VALUES
('crm_personas_general', 'Personas registradas', 'Listado general de personas del CRM.', 'crm', 'operativo', 'crm.personas.ver'),
('crm_membresia_resumen', 'Resumen de membresía', 'Resumen por estado de membresía.', 'crm', 'operativo', 'crm.personas.ver'),

('disc_avance_general', 'Avance de discipulado', 'Reporte de personas en rutas de discipulado.', 'discipulado', 'ministerial', 'disc.avance.ver'),

('past_casos_resumen', 'Resumen pastoral', 'Resumen de casos pastorales por estado y prioridad.', 'pastoral', 'ministerial', 'past.casos.ver'),

('fin_movimientos_periodo', 'Movimientos financieros por período', 'Ingresos y egresos por fecha.', 'finanzas', 'financiero', 'fin.movimientos.ver'),
('fin_resumen_categorias', 'Resumen financiero por categorías', 'Totales por categoría.', 'finanzas', 'financiero', 'fin.movimientos.ver'),
('fin_saldo_cuentas', 'Saldo por cuentas', 'Saldos financieros por cuenta.', 'finanzas', 'financiero', 'fin.cuentas.ver'),

('acct_libro_diario', 'Libro diario', 'Libro diario contable.', 'contabilidad', 'contable', 'acct.reportes.ver'),
('acct_libro_mayor', 'Libro mayor', 'Libro mayor por cuenta.', 'contabilidad', 'contable', 'acct.reportes.ver'),
('acct_balance_comprobacion', 'Balance de comprobación', 'Balance de comprobación del período.', 'contabilidad', 'contable', 'acct.reportes.ver'),
('acct_estado_resultados', 'Estado de resultados', 'Ingresos y gastos del período.', 'contabilidad', 'contable', 'acct.reportes.ver'),

('legal_consentimientos', 'Consentimientos', 'Reporte de consentimientos otorgados y revocados.', 'legal', 'legal', 'legal.consentimientos.ver'),
('audit_accesos_sensibles', 'Accesos sensibles', 'Reporte de acceso a datos sensibles.', 'legal', 'legal', 'audit.sensitive.ver');
7. ENDPOINTS ESPERADOS
7.1 Reportes
GET  /api/reportes
GET  /api/reportes/{codigo}
POST /api/reportes/{codigo}/generar
POST /api/reportes/{codigo}/exportar
GET  /api/reportes/historial
POST /api/reportes/{id}/favorito
DELETE /api/reportes/{id}/favorito
7.2 BI Dashboard
GET /api/bi/dashboard
GET /api/bi/crm
GET /api/bi/finanzas
GET /api/bi/discipulado
GET /api/bi/pastoral
7.3 Cumplimiento legal
GET   /api/legal/configuracion
PATCH /api/legal/configuracion

GET   /api/legal/consentimientos
POST  /api/legal/consentimientos
PATCH /api/legal/consentimientos/{id}/revocar

GET   /api/legal/solicitudes-datos
POST  /api/legal/solicitudes-datos
PATCH /api/legal/solicitudes-datos/{id}

POST  /api/legal/exportaciones
GET   /api/legal/exportaciones
GET   /api/legal/exportaciones/{id}/descargar
7.4 Auditoría
GET /api/auditoria/logs
GET /api/auditoria/accesos-sensibles
POST /api/auditoria/accesos-sensibles
8. PERMISOS REQUERIDOS
rep.reportes.ver
rep.reportes.generar
rep.reportes.exportar

bi.dashboard.ver
bi.crm.ver
bi.finanzas.ver
bi.discipulado.ver
bi.pastoral.ver

legal.configuracion.ver
legal.configuracion.editar
legal.consentimientos.ver
legal.consentimientos.crear
legal.consentimientos.revocar
legal.solicitudes.ver
legal.solicitudes.crear
legal.solicitudes.resolver
legal.exportaciones.generar
legal.exportaciones.descargar

audit.logs.ver
audit.sensitive.ver
9. CONSULTAS BASE
9.1 Dashboard general
SELECT
    (SELECT COUNT(*) FROM crm_personas p
     WHERE p.tenant_id = :tenant_id AND p.deleted_at IS NULL) AS total_personas,

    (SELECT COUNT(*) FROM crm_personas p
     WHERE p.tenant_id = :tenant_id AND p.estado_persona = 'miembro' AND p.deleted_at IS NULL) AS miembros,

    (SELECT COUNT(*) FROM crm_personas p
     WHERE p.tenant_id = :tenant_id AND p.estado_persona = 'visita' AND p.deleted_at IS NULL) AS visitas,

    (SELECT COALESCE(SUM(m.monto),0) FROM fin_movimientos m
     WHERE m.tenant_id = :tenant_id
       AND m.tipo = 'ingreso'
       AND m.estado <> 'anulado'
       AND MONTH(m.fecha_movimiento) = MONTH(CURDATE())
       AND YEAR(m.fecha_movimiento) = YEAR(CURDATE())) AS ingresos_mes,

    (SELECT COALESCE(SUM(m.monto),0) FROM fin_movimientos m
     WHERE m.tenant_id = :tenant_id
       AND m.tipo = 'egreso'
       AND m.estado <> 'anulado'
       AND MONTH(m.fecha_movimiento) = MONTH(CURDATE())
       AND YEAR(m.fecha_movimiento) = YEAR(CURDATE())) AS egresos_mes;
9.2 Resumen CRM por estado
SELECT
    estado_persona,
    COUNT(*) AS total
FROM crm_personas
WHERE tenant_id = :tenant_id
  AND deleted_at IS NULL
GROUP BY estado_persona
ORDER BY total DESC;
9.3 Resumen financiero por categoría
SELECT
    m.tipo,
    c.nombre AS categoria,
    SUM(m.monto) AS total
FROM fin_movimientos m
INNER JOIN fin_categorias c
    ON c.id = m.categoria_id
    AND c.tenant_id = m.tenant_id
WHERE m.tenant_id = :tenant_id
  AND m.estado <> 'anulado'
  AND m.fecha_movimiento BETWEEN :fecha_inicio AND :fecha_fin
GROUP BY m.tipo, c.nombre
ORDER BY m.tipo, total DESC;
9.4 Casos pastorales por prioridad
SELECT
    prioridad,
    estado,
    COUNT(*) AS total
FROM past_casos
WHERE tenant_id = :tenant_id
  AND deleted_at IS NULL
GROUP BY prioridad, estado
ORDER BY prioridad, estado;
10. REGLAS DE NEGOCIO
10.1 Reportes
Todo reporte debe validar tenant.
Todo reporte debe validar permiso.
Si el reporte usa un módulo inactivo, debe bloquearse.
Toda exportación debe registrarse.
Reportes no modifican información.
Los filtros usados deben guardarse en historial.
Los reportes sensibles deben registrar acceso en audit_sensitive_access.
10.2 BI
BI solo lee datos.
BI debe ser rápido y filtrable.
Los indicadores deben respetar permisos.
Si el usuario no puede ver Finanzas, no debe ver KPIs financieros.
Si el usuario no puede ver Pastoral, no debe ver KPIs pastorales.
10.3 Legal
Cada tenant tiene su propia configuración legal.
Los consentimientos deben poder registrarse y revocarse.
Las solicitudes de datos deben tener trazabilidad.
La eliminación de datos personales debe evaluarse según normativa y reglas internas.
Si no se puede eliminar por razones contables o legales, se debe anonimizar cuando corresponda.
Toda descarga legal debe auditarse.
El sistema debe estar preparado para distintas jurisdicciones.
10.4 Datos sensibles

Se consideran sensibles:

Consejería pastoral
Solicitudes de oración privadas
Datos personales
Donaciones identificadas
Informes financieros
Exportaciones
Accesos administrativos

Todo acceso sensible debe auditarse.

11. UI SUGERIDA
11.1 Menú Reportes
Dashboard
CRM
Discipulado
Pastoral
Ministerios
Finanzas
Contabilidad
Legal
Exportaciones
11.2 Menú Cumplimiento
Configuración legal
Consentimientos
Solicitudes de datos
Exportaciones
Auditoría
Accesos sensibles
11.3 Dashboard BI

Indicadores:

Total personas
Miembros activos
Visitas recientes
Personas en discipulado
Casos pastorales abiertos
Ingresos del mes
Egresos del mes
Saldo neto
Reportes recientes
Alertas legales
12. AUDITORÍA

Debe auditarse:

rep.reporte.generated
rep.reporte.exported
rep.reporte.downloaded

legal.config.updated
legal.consentimiento.created
legal.consentimiento.revoked
legal.solicitud.created
legal.solicitud.resolved
legal.exportacion.generated
legal.exportacion.downloaded

audit.sensitive.viewed
audit.sensitive.exported
audit.sensitive.downloaded
13. CRITERIO DE ÉXITO

Este módulo estará correctamente implementado cuando:

Se puedan listar reportes disponibles.
Se puedan generar reportes por módulo.
Se puedan exportar reportes.
Se registre historial de reportes.
Se registren consentimientos.
Se gestionen solicitudes de datos personales.
Se auditen accesos sensibles.
BI respete permisos.
Legal sea configurable por país.
Nada de este módulo modifique datos operativos originales.