# FEACTIVA IGLESIA SAAS — API FINANZAS

## 1. Objetivo

Definir los endpoints REST del módulo Finanzas.

Incluye:

- Cuentas financieras
- Categorías
- Centros de costo
- Campañas
- Ingresos
- Egresos
- Documentos
- Presupuestos
- Anulación de movimientos
- Reportes financieros básicos

---

# 2. Reglas generales

Antes de cualquier endpoint:

```text
1. Validar autenticación
2. Obtener user_id
3. Obtener tenant_id desde sesión
4. Validar tenant activo
5. Validar módulo finanzas activo
6. Validar permiso requerido
7. Validar que los recursos pertenezcan al tenant
8. Usar transacciones en operaciones críticas
9. Ejecutar acción
10. Auditar

Regla crítica:

Un movimiento financiero nunca se elimina físicamente. Se anula.
3. Cuentas financieras
3.1 Listar cuentas
GET /api/v1/finanzas/cuentas

Permiso:

fin.cuentas.ver
3.2 Crear cuenta
POST /api/v1/finanzas/cuentas

Permiso:

fin.cuentas.crear

Body:

{
  "nombre": "Cuenta Banco",
  "tipo": "banco",
  "banco": "BancoEstado",
  "numero_cuenta": "12345678",
  "moneda": "CLP",
  "saldo_inicial": 0,
  "fecha_saldo_inicial": "2026-04-26",
  "es_principal": true
}
3.3 Actualizar cuenta
PATCH /api/v1/finanzas/cuentas/{id}

Permiso:

fin.cuentas.editar
3.4 Desactivar cuenta
DELETE /api/v1/finanzas/cuentas/{id}

Permiso:

fin.cuentas.eliminar

Acción:

deleted_at = NOW()
deleted_by = user_id

No eliminar físicamente.

4. Categorías financieras
4.1 Listar categorías
GET /api/v1/finanzas/categorias

Permiso:

fin.categorias.ver

Filtros:

tipo=ingreso|egreso
4.2 Crear categoría
POST /api/v1/finanzas/categorias

Permiso:

fin.categorias.crear

Body:

{
  "tipo": "ingreso",
  "codigo": "diezmo",
  "nombre": "Diezmo",
  "descripcion": "Ingresos por diezmos",
  "orden": 1
}
4.3 Actualizar categoría
PATCH /api/v1/finanzas/categorias/{id}

Permiso:

fin.categorias.editar
4.4 Eliminar categoría
DELETE /api/v1/finanzas/categorias/{id}

Permiso:

fin.categorias.eliminar

Regla:

Si tiene movimientos asociados, solo marcar como inactiva.
5. Centros de costo
5.1 Listar centros de costo
GET /api/v1/finanzas/centros-costo

Permiso:

fin.centros_costo.ver
5.2 Crear centro de costo
POST /api/v1/finanzas/centros-costo

Permiso:

fin.centros_costo.crear

Body:

{
  "codigo": "jovenes",
  "nombre": "Ministerio de Jóvenes",
  "descripcion": "Centro de costo para jóvenes",
  "responsable_persona_id": 10
}
5.3 Actualizar centro de costo
PATCH /api/v1/finanzas/centros-costo/{id}

Permiso:

fin.centros_costo.editar
5.4 Eliminar centro de costo
DELETE /api/v1/finanzas/centros-costo/{id}

Permiso:

fin.centros_costo.eliminar

Soft delete.

6. Campañas
6.1 Listar campañas
GET /api/v1/finanzas/campanas

Permiso:

fin.campanas.ver
6.2 Crear campaña
POST /api/v1/finanzas/campanas

Permiso:

fin.campanas.crear

Body:

{
  "nombre": "Construcción templo",
  "descripcion": "Campaña de construcción",
  "meta_monto": 5000000,
  "moneda": "CLP",
  "fecha_inicio": "2026-05-01",
  "fecha_fin": "2026-12-31",
  "estado": "activa"
}
7. Movimientos financieros
7.1 Listar movimientos
GET /api/v1/finanzas/movimientos

Permiso:

fin.movimientos.ver

Filtros:

tipo
estado
cuenta_id
categoria_id
centro_costo_id
campana_id
persona_id
fecha_inicio
fecha_fin
medio_pago
search
page
limit
7.2 Ver movimiento
GET /api/v1/finanzas/movimientos/{id}

Permiso:

fin.movimientos.ver

Debe incluir:

cuenta
categoria
centro_costo
campaña
persona
documentos
estado
auditoría básica
7.3 Crear movimiento
POST /api/v1/finanzas/movimientos

Permiso:

fin.movimientos.crear

Body:

{
  "cuenta_id": 1,
  "categoria_id": 2,
  "centro_costo_id": 1,
  "campana_id": null,
  "persona_id": 5,
  "tipo": "ingreso",
  "subtipo": "diezmo",
  "descripcion": "Diezmo domingo",
  "monto": 25000,
  "moneda": "CLP",
  "fecha_movimiento": "2026-04-26",
  "fecha_contable": "2026-04-26",
  "medio_pago": "transferencia",
  "referencia_pago": "TRX-123",
  "observacion": "Aporte dominical"
}

Validaciones:

cuenta_id requerido y pertenece al tenant
categoria_id requerido y pertenece al tenant
tipo ingreso|egreso
monto > 0
fecha_movimiento requerida
fecha_contable requerida
persona_id opcional, pero si existe debe pertenecer al tenant
centro_costo_id opcional, pero si existe debe pertenecer al tenant
campana_id opcional, pero si existe debe pertenecer al tenant

Acciones:

1. Abrir transacción
2. Crear fin_movimientos
3. Si contabilidad activa y existe mapeo:
   - crear asiento contable en borrador o aprobado según configuración
4. Registrar auditoría
5. Confirmar transacción

Respuesta:

{
  "success": true,
  "data": {
    "id": 15
  },
  "message": "Movimiento financiero creado correctamente"
}
7.4 Actualizar movimiento
PATCH /api/v1/finanzas/movimientos/{id}

Permiso:

fin.movimientos.editar

Reglas:

Solo se puede editar si estado = registrado.
No se puede editar si estado = anulado.
Si ya generó asiento contable aprobado, no se permite editar; debe anularse.
7.5 Anular movimiento
POST /api/v1/finanzas/movimientos/{id}/anular

Permiso:

fin.movimientos.anular

Body:

{
  "motivo_anulacion": "Registro duplicado"
}

Acciones:

1. Abrir transacción
2. Validar movimiento pertenece al tenant
3. Validar estado != anulado
4. Marcar estado = anulado
5. Guardar motivo, anulado_at, anulado_by
6. Si existe asiento contable aprobado:
   - crear asiento reverso
7. Registrar auditoría
8. Confirmar transacción
8. Documentos financieros
8.1 Listar documentos de movimiento
GET /api/v1/finanzas/movimientos/{id}/documentos

Permiso:

fin.documentos.ver
8.2 Adjuntar documento
POST /api/v1/finanzas/movimientos/{id}/documentos

Permiso:

fin.documentos.crear

Body multipart/form-data o JSON con archivo_url:

{
  "tipo_documento": "comprobante_ingreso",
  "numero_documento": "CI-001",
  "fecha_documento": "2026-04-26",
  "archivo_url": "/uploads/finanzas/comprobante.pdf",
  "descripcion": "Comprobante transferencia"
}
8.3 Eliminar documento
DELETE /api/v1/finanzas/documentos/{id}

Permiso:

fin.documentos.eliminar

Regla:

Auditar eliminación.
No eliminar archivo físico sin política definida.
9. Presupuestos
9.1 Listar presupuestos
GET /api/v1/finanzas/presupuestos

Permiso:

fin.presupuestos.ver
9.2 Crear presupuesto
POST /api/v1/finanzas/presupuestos

Permiso:

fin.presupuestos.crear

Body:

{
  "nombre": "Presupuesto 2026",
  "periodo_inicio": "2026-01-01",
  "periodo_fin": "2026-12-31",
  "moneda": "CLP"
}
9.3 Agregar detalle de presupuesto
POST /api/v1/finanzas/presupuestos/{id}/detalles

Permiso:

fin.presupuestos.editar

Body:

{
  "categoria_id": 3,
  "centro_costo_id": 2,
  "tipo": "egreso",
  "monto_presupuestado": 1000000,
  "observacion": "Presupuesto jóvenes"
}
9.4 Aprobar presupuesto
POST /api/v1/finanzas/presupuestos/{id}/aprobar

Permiso:

fin.presupuestos.aprobar
10. Reportes financieros
10.1 Resumen financiero
GET /api/v1/finanzas/reportes/resumen

Permiso:

fin.reportes.ver

Filtros:

fecha_inicio
fecha_fin
10.2 Saldo por cuenta
GET /api/v1/finanzas/reportes/saldo-cuentas
10.3 Ingresos por categoría
GET /api/v1/finanzas/reportes/ingresos-categoria
10.4 Egresos por categoría
GET /api/v1/finanzas/reportes/egresos-categoria
10.5 Presupuesto vs real
GET /api/v1/finanzas/reportes/presupuesto-vs-real
11. Códigos de error
FIN_ACCOUNT_NOT_FOUND
FIN_CATEGORY_NOT_FOUND
FIN_COST_CENTER_NOT_FOUND
FIN_CAMPAIGN_NOT_FOUND
FIN_MOVEMENT_NOT_FOUND
FIN_MOVEMENT_ALREADY_CANCELLED
FIN_MOVEMENT_LOCKED_BY_ACCOUNTING
FIN_INVALID_AMOUNT
FIN_INVALID_DATE
FIN_BUDGET_NOT_FOUND
FIN_DOCUMENT_NOT_FOUND
12. Auditoría

Auditar:

fin.cuenta.created
fin.cuenta.updated
fin.cuenta.deleted

fin.categoria.created
fin.categoria.updated
fin.categoria.deleted

fin.centro_costo.created
fin.centro_costo.updated
fin.centro_costo.deleted

fin.campana.created
fin.campana.updated

fin.movimiento.created
fin.movimiento.updated
fin.movimiento.cancelled

fin.documento.uploaded
fin.documento.deleted

fin.presupuesto.created
fin.presupuesto.updated
fin.presupuesto.approved
13. Criterio de éxito

El contrato API Finanzas estará cumplido cuando:

Se puedan registrar ingresos.
Se puedan registrar egresos.
Todo movimiento respete tenant.
Todo movimiento tenga monto positivo.
Todo movimiento pueda anularse sin eliminarse.
Las cuentas y categorías pertenezcan al tenant.
Se pueda calcular saldo por cuenta.
Se puedan adjuntar documentos.
Se puedan crear presupuestos.
Todo movimiento crítico use transacción.
Si contabilidad está activa, se pueda integrar sin romper finanzas.