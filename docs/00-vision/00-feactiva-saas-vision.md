# FeActiva Iglesia SaaS — Visión General del Sistema

## 1. Propósito del sistema

FeActiva Iglesia SaaS es una plataforma modular para iglesias cristianas que permite gestionar de forma integral:

- Personas
- Membresía
- Discipulado
- Seguimiento pastoral
- Ministerios
- Finanzas
- Contabilidad
- Comunicación
- Reportes
- Cumplimiento legal

El sistema debe funcionar como una extensión administrativa de FeActiva, separada de la experiencia de usuario final de la App, Aula, Biblioteca y Mentor IA.

FeActiva App está orientado al miembro.
FeActiva Iglesia está orientado a la administración pastoral, ministerial y contable.

---

## 2. Principio rector

El sistema no debe ser solamente un CRM. Debe ser una plataforma de gestión ministerial, administrativa y financiera.

Principio central:

> Administrar la iglesia con orden, cuidar a las personas con propósito y rendir cuentas con transparencia.

---

## 3. Arquitectura modular

Cada módulo debe poder funcionar de forma independiente, pero también integrarse con los demás módulos.

Esto significa:

- Una iglesia puede usar solo CRM.
- Una iglesia puede usar solo Finanzas.
- Una iglesia puede usar CRM + Discipulado.
- Una iglesia puede usar CRM + Finanzas + Contabilidad.
- Una iglesia puede activar o desactivar módulos según su plan contratado.

Ningún módulo debe romper el sistema si está desactivado.

---

## 4. Módulos principales

### 4.1 Core SaaS

Módulo obligatorio.

Incluye:

- Iglesias / tenants
- Usuarios
- Roles
- Permisos
- Planes contratados
- Módulos activos
- Auditoría
- Configuración general

Todas las tablas del sistema deben relacionarse con una iglesia mediante `tenant_id`.

---

### 4.2 CRM de Personas

Gestiona la información relacional de la iglesia.

Incluye:

- Personas
- Familias
- Miembros
- Visitas
- Estados de membresía
- Historial de contacto
- Segmentación
- Datos personales
- Datos pastorales básicos

Debe poder funcionar sin Finanzas, sin Discipulado y sin Comunicación.

---

### 4.3 Discipulado

Gestiona el crecimiento espiritual y formativo.

Incluye:

- Rutas de discipulado
- Etapas
- Cursos
- Mentores
- Avance por persona
- Certificados internos
- Bautismos
- Profesión de fe
- Participación en Santa Cena

Depende de:

- Core
- CRM de Personas

---

### 4.4 Seguimiento Pastoral

Gestiona acompañamiento sensible.

Incluye:

- Consejería pastoral
- Solicitudes de oración
- Visitas pastorales
- Casos de acompañamiento
- Alertas por ausencia
- Derivaciones internas
- Notas privadas

Debe tener permisos estrictos y trazabilidad completa.

Depende de:

- Core
- CRM de Personas

---

### 4.5 Ministerios y Equipos

Gestiona la organización interna de la iglesia.

Incluye:

- Ministerios
- Equipos
- Líderes
- Servidores
- Responsabilidades
- Disponibilidad
- Participación
- Actividades internas

Depende de:

- Core
- CRM de Personas

---

### 4.6 Finanzas

Gestiona movimientos económicos reales.

Incluye:

- Diezmos
- Ofrendas
- Donaciones
- Promesas de fe
- Campañas
- Egresos
- Caja
- Bancos
- Presupuestos
- Centros de costo
- Comprobantes
- Conciliación bancaria

Puede funcionar sin Discipulado.

Depende de:

- Core
- Opcionalmente CRM de Personas para identificar donantes

---

### 4.7 Contabilidad

Gestiona la estructura contable formal.

No debe confundirse con Finanzas.

Finanzas registra movimientos operativos.
Contabilidad transforma esos movimientos en registros contables.

Incluye:

- Plan de cuentas
- Libro diario
- Libro mayor
- Asientos contables
- Balance
- Estado de resultados
- Flujo de caja
- Centros de costo
- Exportación contable
- Reportes por normativa

Depende de:

- Core
- Finanzas

---

### 4.8 Comunicación

Gestiona los mensajes enviados desde la iglesia.

Incluye:

- WhatsApp
- Email
- Notificaciones internas
- Plantillas
- Segmentos
- Campañas
- Recordatorios
- Cumpleaños
- Invitaciones
- Seguimiento de mensajes

Depende de:

- Core
- Opcionalmente CRM de Personas

---

### 4.9 Reportes y BI

Módulo de lectura y análisis.

Incluye:

- Reportes de membresía
- Reportes de asistencia
- Reportes de crecimiento
- Reportes financieros
- Reportes contables
- Reportes de discipulado
- Reportes ministeriales
- Exportación PDF/Excel

Este módulo no debe modificar datos operativos.

Depende de:

- Core
- Los módulos activos de cada iglesia

---

### 4.10 Cumplimiento Legal

Módulo transversal.

Incluye:

- Protección de datos personales
- Trazabilidad de cambios
- Respaldo documental
- Exportación de información
- Control de acceso
- Auditoría
- Configuración por país
- Reglas fiscales y administrativas por jurisdicción

Debe ser adaptable a:

- Chile
- Estados Unidos
- Latinoamérica
- Organizaciones religiosas
- Entidades sin fines de lucro
- Fundaciones y corporaciones

---

## 5. Reglas de diseño SaaS

### 5.1 Multi-tenant obligatorio

Toda tabla funcional debe incluir:

- `id`
- `tenant_id`
- `created_at`
- `updated_at`
- `created_by`
- `updated_by`
- `deleted_at`, si aplica eliminación lógica

Nunca debe existir información funcional sin `tenant_id`.

---

### 5.2 Módulos activables

Cada iglesia debe poder activar o desactivar módulos.

Debe existir una tabla que controle qué módulos tiene disponibles cada iglesia.

Ejemplo:

- CRM activo
- Finanzas activo
- Contabilidad inactivo
- Comunicación activo
- Discipulado activo

La interfaz debe ocultar módulos no contratados.

La API debe bloquear acceso a módulos no activos.

---

### 5.3 Independencia de módulos

Reglas:

1. Cada módulo debe tener sus propias tablas.
2. Ningún módulo debe depender innecesariamente de otro.
3. Las relaciones entre módulos deben hacerse por IDs.
4. Un módulo puede consumir datos de otro solo mediante servicios, APIs o consultas controladas.
5. Un módulo desactivado no debe romper el dashboard principal.

---

### 5.4 Seguridad

El sistema debe implementar:

- Login seguro
- Hash de contraseñas
- Roles y permisos
- CSRF en formularios
- Validación de entrada
- Escape de salida
- Protección contra SQL Injection
- Auditoría de acciones críticas
- Control por tenant
- Sesiones seguras

---

### 5.5 Auditoría

Todo cambio importante debe quedar registrado.

Debe auditarse especialmente:

- Personas
- Datos pastorales
- Finanzas
- Contabilidad
- Permisos
- Usuarios
- Eliminaciones
- Cambios de configuración

La auditoría debe registrar:

- Usuario
- Iglesia
- Acción
- Tabla afectada
- ID del registro afectado
- Valores anteriores
- Valores nuevos
- Fecha y hora
- IP si está disponible

---

## 6. Roles base

El sistema debe contemplar al menos estos roles:

- Super Admin FeActiva
- Administrador de Iglesia
- Pastor Principal
- Pastor / Asistente Pastoral
- Tesorero
- Contador
- Líder de Ministerio
- Mentor / Discipulador
- Miembro
- Usuario App

Cada rol debe tener permisos específicos.

Ningún usuario debe ver información que no corresponde a su rol.

---

## 7. Experiencia de usuario

La interfaz debe respetar la identidad visual actual de FeActiva:

- Fondo oscuro
- Tonos violeta / azul
- Tarjetas modernas
- Tipografía elegante
- Diseño limpio
- Navegación simple
- Experiencia tipo dashboard premium

El usuario no técnico debe poder operar el sistema sin capacitación compleja.

---

## 8. Menú sugerido para FeActiva Iglesia

Menú principal:

- Inicio
- Personas
- Discipulado
- Pastoral
- Ministerios
- Finanzas
- Contabilidad
- Comunicación
- Reportes
- Configuración

Cada opción debe mostrarse solo si el módulo está activo para la iglesia.

---

## 9. Principios para Codex

Codex debe cumplir estas reglas:

1. No crear funcionalidades no especificadas.
2. No modificar archivos fuera del alcance solicitado.
3. Respetar arquitectura modular.
4. Respetar `tenant_id` en todas las tablas funcionales.
5. Usar prepared statements.
6. Validar entradas.
7. Escapar salidas.
8. Mantener separación entre frontend, backend y base de datos.
9. Implementar código incremental.
10. Cada cambio debe ser reversible.
11. No mezclar lógica de módulos distintos sin contrato definido.
12. No asumir estructura existente sin revisar archivos indicados por el usuario.

---

## 10. Objetivo del MVP

El primer MVP de FeActiva Iglesia debe incluir:

1. Core SaaS
2. Gestión de iglesias
3. Usuarios, roles y permisos
4. CRM de personas
5. Finanzas básicas
6. Reportes básicos
7. Auditoría
8. Activación/desactivación de módulos

Después del MVP se incorporan:

1. Discipulado
2. Seguimiento pastoral
3. Comunicación
4. Contabilidad formal
5. Cumplimiento legal avanzado
6. BI avanzado

---

## 11. Definición de éxito

El sistema será considerado exitoso si permite que una iglesia pueda:

- Registrar sus miembros y visitas
- Hacer seguimiento pastoral
- Organizar ministerios
- Registrar ingresos y egresos
- Ver reportes claros
- Controlar accesos por rol
- Separar información por iglesia
- Activar módulos según necesidad
- Exportar información administrativa
- Operar sin depender de Excel

---

## 12. Frase comercial base

FeActiva Iglesia:

> Administra tu iglesia con orden, cuida a tus miembros con propósito y rinde cuentas con transparencia.