# FEACTIVA — DASHBOARD IGLESIA

## 1. Objetivo

Mostrar un resumen claro, rápido y accionable del estado de la iglesia.

Debe permitir:

- Ver el estado general en segundos
- Detectar problemas
- Tomar decisiones rápidas

---

# 2. Estructura

## 2.1 KPIs principales (fila superior)

```text
Total personas
Miembros activos
Visitas del mes
Personas en discipulado
Casos pastorales activos
Ingresos del mes
Egresos del mes
Saldo actual
2.2 Distribución visual
[ KPI KPI KPI KPI ]
[ KPI KPI KPI KPI ]

[ Gráfico financiero ] [ Gráfico crecimiento ]

[ CRM reciente ]
[ Discipulado ]
[ Pastoral ]
[ Finanzas recientes ]
2.3 Secciones
CRM
Últimas personas registradas
Nuevas visitas
Discipulado
Personas activas en rutas
Etapas completadas recientemente
Pastoral
Casos abiertos
Casos críticos
Finanzas
Últimos movimientos
Alertas (egresos altos, saldo bajo)
3. Acciones rápidas

Botones visibles:

+ Nueva persona
+ Registrar ingreso
+ Crear caso pastoral
+ Enviar mensaje
4. Permisos
Ocultar bloques según permisos
Ej: sin finanzas → no mostrar ingresos/egresos
5. Estados UI
Loading → skeletons
Empty → "No hay datos aún"
Error → mensaje controlado
6. Criterio de éxito
Dashboard entendible en menos de 5 segundos
Acciones rápidas visibles
Información priorizada

---

# 📁 2. Pantallas CRM (faltante)

```text
/docs/04-ui/03-pantallas-crm.md
# FEACTIVA — UI CRM

## 1. Listado de personas

### Componentes

- Buscador
- Filtros
- Tabla
- Paginación
- Botón crear

---

## 2. Tabla

Columnas:

```text
Nombre completo
Email
Teléfono
Estado
Ciudad
Fecha ingreso
Acciones
3. Filtros
Estado (miembro, visita, etc)
Ciudad
Etiqueta
Fecha ingreso
4. Crear / Editar persona

Formulario:

Nombres
Apellidos
Email
Teléfono
WhatsApp
Dirección
Ciudad
Estado persona
Observaciones
5. Vista detalle persona

Secciones:

Datos generales
Familia
Etiquetas
Historial de contactos
Discipulado
Pastoral
6. Acciones
Editar
Eliminar
Asignar etiqueta
Agregar contacto
Asignar discipulado
7. Estados UI
Loading
Empty → "No hay personas"
Error
8. Criterio de éxito
CRUD fluido
Navegación simple
Integración con otros módulos

---

# 📁 3. Pantallas Finanzas (faltante)

```text
/docs/04-ui/04-pantallas-finanzas.md
# FEACTIVA — UI FINANZAS

## 1. Listado de movimientos

### Componentes

- Filtros
- Buscador
- Tabla
- Paginación
- Botón crear

---

## 2. Tabla

Columnas:

```text
Fecha
Tipo (Ingreso/Egreso)
Categoría
Cuenta
Monto
Estado
Acciones
3. Filtros
Tipo
Categoría
Cuenta
Centro de costo
Fecha
Estado
4. Crear movimiento

Campos:

Cuenta
Categoría
Centro de costo
Persona (opcional)
Monto
Fecha
Medio de pago
Descripción
5. Detalle de movimiento

Secciones:

Datos generales
Documentos adjuntos
Auditoría básica
6. Acciones
Editar
Anular
Adjuntar documento
7. Presupuestos
Listado de presupuestos
Crear presupuesto
Detalle por categoría
Comparación real vs presupuesto
8. Reportes
Ingresos por categoría
Egresos por categoría
Saldo por cuenta
Presupuesto vs real
9. UX visual
Verde → ingresos
Rojo → egresos
Gris → anulado
10. Estados UI
Loading
Empty
Error
11. Criterio de éxito
Registro rápido
Visualización clara
Control financiero simple