# FEACTIVA IGLESIA SAAS — FLUJOS PRINCIPALES

## 1. Objetivo

Definir los flujos críticos del sistema FeActiva Iglesia SaaS.

Este documento guía a Codex en:

- Cómo se comporta el sistema
- En qué orden ocurren las acciones
- Qué validaciones se aplican
- Qué módulos interactúan entre sí

⚠️ Esto es clave: sin flujos, Codex solo construye CRUD. Con flujos, construye sistema real.

---

# 2. PRINCIPIOS DE FLUJOS

1. Todo flujo debe respetar:
   - tenant_id
   - permisos
   - módulo activo

2. Todo flujo debe:
   - validar entrada
   - ejecutar acción
   - registrar auditoría si aplica

3. Ningún flujo debe:
   - saltarse validaciones
   - mezclar datos entre tenants

4. Los flujos pueden ser:
   - manuales
   - automáticos (event-driven)

---

# 3. FLUJO 1 — CREACIÓN DE IGLESIA (TENANT)

## Actor
Super Admin FeActiva

## Flujo

```text
1. Super Admin crea tenant
2. Sistema valida datos básicos
3. Se crea registro en saas_tenants
4. Se asigna plan
5. Se crean módulos base según plan
6. Se crea usuario admin inicial
7. Se asigna rol admin_iglesia
8. Se crean:
   - configuración legal
   - configuración contable
   - cuenta financiera inicial
   - categorías financieras
   - estados de membresía
9. Se registra auditoría
Resultado

Tenant operativo con configuración base completa.

4. FLUJO 2 — LOGIN Y CONTEXTO DE TENANT
Actor

Usuario

Flujo
1. Usuario ingresa credenciales
2. Sistema valida email/password
3. Sistema obtiene tenants asociados
4. Usuario selecciona iglesia (si tiene más de una)
5. Sistema genera sesión:
   - user_id
   - tenant_id
   - roles
   - permisos
6. Sistema retorna token o sesión activa
Validaciones
Usuario activo
Relación user-tenant activa
5. FLUJO 3 — CREACIÓN DE PERSONA (CRM)
Actor

Admin / Pastor / Líder autorizado

Flujo
1. Usuario envía datos persona
2. Backend valida:
   - permiso crm.personas.crear
   - tenant activo
3. Valida duplicados (documento/email)
4. Inserta en crm_personas
5. Asigna estado inicial (visita o nuevo_asistente)
6. Registra historial de membresía
7. Opcional:
   - asigna familia
   - asigna etiquetas
8. Registra auditoría
Resultado

Persona creada en CRM lista para seguimiento.

6. FLUJO 4 — ASIGNACIÓN A DISCIPULADO
Actor

Pastor / Mentor

Flujo
1. Usuario selecciona persona
2. Selecciona ruta de discipulado
3. Backend valida:
   - permiso disc.rutas.asignar
4. Inserta en disc_persona_rutas
5. Genera etapas automáticamente
6. Asigna mentor (si corresponde)
7. Registra auditoría
Resultado

Persona entra en proceso de discipulado.

7. FLUJO 5 — REGISTRO DE MENTORÍA
Actor

Mentor

Flujo
1. Mentor registra sesión
2. Backend valida:
   - permiso disc.mentorias.crear
   - que la persona esté asignada
3. Inserta en disc_mentorias
4. Actualiza avance si corresponde
5. Si hay próxima sesión → agenda seguimiento
6. Registra auditoría
8. FLUJO 6 — CREACIÓN DE CASO PASTORAL
Actor

Pastor / Líder autorizado

Flujo
1. Usuario crea caso
2. Backend valida:
   - permiso past.casos.crear
3. Inserta en past_casos
4. Asigna responsable
5. Marca como confidencial
6. Registra auditoría
Seguridad
Solo usuarios con permiso pueden ver
Acceso queda auditado
9. FLUJO 7 — SESIÓN PASTORAL
Actor

Pastor

Flujo
1. Usuario accede a caso
2. Backend valida acceso confidencial
3. Usuario registra sesión
4. Inserta en past_sesiones
5. Puede definir:
   - acuerdos
   - próxima acción
6. Registra auditoría
10. FLUJO 8 — REGISTRO DE INGRESO
Actor

Tesorero

Flujo
1. Usuario crea ingreso
2. Backend valida:
   - permiso fin.movimientos.crear
3. Inserta en fin_movimientos
4. Asocia:
   - cuenta
   - categoría
   - persona (opcional)
5. Adjunta documento si existe
6. Si contabilidad activa:
   - genera asiento automático (si hay mapeo)
7. Registra auditoría
11. FLUJO 9 — ANULACIÓN DE MOVIMIENTO
Actor

Tesorero autorizado

Flujo
1. Usuario solicita anulación
2. Backend valida:
   - permiso fin.movimientos.anular
3. Marca movimiento como anulado
4. Registra motivo
5. Si hay asiento contable:
   - genera reversa
6. Registra auditoría
12. FLUJO 10 — CREACIÓN DE ASIENTO CONTABLE
Actor

Contador

Flujo
1. Usuario crea asiento
2. Backend valida:
   - permiso acct.asientos.crear
3. Inserta encabezado
4. Inserta detalles
5. Valida doble partida:
   debe == haber
6. Guarda como borrador
13. FLUJO 11 — APROBACIÓN DE ASIENTO
Actor

Contador autorizado

Flujo
1. Usuario aprueba asiento
2. Backend valida:
   - permiso acct.asientos.aprobar
3. Valida:
   - periodo abierto
   - doble partida correcta
4. Marca como aprobado
5. Registra auditoría
14. FLUJO 12 — ENVÍO DE MENSAJE
Actor

Admin / Líder

Flujo
1. Usuario crea mensaje
2. Selecciona:
   - segmento o personas
   - canal
3. Backend valida:
   - permiso com.mensajes.enviar
4. Genera destinatarios
5. Inserta com_mensajes
6. Inserta com_mensaje_destinatarios
7. Envía según canal
8. Actualiza estado
9. Registra auditoría
15. FLUJO 13 — AUTOMATIZACIÓN
Evento ejemplo: nueva persona
1. Se crea persona
2. Sistema detecta evento
3. Busca automatizaciones activas
4. Aplica filtro
5. Genera mensaje automático
6. Programa o envía
7. Registra auditoría
16. FLUJO 14 — GENERACIÓN DE REPORTE
Actor

Usuario autorizado

1. Usuario solicita reporte
2. Backend valida:
   - permiso rep.reportes.generar
3. Ejecuta query
4. Aplica filtros
5. Devuelve datos o archivo
6. Registra en historial
7. Si es sensible:
   - registra acceso en audit_sensitive_access
17. FLUJO 15 — EXPORTACIÓN LEGAL
Actor

Admin / Legal

1. Usuario solicita exportación
2. Backend valida:
   - permiso legal.exportaciones.generar
3. Genera dataset
4. Genera archivo
5. Guarda registro en legal_exportaciones
6. Devuelve link
7. Registra auditoría
18. FLUJO 16 — ACCESO A DATOS SENSIBLES
1. Usuario intenta acceder a:
   - caso pastoral
   - datos personales
   - finanzas detalladas
2. Backend valida permiso especial
3. Si acceso permitido:
   - registra en audit_sensitive_access
4. Devuelve datos
19. FLUJO 17 — CIERRE DE PERÍODO CONTABLE
Actor

Contador

1. Usuario solicita cierre
2. Backend valida:
   - permiso acct.periodos.cerrar
3. Valida:
   - no existan asientos pendientes
4. Marca período como cerrado
5. Bloquea edición futura
6. Registra auditoría
20. FLUJO 18 — SEGURIDAD GLOBAL

Todos los endpoints deben seguir:

1. Validar autenticación
2. Validar tenant
3. Validar módulo activo
4. Validar permiso
5. Validar reglas específicas
6. Ejecutar acción
7. Auditar
21. CRITERIO DE ÉXITO

El sistema estará correctamente diseñado cuando:

Cada acción importante tenga flujo definido
No existan acciones "libres" sin validación
Los módulos se integren entre sí
Las finanzas puedan conectar con contabilidad
CRM conecte con discipulado y pastoral
Comunicación pueda usar segmentos
BI pueda leer todo sin romper seguridad
Todo acceso sensible esté auditado