# FEACTIVA IGLESIA SAAS — CHECKLIST DE VALIDACIÓN

## 1. Objetivo

Definir el checklist obligatorio que Codex (y el desarrollador) deben cumplir antes de considerar una funcionalidad como terminada.

Este checklist garantiza:

- Seguridad
- Consistencia
- Multi-tenant real
- Cumplimiento de arquitectura
- Calidad mínima profesional

---

# 2. REGLA PRINCIPAL

Ningún módulo se considera terminado si:

```text
No cumple el 100% del checklist
3. VALIDACIÓN GENERAL (TODOS LOS MÓDULOS)
3.1 Seguridad
[ ] Se valida autenticación
[ ] Se obtiene tenant_id desde sesión (no request)
[ ] Se valida que el usuario pertenece al tenant
[ ] Se valida que el módulo está activo
[ ] Se valida permiso antes de ejecutar acción
[ ] No hay exposición de datos de otros tenants
3.2 Base de datos
[ ] Todas las queries usan tenant_id
[ ] No existe ningún SELECT sin WHERE tenant_id
[ ] No se usa SELECT *
[ ] Se usan prepared statements
[ ] No hay SQL concatenado
[ ] Las tablas usadas existen en documentación
[ ] Los campos usados existen en documentación
3.3 API
[ ] Endpoint sigue estándar REST (/api/v1/...)
[ ] Método HTTP correcto (GET, POST, PATCH, DELETE)
[ ] Respuesta cumple formato estándar (success/data/error)
[ ] Manejo correcto de códigos HTTP
[ ] Manejo correcto de errores
[ ] Paginación implementada cuando aplica
[ ] Filtros controlados (no abiertos)
3.4 Validaciones
[ ] Campos requeridos validados
[ ] Tipos de datos validados
[ ] Enum validados
[ ] Reglas de negocio validadas
[ ] Relaciones validadas (ej: cuenta pertenece al tenant)
3.5 Auditoría
[ ] Se registra CREATE
[ ] Se registra UPDATE
[ ] Se registra DELETE (soft)
[ ] Se registra ANULACIÓN
[ ] Se registra APROBACIÓN
[ ] Se registra REVERSA
[ ] Se registra EXPORTACIÓN
[ ] Se registra acceso a datos sensibles
3.6 Manejo de errores
[ ] No se exponen errores SQL
[ ] No se expone stack trace
[ ] Mensajes son controlados
[ ] Logs se guardan internamente
4. VALIDACIÓN CRM
[ ] Personas se crean correctamente
[ ] No se duplican documentos dentro del tenant
[ ] Eliminación es lógica (soft delete)
[ ] Historial de membresía se registra
[ ] Contactos se registran correctamente
[ ] Etiquetas funcionan correctamente
[ ] Relaciones familiares correctas
5. VALIDACIÓN DISCIPULADO
[ ] Rutas se crean correctamente
[ ] Etapas se ordenan correctamente
[ ] Persona se asigna a ruta
[ ] Etapas se generan automáticamente
[ ] Mentor solo accede a personas asignadas
[ ] Avance se registra correctamente
[ ] No se completan etapas duplicadas
6. VALIDACIÓN PASTORAL
[ ] Casos se crean correctamente
[ ] Casos confidenciales protegidos
[ ] Solo usuarios autorizados acceden
[ ] Sesiones se registran correctamente
[ ] Accesos quedan auditados
7. VALIDACIÓN FINANZAS
[ ] Se pueden registrar ingresos
[ ] Se pueden registrar egresos
[ ] Montos siempre positivos
[ ] Movimiento pertenece al tenant
[ ] Categoría pertenece al tenant
[ ] Cuenta pertenece al tenant
[ ] Centro de costo pertenece al tenant
[ ] Campaña pertenece al tenant (si aplica)
[ ] No se elimina movimiento (solo anula)
[ ] Anulación registra motivo
[ ] Anulación registra usuario
[ ] Anulación registra fecha
[ ] Se usa transacción en creación
[ ] Se usa transacción en anulación
8. VALIDACIÓN CONTABILIDAD
[ ] Plan de cuentas se crea correctamente
[ ] Código de cuenta es único por tenant
[ ] Se pueden crear períodos
[ ] No se crean asientos fuera de período abierto
[ ] Todo asiento tiene al menos 2 líneas
[ ] Debe = Haber
[ ] No se aprueba asiento descuadrado
[ ] No se edita asiento aprobado
[ ] Reversa crea asiento correcto
[ ] Anulación no elimina asiento
[ ] Integración con finanzas funciona
9. VALIDACIÓN COMUNICACIÓN
[ ] Mensajes se crean correctamente
[ ] Segmentos funcionan correctamente
[ ] Destinatarios se generan correctamente
[ ] Estados de envío se registran
[ ] Automatizaciones se ejecutan correctamente
10. VALIDACIÓN REPORTES / BI
[ ] Reportes no modifican datos
[ ] Reportes respetan tenant
[ ] Reportes respetan permisos
[ ] Exportaciones se registran
[ ] Exportaciones sensibles se auditan
[ ] BI solo lectura
11. VALIDACIÓN LEGAL
[ ] Configuración legal existe por tenant
[ ] Consentimientos se registran
[ ] Consentimientos se pueden revocar
[ ] Solicitudes de datos se registran
[ ] Exportaciones legales se registran
[ ] Exportaciones tienen fecha de expiración
12. VALIDACIÓN UI
[ ] Listados funcionan
[ ] Formularios validan antes de enviar
[ ] Estados loading/error/empty presentes
[ ] Botones ocultos según permisos
[ ] No se muestra información sin permiso
13. VALIDACIÓN DE PERFORMANCE
[ ] Queries tienen índices adecuados
[ ] No hay N+1 queries
[ ] Paginación en listados grandes
[ ] No se cargan datos innecesarios
14. VALIDACIÓN DE TRANSACCIONES
[ ] Operaciones críticas usan beginTransaction
[ ] Rollback en caso de error
[ ] Commit solo si todo es correcto
15. VALIDACIÓN DE MULTI-TENANT
[ ] No existe ningún endpoint sin tenant_id
[ ] No se cruzan datos entre tenants
[ ] No se pueden adivinar IDs de otros tenants
[ ] Queries siempre filtran por tenant
16. VALIDACIÓN DE INTEGRACIÓN
[ ] CRM → Discipulado funciona
[ ] CRM → Pastoral funciona
[ ] Finanzas → Contabilidad funciona
[ ] Comunicación → CRM funciona
[ ] BI → todos los módulos (lectura) funciona
17. VALIDACIÓN FINAL DE MÓDULO

Antes de cerrar módulo:

[ ] Todos los endpoints funcionan
[ ] Todos los permisos están definidos
[ ] Todas las validaciones están implementadas
[ ] Auditoría funciona
[ ] UI mínima operativa
[ ] No rompe otros módulos
18. VALIDACIÓN DE DEPLOY
[ ] Migraciones ejecutan correctamente
[ ] Seeds cargan correctamente
[ ] Variables de entorno configuradas
[ ] Logs activos
[ ] Sin errores críticos
19. CRITERIO DE RECHAZO

El sistema debe considerarse inválido si:

❌ Existe acceso entre tenants
❌ Se puede saltar permisos
❌ Se puede editar datos cerrados
❌ Se pueden eliminar datos críticos
❌ Hay SQL inseguro
❌ No hay auditoría en acciones críticas
❌ API no cumple estándar
❌ Módulos mezclan responsabilidades
20. CRITERIO DE APROBACIÓN

El sistema está listo cuando:

✔ Cumple 100% del checklist
✔ No presenta vulnerabilidades básicas
✔ No rompe arquitectura
✔ No pierde consistencia de datos
✔ Es escalable
✔ Es auditable
✔ Es mantenible
21. RESUMEN FINAL

Este checklist garantiza:

seguridad
consistencia
escalabilidad
multi-tenant real
nivel SaaS profesional

Sin este checklist:

❌ el sistema puede funcionar
✔ pero no es confiable

Con este checklist:

✔ el sistema es profesional
✔ listo para producción
✔ listo para escalar