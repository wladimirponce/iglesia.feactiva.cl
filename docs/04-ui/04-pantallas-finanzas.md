# FEACTIVA — UI FINANZAS

## 1. Objetivo

Definir las pantallas del módulo Finanzas.

Debe permitir:

- Control financiero claro
- Registro rápido
- Visualización simple
- Integración con contabilidad

---

# 2. Pantalla: Listado de movimientos

## Componentes

```text
Buscador
Filtros
Tabla
Paginación
Botón "Nuevo movimiento"
Tabla

Columnas:

Fecha
Tipo (Ingreso/Egreso)
Categoría
Cuenta
Centro de costo
Monto
Estado
Acciones
Acciones
Ver detalle
Editar (si permitido)
Anular
3. Filtros
Tipo
Categoría
Cuenta
Centro de costo
Campaña
Persona
Fecha rango
Estado
4. Pantalla: Crear movimiento
Tipo

Formulario

Campos
Cuenta
Categoría
Centro de costo
Campaña (opcional)
Persona (opcional)
Tipo
Subtipo
Monto
Moneda
Fecha movimiento
Fecha contable
Medio de pago
Referencia
Descripción
Observaciones
UX
Autocompletado
Validación en tiempo real
5. Pantalla: Detalle movimiento
Secciones
Datos generales
Documentos adjuntos
Auditoría básica
6. Documentos
Subir archivo
Ver archivo
Eliminar (si permitido)
7. Pantalla: Cuentas
Listado
Nombre
Tipo
Banco
Saldo
Estado
Acciones
Crear
Editar
Desactivar
8. Pantalla: Categorías
Listado
Tipo (ingreso/egreso)
Código
Nombre
9. Pantalla: Centros de costo
Listado
Crear
Editar
Asignar responsable
10. Pantalla: Campañas
Nombre
Meta
Progreso
Estado
11. Pantalla: Presupuestos
Vista
Listado de presupuestos
Detalle por categoría
Comparación real vs planificado
12. Pantalla: Reportes
Ingresos por categoría
Egresos por categoría
Saldo por cuenta
Presupuesto vs real
13. UX visual
Verde → ingresos
Rojo → egresos
Gris → anulado
14. Estados UI
Loading
Empty → "No hay movimientos"
Error
15. Permisos UI
Ocultar acciones según rol
Backend valida siempre
16. Criterio de éxito
Registro rápido de movimientos
Visualización clara
Control financiero operativo