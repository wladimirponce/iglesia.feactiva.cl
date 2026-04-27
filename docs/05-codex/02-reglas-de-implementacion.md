# FEACTIVA IGLESIA SAAS — REGLAS DE IMPLEMENTACIÓN PARA CODEX

## 1. Objetivo

Definir cómo Codex debe implementar código en FeActiva Iglesia SaaS.

Este documento regula:

- Estructura de archivos
- Estilo de código
- Backend
- Frontend
- Base de datos
- Validaciones
- Seguridad
- Testing
- Commits incrementales

---

# 2. Regla principal

Codex debe implementar siempre de forma:

```text
incremental
modular
segura
reversible
testeable

Nunca debe generar cambios masivos sin separación clara.

3. Alcance por tarea

Antes de implementar, Codex debe identificar:

1. Módulo afectado
2. Archivos a modificar
3. Tablas involucradas
4. Endpoints involucrados
5. Permisos necesarios
6. Validaciones requeridas
7. Auditoría necesaria

Si una tarea pide CRM, Codex no debe modificar Finanzas.

Si una tarea pide Finanzas, Codex no debe modificar Contabilidad salvo que la integración esté explícitamente solicitada.

4. Estructura backend obligatoria
/backend
  /routes
    crm.routes.php
    finanzas.routes.php
    contabilidad.routes.php

  /controllers
    CrmPersonasController.php
    FinanzasMovimientosController.php
    ContabilidadAsientosController.php

  /services
    CrmPersonasService.php
    FinanzasMovimientosService.php
    ContabilidadAsientosService.php

  /repositories
    CrmPersonasRepository.php
    FinanzasMovimientosRepository.php
    ContabilidadAsientosRepository.php

  /validators
    CrmPersonasValidator.php
    FinanzasMovimientosValidator.php
    ContabilidadAsientosValidator.php

  /middlewares
    AuthMiddleware.php
    TenantMiddleware.php
    PermissionMiddleware.php
    ModuleMiddleware.php

  /helpers
    ResponseHelper.php
    AuditHelper.php
    LoggerHelper.php
5. Flujo obligatorio por endpoint
Route
→ Middleware Auth
→ Middleware Tenant
→ Middleware Module
→ Middleware Permission
→ Controller
→ Validator
→ Service
→ Repository
→ ResponseHelper
6. Responsabilidad por capa
Route

Solo define:

método
ruta
controller
permiso requerido
módulo requerido

No debe contener lógica.

Controller

Debe:

recibir request
llamar validator
llamar service
retornar respuesta

No debe tener SQL.

Validator

Debe validar:

campos requeridos
tipos de datos
formatos
rangos
enum permitidos
Service

Debe contener:

reglas de negocio
transacciones
validaciones cruzadas
auditoría
coordinación entre repositorios
Repository

Debe contener:

SQL preparado
consultas por tenant
acceso a datos

No debe contener reglas de negocio complejas.

7. Base de datos

Codex debe:

usar PDO
usar prepared statements
usar named parameters
filtrar siempre por tenant_id
evitar SELECT *
usar columnas explícitas
usar transacciones cuando corresponda

Ejemplo correcto:

$sql = "
    SELECT id, nombres, apellidos, email, estado_persona
    FROM crm_personas
    WHERE tenant_id = :tenant_id
      AND deleted_at IS NULL
    ORDER BY apellidos, nombres
    LIMIT :limit OFFSET :offset
";
8. Prohibiciones SQL

Codex no puede:

usar SQL concatenado
usar SELECT *
omitir tenant_id
borrar físicamente datos críticos
modificar tablas no solicitadas
crear campos no definidos
crear relaciones no especificadas
9. Transacciones obligatorias

Usar transacción en:

crear movimiento financiero
anular movimiento financiero
crear asiento contable
aprobar asiento contable
reversar asiento contable
crear tenant completo
asignar ruta de discipulado con etapas
crear persona con familia/etiquetas

Ejemplo:

$pdo->beginTransaction();

try {
    // operaciones
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}
10. Respuestas API

Codex debe usar siempre ResponseHelper.

Éxito
return ResponseHelper::success($data, 'Operación realizada correctamente');
Error
return ResponseHelper::error('VALIDATION_ERROR', 'Datos inválidos', $details, 422);
11. Validación de permisos

Cada endpoint debe definir permiso.

Ejemplo:

$router->post(
    '/api/v1/crm/personas',
    [CrmPersonasController::class, 'store'],
    [
        'module' => 'crm',
        'permission' => 'crm.personas.crear'
    ]
);
12. Auditoría

Toda acción crítica debe llamar a AuditHelper.

Ejemplo:

AuditHelper::log([
    'tenant_id' => $tenantId,
    'user_id' => $userId,
    'module_code' => 'crm',
    'action' => 'crm.persona.created',
    'table_name' => 'crm_personas',
    'record_id' => $personaId,
    'old_values' => null,
    'new_values' => $data
]);
13. Manejo de errores

Codex debe:

capturar excepciones
registrar error técnico
retornar mensaje seguro

Nunca mostrar:

SQL
stack trace
credenciales
rutas internas
variables sensibles
14. Frontend

Codex debe:

mantener estética FeActiva
usar componentes reutilizables
separar JS por módulo
no mezclar HTML con lógica compleja
validar formularios antes de enviar
manejar loading/error/empty states

Estructura sugerida:

/frontend
  /assets
  /components
  /pages
    /admin
      /crm
      /finanzas
      /contabilidad
  /js
    crm.js
    finanzas.js
    contabilidad.js
  /css
15. UI obligatoria por módulo

Cada módulo debe tener:

listado
filtros
formulario crear/editar
detalle
acciones permitidas según rol
estado vacío
estado cargando
estado error
16. Seguridad frontend

Frontend puede ocultar botones según permisos, pero:

backend siempre valida
frontend nunca decide seguridad final
17. Naming de código
Clases
PascalCase
CrmPersonasService
Métodos
camelCase
crearPersona()
listarPersonas()
Variables
camelCase
tenantId
personaId
Tablas/campos
snake_case
crm_personas
tenant_id
18. Archivos por módulo

Codex debe crear archivos separados por dominio.

Ejemplo CRM:

CrmPersonasController.php
CrmPersonasService.php
CrmPersonasRepository.php
CrmPersonasValidator.php
crm.routes.php

No crear un solo archivo gigante.

19. Testing mínimo

Por cada módulo, Codex debe preparar pruebas o checklist para:

crear
listar
ver detalle
editar
eliminar/anular
validar permisos
validar tenant
validar módulo inactivo
validar datos inválidos
20. Migraciones SQL

Cada cambio de base de datos debe ir en archivo separado:

/database/migrations
  20260426_001_create_core_tables.sql
  20260426_002_create_crm_tables.sql
  20260426_003_create_finanzas_tables.sql

Reglas:

no mezclar módulos en una misma migración
incluir rollback si aplica
respetar orden de dependencias
21. Seeds

Datos iniciales deben ir en:

/database/seeds

Ejemplos:

seed_modules.sql
seed_roles.sql
seed_permissions.sql
seed_financial_categories.sql
22. Orden de implementación recomendado

Codex debe implementar en este orden:

1. Migraciones core
2. Seeds core
3. Auth + tenant middleware
4. Permission middleware
5. Module middleware
6. CRM backend
7. CRM UI
8. Finanzas backend
9. Finanzas UI
10. Contabilidad backend
11. Contabilidad UI
12. Discipulado backend
13. Pastoral backend
14. Comunicación backend
15. Reportes/BI
23. Control de cambios

Codex debe trabajar por tareas pequeñas.

Ejemplo correcto:

Tarea: implementar listado de personas CRM
Archivos:
- crm.routes.php
- CrmPersonasController.php
- CrmPersonasService.php
- CrmPersonasRepository.php

Ejemplo incorrecto:

Implementar todo el CRM completo en una sola respuesta.
24. Regla anti-improvisación

Si falta información, Codex debe:

1. Revisar documentos existentes
2. Inferir solo si está alineado con arquitectura
3. No inventar campos ni tablas
4. Marcar pendiente si falta definición
25. Checklist antes de entregar código

Codex debe verificar:

¿Respeta tenant_id?
¿Valida permisos?
¿Valida módulo activo?
¿Usa prepared statements?
¿Audita acciones críticas?
¿Respeta estándar REST?
¿Usa ResponseHelper?
¿No modifica módulos ajenos?
¿No expone errores técnicos?
¿Respeta nombres definidos?
26. Criterio de éxito

La implementación será válida cuando:

El código sea modular.
El backend sea seguro.
Las queries filtren por tenant.
Los permisos se validen en backend.
Los módulos puedan activarse/desactivarse.
Las acciones críticas se auditen.
Las tablas y endpoints respeten los documentos.
El sistema pueda crecer sin rehacer lo ya construido.