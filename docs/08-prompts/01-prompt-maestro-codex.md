# FEACTIVA IGLESIA SAAS — PROMPT MAESTRO CODEX

## 1. Objetivo

Este prompt define cómo Codex debe generar el sistema completo FeActiva respetando:

- Arquitectura
- Seguridad
- Multi-tenant
- API REST
- Base de datos
- UI

⚠️ Este prompt debe usarse como base para TODAS las tareas.

---

# 2. CONTEXTO GLOBAL

Estás desarrollando un sistema SaaS llamado **FeActiva Iglesia**.

El sistema es:

- Multi-tenant
- Modular
- Basado en roles y permisos
- Con backend PHP (PDO) + frontend web
- Con arquitectura separada por capas

Debes seguir estrictamente la documentación en:

```text
/docs
3. ARQUITECTURA OBLIGATORIA

Estructura:

/backend
  /routes
  /controllers
  /services
  /repositories
  /validators
  /middlewares
  /helpers

/frontend
  /pages
  /components
  /js
  /css
4. REGLAS CRÍTICAS

Debes cumplir SIEMPRE:

✔ Usar tenant_id en TODAS las queries
✔ Usar prepared statements
✔ Validar permisos en backend
✔ Validar módulo activo
✔ Usar soft delete
✔ Auditar acciones críticas
✔ Respetar endpoints definidos
✔ Respetar nombres de tablas/campos
5. PROHIBICIONES

Nunca:

❌ Usar SELECT *
❌ Concatenar SQL
❌ Omitir tenant_id
❌ Exponer errores SQL
❌ Mezclar módulos
❌ Crear tablas/campos no definidos
❌ Saltarse validaciones
6. FORMATO DE RESPUESTA

Debes entregar:

1. Archivos modificados
2. Código completo (no fragmentos)
3. Explicación breve
7. ORDEN DE DESARROLLO

Sigue este orden:

1. Auth + tenant
2. CRM
3. Finanzas
4. Contabilidad
5. Discipulado
6. Pastoral
7. Comunicación
8. Reportes
8. FORMATO DE TAREA

Ejemplo:

Implementa listado de personas CRM

Debes generar:

- routes
- controller
- service
- repository
- validator
9. VALIDACIÓN OBLIGATORIA

Antes de entregar código, verifica:

¿Usa tenant_id?
¿Valida permisos?
¿Respeta REST?
¿Usa prepared statements?
¿Audita?
¿No rompe otros módulos?
10. OBJETIVO FINAL

Generar un sistema:

✔ Seguro
✔ Modular
✔ Escalable
✔ Multi-tenant real
✔ Nivel SaaS profesional