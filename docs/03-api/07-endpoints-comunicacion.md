# FEACTIVA IGLESIA SAAS — API COMUNICACIÓN

## Objetivo

Definir endpoints para mensajes, plantillas, segmentos, canales y automatizaciones.

## Reglas generales

Antes de ejecutar:

1. Validar autenticación.
2. Validar tenant.
3. Validar módulo `comunicacion`.
4. Validar permiso.
5. Auditar acciones críticas.

## Canales

```http
GET   /api/v1/comunicacion/canales
POST  /api/v1/comunicacion/canales
PATCH /api/v1/comunicacion/canales/{id}

Permisos:

com.canales.ver
com.canales.configurar
Plantillas
GET    /api/v1/comunicacion/plantillas
GET    /api/v1/comunicacion/plantillas/{id}
POST   /api/v1/comunicacion/plantillas
PATCH  /api/v1/comunicacion/plantillas/{id}
DELETE /api/v1/comunicacion/plantillas/{id}

Body:

{
  "nombre": "Bienvenida",
  "tipo": "whatsapp",
  "asunto": null,
  "contenido": "Hola {{nombre}}, bienvenido a nuestra iglesia.",
  "variables": ["nombre"]
}
Mensajes
GET  /api/v1/comunicacion/mensajes
GET  /api/v1/comunicacion/mensajes/{id}
POST /api/v1/comunicacion/mensajes
POST /api/v1/comunicacion/mensajes/{id}/enviar
POST /api/v1/comunicacion/mensajes/{id}/programar

Body crear mensaje:

{
  "canal_id": 1,
  "asunto": "Bienvenida",
  "contenido": "Hola, queremos saludarte.",
  "destinatarios": [1, 2, 3],
  "segmento_id": null
}
Segmentos
GET    /api/v1/comunicacion/segmentos
POST   /api/v1/comunicacion/segmentos
PATCH  /api/v1/comunicacion/segmentos/{id}
DELETE /api/v1/comunicacion/segmentos/{id}

Body:

{
  "nombre": "Miembros activos",
  "descripcion": "Personas con estado miembro",
  "criterio": {
    "estado_persona": "miembro"
  },
  "es_dinamico": true
}
Automatizaciones
GET   /api/v1/comunicacion/automatizaciones
POST  /api/v1/comunicacion/automatizaciones
PATCH /api/v1/comunicacion/automatizaciones/{id}
POST  /api/v1/comunicacion/automatizaciones/{id}/desactivar
Reglas de negocio
Un mensaje puede tener destinatarios manuales o segmento.
El sistema debe registrar estado por destinatario.
No enviar mensajes a personas sin contacto válido.
Automatizaciones deben poder activarse/desactivarse.
Envíos masivos deben registrar auditoría.
Auditoría
com.plantilla.created
com.plantilla.updated
com.mensaje.created
com.mensaje.sent
com.mensaje.scheduled
com.segmento.created
com.automatizacion.created
com.automatizacion.disabled