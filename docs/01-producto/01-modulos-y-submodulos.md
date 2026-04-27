# FEACTIVA IGLESIA SAAS — MÓDULOS Y SUBMÓDULOS

## 1. OBJETIVO DEL DOCUMENTO

Definir todos los módulos y submódulos del sistema FeActiva Iglesia SaaS de forma estructurada, desacoplada y preparada para implementación.

Este documento es el contrato funcional que Codex debe seguir para:

- Crear tablas
- Crear endpoints
- Crear vistas
- Implementar lógica

---

## 2. PRINCIPIOS GENERALES

1. Cada módulo debe ser independiente.
2. Cada módulo puede activarse o desactivarse por tenant.
3. Cada módulo tiene sus propias tablas.
4. La relación entre módulos se realiza solo mediante IDs.
5. Ningún módulo debe romper el sistema si está desactivado.
6. Todo módulo depende de CORE.
7. Todo dato funcional debe incluir `tenant_id`.

---

## 3. LISTADO GENERAL DE MÓDULOS

### CORE (obligatorio)
- SaaS
- Auth
- Auditoría

### FUNCIONALES
- CRM Personas
- Discipulado
- Seguimiento Pastoral
- Ministerios
- Comunicación
- Finanzas
- Contabilidad
- Reportes
- Cumplimiento Legal

---

## 4. MÓDULO: CORE SAAS

### Descripción
Gestiona la estructura SaaS del sistema.

### Submódulos

#### 4.1 Iglesias (Tenants)
- Crear iglesia
- Editar iglesia
- Configurar país, moneda, zona horaria

#### 4.2 Planes
- Definir planes SaaS
- Asociar módulos a planes

#### 4.3 Activación de módulos
- Activar/desactivar módulos por iglesia

#### 4.4 Configuración general
- Parámetros globales por tenant

---

## 5. MÓDULO: AUTH (USUARIOS Y SEGURIDAD)

### Submódulos

#### 5.1 Usuarios
- Crear usuario
- Editar usuario
- Activar/desactivar usuario

#### 5.2 Roles
- Crear roles
- Asignar roles por tenant

#### 5.3 Permisos
- Permisos granulares por módulo
- Formato: modulo.accion

#### 5.4 Sesiones
- Login
- Logout
- Control de sesión

---

## 6. MÓDULO: CRM PERSONAS

### Descripción
Gestión completa de personas y relaciones.

### Submódulos

#### 6.1 Personas
- Crear persona
- Editar persona
- Eliminar (lógico)
- Ver ficha completa

#### 6.2 Familias
- Crear familia
- Asociar personas a familia

#### 6.3 Estados de membresía
- Visita
- Nuevo
- Miembro
- Líder
- Inactivo

#### 6.4 Historial
- Registro de actividades
- Registro de contacto

#### 6.5 Segmentación
- Filtros por estado
- Filtros por edad
- Filtros por ministerio

---

## 7. MÓDULO: DISCIPULADO

### Descripción
Gestión del crecimiento espiritual.

### Submódulos

#### 7.1 Rutas
- Crear rutas de discipulado

#### 7.2 Etapas
- Definir etapas dentro de rutas

#### 7.3 Progreso
- Asignar persona a ruta
- Registrar avance

#### 7.4 Mentoría
- Asignar mentor
- Seguimiento de mentorías

#### 7.5 Registros espirituales
- Bautismo
- Profesión de fe
- Santa Cena

---

## 8. MÓDULO: SEGUIMIENTO PASTORAL

### Descripción
Gestión de acompañamiento pastoral sensible.

### Submódulos

#### 8.1 Consejería
- Crear caso
- Registrar sesiones
- Notas privadas

#### 8.2 Visitas
- Registro de visitas pastorales

#### 8.3 Solicitudes de oración
- Registro de peticiones

#### 8.4 Alertas
- Personas ausentes
- Seguimientos pendientes

#### 8.5 Derivaciones
- Asignar caso a otro líder/profesional

---

## 9. MÓDULO: MINISTERIOS

### Descripción
Organización interna de la iglesia.

### Submódulos

#### 9.1 Ministerios
- Crear ministerio
- Asignar líder

#### 9.2 Equipos
- Crear equipos dentro de ministerio

#### 9.3 Miembros
- Asignar personas a equipos

#### 9.4 Actividades
- Planificar actividades

#### 9.5 Roles internos
- Definir responsabilidades

---

## 10. MÓDULO: COMUNICACIÓN

### Descripción
Gestión de mensajes y contacto.

### Submódulos

#### 10.1 Mensajería
- Envío de mensajes individuales
- Envío masivo

#### 10.2 Canales
- WhatsApp
- Email
- Notificaciones internas

#### 10.3 Plantillas
- Crear plantillas reutilizables

#### 10.4 Segmentos
- Envío por grupo

#### 10.5 Automatizaciones
- Bienvenida automática
- Recordatorios

---

## 11. MÓDULO: FINANZAS

### Descripción
Gestión de ingresos y egresos.

### Submódulos

#### 11.1 Ingresos
- Diezmos
- Ofrendas
- Donaciones

#### 11.2 Egresos
- Gastos operativos
- Sueldos
- Proveedores

#### 11.3 Caja y banco
- Control de caja
- Conciliación bancaria

#### 11.4 Presupuesto
- Presupuesto por período

#### 11.5 Centros de costo
- Ministerios como centros de costo

#### 11.6 Documentos
- Comprobantes
- Soportes

---

## 12. MÓDULO: CONTABILIDAD

### Descripción
Gestión contable formal.

### Submódulos

#### 12.1 Plan de cuentas
- Crear cuentas contables

#### 12.2 Asientos contables
- Registro de doble partida

#### 12.3 Libro diario
- Registro cronológico

#### 12.4 Libro mayor
- Movimientos por cuenta

#### 12.5 Estados financieros
- Balance
- Estado de resultados
- Flujo de caja

---

## 13. MÓDULO: REPORTES

### Descripción
Análisis y visualización de datos.

### Submódulos

#### 13.1 Reportes CRM
- Crecimiento
- Retención

#### 13.2 Reportes financieros
- Ingresos vs gastos

#### 13.3 Reportes ministeriales
- Participación

#### 13.4 Exportación
- PDF
- Excel

---

## 14. MÓDULO: CUMPLIMIENTO LEGAL

### Descripción
Gestión normativa y legal.

### Submódulos

#### 14.1 Protección de datos
- Control de acceso

#### 14.2 Auditoría
- Registro de acciones

#### 14.3 Configuración país
- Normas locales

#### 14.4 Exportación legal
- Documentos oficiales

---

## 15. DEPENDENCIAS ENTRE MÓDULOS

| Módulo         | Depende de |
|----------------|-----------|
| CRM           | CORE      |
| Discipulado   | CRM       |
| Pastoral      | CRM       |
| Ministerios   | CRM       |
| Comunicación  | CORE (opcional CRM) |
| Finanzas      | CORE (opcional CRM) |
| Contabilidad  | Finanzas  |
| Reportes      | Todos     |
| Legal         | CORE      |

---

## 16. REGLA FINAL PARA CODEX

Codex debe:

1. Implementar cada módulo por separado
2. No mezclar lógica entre módulos
3. Respetar dependencias
4. No crear relaciones innecesarias
5. Usar tenant_id siempre
6. Validar permisos siempre
7. Implementar incrementalmente

---

## 17. ORDEN DE IMPLEMENTACIÓN

### MVP

1. CORE
2. AUTH
3. CRM
4. FINANZAS
5. REPORTES
6. AUDITORÍA

### FASE 2

1. DISCIPULADO
2. PASTORAL
3. COMUNICACIÓN

### FASE 3

1. CONTABILIDAD
2. CUMPLIMIENTO LEGAL
3. BI AVANZADO