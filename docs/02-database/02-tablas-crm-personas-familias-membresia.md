# FEACTIVA IGLESIA SAAS — TABLAS CRM, PERSONAS, FAMILIAS Y MEMBRESÍA

## 1. Objetivo

Definir el modelo de datos del módulo CRM de FeActiva Iglesia SaaS.

Este módulo gestiona:

- Personas
- Familias
- Membresía
- Visitas
- Estados relacionales
- Historial de contacto
- Segmentación básica

El CRM es la base para:

- Discipulado
- Seguimiento pastoral
- Ministerios
- Comunicación
- Finanzas
- Reportes

---

## 2. Principios

1. Toda persona pertenece a un `tenant_id`.
2. El CRM debe poder funcionar solo.
3. No debe depender de Finanzas ni Contabilidad.
4. Debe permitir registrar miembros, visitas, líderes e inactivos.
5. La eliminación debe ser lógica.
6. Toda ficha debe poder ser auditada.
7. Los datos sensibles deben respetar permisos.

---

# 3. TABLAS CRM

## 3.1 Personas

Tabla principal del CRM.

```sql
CREATE TABLE crm_personas (
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
3.2 Familias

Agrupa personas en una unidad familiar.

CREATE TABLE crm_familias (
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
3.3 Relación persona-familia

Permite asociar personas a familias y definir parentesco.

CREATE TABLE crm_persona_familia (
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
3.4 Estados de membresía

Catálogo configurable por iglesia.

CREATE TABLE crm_estados_membresia (
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
3.5 Historial de membresía

Permite registrar cambios de estado.

CREATE TABLE crm_historial_membresia (
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
3.6 Historial de contactos

Registra interacciones no sensibles.

CREATE TABLE crm_contactos_historial (
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
3.7 Etiquetas CRM

Permite segmentar personas.

CREATE TABLE crm_etiquetas (
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
3.8 Etiquetas asignadas a personas
CREATE TABLE crm_persona_etiquetas (
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
4. DATOS INICIALES RECOMENDADOS
4.1 Estados de membresía por tenant

Codex debe crear estos estados al crear una nueva iglesia:

INSERT INTO crm_estados_membresia
(tenant_id, code, nombre, descripcion, color, orden, es_activo)
VALUES
(:tenant_id, 'visita', 'Visita', 'Persona que asistió o tuvo primer contacto.', '#94a3b8', 1, 1),
(:tenant_id, 'nuevo_asistente', 'Nuevo asistente', 'Persona en proceso inicial de integración.', '#38bdf8', 2, 1),
(:tenant_id, 'miembro', 'Miembro', 'Persona reconocida como miembro de la iglesia.', '#22c55e', 3, 1),
(:tenant_id, 'lider', 'Líder', 'Persona con responsabilidad de liderazgo.', '#a855f7', 4, 1),
(:tenant_id, 'servidor', 'Servidor', 'Persona que participa activamente en algún servicio.', '#f59e0b', 5, 1),
(:tenant_id, 'inactivo', 'Inactivo', 'Persona sin participación reciente.', '#ef4444', 6, 1),
(:tenant_id, 'trasladado', 'Trasladado', 'Persona trasladada a otra iglesia.', '#64748b', 7, 1),
(:tenant_id, 'fallecido', 'Fallecido', 'Persona fallecida.', '#111827', 8, 1);
5. CONSULTAS BASE
5.1 Listar personas activas del tenant
SELECT
    id,
    nombres,
    apellidos,
    email,
    telefono,
    whatsapp,
    estado_persona,
    fecha_ingreso,
    created_at
FROM crm_personas
WHERE tenant_id = :tenant_id
  AND deleted_at IS NULL
ORDER BY apellidos, nombres;
5.2 Buscar persona
SELECT
    id,
    nombres,
    apellidos,
    email,
    telefono,
    whatsapp,
    estado_persona
FROM crm_personas
WHERE tenant_id = :tenant_id
  AND deleted_at IS NULL
  AND (
      nombres LIKE :search
      OR apellidos LIKE :search
      OR email LIKE :search
      OR telefono LIKE :search
      OR whatsapp LIKE :search
      OR numero_documento LIKE :search
  )
ORDER BY apellidos, nombres
LIMIT :limit OFFSET :offset;
5.3 Ficha completa de persona
SELECT
    p.*,
    f.id AS familia_id,
    f.nombre_familia,
    pf.parentesco
FROM crm_personas p
LEFT JOIN crm_persona_familia pf
    ON pf.persona_id = p.id
    AND pf.tenant_id = p.tenant_id
LEFT JOIN crm_familias f
    ON f.id = pf.familia_id
    AND f.tenant_id = p.tenant_id
WHERE p.tenant_id = :tenant_id
  AND p.id = :persona_id
  AND p.deleted_at IS NULL;
5.4 Personas con seguimiento pendiente
SELECT
    p.id,
    p.nombres,
    p.apellidos,
    ch.tipo_contacto,
    ch.fecha_contacto,
    ch.fecha_seguimiento,
    ch.asunto
FROM crm_contactos_historial ch
INNER JOIN crm_personas p
    ON p.id = ch.persona_id
    AND p.tenant_id = ch.tenant_id
WHERE ch.tenant_id = :tenant_id
  AND ch.requiere_seguimiento = 1
  AND ch.fecha_seguimiento <= CURDATE()
  AND p.deleted_at IS NULL
ORDER BY ch.fecha_seguimiento ASC;
6. ENDPOINTS ESPERADOS
6.1 Personas
GET    /api/crm/personas
GET    /api/crm/personas/{id}
POST   /api/crm/personas
PATCH  /api/crm/personas/{id}
DELETE /api/crm/personas/{id}
6.2 Familias
GET    /api/crm/familias
GET    /api/crm/familias/{id}
POST   /api/crm/familias
PATCH  /api/crm/familias/{id}
DELETE /api/crm/familias/{id}
POST   /api/crm/familias/{id}/personas
DELETE /api/crm/familias/{id}/personas/{persona_id}
6.3 Historial de contacto
GET    /api/crm/personas/{id}/contactos
POST   /api/crm/personas/{id}/contactos
6.4 Etiquetas
GET    /api/crm/etiquetas
POST   /api/crm/etiquetas
PATCH  /api/crm/etiquetas/{id}
DELETE /api/crm/etiquetas/{id}
POST   /api/crm/personas/{id}/etiquetas
DELETE /api/crm/personas/{id}/etiquetas/{etiqueta_id}
7. PERMISOS REQUERIDOS
crm.personas.ver
crm.personas.crear
crm.personas.editar
crm.personas.eliminar

crm.familias.ver
crm.familias.crear
crm.familias.editar
crm.familias.eliminar

crm.contactos.ver
crm.contactos.crear

crm.etiquetas.ver
crm.etiquetas.crear
crm.etiquetas.editar
crm.etiquetas.eliminar
8. REGLAS DE NEGOCIO
8.1 Personas
Una persona debe pertenecer siempre a un tenant.
No se debe permitir duplicar documento dentro del mismo tenant.
Se permite que una persona no tenga documento.
Email y teléfono no son obligatorios.
La eliminación debe ser lógica usando deleted_at.
El cambio de estado debe registrarse en crm_historial_membresia.
Toda creación, edición y eliminación debe auditarse.
Las observaciones pastorales sensibles NO deben guardarse aquí; deben ir en el módulo Pastoral.
8.2 Familias
Una persona puede pertenecer a una familia.
En casos excepcionales, puede pertenecer a más de una relación familiar si el tenant lo permite.
Una familia puede tener un contacto principal.
La eliminación de una familia no debe eliminar personas.
Si se elimina una familia, se deben eliminar o desactivar las relaciones familiares.
8.3 Historial de contacto
Solo debe guardar interacciones generales.
No debe guardar consejería sensible.
Si el contacto requiere seguimiento, debe tener fecha de seguimiento.
El historial no se borra físicamente.
9. VALIDACIONES BACKEND

Antes de ejecutar cualquier endpoint CRM:

Validar usuario autenticado.
Validar tenant activo.
Validar módulo crm activo.
Validar permiso requerido.
Validar que los registros consultados pertenezcan al tenant.
Validar entrada.
Usar prepared statements.
Registrar auditoría en acciones críticas.
10. UI SUGERIDA
10.1 Menú CRM
Personas
Familias
Visitas
Miembros
Seguimientos
Etiquetas
10.2 Dashboard CRM

Indicadores:

Total personas
Miembros activos
Visitas recientes
Nuevos asistentes
Personas inactivas
Seguimientos pendientes
Cumpleaños del mes
10.3 Ficha persona

Secciones:

Datos generales
Familia
Membresía
Contacto
Historial
Etiquetas
Discipulado, si módulo está activo
Finanzas, si módulo está activo y usuario tiene permiso
Pastoral, si módulo está activo y usuario tiene permiso
11. CRITERIO DE ÉXITO

El módulo CRM estará correctamente implementado cuando:

Se puedan registrar personas.
Se puedan buscar personas.
Se puedan clasificar por estado.
Se puedan asociar a familias.
Se pueda ver historial de contacto.
Se pueda segmentar con etiquetas.
Se respeten tenant, permisos y módulos activos.
No se mezclen datos entre iglesias.
Se registre auditoría de acciones críticas.