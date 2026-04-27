# FeActiva Iglesia SaaS — Principios de Arquitectura

## 1. Objetivo

Este documento define las reglas técnicas base para desarrollar FeActiva Iglesia SaaS.

Codex debe usar este documento como contrato obligatorio antes de crear tablas, endpoints, pantallas o lógica de negocio.

---

## 2. Arquitectura general

FeActiva Iglesia debe construirse como un sistema:

- SaaS
- Multi-tenant
- Modular
- Seguro
- Escalable
- Auditable
- Preparado para distintos países
- Compatible con uso independiente o conjunto de módulos

---

## 3. Separación de mundos

FeActiva tiene dos áreas principales:

### 3.1 FeActiva App

Orientada a miembros y usuarios finales.

Incluye:

- Libros
- Videos
- Aula
- Mentor IA
- Estudios
- Perfil
- Comunidad
- Soporte

### 3.2 FeActiva Iglesia

Orientada a administración pastoral, ministerial y financiera.

Incluye:

- Personas
- Discipulado
- Pastoral
- Ministerios
- Finanzas
- Contabilidad
- Comunicación
- Reportes
- Cumplimiento legal
- Configuración

Estas áreas pueden compartir autenticación, identidad visual y usuarios, pero deben mantener lógica funcional separada.

---

## 4. Principio multi-tenant

Cada iglesia debe operar como un tenant independiente.

Reglas obligatorias:

1. Toda tabla funcional debe tener `tenant_id`.
2. Ninguna consulta debe devolver datos de otro tenant.
3. Toda operación de lectura, creación, edición o eliminación debe validar `tenant_id`.
4. El `tenant_id` debe obtenerse desde la sesión/autenticación, no desde parámetros manipulables del frontend.
5. El Super Admin FeActiva puede administrar varios tenants, pero debe hacerlo mediante permisos explícitos.
6. Nunca se debe confiar en el `tenant_id` enviado por el cliente.

---

## 5. Tipos de tablas

El sistema debe separar las tablas en categorías.

### 5.1 Tablas globales

No pertenecen a una iglesia específica.

Ejemplos:

- `saas_tenants`
- `saas_modules`
- `saas_plans`
- `saas_plan_modules`
- `saas_countries`
- `saas_currencies`

Estas tablas pueden no tener `tenant_id`.

### 5.2 Tablas funcionales

Pertenecen a una iglesia.

Ejemplos:

- `crm_personas`
- `fin_movimientos`
- `disc_rutas`
- `com_mensajes`

Estas tablas siempre deben tener `tenant_id`.

### 5.3 Tablas de auditoría

Registran acciones del sistema.

Ejemplo:

- `audit_logs`

Deben incluir `tenant_id` cuando la acción corresponda a una iglesia.

### 5.4 Tablas puente

Relacionan entidades.

Ejemplos:

- `user_tenants`
- `crm_persona_familias`
- `min_persona_equipos`

Deben incluir `tenant_id` cuando relacionan datos funcionales.

---

## 6. Convención de nombres

Usar prefijos por dominio.

### 6.1 Prefijos recomendados

- `saas_` para núcleo SaaS
- `auth_` para autenticación y seguridad
- `crm_` para personas y membresía
- `disc_` para discipulado
- `past_` para seguimiento pastoral
- `min_` para ministerios
- `fin_` para finanzas
- `acct_` para contabilidad
- `com_` para comunicación
- `rep_` para reportes
- `audit_` para auditoría
- `legal_` para cumplimiento legal

### 6.2 Reglas

1. Los nombres deben estar en español o inglés técnico, pero no mezclados sin criterio.
2. Para este proyecto se usará español funcional con prefijo técnico.
3. Todas las tablas deben estar en plural cuando representen colecciones.
4. Las claves primarias deben llamarse `id`.
5. Las claves foráneas deben usar el formato `entidad_id`.

Ejemplos:

- `tenant_id`
- `persona_id`
- `usuario_id`
- `ministerio_id`
- `movimiento_id`

---

## 7. Campos estándar

Toda tabla funcional debe incluir:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
tenant_id BIGINT UNSIGNED NOT NULL,
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
created_by BIGINT UNSIGNED NULL,
updated_by BIGINT UNSIGNED NULL,
deleted_at DATETIME NULL

Cuando una tabla sea crítica, también puede incluir:

deleted_by BIGINT UNSIGNED NULL,
status VARCHAR(50) NOT NULL DEFAULT 'activo'
8. Eliminación lógica

Por defecto, no se deben eliminar físicamente registros críticos.

Usar deleted_at.

Aplica especialmente a:

Personas
Finanzas
Contabilidad
Pastoral
Usuarios
Permisos
Donaciones
Auditoría

La eliminación física solo se permite en tablas temporales o datos claramente no críticos.

9. Auditoría obligatoria

Toda acción crítica debe registrarse.

Debe auditarse:

Crear persona
Editar persona
Eliminar persona
Crear ingreso financiero
Crear egreso financiero
Anular movimiento financiero
Crear asiento contable
Modificar usuario
Cambiar permisos
Activar/desactivar módulos
Acceder a datos pastorales sensibles
Exportar información

La auditoría debe registrar:

tenant_id
usuario_id
accion
modulo
tabla_afectada
registro_id
valores_anteriores
valores_nuevos
ip
user_agent
created_at
10. Activación de módulos

Cada tenant puede tener módulos activos o inactivos.

Debe existir una relación entre tenant y módulo.

Reglas:

La interfaz debe ocultar módulos inactivos.
La API debe bloquear módulos inactivos.
El backend nunca debe depender solo del frontend para validar acceso.
Si un módulo está inactivo, sus endpoints deben responder con error controlado.
Un módulo inactivo no debe romper el dashboard.
11. Independencia modular

Cada módulo debe tener dominio propio.

Reglas:

El módulo CRM no debe depender de Finanzas.
Finanzas puede relacionarse opcionalmente con CRM mediante persona_id.
Contabilidad puede consumir movimientos financieros, pero no reemplaza a Finanzas.
Comunicación puede usar segmentos del CRM, pero debe poder enviar mensajes manuales.
Reportes solo debe leer datos.
Pastoral debe tener permisos más estrictos que CRM.
Discipulado depende de CRM, pero no de Finanzas.
Ministerios depende de CRM, pero no de Contabilidad.
12. Seguridad

El sistema debe implementar:

Contraseñas con password_hash
Validación de sesión
Regeneración de sesión después de login
CSRF en formularios
Prepared statements
Validación de entrada
Escape de salida
Control de permisos por acción
Control de permisos por módulo
Control por tenant
Registro de acciones críticas
Protección contra XSS
Protección contra SQL Injection
Manejo seguro de errores

Nunca mostrar errores técnicos al usuario final en producción.

13. Control de permisos

Los permisos deben ser granulares.

Formato recomendado:

modulo.accion

Ejemplos:

crm.personas.ver
crm.personas.crear
crm.personas.editar
crm.personas.eliminar

fin.movimientos.ver
fin.movimientos.crear
fin.movimientos.anular

acct.asientos.ver
acct.asientos.crear

past.casos.ver
past.casos.crear
past.casos.editar

saas.modulos.activar

Un rol agrupa permisos.

Un usuario puede tener roles distintos en tenants distintos.

14. Roles base

Roles mínimos:

super_admin
admin_iglesia
pastor_principal
pastor_asistente
tesorero
contador
lider_ministerio
mentor
miembro
usuario_app

Los roles deben ser configurables por tenant, pero estos pueden existir como plantilla inicial.

15. APIs

La API debe seguir principios REST.

Métodos:

GET para consultar
POST para crear
PUT para reemplazar
PATCH para actualizar parcialmente
DELETE para eliminación lógica
OPTIONS para preflight si aplica

Respuestas recomendadas:

200 OK
201 Created
204 No Content
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
409 Conflict
422 Unprocessable Entity
500 Internal Server Error

Todas las respuestas deben usar JSON.

Formato base:

{
  "success": true,
  "data": {},
  "message": "Operación realizada correctamente"
}

Errores:

{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Datos inválidos",
    "details": {}
  }
}
16. Frontend

El frontend debe:

Mantener identidad visual FeActiva
Usar componentes reutilizables
Separar vistas por módulo
Validar formularios antes de enviar
Mostrar errores claros
No exponer datos sensibles innecesarios
Ocultar módulos no activos
Adaptarse a escritorio y móvil
17. Backend

El backend debe:

Separar rutas/controladores/servicios/repositorios
Validar permisos antes de ejecutar acciones
Validar módulo activo
Validar tenant
Usar transacciones en operaciones críticas
Registrar auditoría
Manejar errores sin exponer detalles internos
18. Base de datos

La base de datos debe:

Usar claves primarias numéricas
Usar claves foráneas cuando sea posible
Indexar tenant_id
Indexar campos de búsqueda frecuente
Usar utf8mb4
Usar InnoDB
Mantener integridad referencial
Evitar duplicidad innecesaria

Índices mínimos recomendados en tablas funcionales:

INDEX idx_tenant_id (tenant_id),
INDEX idx_deleted_at (deleted_at),
INDEX idx_created_at (created_at)
19. Manejo financiero

Las tablas financieras deben considerar:

Moneda
País
Tipo de movimiento
Medio de pago
Documento asociado
Centro de costo
Usuario responsable
Fecha contable
Fecha real del movimiento
Estado del movimiento
Posibilidad de anulación, no borrado

Regla clave:

Un movimiento financiero no se borra; se anula.

20. Manejo contable

La contabilidad debe manejarse con lógica formal.

Debe permitir:

Plan de cuentas configurable
Asientos de doble partida
Débito y crédito
Libro diario
Libro mayor
Estados financieros
Centros de costo
Exportación

Regla clave:

Todo asiento contable debe cuadrar: total debe = total haber.

21. Cumplimiento internacional

El sistema debe ser configurable por país.

Debe considerar:

País
Moneda
Idioma
Zona horaria
Formato de fecha
Normas tributarias locales
Requisitos documentales
Tipo de entidad religiosa/legal

No se deben codificar reglas legales rígidas en el núcleo.

Las reglas por país deben vivir en configuración o módulos específicos.

22. Reportes

Los reportes deben:

Ser filtrables por fechas
Respetar tenant
Respetar permisos
Exportarse a PDF/Excel
No modificar datos
Indicar fecha de generación
Indicar usuario que generó el reporte
23. Logs y errores

El sistema debe registrar errores internos en logs.

No debe mostrar:

SQL completo
Rutas internas sensibles
Credenciales
Stack traces
Variables de entorno

El usuario debe ver mensajes simples y seguros.

24. Compatibilidad futura con IA

El sistema debe preparar sus datos para futuras funciones de IA.

Ejemplos:

Recomendaciones pastorales
Alertas de ausencia
Segmentación inteligente
Resúmenes financieros
Generación de mensajes
Análisis de crecimiento
Asistencia para sermones y discipulado

La IA nunca debe acceder a datos sensibles sin permisos explícitos.

25. Reglas para Codex

Codex debe:

Leer este documento antes de implementar.
No crear funcionalidades fuera del alcance solicitado.
No cambiar nombres de tablas sin autorización.
No eliminar campos estándar.
No omitir tenant_id.
No omitir validaciones de permisos.
No omitir prepared statements.
No mezclar módulos sin contrato.
No crear datos globales cuando deben pertenecer a un tenant.
No borrar físicamente registros críticos.
No exponer errores técnicos en frontend.
Mantener cambios pequeños, incrementales y reversibles.
26. Regla final

Toda nueva funcionalidad debe responder estas preguntas antes de implementarse:

¿A qué tenant pertenece?
¿Qué módulo la controla?
¿Qué permisos requiere?
¿Debe auditarse?
¿Puede funcionar si otro módulo está desactivado?
¿Qué datos sensibles maneja?
¿Qué reportes se verán afectados?
¿Qué tablas toca?
¿Qué endpoints necesita?
¿Cómo se prueba?