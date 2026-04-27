# FEACTIVA — LAYOUT ADMIN IGLESIA

## 1. Objetivo

Definir la estructura base visual del sistema administrativo.

Debe ser:

- Profesional
- Limpio
- Modular
- Escalable
- Tipo SaaS moderno (inspiración: Notion / Stripe / HubSpot)

---

# 2. Estructura general

```text
┌───────────────────────────────┐
│ Topbar                        │
├───────────────┬───────────────┤
│ Sidebar       │ Main Content  │
│               │               │
│               │               │
└───────────────┴───────────────┘
3. Sidebar
Secciones
Dashboard
CRM
Discipulado
Pastoral
Ministerios
Comunicación
Finanzas
Contabilidad
Reportes
Configuración
Comportamiento
- Colapsable
- Íconos + texto
- Estado activo visible
- Permisos controlan visibilidad
4. Topbar

Elementos:

- Selector de iglesia (tenant)
- Buscador global
- Notificaciones
- Usuario (perfil/logout)
5. Main Content

Debe contener:

- Breadcrumb
- Título
- Acciones principales
- Contenido dinámico
6. Estilo visual
- Fondo claro (#f9fafb)
- Cards blancas
- Bordes suaves
- Sombras leves
- Tipografía moderna
7. Componentes base
Card
Table
Form
Modal
Button
Badge
Tabs
Filters
Pagination
8. Responsive
- Sidebar colapsa en móvil
- Tablas scroll horizontal
- Formularios en una columna
9. Estados UI obligatorios
Loading
Empty
Error
Success
10. Criterio de éxito
Navegación clara
Jerarquía visual
Modularidad

---

# 📁 2. Dashboard

```text
/docs/04-ui/02-dashboard-iglesia.md
# FEACTIVA — DASHBOARD IGLESIA

## 1. Objetivo

Mostrar resumen ejecutivo en tiempo real.

---

# 2. Estructura

## KPIs superiores

```text
Total personas
Miembros activos
Visitas recientes
Personas en discipulado
Casos pastorales activos
Ingresos del mes
Egresos del mes
Saldo
Gráficos
Ingresos vs egresos
Crecimiento de miembros
Discipulado progreso
Secciones
CRM
Últimas personas registradas
Discipulado
Personas en progreso
Pastoral
Casos abiertos
Finanzas
Movimientos recientes
3. Acciones rápidas
+ Nueva persona
+ Registrar ingreso
+ Crear caso pastoral
+ Enviar mensaje
4. Permisos
Ocultar módulos no autorizados
5. UX
Carga rápida
Información priorizada
Sin saturación
6. Criterio de éxito
Información clara en <3 segundos
Acciones rápidas visibles

---

# 📁 3. CRM UI

```text
/docs/04-ui/03-pantallas-crm.md
# FEACTIVA — UI CRM

## 1. Listado de personas

### Elementos

```text
Tabla
Buscador
Filtros
Paginación
Botón crear
Columnas
Nombre
Email
Teléfono
Estado
Ciudad
Fecha ingreso
2. Filtros
Estado
Ciudad
Etiqueta
Fecha
3. Crear / editar persona

Campos:

Nombres
Apellidos
Email
Teléfono
Dirección
Ciudad
Estado
Observaciones
4. Vista detalle persona

Secciones:

Datos generales
Familia
Etiquetas
Historial contactos
Discipulado
Pastoral
5. Acciones
Editar
Eliminar
Asignar etiqueta
Agregar contacto
Asignar discipulado
6. Estados
Vacío: "No hay personas"
Loading
Error
7. Criterio de éxito
CRUD completo
Navegación simple
Integración con otros módulos

---

# 📁 4. Finanzas UI

```text
/docs/04-ui/04-pantallas-finanzas.md
# FEACTIVA — UI FINANZAS

## 1. Listado de movimientos

### Elementos

```text
Tabla
Filtros
Buscador
Paginación
Botón crear
Columnas
Fecha
Tipo (ingreso/egreso)
Categoría
Cuenta
Monto
Estado
2. Filtros
Tipo
Categoría
Cuenta
Centro costo
Fecha
Estado
3. Crear movimiento

Campos:

Cuenta
Categoría
Centro de costo
Persona (opcional)
Monto
Fecha
Medio pago
Descripción
4. Detalle movimiento
Datos
Documentos
Auditoría básica
5. Acciones
Editar
Anular
Adjuntar documento
6. Presupuestos
Listado
Crear
Detalle por categoría
Comparación real vs presupuesto
7. Reportes
Ingresos por categoría
Egresos por categoría
Saldo por cuenta
Presupuesto vs real
8. UX
Colores:
Verde → ingresos
Rojo → egresos
Gris → anulado
9. Estados
Loading
Empty
Error
10. Criterio de éxito
Registro rápido
Visualización clara
Control financiero simple