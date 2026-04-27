# FEACTIVA IGLESIA SAAS — API CRM PERSONAS

## 1. Objetivo

Definir los endpoints REST del módulo CRM.

Este documento cubre:

- Personas
- Familias
- Membresía
- Contactos
- Etiquetas

Todos los endpoints deben respetar:

- Autenticación
- tenant_id desde sesión
- Módulo CRM activo
- Permisos
- Auditoría
- Validación de entrada

---

# 2. Reglas generales CRM

Antes de ejecutar cualquier endpoint:

```text
1. Validar token/sesión
2. Obtener user_id
3. Obtener tenant_id desde contexto seguro
4. Validar tenant activo
5. Validar módulo crm activo
6. Validar permiso requerido
7. Validar que el recurso pertenece al tenant
8. Ejecutar acción
9. Auditar si corresponde
3. Personas
3.1 Listar personas
GET /api/v1/crm/personas
Permiso
crm.personas.ver
Query params
page
limit
search
estado
ciudad
etiqueta_id
sort
order
Respuesta 200
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombres": "Juan",
      "apellidos": "Pérez",
      "email": "juan@email.com",
      "telefono": "+569...",
      "whatsapp": "+569...",
      "estado_persona": "miembro",
      "fecha_ingreso": "2026-04-26"
    }
  ],
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 1,
    "total_pages": 1
  },
  "message": null
}
3.2 Ver persona
GET /api/v1/crm/personas/{id}
Permiso
crm.personas.ver
Respuesta 200
{
  "success": true,
  "data": {
    "id": 1,
    "nombres": "Juan",
    "apellidos": "Pérez",
    "email": "juan@email.com",
    "telefono": "+569...",
    "whatsapp": "+569...",
    "estado_persona": "miembro",
    "familia": {
      "id": 3,
      "nombre_familia": "Familia Pérez",
      "parentesco": "jefe_hogar"
    },
    "etiquetas": [],
    "historial_contactos": []
  },
  "message": null
}
3.3 Crear persona
POST /api/v1/crm/personas
Permiso
crm.personas.crear
Body
{
  "nombres": "Juan",
  "apellidos": "Pérez",
  "tipo_documento": "rut",
  "numero_documento": "11111111-1",
  "email": "juan@email.com",
  "telefono": "+569...",
  "whatsapp": "+569...",
  "fecha_nacimiento": "1985-05-10",
  "genero": "masculino",
  "estado_civil": "casado",
  "direccion": "Calle 123",
  "ciudad": "Santiago",
  "region": "Metropolitana",
  "pais": "Chile",
  "estado_persona": "visita",
  "origen_contacto": "culto_domingo",
  "observaciones_generales": "Primera visita."
}
Validaciones
nombres requerido
apellidos requerido
email debe ser válido si existe
numero_documento no puede duplicarse dentro del tenant
estado_persona debe ser válido
Acciones
1. Crear crm_personas
2. Crear crm_historial_membresia
3. Registrar audit_logs
Respuesta 201
{
  "success": true,
  "data": {
    "id": 1
  },
  "message": "Persona creada correctamente"
}
3.4 Actualizar persona
PATCH /api/v1/crm/personas/{id}
Permiso
crm.personas.editar
Body
{
  "telefono": "+569...",
  "estado_persona": "miembro",
  "fecha_membresia": "2026-04-26"
}
Acciones especiales

Si cambia estado_persona, crear registro en:

crm_historial_membresia
Respuesta 200
{
  "success": true,
  "data": {
    "id": 1
  },
  "message": "Persona actualizada correctamente"
}
3.5 Eliminar persona
DELETE /api/v1/crm/personas/{id}
Permiso
crm.personas.eliminar
Acción

Soft delete:

deleted_at = NOW()
deleted_by = user_id
Respuesta 204

Sin body.

4. Familias
4.1 Listar familias
GET /api/v1/crm/familias
Permiso
crm.familias.ver
4.2 Ver familia
GET /api/v1/crm/familias/{id}
Permiso
crm.familias.ver

Debe incluir miembros de la familia.

4.3 Crear familia
POST /api/v1/crm/familias
Permiso
crm.familias.crear
Body
{
  "nombre_familia": "Familia Pérez",
  "direccion": "Calle 123",
  "telefono_principal": "+569...",
  "email_principal": "familia@email.com"
}
4.4 Actualizar familia
PATCH /api/v1/crm/familias/{id}
Permiso
crm.familias.editar
4.5 Eliminar familia
DELETE /api/v1/crm/familias/{id}
Permiso
crm.familias.eliminar

Soft delete.

4.6 Agregar persona a familia
POST /api/v1/crm/familias/{id}/personas
Permiso
crm.familias.editar
Body
{
  "persona_id": 1,
  "parentesco": "hijo",
  "es_contacto_principal": false,
  "vive_en_hogar": true
}
4.7 Remover persona de familia
DELETE /api/v1/crm/familias/{id}/personas/{persona_id}
Permiso
crm.familias.editar
5. Historial de contacto
5.1 Listar contactos de persona
GET /api/v1/crm/personas/{id}/contactos
Permiso
crm.contactos.ver
5.2 Crear contacto
POST /api/v1/crm/personas/{id}/contactos
Permiso
crm.contactos.crear
Body
{
  "tipo_contacto": "whatsapp",
  "fecha_contacto": "2026-04-26 10:30:00",
  "asunto": "Bienvenida",
  "resumen": "Se envió mensaje de bienvenida.",
  "resultado": "Respondió positivamente",
  "requiere_seguimiento": true,
  "fecha_seguimiento": "2026-05-01"
}
Regla

No guardar consejería sensible aquí.

6. Etiquetas
6.1 Listar etiquetas
GET /api/v1/crm/etiquetas
Permiso
crm.etiquetas.ver
6.2 Crear etiqueta
POST /api/v1/crm/etiquetas
Permiso
crm.etiquetas.crear
Body
{
  "nombre": "Jóvenes",
  "descripcion": "Personas del grupo de jóvenes",
  "color": "#7c3aed"
}
6.3 Actualizar etiqueta
PATCH /api/v1/crm/etiquetas/{id}
Permiso
crm.etiquetas.editar
6.4 Eliminar etiqueta
DELETE /api/v1/crm/etiquetas/{id}
Permiso
crm.etiquetas.eliminar
6.5 Asignar etiqueta a persona
POST /api/v1/crm/personas/{id}/etiquetas
Permiso
crm.etiquetas.editar
Body
{
  "etiqueta_id": 4
}
6.6 Quitar etiqueta de persona
DELETE /api/v1/crm/personas/{id}/etiquetas/{etiqueta_id}
Permiso
crm.etiquetas.editar
7. Membresía
7.1 Ver historial de membresía
GET /api/v1/crm/personas/{id}/membresia/historial
Permiso
crm.membresia.ver
7.2 Cambiar estado de membresía
POST /api/v1/crm/personas/{id}/membresia/cambiar-estado
Permiso
crm.membresia.editar
Body
{
  "estado_nuevo": "miembro",
  "fecha_cambio": "2026-04-26",
  "motivo": "Recepción como miembro",
  "observacion": "Aprobado por liderazgo."
}
Acciones
1. Actualizar crm_personas.estado_persona
2. Insertar crm_historial_membresia
3. Auditar
8. Códigos de error CRM
CRM_PERSON_NOT_FOUND
CRM_FAMILY_NOT_FOUND
CRM_DUPLICATE_DOCUMENT
CRM_INVALID_STATUS
CRM_TAG_NOT_FOUND
CRM_CONTACT_NOT_FOUND
CRM_MEMBERSHIP_INVALID_TRANSITION
9. Auditoría requerida

Auditar:

crm.persona.created
crm.persona.updated
crm.persona.deleted

crm.familia.created
crm.familia.updated
crm.familia.deleted

crm.contacto.created

crm.etiqueta.created
crm.etiqueta.updated
crm.etiqueta.deleted
crm.etiqueta.assigned
crm.etiqueta.removed

crm.membresia.changed
10. Criterio de éxito

El contrato API CRM estará cumplido cuando:

Todos los endpoints respondan con formato estándar.
Todos validen tenant.
Todos validen módulo CRM activo.
Todos validen permisos.
Ningún endpoint exponga datos de otro tenant.
Las acciones críticas queden auditadas.
La eliminación sea lógica.
El historial de membresía registre cambios correctamente.