# FEACTIVA IGLESIA SAAS — TABLAS MINISTERIOS Y COMUNICACIÓN

## 1. Objetivo

Definir el modelo de datos para los módulos:

- Ministerios
- Equipos
- Participación
- Actividades
- Comunicación
- Mensajería
- Segmentación
- Plantillas
- Automatizaciones

Estos módulos permiten organizar la iglesia y mantener comunicación activa con sus miembros.

---

# 2. MÓDULO: MINISTERIOS

## 2.1 Ministerios

```sql
CREATE TABLE min_ministerios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    nombre VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,
    area VARCHAR(120) NULL,

    lider_persona_id BIGINT UNSIGNED NULL,

    es_activo TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_min_ministerios_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_min_ministerios_lider
        FOREIGN KEY (lider_persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,

    INDEX idx_min_ministerios_tenant_id (tenant_id),
    INDEX idx_min_ministerios_lider (lider_persona_id),
    INDEX idx_min_ministerios_activo (es_activo),
    INDEX idx_min_ministerios_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
2.2 Equipos
CREATE TABLE min_equipos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    ministerio_id BIGINT UNSIGNED NOT NULL,

    nombre VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,

    lider_persona_id BIGINT UNSIGNED NULL,

    es_activo TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_min_equipos_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_min_equipos_ministerio
        FOREIGN KEY (ministerio_id) REFERENCES min_ministerios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_min_equipos_lider
        FOREIGN KEY (lider_persona_id) REFERENCES crm_personas(id)
        ON DELETE SET NULL,

    INDEX idx_min_equipos_tenant_id (tenant_id),
    INDEX idx_min_equipos_ministerio_id (ministerio_id),
    INDEX idx_min_equipos_lider (lider_persona_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
2.3 Miembros de equipo
CREATE TABLE min_equipo_personas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    equipo_id BIGINT UNSIGNED NOT NULL,
    persona_id BIGINT UNSIGNED NOT NULL,

    rol_equipo VARCHAR(120) NULL,
    fecha_ingreso DATE NULL,
    es_activo TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_min_equipo_personas_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_min_equipo_personas_equipo
        FOREIGN KEY (equipo_id) REFERENCES min_equipos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_min_equipo_personas_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_equipo_persona (tenant_id, equipo_id, persona_id),

    INDEX idx_min_equipo_personas_tenant_id (tenant_id),
    INDEX idx_min_equipo_personas_equipo_id (equipo_id),
    INDEX idx_min_equipo_personas_persona_id (persona_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
2.4 Actividades
CREATE TABLE min_actividades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    ministerio_id BIGINT UNSIGNED NULL,
    equipo_id BIGINT UNSIGNED NULL,

    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,

    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NULL,
    ubicacion VARCHAR(180) NULL,

    tipo ENUM('reunion','culto','evento','capacitacion','otro') NOT NULL DEFAULT 'reunion',

    es_publica TINYINT(1) NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_min_actividades_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),

    CONSTRAINT fk_min_actividades_ministerio
        FOREIGN KEY (ministerio_id) REFERENCES min_ministerios(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_min_actividades_equipo
        FOREIGN KEY (equipo_id) REFERENCES min_equipos(id)
        ON DELETE SET NULL,

    INDEX idx_min_actividades_tenant_id (tenant_id),
    INDEX idx_min_actividades_fecha (fecha_inicio),
    INDEX idx_min_actividades_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
3. MÓDULO: COMUNICACIÓN
3.1 Canales
CREATE TABLE com_canales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    tipo ENUM('whatsapp','email','app','sms') NOT NULL,
    nombre VARCHAR(120) NOT NULL,

    configuracion JSON NULL,
    es_activo TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_com_canales_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),

    INDEX idx_com_canales_tenant_id (tenant_id),
    INDEX idx_com_canales_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
3.2 Plantillas
CREATE TABLE com_plantillas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    nombre VARCHAR(150) NOT NULL,
    tipo ENUM('whatsapp','email','app') NOT NULL,

    asunto VARCHAR(180) NULL,
    contenido TEXT NOT NULL,

    variables JSON NULL,

    es_activa TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_com_plantillas_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),

    INDEX idx_com_plantillas_tenant_id (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
3.3 Mensajes
CREATE TABLE com_mensajes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    canal_id BIGINT UNSIGNED NOT NULL,

    asunto VARCHAR(180) NULL,
    contenido TEXT NOT NULL,

    estado ENUM('borrador','enviado','fallido','programado') NOT NULL DEFAULT 'borrador',

    fecha_envio DATETIME NULL,
    fecha_programada DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_com_mensajes_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),

    CONSTRAINT fk_com_mensajes_canal
        FOREIGN KEY (canal_id) REFERENCES com_canales(id),

    INDEX idx_com_mensajes_tenant_id (tenant_id),
    INDEX idx_com_mensajes_estado (estado),
    INDEX idx_com_mensajes_fecha (fecha_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
3.4 Destinatarios
CREATE TABLE com_mensaje_destinatarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    mensaje_id BIGINT UNSIGNED NOT NULL,
    persona_id BIGINT UNSIGNED NOT NULL,

    estado_envio ENUM('pendiente','enviado','fallido','leido') NOT NULL DEFAULT 'pendiente',
    fecha_envio DATETIME NULL,
    fecha_lectura DATETIME NULL,

    error TEXT NULL,

    CONSTRAINT fk_com_destinatarios_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),

    CONSTRAINT fk_com_destinatarios_mensaje
        FOREIGN KEY (mensaje_id) REFERENCES com_mensajes(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_com_destinatarios_persona
        FOREIGN KEY (persona_id) REFERENCES crm_personas(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_mensaje_persona (tenant_id, mensaje_id, persona_id),

    INDEX idx_com_destinatarios_tenant_id (tenant_id),
    INDEX idx_com_destinatarios_estado (estado_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
3.5 Segmentos
CREATE TABLE com_segmentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,

    criterio JSON NULL,

    es_dinamico TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,

    CONSTRAINT fk_com_segmentos_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),

    INDEX idx_com_segmentos_tenant_id (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
3.6 Automatizaciones
CREATE TABLE com_automatizaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,

    nombre VARCHAR(150) NOT NULL,
    tipo_evento ENUM(
        'nueva_persona',
        'cumpleanos',
        'inactividad',
        'evento',
        'manual'
    ) NOT NULL,

    plantilla_id BIGINT UNSIGNED NOT NULL,
    retraso_minutos INT UNSIGNED DEFAULT 0,

    es_activa TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_com_auto_tenant
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id),

    CONSTRAINT fk_com_auto_plantilla
        FOREIGN KEY (plantilla_id) REFERENCES com_plantillas(id),

    INDEX idx_com_auto_tenant_id (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
4. ENDPOINTS
GET /api/ministerios
POST /api/ministerios
PATCH /api/ministerios/{id}

GET /api/equipos
POST /api/equipos

GET /api/actividades
POST /api/actividades

GET /api/comunicacion/mensajes
POST /api/comunicacion/mensajes

POST /api/comunicacion/mensajes/{id}/enviar

GET /api/comunicacion/segmentos
POST /api/comunicacion/segmentos

GET /api/comunicacion/plantillas
POST /api/comunicacion/plantillas
5. PERMISOS
min.ministerios.ver
min.ministerios.crear
min.ministerios.editar

min.equipos.ver
min.equipos.crear

com.mensajes.ver
com.mensajes.crear
com.mensajes.enviar

com.plantillas.ver
com.plantillas.crear

com.segmentos.ver
com.segmentos.crear
6. REGLAS DE NEGOCIO
Ministerios
Una persona puede estar en múltiples equipos
Un equipo pertenece a un ministerio
Un ministerio puede existir sin equipos
Comunicación
Un mensaje puede enviarse a múltiples personas
Los destinatarios se generan desde:
selección manual
segmento
Los mensajes no se eliminan
Se registra estado de envío
Automatización
Se ejecuta por evento
Puede usar plantillas
Puede retrasar envío
7. CRITERIO DE ÉXITO
Se pueden crear ministerios
Se pueden organizar equipos
Se pueden planificar actividades
Se pueden enviar mensajes
Se pueden segmentar personas
Se pueden automatizar comunicaciones
Todo respeta tenant y permisos