# FEACTIVA — UI CRM

## 1. Objetivo

Definir las pantallas del módulo CRM para gestión de personas, familias, contactos y segmentación.

Debe ser:

- Rápido
- Intuitivo
- Escalable
- Integrado con discipulado, pastoral y comunicación

---

# 2. Pantalla: Listado de personas

## Componentes

```text
- Buscador global
- Filtros avanzados
- Tabla
- Paginación
- Botón "Nueva persona"
Tabla

Columnas:

Nombre completo
Email
Teléfono
Estado (miembro, visita, etc)
Ciudad
Fecha ingreso
Etiquetas
Acciones
Acciones por fila
Ver detalle
Editar
Eliminar (soft delete)
Más acciones (dropdown)
3. Filtros
Estado
Ciudad
Etiqueta
Fecha ingreso
Tiene discipulado
Tiene caso pastoral
4. Pantalla: Crear / Editar persona
Tipo

Formulario en modal o página

Campos
Nombres
Apellidos
Tipo documento
Número documento
Email
Teléfono
WhatsApp
Fecha nacimiento
Género
Estado civil
Dirección
Ciudad
Región
País
Estado persona
Origen contacto
Observaciones
Validaciones UI
Campos obligatorios marcados
Email válido
Documento único
5. Pantalla: Detalle de persona
Layout
[ Datos generales ]
[ Tabs ]
Tabs
1. Información
Datos personales completos
2. Familia
Grupo familiar
Relaciones
3. Etiquetas
Listado editable
4. Contactos
Historial de interacciones
5. Discipulado
Rutas asignadas
Progreso
Mentor
6. Pastoral
Casos activos
Historial pastoral
(Según permisos)
6. Pantalla: Familias
Listado
Nombre familia
Dirección
Teléfono
Miembros
Acciones
Crear familia
Editar
Ver miembros
Asignar persona
7. Pantalla: Contactos
Vista
Timeline de interacciones
Crear contacto

Campos:

Tipo contacto
Fecha
Asunto
Resumen
Resultado
Requiere seguimiento
Fecha seguimiento
8. Pantalla: Etiquetas
Listado
Nombre
Color
Descripción
Acciones
Crear
Editar
Eliminar
Asignar a personas
9. UX obligatoria
Loading → skeleton
Empty → "No hay personas registradas"
Error → mensaje claro
10. Permisos UI
Ocultar botones según permisos
Nunca confiar solo en frontend
11. Criterio de éxito
CRUD completo
Navegación clara
Integración total con módulos