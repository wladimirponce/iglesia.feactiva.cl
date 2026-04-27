# FEACTIVA IGLESIA SAAS — ROLES Y PERMISOS

## 1. Objetivo

Definir los roles base, permisos granulares y reglas de acceso del sistema FeActiva Iglesia SaaS.

Este documento debe guiar a Codex en la implementación de:

- Roles
- Permisos
- Restricciones por módulo
- Restricciones por tenant
- Acceso a información sensible
- Seguridad funcional

---

## 2. Principios generales

1. Todo usuario debe estar autenticado.
2. Todo usuario debe pertenecer a un tenant para operar dentro de una iglesia.
3. Un usuario puede tener roles distintos en iglesias distintas.
4. Un permiso siempre debe validarse en backend.
5. El frontend puede ocultar opciones, pero nunca reemplaza la validación del backend.
6. El acceso pastoral y financiero debe ser especialmente restringido.
7. Todo acceso a datos sensibles debe auditarse.
8. Ningún usuario debe ver información de otro tenant.
9. El Super Admin FeActiva puede administrar la plataforma, pero no debe acceder a datos pastorales sensibles sin autorización explícita.

---

# 3. ROLES BASE

## 3.1 Super Admin FeActiva

Rol global de plataforma.

Puede:

- Crear tenants
- Suspender tenants
- Administrar planes
- Activar módulos
- Ver configuración global
- Gestionar módulos del sistema
- Ver auditoría técnica global

No debe por defecto:

- Leer casos pastorales
- Leer sesiones de consejería
- Leer notas confidenciales
- Ver donaciones identificadas sin permiso especial

---

## 3.2 Administrador de Iglesia

Rol administrativo principal del tenant.

Puede:

- Gestionar usuarios del tenant
- Gestionar roles internos
- Activar configuración del tenant
- Ver dashboard general
- Administrar CRM
- Administrar módulos activos
- Ver reportes generales

Puede acceder a finanzas solo si tiene permiso financiero asignado.

---

## 3.3 Pastor Principal

Rol pastoral superior dentro del tenant.

Puede:

- Ver CRM
- Ver discipulado
- Ver seguimiento pastoral
- Crear y cerrar casos pastorales
- Ver reportes ministeriales
- Ver solicitudes de oración
- Asignar responsables pastorales

Puede ver finanzas solo si el tenant decide asignarle permisos financieros.

---

## 3.4 Pastor Asistente

Puede:

- Ver personas asignadas
- Crear seguimientos pastorales
- Registrar sesiones pastorales
- Ver solicitudes de oración asignadas
- Ver discipulado de personas asignadas

No puede:

- Ver todos los casos confidenciales sin autorización
- Administrar usuarios
- Ver finanzas salvo permiso explícito

---

## 3.5 Tesorero

Puede:

- Ver cuentas financieras
- Registrar ingresos
- Registrar egresos
- Adjuntar comprobantes
- Anular movimientos si tiene permiso
- Ver reportes financieros
- Gestionar presupuestos

No puede:

- Ver casos pastorales
- Modificar contabilidad aprobada
- Administrar usuarios

---

## 3.6 Contador

Puede:

- Ver movimientos financieros
- Ver comprobantes
- Gestionar plan de cuentas
- Crear asientos
- Aprobar asientos si tiene permiso
- Cerrar períodos si tiene permiso
- Generar reportes contables

No puede:

- Crear casos pastorales
- Acceder a consejería
- Modificar personas salvo lectura necesaria para reportes

---

## 3.7 Líder de Ministerio

Puede:

- Ver su ministerio
- Gestionar equipos de su ministerio
- Ver personas asociadas a su equipo
- Crear actividades
- Enviar comunicaciones a su grupo si tiene permiso

No puede:

- Ver finanzas globales
- Ver casos pastorales
- Administrar roles

---

## 3.8 Mentor / Discipulador

Puede:

- Ver personas asignadas
- Registrar avance de discipulado
- Registrar mentorías
- Ver rutas de discipulado asignadas

No puede:

- Ver casos pastorales confidenciales
- Ver finanzas
- Administrar CRM completo

---

## 3.9 Miembro

Puede:

- Ver su perfil
- Actualizar datos permitidos
- Ver actividades públicas
- Ver cursos o rutas asignadas
- Enviar solicitudes de oración
- Ver comunicaciones recibidas

No puede:

- Ver CRM de otras personas
- Ver finanzas
- Ver reportes administrativos
- Ver casos pastorales

---

## 3.10 Usuario App

Usuario general de la app FeActiva.

Puede:

- Acceder a contenido permitido
- Ver recursos formativos
- Usar funciones básicas de app

No tiene acceso al área administrativa de FeActiva Iglesia.

---

# 4. PERMISOS POR MÓDULO

## 4.1 Core SaaS

```text
saas.tenants.ver
saas.tenants.crear
saas.tenants.editar
saas.tenants.suspender

saas.planes.ver
saas.planes.crear
saas.planes.editar

saas.modulos.ver
saas.modulos.activar
saas.modulos.desactivar

saas.configuracion.ver
saas.configuracion.editar
4.2 Auth
auth.usuarios.ver
auth.usuarios.crear
auth.usuarios.editar
auth.usuarios.desactivar

auth.roles.ver
auth.roles.crear
auth.roles.editar
auth.roles.eliminar

auth.permisos.ver
auth.permisos.asignar

auth.sesiones.ver
auth.sesiones.cerrar
4.3 CRM
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

crm.membresia.ver
crm.membresia.editar
4.4 Discipulado
disc.rutas.ver
disc.rutas.crear
disc.rutas.editar
disc.rutas.eliminar

disc.avance.ver
disc.avance.editar

disc.mentorias.ver
disc.mentorias.crear
disc.mentorias.editar

disc.registros.ver
disc.registros.crear
disc.registros.editar
4.5 Pastoral
past.casos.ver
past.casos.ver_confidencial
past.casos.crear
past.casos.editar
past.casos.cerrar
past.casos.derivar

past.sesiones.ver
past.sesiones.crear
past.sesiones.editar

past.oracion.ver
past.oracion.crear
past.oracion.editar
past.oracion.cerrar
4.6 Ministerios
min.ministerios.ver
min.ministerios.crear
min.ministerios.editar
min.ministerios.eliminar

min.equipos.ver
min.equipos.crear
min.equipos.editar
min.equipos.eliminar

min.miembros.ver
min.miembros.asignar
min.miembros.remover

min.actividades.ver
min.actividades.crear
min.actividades.editar
min.actividades.eliminar
4.7 Comunicación
com.canales.ver
com.canales.configurar

com.plantillas.ver
com.plantillas.crear
com.plantillas.editar
com.plantillas.eliminar

com.mensajes.ver
com.mensajes.crear
com.mensajes.enviar
com.mensajes.programar

com.segmentos.ver
com.segmentos.crear
com.segmentos.editar
com.segmentos.eliminar

com.automatizaciones.ver
com.automatizaciones.crear
com.automatizaciones.editar
com.automatizaciones.desactivar
4.8 Finanzas
fin.cuentas.ver
fin.cuentas.crear
fin.cuentas.editar
fin.cuentas.eliminar

fin.categorias.ver
fin.categorias.crear
fin.categorias.editar
fin.categorias.eliminar

fin.centros_costo.ver
fin.centros_costo.crear
fin.centros_costo.editar
fin.centros_costo.eliminar

fin.movimientos.ver
fin.movimientos.crear
fin.movimientos.editar
fin.movimientos.anular

fin.documentos.ver
fin.documentos.crear
fin.documentos.eliminar

fin.presupuestos.ver
fin.presupuestos.crear
fin.presupuestos.editar
fin.presupuestos.aprobar
fin.presupuestos.eliminar

fin.reportes.ver
fin.reportes.exportar
4.9 Contabilidad
acct.configuracion.ver
acct.configuracion.editar

acct.cuentas.ver
acct.cuentas.crear
acct.cuentas.editar
acct.cuentas.eliminar

acct.periodos.ver
acct.periodos.crear
acct.periodos.editar
acct.periodos.cerrar
acct.periodos.abrir

acct.asientos.ver
acct.asientos.crear
acct.asientos.editar
acct.asientos.aprobar
acct.asientos.anular
acct.asientos.reversar

acct.mapeo.ver
acct.mapeo.crear
acct.mapeo.editar
acct.mapeo.eliminar

acct.reportes.ver
acct.reportes.exportar
4.10 Reportes / BI
rep.reportes.ver
rep.reportes.generar
rep.reportes.exportar

bi.dashboard.ver
bi.crm.ver
bi.finanzas.ver
bi.discipulado.ver
bi.pastoral.ver
bi.contabilidad.ver
4.11 Legal / Auditoría
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
audit.sensitive.registrar
5. MATRIZ BASE DE ROLES Y PERMISOS
5.1 Super Admin FeActiva

Tiene permisos:

saas.*
auth.*
rep.*
audit.logs.ver

No incluye por defecto:

past.casos.ver_confidencial
past.sesiones.ver
fin.movimientos.ver
acct.asientos.ver
5.2 Administrador de Iglesia

Permisos sugeridos:

auth.usuarios.*
auth.roles.*
crm.*
min.*
com.*
rep.reportes.ver
rep.reportes.generar
bi.dashboard.ver
legal.configuracion.ver
5.3 Pastor Principal

Permisos sugeridos:

crm.personas.ver
crm.personas.crear
crm.personas.editar
crm.familias.ver
crm.contactos.*

disc.*
past.*
min.ministerios.ver
min.equipos.ver
min.miembros.ver

rep.reportes.ver
bi.dashboard.ver
bi.crm.ver
bi.discipulado.ver
bi.pastoral.ver
5.4 Pastor Asistente

Permisos sugeridos:

crm.personas.ver
crm.contactos.ver
crm.contactos.crear

disc.avance.ver
disc.avance.editar
disc.mentorias.ver
disc.mentorias.crear

past.casos.ver
past.casos.crear
past.casos.editar
past.sesiones.ver
past.sesiones.crear
past.oracion.ver
past.oracion.crear
5.5 Tesorero

Permisos sugeridos:

fin.cuentas.ver
fin.cuentas.crear
fin.cuentas.editar

fin.categorias.ver
fin.centros_costo.ver

fin.movimientos.ver
fin.movimientos.crear
fin.movimientos.editar
fin.movimientos.anular

fin.documentos.*
fin.presupuestos.*
fin.reportes.ver
fin.reportes.exportar

bi.finanzas.ver
5.6 Contador

Permisos sugeridos:

fin.movimientos.ver
fin.documentos.ver
fin.reportes.ver
fin.reportes.exportar

acct.configuracion.ver
acct.cuentas.*
acct.periodos.*
acct.asientos.*
acct.mapeo.*
acct.reportes.ver
acct.reportes.exportar

bi.contabilidad.ver
5.7 Líder de Ministerio

Permisos sugeridos:

crm.personas.ver
min.ministerios.ver
min.equipos.ver
min.equipos.editar
min.miembros.ver
min.miembros.asignar
min.actividades.*

com.mensajes.crear
com.mensajes.enviar
com.plantillas.ver

Restricción:

Solo sobre ministerios/equipos donde participa o lidera.

5.8 Mentor

Permisos sugeridos:

crm.personas.ver
disc.avance.ver
disc.avance.editar
disc.mentorias.ver
disc.mentorias.crear
disc.registros.ver
past.oracion.crear

Restricción:

Solo sobre personas asignadas.

5.9 Miembro

Permisos sugeridos:

crm.perfil.ver
crm.perfil.editar

disc.mi_avance.ver

past.oracion.crear

com.mis_mensajes.ver
min.mis_actividades.ver
6. REGLAS DE ACCESO ESPECIALES
6.1 Datos pastorales confidenciales

Para ver casos confidenciales se requiere:

past.casos.ver_confidencial

Además, debe cumplirse al menos una condición:

Usuario es Pastor Principal.
Usuario es responsable asignado del caso.
Usuario tiene autorización explícita.
Usuario es Super Admin con acceso temporal aprobado y auditado.
6.2 Finanzas

Los permisos financieros no se heredan automáticamente por ser pastor o administrador.

Esto evita que un usuario administrativo vea donaciones o egresos sin autorización.

6.3 Contabilidad

Solo contador, tesorero autorizado o admin con permiso explícito pueden aprobar o cerrar períodos.

6.4 Reportes

Un usuario solo puede generar reportes de módulos donde tenga permiso base.

Ejemplo:

Si no tiene:

fin.movimientos.ver

No puede ver:

bi.finanzas.ver
fin.reportes.ver
6.5 Exportaciones

Toda exportación debe:

Validar permiso.
Registrar auditoría.
Guardar filtros usados.
Registrar usuario.
Registrar fecha.
Registrar formato.
7. VALIDACIÓN BACKEND OBLIGATORIA

Antes de permitir una acción:

Validar sesión.
Obtener user_id.
Obtener tenant_id desde sesión o contexto seguro.
Validar que el usuario pertenece al tenant.
Validar que el tenant está activo.
Validar que el módulo está activo.
Validar permiso requerido.
Validar reglas adicionales del recurso.
Ejecutar acción.
Auditar si corresponde.
8. RESPUESTAS API POR PERMISOS
8.1 No autenticado
{
  "success": false,
  "error": {
    "code": "UNAUTHENTICATED",
    "message": "Debe iniciar sesión."
  }
}
8.2 Sin acceso al tenant
{
  "success": false,
  "error": {
    "code": "TENANT_ACCESS_DENIED",
    "message": "No tiene acceso a esta iglesia."
  }
}
8.3 Módulo inactivo
{
  "success": false,
  "error": {
    "code": "MODULE_DISABLED",
    "message": "Este módulo no está activo para esta iglesia."
  }
}
8.4 Sin permiso
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "No tiene permisos para realizar esta acción."
  }
}
9. CRITERIO DE ÉXITO

El sistema de roles y permisos estará correctamente implementado cuando:

Un usuario pueda tener roles distintos en tenants distintos.
El backend valide permisos siempre.
El frontend oculte opciones no autorizadas.
Los módulos inactivos no sean accesibles.
Los datos sensibles estén protegidos.
Las finanzas no sean visibles sin permiso explícito.
Las exportaciones queden auditadas.
Los casos pastorales confidenciales solo sean visibles por usuarios autorizados.