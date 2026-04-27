# FEACTIVA IGLESIA SAAS — PLAN DE PRUEBAS

## 1. Objetivo

Definir las pruebas mínimas obligatorias para validar que FeActiva Iglesia SaaS funcione de forma segura, modular y multi-tenant.

---

# 2. Principios

Toda funcionalidad debe probar:

- Autenticación
- Tenant correcto
- Módulo activo
- Permiso requerido
- Validación de datos
- Auditoría
- Respuesta API estándar

---

# 3. Pruebas obligatorias por endpoint

Para cada endpoint se debe validar:

```text
[ ] Usuario no autenticado recibe 401
[ ] Usuario sin permiso recibe 403
[ ] Módulo inactivo recibe MODULE_DISABLED
[ ] Tenant incorrecto no accede al recurso
[ ] Datos inválidos reciben 422
[ ] Respuesta exitosa cumple formato estándar
[ ] Acción crítica registra auditoría
4. Pruebas multi-tenant

Crear al menos dos tenants:

Iglesia A
Iglesia B

Validar:

[ ] Usuario de Iglesia A no ve datos de Iglesia B
[ ] IDs manipulados no exponen datos externos
[ ] Consultas siempre filtran por tenant_id
[ ] Reportes solo muestran datos del tenant actual
5. Pruebas Auth
[ ] Login correcto
[ ] Login con contraseña inválida
[ ] Logout correcto
[ ] Token inválido rechazado
[ ] Usuario inactivo no puede ingresar
[ ] Usuario con varios tenants puede seleccionar iglesia
6. Pruebas CRM
[ ] Crear persona
[ ] Editar persona
[ ] Eliminar persona con soft delete
[ ] Buscar persona
[ ] Filtrar por estado
[ ] Cambiar estado de membresía
[ ] Registrar historial de membresía
[ ] Crear familia
[ ] Asociar persona a familia
[ ] Crear contacto
[ ] Asignar etiqueta
7. Pruebas Finanzas
[ ] Crear cuenta financiera
[ ] Crear categoría
[ ] Crear centro de costo
[ ] Registrar ingreso
[ ] Registrar egreso
[ ] Validar monto mayor que cero
[ ] Anular movimiento
[ ] Evitar eliminación física
[ ] Adjuntar documento
[ ] Calcular saldo por cuenta
[ ] Registrar auditoría financiera
8. Pruebas Contabilidad
[ ] Crear cuenta contable
[ ] Crear período
[ ] Crear asiento balanceado
[ ] Rechazar asiento descuadrado
[ ] Aprobar asiento
[ ] Impedir edición de asiento aprobado
[ ] Reversar asiento
[ ] Cerrar período
[ ] Impedir asientos en período cerrado
9. Pruebas Discipulado
[ ] Crear ruta
[ ] Crear etapas
[ ] Asignar persona a ruta
[ ] Generar etapas automáticamente
[ ] Registrar avance
[ ] Registrar mentoría
[ ] Mentor solo ve personas asignadas
10. Pruebas Pastoral
[ ] Crear caso pastoral
[ ] Ver caso con permiso
[ ] Bloquear caso confidencial sin permiso
[ ] Registrar sesión pastoral
[ ] Registrar solicitud de oración
[ ] Registrar derivación
[ ] Auditar acceso sensible
11. Pruebas Comunicación
[ ] Crear canal
[ ] Crear plantilla
[ ] Crear mensaje
[ ] Agregar destinatarios
[ ] Enviar mensaje
[ ] Registrar estado de envío
[ ] Crear segmento
[ ] Crear automatización
12. Pruebas Reportes y BI
[ ] Generar reporte CRM
[ ] Generar reporte financiero
[ ] Generar reporte contable
[ ] Exportar reporte
[ ] Registrar historial de reporte
[ ] BI no modifica datos
[ ] BI respeta permisos
13. Pruebas Legal
[ ] Crear configuración legal
[ ] Registrar consentimiento
[ ] Revocar consentimiento
[ ] Crear solicitud de datos
[ ] Resolver solicitud de datos
[ ] Generar exportación legal
[ ] Auditar descarga
14. Pruebas de seguridad
[ ] SQL Injection bloqueado
[ ] XSS bloqueado
[ ] CSRF protegido si aplica
[ ] Contraseñas hasheadas
[ ] Errores SQL no visibles
[ ] Stack trace no visible
[ ] Sesión regenerada al login
15. Pruebas de performance mínima
[ ] Listados usan paginación
[ ] No se usa SELECT *
[ ] Índices principales existen
[ ] Dashboard carga en tiempo aceptable
[ ] Reportes grandes no bloquean el sistema
16. Criterio de aprobación

Una funcionalidad se aprueba solo si:

[ ] Pasa pruebas funcionales
[ ] Pasa pruebas multi-tenant
[ ] Pasa pruebas de permisos
[ ] Registra auditoría cuando corresponde
[ ] No rompe otros módulos
17. Criterio de rechazo

Se rechaza si:

[ ] Filtra datos de otro tenant
[ ] Omite permisos
[ ] Expone errores técnicos
[ ] Borra datos críticos físicamente
[ ] Permite editar registros cerrados
[ ] No audita acciones sensibles