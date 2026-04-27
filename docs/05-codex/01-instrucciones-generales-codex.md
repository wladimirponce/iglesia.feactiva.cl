# FEACTIVA IGLESIA SAAS — INSTRUCCIONES GENERALES PARA CODEX

## 1. Objetivo

Definir las reglas obligatorias para que Codex genere código consistente, seguro y alineado a la arquitectura del sistema FeActiva Iglesia SaaS.

⚠️ Este documento tiene prioridad sobre cualquier otra instrucción.

---

# 2. PRINCIPIOS INNEGOCIABLES

Codex DEBE cumplir siempre:

```text
1. Respetar arquitectura definida en /docs
2. No inventar estructuras nuevas
3. No cambiar nombres de tablas ni campos
4. No mezclar lógica entre módulos
5. No omitir validaciones de seguridad
6. No generar SQL inseguro
7. No usar datos de otro tenant
8. No exponer datos sensibles sin control
3. ARQUITECTURA OBLIGATORIA
3.1 Estructura backend
/backend
  /controllers
  /services
  /repositories
  /middlewares
  /validators
  /routes
3.2 Separación de responsabilidades

Codex debe respetar:

Controller  → recibe request
Service     → lógica de negocio
Repository  → acceso a base de datos
Validator   → validación de datos
Middleware  → auth, permisos, tenant

❌ PROHIBIDO:

lógica SQL en controllers
lógica de negocio en routes
validaciones en frontend como única capa
4. MULTI-TENANT (CRÍTICO)
4.1 Regla principal
TODAS las queries deben incluir tenant_id

Ejemplo correcto:

SELECT * FROM crm_personas
WHERE tenant_id = :tenant_id

❌ Incorrecto:

SELECT * FROM crm_personas
4.2 Obtención de tenant
tenant_id SIEMPRE viene del token/session
NUNCA del request body o query params
5. SEGURIDAD OBLIGATORIA
5.1 Flujo obligatorio en cada endpoint
1. Validar autenticación
2. Obtener user_id
3. Obtener tenant_id
4. Validar usuario pertenece al tenant
5. Validar módulo activo
6. Validar permisos
7. Validar datos de entrada
8. Ejecutar lógica
9. Auditar si corresponde
5.2 SQL

✔ Usar siempre:

Prepared statements

❌ Prohibido:

SQL concatenado
5.3 Manejo de errores

❌ Nunca:

mostrar errores SQL
mostrar stack traces

✔ Siempre:

{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Mensaje controlado"
  }
}
6. VALIDACIONES

Codex debe:

1. Validar campos requeridos
2. Validar tipos de datos
3. Validar pertenencia al tenant
4. Validar estados válidos
5. Validar reglas de negocio

Ejemplo:

No permitir editar asiento aprobado
No permitir eliminar cuenta con movimientos
No permitir ver caso pastoral sin permiso
7. AUDITORÍA

Codex debe registrar SIEMPRE en acciones críticas:

user_id
tenant_id
modulo
accion
tabla
registro_id
old_values
new_values
timestamp
7.1 Acciones obligatorias a auditar
CREATE
UPDATE
DELETE (soft)
ANULAR
APROBAR
REVERSAR
EXPORTAR
VER DATOS SENSIBLES
8. ESTRUCTURA DE RESPUESTA

Codex debe respetar:

Éxito
{
  "success": true,
  "data": {},
  "meta": {},
  "message": null
}
Error
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Mensaje"
  }
}
9. TRANSACCIONES

Codex debe usar transacciones en:

- creación de movimientos financieros
- generación de asientos contables
- anulaciones
- reversas
- operaciones que afectan múltiples tablas
10. RELACIÓN ENTRE MÓDULOS

Codex debe respetar:

Finanzas → genera datos operativos
Contabilidad → registra formalmente
CRM → base de personas
Discipulado → usa CRM
Pastoral → usa CRM
Comunicación → usa CRM
BI → solo lectura

❌ Prohibido:

que Contabilidad modifique Finanzas
que BI escriba datos
que CRM modifique Contabilidad
11. MANEJO DE ESTADOS

Codex debe respetar estados definidos:

Ejemplo:

fin_movimientos.estado:
- registrado
- anulado

acct_asientos.estado:
- borrador
- aprobado
- anulado

❌ No inventar nuevos estados.

12. SOFT DELETE

Codex debe usar:

deleted_at
deleted_by

❌ Nunca DELETE físico en tablas críticas.

13. FECHAS
Backend trabaja en UTC
Frontend convierte a local
14. NOMENCLATURA
Tablas
modulo_nombre
Ej: crm_personas
Campos
snake_case
IDs
id BIGINT UNSIGNED
15. LOGGING

Codex debe:

✔ registrar errores en log interno
❌ no mostrarlos al usuario

16. PROHIBICIONES ABSOLUTAS

Codex NO puede:

❌ Usar SELECT *
❌ Omitir tenant_id
❌ Crear endpoints fuera del estándar REST
❌ Inventar campos
❌ Cambiar estructura de tablas
❌ Saltarse validaciones
❌ Exponer datos de otros tenants
❌ Mezclar módulos
❌ Eliminar datos financieros o contables
❌ Permitir edición de registros cerrados
17. PRIORIDAD DE DOCUMENTOS

Orden de obediencia:

1. Este documento (Codex instrucciones)
2. /03-api/*
3. /02-database/*
4. /01-producto/*
5. /00-vision/*
18. COMPORTAMIENTO ESPERADO DE CODEX

Cuando Codex genere código:

✔ Debe seguir arquitectura
✔ Debe validar seguridad
✔ Debe respetar multi-tenant
✔ Debe usar prepared statements
✔ Debe ser consistente

19. CRITERIO DE ÉXITO

Codex está funcionando correctamente cuando:

No genera código inseguro
No rompe arquitectura
No mezcla módulos
No ignora tenant_id
No omite permisos
No inventa lógica
No produce endpoints inconsistentes