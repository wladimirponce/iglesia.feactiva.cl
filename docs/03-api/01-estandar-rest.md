# FEACTIVA IGLESIA SAAS — ESTÁNDAR API REST

## 1. Objetivo

Definir el estándar obligatorio para todas las APIs del sistema FeActiva.

Este documento controla:

- Estructura de endpoints
- Formato de request/response
- Manejo de errores
- Seguridad
- Validaciones
- Convenciones REST

⚠️ Codex NO puede desviarse de este estándar.

---

# 2. PRINCIPIOS

1. API RESTful real (no pseudo REST)
2. JSON como formato único
3. Stateless
4. Uso correcto de HTTP methods
5. Respuestas consistentes
6. Errores estructurados
7. Seguridad en backend (no confiar en frontend)
8. Multi-tenant obligatorio
9. Versionamiento desde el inicio

---

# 3. BASE URL

```text
/api/v1/

Ejemplos:

/api/v1/crm/personas
/api/v1/finanzas/movimientos
/api/v1/contabilidad/asientos
4. HEADERS OBLIGATORIOS
Content-Type: application/json
Authorization: Bearer {token}

Opcional:

X-Tenant-ID: {tenant_id}  ❌ NO CONFIAR

⚠️ El tenant se obtiene desde el token, NO del header.

5. ESTRUCTURA DE RESPUESTA
5.1 Éxito
{
  "success": true,
  "data": {},
  "meta": {},
  "message": null
}
5.2 Error
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Mensaje claro",
    "details": []
  }
}
6. CÓDIGOS HTTP
6.1 Éxito
200 OK            → GET exitoso
201 Created       → POST exitoso
204 No Content    → DELETE exitoso
6.2 Cliente
400 Bad Request       → error validación
401 Unauthorized      → no autenticado
403 Forbidden         → sin permiso
404 Not Found         → no existe
409 Conflict          → duplicado
422 Unprocessable     → validación lógica
6.3 Servidor
500 Internal Server Error
7. MÉTODOS HTTP
7.1 GET
GET /api/v1/crm/personas
GET /api/v1/crm/personas/{id}

Reglas:

No modifica datos
Soporta filtros
Soporta paginación
7.2 POST
POST /api/v1/crm/personas

Reglas:

Crea recurso
Retorna 201
Retorna objeto creado
7.3 PATCH
PATCH /api/v1/crm/personas/{id}

Reglas:

Actualización parcial
No requiere todos los campos
7.4 PUT (opcional)
PUT /api/v1/crm/personas/{id}

Reglas:

Reemplazo completo
7.5 DELETE
DELETE /api/v1/crm/personas/{id}

Reglas:

Eliminación lógica (soft delete)
Retorna 204
8. PAGINACIÓN
Request
GET /api/v1/crm/personas?page=1&limit=20
Response
{
  "success": true,
  "data": [],
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 120,
    "total_pages": 6
  }
}
9. FILTROS
GET /api/v1/crm/personas?estado=miembro&ciudad=Santiago

Reglas:

Campos permitidos definidos por backend
No permitir filtros arbitrarios
10. BÚSQUEDA
GET /api/v1/crm/personas?search=juan
11. ORDENAMIENTO
GET /api/v1/crm/personas?sort=created_at&order=desc
12. VALIDACIONES
Ejemplo error
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Error de validación",
    "details": [
      {
        "field": "email",
        "message": "Email inválido"
      }
    ]
  }
}
13. SEGURIDAD OBLIGATORIA
13.1 Flujo backend
1. Validar token
2. Obtener user_id
3. Obtener tenant_id desde sesión
4. Validar relación usuario-tenant
5. Validar módulo activo
6. Validar permiso
7. Ejecutar acción
8. Auditar
13.2 Reglas críticas
❌ No confiar en tenant_id del frontend
❌ No exponer errores SQL
❌ No permitir acceso sin permisos
✔ Siempre usar prepared statements
✔ Sanitizar input
✔ Escapar output
14. ESTRUCTURA DE ENDPOINTS
Convención
/api/v1/{modulo}/{recurso}
/api/v1/{modulo}/{recurso}/{id}
/api/v1/{modulo}/{recurso}/{id}/{accion}
Ejemplos reales
/api/v1/crm/personas
/api/v1/crm/personas/10
/api/v1/crm/personas/10/contactos

/api/v1/finanzas/movimientos
/api/v1/finanzas/movimientos/15/anular

/api/v1/contabilidad/asientos
/api/v1/contabilidad/asientos/20/aprobar
15. ACCIONES ESPECIALES
Ejemplo
POST /api/v1/finanzas/movimientos/{id}/anular
POST /api/v1/contabilidad/asientos/{id}/aprobar
POST /api/v1/pastoral/casos/{id}/cerrar

Reglas:

Siempre POST
Nunca GET
Debe validar permisos
16. AUDITORÍA

Toda acción crítica debe registrar:

user_id
tenant_id
modulo
accion
tabla
registro_id
old_values
new_values
timestamp
17. VERSIONAMIENTO
/api/v1/
/api/v2/ (futuro)

Regla:

Nunca romper compatibilidad en v1
18. TIMEZONE
Backend maneja UTC
Frontend convierte a local
19. LOGGING

Registrar:

errores
accesos
operaciones críticas

Nunca mostrar logs al usuario final.

20. CRITERIO DE ÉXITO

API está correcta cuando:

Todos los endpoints siguen estructura REST
Todas las respuestas son consistentes
Todos los errores son estructurados
Ninguna acción salta validaciones
Multi-tenant es respetado siempre
Seguridad se valida en backend