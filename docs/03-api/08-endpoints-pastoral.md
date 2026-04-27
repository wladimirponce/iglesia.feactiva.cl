# FEACTIVA IGLESIA SAAS — API SEGUIMIENTO PASTORAL

## Objetivo

Definir endpoints para casos pastorales, sesiones, solicitudes de oración y derivaciones.

Este módulo maneja datos sensibles.

## Reglas generales

Antes de ejecutar cualquier endpoint:

1. Validar autenticación.
2. Validar tenant.
3. Validar módulo `pastoral`.
4. Validar permiso.
5. Validar acceso a datos confidenciales.
6. Auditar acceso sensible.

## Casos pastorales

```http
GET   /api/v1/pastoral/casos
GET   /api/v1/pastoral/casos/{id}
POST  /api/v1/pastoral/casos
PATCH /api/v1/pastoral/casos/{id}
POST  /api/v1/pastoral/casos/{id}/cerrar
POST  /api/v1/pastoral/casos/{id}/derivar

Permisos:

past.casos.ver
past.casos.ver_confidencial
past.casos.crear
past.casos.editar
past.casos.cerrar
past.casos.derivar

Body crear caso:

{
  "persona_id": 1,
  "responsable_user_id": 2,
  "tipo": "consejeria",
  "titulo": "Acompañamiento familiar",
  "descripcion_general": "Resumen general del caso",
  "prioridad": "media",
  "fecha_apertura": "2026-04-26",
  "es_confidencial": true
}
Sesiones pastorales
GET  /api/v1/pastoral/casos/{id}/sesiones
POST /api/v1/pastoral/casos/{id}/sesiones

Body:

{
  "fecha_sesion": "2026-04-26 19:00:00",
  "modalidad": "presencial",
  "resumen": "Resumen pastoral",
  "acuerdos": "Acuerdos tomados",
  "proxima_accion": "Nuevo seguimiento",
  "proxima_fecha": "2026-05-03 19:00:00",
  "es_confidencial": true
}
Solicitudes de oración
GET   /api/v1/pastoral/oracion
POST  /api/v1/pastoral/oracion
PATCH /api/v1/pastoral/oracion/{id}
POST  /api/v1/pastoral/oracion/{id}/cerrar

Body:

{
  "persona_id": 1,
  "titulo": "Petición de salud",
  "detalle": "Detalle de la solicitud",
  "categoria": "salud",
  "privacidad": "privada"
}
Derivaciones
POST /api/v1/pastoral/casos/{id}/derivar

Body:

{
  "derivado_a_user_id": 4,
  "tipo_derivacion": "psicologo",
  "motivo": "Requiere acompañamiento especializado"
}
Reglas de seguridad
Casos confidenciales requieren past.casos.ver_confidencial.
Todo acceso a caso pastoral debe registrarse en audit_sensitive_access.
El Super Admin no accede por defecto a casos pastorales.
Las sesiones pastorales no aparecen en CRM general.
No se elimina físicamente información pastoral.
Auditoría
past.caso.created
past.caso.viewed
past.caso.updated
past.caso.closed
past.sesion.created
past.oracion.created
past.oracion.closed
past.derivacion.created
Códigos de error
PAST_CASE_NOT_FOUND
PAST_ACCESS_DENIED
PAST_CONFIDENTIAL_ACCESS_DENIED
PAST_SESSION_NOT_FOUND
PAST_PRAYER_REQUEST_NOT_FOUND
PAST_INVALID_STATE