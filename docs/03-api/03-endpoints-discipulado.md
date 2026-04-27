# FEACTIVA IGLESIA SAAS — API DISCIPULADO

## 1. Objetivo

Definir los endpoints del módulo de discipulado.

Incluye:

- Rutas de discipulado
- Etapas
- Asignación de personas
- Avance
- Mentorías
- Registros espirituales

---

# 2. Reglas generales

Antes de cualquier endpoint:

```text
1. Validar autenticación
2. Obtener user_id
3. Obtener tenant_id desde sesión
4. Validar tenant activo
5. Validar módulo discipulado activo
6. Validar permisos
7. Validar pertenencia al tenant
8. Ejecutar acción
9. Auditar
3. Rutas de discipulado
3.1 Listar rutas
GET /api/v1/discipulado/rutas
Permiso
disc.rutas.ver
3.2 Crear ruta
POST /api/v1/discipulado/rutas
Permiso
disc.rutas.crear
Body
{
  "nombre": "Fundamentos de la Fe",
  "descripcion": "Ruta inicial para nuevos creyentes",
  "publico_objetivo": "nuevos",
  "duracion_estimada_dias": 90
}
3.3 Actualizar ruta
PATCH /api/v1/discipulado/rutas/{id}
Permiso
disc.rutas.editar
3.4 Eliminar ruta
DELETE /api/v1/discipulado/rutas/{id}
Permiso
disc.rutas.eliminar

Soft delete.

4. Etapas
4.1 Crear etapa
POST /api/v1/discipulado/rutas/{id}/etapas
Permiso
disc.rutas.editar
Body
{
  "nombre": "Salvación",
  "descripcion": "Comprensión del evangelio",
  "orden": 1,
  "duracion_estimada_dias": 7
}
4.2 Actualizar etapa
PATCH /api/v1/discipulado/etapas/{id}
4.3 Eliminar etapa
DELETE /api/v1/discipulado/etapas/{id}
5. Asignación de persona a ruta
5.1 Asignar persona
POST /api/v1/discipulado/personas/{persona_id}/rutas
Permiso
disc.rutas.crear
Body
{
  "ruta_id": 1,
  "mentor_persona_id": 5,
  "fecha_inicio": "2026-04-26"
}
Acciones
1. Crear disc_persona_rutas
2. Generar etapas automáticamente
3. Asignar mentor si existe
4. Auditar
5.2 Ver rutas de una persona
GET /api/v1/discipulado/personas/{persona_id}/rutas
5.3 Actualizar estado de ruta
PATCH /api/v1/discipulado/persona-rutas/{id}
Body
{
  "estado": "completada",
  "fecha_fin": "2026-07-01"
}
6. Avance por etapa
6.1 Completar etapa
POST /api/v1/discipulado/persona-etapas/{id}/completar
Permiso
disc.avance.editar
Body
{
  "nota_resultado": "Comprensión adecuada",
  "observacion": "Participó activamente"
}
6.2 Ver avance de persona
GET /api/v1/discipulado/personas/{persona_id}/avance
7. Mentorías
7.1 Listar mentorías
GET /api/v1/discipulado/personas/{persona_id}/mentorias
Permiso
disc.mentorias.ver
7.2 Crear mentoría
POST /api/v1/discipulado/personas/{persona_id}/mentorias
Permiso
disc.mentorias.crear
Body
{
  "mentor_persona_id": 5,
  "fecha_mentoria": "2026-04-26 10:00:00",
  "modalidad": "presencial",
  "tema": "Oración",
  "resumen": "Se explicó la oración básica",
  "acuerdos": "Orar diariamente",
  "proxima_fecha": "2026-05-03"
}
8. Registros espirituales
8.1 Listar registros
GET /api/v1/discipulado/personas/{persona_id}/registros-espirituales
8.2 Crear registro
POST /api/v1/discipulado/personas/{persona_id}/registros-espirituales
Permiso
disc.registros.crear
Body
{
  "tipo": "bautismo",
  "fecha_evento": "2026-03-10",
  "lugar": "Templo Central",
  "ministro_responsable": "Pastor Juan",
  "observacion": "Bautismo en servicio dominical"
}
9. Restricciones importantes
9.1 Mentor

Solo puede:

Ver personas asignadas
Registrar mentorías sobre sus asignados
9.2 Líder

Puede ver solo:

Rutas de su área
Personas bajo su cobertura
9.3 Pastor

Puede ver todo discipulado (según permisos)

10. Códigos de error
DISC_RUTA_NOT_FOUND
DISC_ETAPA_NOT_FOUND
DISC_PERSONA_RUTA_NOT_FOUND
DISC_PERSONA_NO_ASIGNADA
DISC_MENTOR_NO_VALIDO
DISC_ETAPA_YA_COMPLETADA
DISC_INVALID_STATE
11. Auditoría
disc.ruta.created
disc.ruta.updated
disc.ruta.deleted

disc.persona_ruta.assigned
disc.persona_ruta.updated

disc.etapa.completed

disc.mentoria.created

disc.registro_espiritual.created
12. Criterio de éxito
Se pueden crear rutas
Se pueden crear etapas
Se puede asignar persona a ruta
Se puede registrar avance
Se pueden registrar mentorías
Se pueden registrar eventos espirituales
Todo respeta tenant y permisos
No hay acceso indebido a personas no asignadas