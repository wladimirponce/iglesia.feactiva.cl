# FEACTIVA IGLESIA SAAS — API CONTABILIDAD

## 1. Objetivo

Definir los endpoints REST del módulo Contabilidad.

Incluye:

- Configuración contable
- Plan de cuentas
- Períodos contables
- Asientos contables
- Doble partida
- Aprobación
- Anulación
- Reversa
- Mapeo Finanzas → Contabilidad
- Reportes contables

---

# 2. Reglas generales

Antes de cualquier endpoint:

```text
1. Validar autenticación
2. Obtener user_id
3. Obtener tenant_id desde sesión
4. Validar tenant activo
5. Validar módulo contabilidad activo
6. Validar permiso requerido
7. Validar que los recursos pertenezcan al tenant
8. Usar transacciones en operaciones críticas
9. Auditar acciones críticas

Regla crítica:

Todo asiento aprobado debe cuadrar:
total_debe = total_haber
3. Configuración contable
3.1 Ver configuración
GET /api/v1/contabilidad/configuracion

Permiso:

acct.configuracion.ver
3.2 Actualizar configuración
PATCH /api/v1/contabilidad/configuracion

Permiso:

acct.configuracion.editar

Body:

{
  "pais_codigo": "CL",
  "moneda_base": "CLP",
  "norma_contable": "GENERAL",
  "periodo_inicio_mes": 1,
  "usa_centros_costo": true,
  "requiere_aprobacion_asientos": true,
  "numeracion_automatica": true
}

Auditar:

acct.config.updated
4. Plan de cuentas
4.1 Listar cuentas
GET /api/v1/contabilidad/cuentas

Permiso:

acct.cuentas.ver

Filtros:

tipo
es_activa
cuenta_padre_id
search
4.2 Ver cuenta
GET /api/v1/contabilidad/cuentas/{id}

Permiso:

acct.cuentas.ver
4.3 Crear cuenta
POST /api/v1/contabilidad/cuentas

Permiso:

acct.cuentas.crear

Body:

{
  "codigo": "1.1.01",
  "nombre": "Caja General",
  "descripcion": "Cuenta de caja principal",
  "tipo": "activo",
  "naturaleza": "deudora",
  "cuenta_padre_id": 1,
  "nivel": 3,
  "es_movimiento": true
}

Validaciones:

codigo requerido
nombre requerido
tipo válido
naturaleza válida
codigo único por tenant
si cuenta_padre_id existe, debe pertenecer al mismo tenant

Auditar:

acct.cuenta.created
4.4 Actualizar cuenta
PATCH /api/v1/contabilidad/cuentas/{id}

Permiso:

acct.cuentas.editar

Reglas:

No permitir cambiar tipo si la cuenta tiene movimientos.
No permitir eliminar cuenta con asientos.
4.5 Eliminar / desactivar cuenta
DELETE /api/v1/contabilidad/cuentas/{id}

Permiso:

acct.cuentas.eliminar

Regla:

Si tiene movimientos, marcar es_activa = 0.
No eliminar físicamente.
5. Períodos contables
5.1 Listar períodos
GET /api/v1/contabilidad/periodos

Permiso:

acct.periodos.ver
5.2 Crear período
POST /api/v1/contabilidad/periodos

Permiso:

acct.periodos.crear

Body:

{
  "nombre": "Ejercicio 2026",
  "fecha_inicio": "2026-01-01",
  "fecha_fin": "2026-12-31"
}
5.3 Cerrar período
POST /api/v1/contabilidad/periodos/{id}/cerrar

Permiso:

acct.periodos.cerrar

Validaciones:

No deben existir asientos borrador dentro del período.
No debe estar ya cerrado.

Auditar:

acct.periodo.closed
5.4 Reabrir período
POST /api/v1/contabilidad/periodos/{id}/abrir

Permiso:

acct.periodos.abrir

Auditar:

acct.periodo.reopened
6. Asientos contables
6.1 Listar asientos
GET /api/v1/contabilidad/asientos

Permiso:

acct.asientos.ver

Filtros:

fecha_inicio
fecha_fin
estado
origen
periodo_id
search
page
limit
6.2 Ver asiento
GET /api/v1/contabilidad/asientos/{id}

Permiso:

acct.asientos.ver

Debe incluir:

encabezado
detalles
totales
estado
origen
movimiento_financiero_asociado
6.3 Crear asiento
POST /api/v1/contabilidad/asientos

Permiso:

acct.asientos.crear

Body:

{
  "fecha_asiento": "2026-04-26",
  "descripcion": "Registro manual de ajuste",
  "origen": "manual",
  "moneda": "CLP",
  "detalles": [
    {
      "cuenta_id": 1,
      "centro_costo_id": null,
      "descripcion": "Debe",
      "debe": 100000,
      "haber": 0
    },
    {
      "cuenta_id": 4,
      "centro_costo_id": null,
      "descripcion": "Haber",
      "debe": 0,
      "haber": 100000
    }
  ]
}

Validaciones:

Debe tener al menos 2 detalles.
Cada detalle debe tener cuenta válida del tenant.
Cada línea debe tener debe > 0 o haber > 0, no ambos.
Suma debe debe ser igual a suma haber.
Periodo debe estar abierto.

Acciones:

1. Abrir transacción
2. Crear encabezado en acct_asientos
3. Crear líneas en acct_asiento_detalles
4. Calcular total_debe y total_haber
5. Guardar como borrador
6. Auditar
7. Confirmar transacción
6.4 Actualizar asiento borrador
PATCH /api/v1/contabilidad/asientos/{id}

Permiso:

acct.asientos.editar

Reglas:

Solo se puede editar si estado = borrador.
No se puede editar si estado = aprobado o anulado.
Debe volver a validar doble partida.
6.5 Aprobar asiento
POST /api/v1/contabilidad/asientos/{id}/aprobar

Permiso:

acct.asientos.aprobar

Validaciones:

estado debe ser borrador
periodo debe estar abierto
total_debe = total_haber
total_debe > 0

Acciones:

estado = aprobado
aprobado_at = NOW()
aprobado_by = user_id

Auditar:

acct.asiento.approved
6.6 Anular asiento
POST /api/v1/contabilidad/asientos/{id}/anular

Permiso:

acct.asientos.anular

Body:

{
  "motivo_anulacion": "Error de clasificación"
}

Reglas:

No eliminar asiento.
Registrar motivo, usuario y fecha.
Si está aprobado, debe preferirse reversa contable.

Auditar:

acct.asiento.cancelled
6.7 Reversar asiento
POST /api/v1/contabilidad/asientos/{id}/reversar

Permiso:

acct.asientos.reversar

Body:

{
  "fecha_asiento": "2026-04-27",
  "descripcion": "Reversa de asiento 2026-0001"
}

Acciones:

1. Crear nuevo asiento con debe/haber invertidos
2. Referenciar asiento_reversado_id
3. Guardar como borrador o aprobado según configuración
4. Auditar
7. Mapeo Finanzas → Contabilidad
7.1 Listar mapeos
GET /api/v1/contabilidad/mapeo-finanzas

Permiso:

acct.mapeo.ver
7.2 Crear mapeo
POST /api/v1/contabilidad/mapeo-finanzas

Permiso:

acct.mapeo.crear

Body:

{
  "categoria_id": 1,
  "tipo_movimiento": "ingreso",
  "cuenta_debe_id": 2,
  "cuenta_haber_id": 10,
  "descripcion": "Mapeo diezmos"
}

Validaciones:

categoria_id pertenece al tenant
cuentas pertenecen al tenant
tipo_movimiento coincide con categoría
7.3 Actualizar mapeo
PATCH /api/v1/contabilidad/mapeo-finanzas/{id}

Permiso:

acct.mapeo.editar
7.4 Eliminar / desactivar mapeo
DELETE /api/v1/contabilidad/mapeo-finanzas/{id}

Permiso:

acct.mapeo.eliminar
8. Generar asiento desde movimiento financiero
8.1 Generar asiento
POST /api/v1/contabilidad/generar-desde-finanzas/{movimiento_id}

Permiso:

acct.asientos.crear

Validaciones:

Movimiento financiero pertenece al tenant.
Movimiento no está anulado.
Movimiento no tiene asiento asociado.
Existe mapeo para categoría + tipo.

Acciones:

1. Leer movimiento financiero
2. Obtener mapeo
3. Crear asiento
4. Crear detalle debe/haber
5. Relacionar asiento con movimiento financiero
6. Auditar
9. Reportes contables
9.1 Libro diario
GET /api/v1/contabilidad/reportes/libro-diario

Permiso:

acct.reportes.ver

Filtros:

fecha_inicio
fecha_fin
estado=aprobado
9.2 Libro mayor
GET /api/v1/contabilidad/reportes/libro-mayor

Permiso:

acct.reportes.ver

Filtros:

cuenta_id
fecha_inicio
fecha_fin
9.3 Balance de comprobación
GET /api/v1/contabilidad/reportes/balance-comprobacion
9.4 Estado de resultados
GET /api/v1/contabilidad/reportes/estado-resultados
9.5 Balance general
GET /api/v1/contabilidad/reportes/balance-general
9.6 Flujo de caja
GET /api/v1/contabilidad/reportes/flujo-caja
10. Códigos de error
ACCT_CONFIG_NOT_FOUND
ACCT_ACCOUNT_NOT_FOUND
ACCT_ACCOUNT_HAS_MOVEMENTS
ACCT_PERIOD_NOT_FOUND
ACCT_PERIOD_CLOSED
ACCT_ENTRY_NOT_FOUND
ACCT_ENTRY_NOT_BALANCED
ACCT_ENTRY_ALREADY_APPROVED
ACCT_ENTRY_ALREADY_CANCELLED
ACCT_MAPPING_NOT_FOUND
ACCT_FIN_MOVEMENT_ALREADY_LINKED
ACCT_INVALID_ACCOUNT_TYPE
11. Auditoría

Auditar:

acct.config.updated

acct.cuenta.created
acct.cuenta.updated
acct.cuenta.disabled

acct.periodo.created
acct.periodo.closed
acct.periodo.reopened

acct.asiento.created
acct.asiento.updated
acct.asiento.approved
acct.asiento.cancelled
acct.asiento.reversed

acct.mapeo.created
acct.mapeo.updated
acct.mapeo.deleted

acct.reporte.generated
acct.reporte.exported
12. Criterio de éxito

El contrato API Contabilidad estará cumplido cuando:

Se pueda configurar contabilidad por tenant.
Se pueda gestionar plan de cuentas.
Se puedan crear períodos.
Se puedan crear asientos con doble partida.
No se apruebe un asiento descuadrado.
No se editen asientos aprobados.
Se pueda reversar un asiento.
Se pueda generar contabilidad desde finanzas.
Se puedan consultar libro diario, mayor y estados financieros.
Todo respete tenant, permisos, módulo activo y auditoría.