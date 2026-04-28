<?php

declare(strict_types=1);

$router = new Router();

$router->get('/api/v1/health', static function (): void {
    Response::success([
        'status' => 'ok',
        'service' => 'feactiva-iglesia-saas',
        'environment' => env('APP_ENV', 'local'),
        'timestamp' => gmdate('c'),
    ]);
});

$authController = new AuthController();

$router->post('/api/v1/auth/login', [$authController, 'login']);

$router->post('/api/v1/auth/logout', [$authController, 'logout'], [
    AuthMiddleware::class,
]);

$router->get('/api/v1/auth/me', [$authController, 'me'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
]);

$router->get('/api/v1/test-auth', static function (): void {
    Response::success([
        'user_id' => AuthContext::userId(),
        'tenant_id' => AuthContext::tenantId(),
    ]);
}, [
    AuthMiddleware::class,
    TenantMiddleware::class,
]);

$router->get('/api/v1/test-permission', static function (): void {
    Response::success([
        'user_id' => AuthContext::userId(),
        'tenant_id' => AuthContext::tenantId(),
        'module' => 'auth',
        'permission' => 'auth.usuarios.ver',
    ]);
}, [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'auth',
    'permission' => 'auth.usuarios.ver',
]);

$whatsAppIdentityController = new WhatsAppIdentityController();

$router->post('/api/v1/integrations/whatsapp/identify', [$whatsAppIdentityController, 'identify'], [
    IntegrationAuthMiddleware::class,
]);

$agentController = new AgentController();

$router->post('/api/v1/agent/requests', [$agentController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
]);

$router->get('/api/v1/agent/requests/{id}', [$agentController, 'show'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
]);

$agendaController = new AgendaController();

$router->get('/api/v1/agenda/items', [$agendaController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'agenda',
    'permission' => 'agenda.items.ver',
]);

$router->post('/api/v1/agenda/items', [$agendaController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'agenda',
    'permission' => 'agenda.items.crear',
]);

$router->get('/api/v1/agenda/day', [$agendaController, 'day'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'agenda',
    'permission' => 'agenda.items.ver',
]);

$router->get('/api/v1/agenda/personas/{persona_id}', [$agendaController, 'byPersona'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'agenda',
    'permission' => 'agenda.items.ver',
]);

$router->get('/api/v1/agenda/familias/{familia_id}', [$agendaController, 'byFamilia'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'agenda',
    'permission' => 'agenda.items.ver',
]);

$router->get('/api/v1/agenda/items/{id}', [$agendaController, 'show'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'agenda',
    'permission' => 'agenda.items.ver',
]);

$router->add('PATCH', '/api/v1/agenda/items/{id}', [$agendaController, 'update'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'agenda',
    'permission' => 'agenda.items.editar',
]);

$router->post('/api/v1/agenda/items/{id}/cancelar', [$agendaController, 'cancel'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'agenda',
    'permission' => 'agenda.items.cancelar',
]);

$router->post('/api/v1/agenda/items/{id}/completar', [$agendaController, 'complete'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'agenda',
    'permission' => 'agenda.items.completar',
]);

$crmPersonasController = new CrmPersonasController();

$router->get('/api/v1/crm/personas', [$crmPersonasController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.personas.ver',
]);

$router->get('/api/v1/crm/personas/{id}', [$crmPersonasController, 'show'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.personas.ver',
]);

$router->post('/api/v1/crm/personas', [$crmPersonasController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.personas.crear',
]);

$router->add('PATCH', '/api/v1/crm/personas/{id}', [$crmPersonasController, 'update'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.personas.editar',
]);

$router->add('DELETE', '/api/v1/crm/personas/{id}', [$crmPersonasController, 'destroy'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.personas.eliminar',
]);

$crmContactosController = new CrmContactosController();

$router->get('/api/v1/crm/personas/{id}/contactos', [$crmContactosController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.contactos.ver',
]);

$router->post('/api/v1/crm/personas/{id}/contactos', [$crmContactosController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.contactos.crear',
]);

$crmEtiquetasController = new CrmEtiquetasController();

$router->get('/api/v1/crm/etiquetas', [$crmEtiquetasController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.etiquetas.ver',
]);

$router->post('/api/v1/crm/etiquetas', [$crmEtiquetasController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.etiquetas.crear',
]);

$router->add('PATCH', '/api/v1/crm/etiquetas/{id}', [$crmEtiquetasController, 'update'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.etiquetas.editar',
]);

$router->add('DELETE', '/api/v1/crm/etiquetas/{id}', [$crmEtiquetasController, 'destroy'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.etiquetas.eliminar',
]);

$router->post('/api/v1/crm/personas/{id}/etiquetas', [$crmEtiquetasController, 'assignToPersona'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.etiquetas.editar',
]);

$router->add('DELETE', '/api/v1/crm/personas/{id}/etiquetas/{etiqueta_id}', [$crmEtiquetasController, 'removeFromPersona'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.etiquetas.editar',
]);

$crmFamiliasController = new CrmFamiliasController();

$router->get('/api/v1/crm/familias', [$crmFamiliasController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.familias.ver',
]);

$router->post('/api/v1/crm/familias', [$crmFamiliasController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.familias.crear',
]);

$router->get('/api/v1/crm/familias/{id}', [$crmFamiliasController, 'show'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.familias.ver',
]);

$router->add('PATCH', '/api/v1/crm/familias/{id}', [$crmFamiliasController, 'update'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.familias.editar',
]);

$router->post('/api/v1/crm/familias/{id}/personas', [$crmFamiliasController, 'addPersona'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.familias.editar',
]);

$router->add('DELETE', '/api/v1/crm/familias/{id}/personas/{persona_id}', [$crmFamiliasController, 'removePersona'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'crm',
    'permission' => 'crm.familias.editar',
]);

$finanzasCuentasController = new FinanzasCuentasController();
$finanzasCategoriasController = new FinanzasCategoriasController();
$finanzasCentrosCostoController = new FinanzasCentrosCostoController();
$finanzasMovimientosController = new FinanzasMovimientosController();
$finanzasDocumentosController = new FinanzasDocumentosController();
$finanzasReportesController = new FinanzasReportesController();

$router->get('/api/v1/finanzas/cuentas', [$finanzasCuentasController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'finanzas',
    'permission' => 'fin.cuentas.ver',
]);

$router->get('/api/v1/finanzas/categorias', [$finanzasCategoriasController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'finanzas',
    'permission' => 'fin.categorias.ver',
]);

$router->get('/api/v1/finanzas/centros-costo', [$finanzasCentrosCostoController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'finanzas',
    'permission' => 'fin.centros_costo.ver',
]);

$router->get('/api/v1/finanzas/movimientos', [$finanzasMovimientosController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'finanzas',
    'permission' => 'fin.movimientos.ver',
]);

$router->get('/api/v1/finanzas/movimientos/{id}', [$finanzasMovimientosController, 'show'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'finanzas',
    'permission' => 'fin.movimientos.ver',
]);

$router->post('/api/v1/finanzas/movimientos', [$finanzasMovimientosController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'finanzas',
    'permission' => 'fin.movimientos.crear',
]);

$router->post('/api/v1/finanzas/movimientos/{id}/anular', [$finanzasMovimientosController, 'cancel'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'finanzas',
    'permission' => 'fin.movimientos.anular',
]);

$router->get('/api/v1/finanzas/movimientos/{id}/documentos', [$finanzasDocumentosController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'finanzas',
    'permission' => 'fin.documentos.ver',
]);

$router->post('/api/v1/finanzas/movimientos/{id}/documentos', [$finanzasDocumentosController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'finanzas',
    'permission' => 'fin.documentos.crear',
]);

$router->add('DELETE', '/api/v1/finanzas/documentos/{id}', [$finanzasDocumentosController, 'destroy'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'finanzas',
    'permission' => 'fin.documentos.eliminar',
]);

$router->get('/api/v1/finanzas/reportes/resumen', [$finanzasReportesController, 'resumen'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'finanzas',
    'permission' => 'fin.reportes.ver',
]);

$router->get('/api/v1/finanzas/reportes/saldo-cuentas', [$finanzasReportesController, 'saldoCuentas'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'finanzas',
    'permission' => 'fin.reportes.ver',
]);

$contabilidadConfiguracionController = new ContabilidadConfiguracionController();
$contabilidadCuentasController = new ContabilidadCuentasController();
$contabilidadPeriodosController = new ContabilidadPeriodosController();
$contabilidadAsientosController = new ContabilidadAsientosController();
$contabilidadReportesController = new ContabilidadReportesController();
$contabilidadMapeoFinanzasController = new ContabilidadMapeoFinanzasController();

$router->get('/api/v1/contabilidad/configuracion', [$contabilidadConfiguracionController, 'show'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.configuracion.ver',
]);

$router->add('PATCH', '/api/v1/contabilidad/configuracion', [$contabilidadConfiguracionController, 'update'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.configuracion.editar',
]);

$router->get('/api/v1/contabilidad/cuentas', [$contabilidadCuentasController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.cuentas.ver',
]);

$router->post('/api/v1/contabilidad/cuentas', [$contabilidadCuentasController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.cuentas.crear',
]);

$router->add('PATCH', '/api/v1/contabilidad/cuentas/{id}', [$contabilidadCuentasController, 'update'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.cuentas.editar',
]);

$router->get('/api/v1/contabilidad/periodos', [$contabilidadPeriodosController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.periodos.ver',
]);

$router->post('/api/v1/contabilidad/periodos', [$contabilidadPeriodosController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.periodos.crear',
]);

$router->post('/api/v1/contabilidad/periodos/{id}/cerrar', [$contabilidadPeriodosController, 'close'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.periodos.cerrar',
]);

$router->get('/api/v1/contabilidad/asientos', [$contabilidadAsientosController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.asientos.ver',
]);

$router->get('/api/v1/contabilidad/asientos/{id}', [$contabilidadAsientosController, 'show'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.asientos.ver',
]);

$router->post('/api/v1/contabilidad/asientos', [$contabilidadAsientosController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.asientos.crear',
]);

$router->post('/api/v1/contabilidad/asientos/{id}/aprobar', [$contabilidadAsientosController, 'approve'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.asientos.aprobar',
]);

$router->post('/api/v1/contabilidad/asientos/{id}/anular', [$contabilidadAsientosController, 'cancel'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.asientos.anular',
]);

$router->post('/api/v1/contabilidad/asientos/{id}/reversar', [$contabilidadAsientosController, 'reverse'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.asientos.reversar',
]);

$router->get('/api/v1/contabilidad/reportes/libro-diario', [$contabilidadReportesController, 'libroDiario'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.reportes.ver',
]);

$router->get('/api/v1/contabilidad/reportes/libro-mayor', [$contabilidadReportesController, 'libroMayor'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.reportes.ver',
]);

$router->get('/api/v1/contabilidad/reportes/balance-comprobacion', [$contabilidadReportesController, 'balanceComprobacion'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.reportes.ver',
]);

$router->get('/api/v1/contabilidad/reportes/estado-resultados', [$contabilidadReportesController, 'estadoResultados'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.reportes.ver',
]);

$router->get('/api/v1/contabilidad/mapeo-finanzas', [$contabilidadMapeoFinanzasController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.mapeo.ver',
]);

$router->post('/api/v1/contabilidad/mapeo-finanzas', [$contabilidadMapeoFinanzasController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.mapeo.crear',
]);

$router->add('PATCH', '/api/v1/contabilidad/mapeo-finanzas/{id}', [$contabilidadMapeoFinanzasController, 'update'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.mapeo.editar',
]);

$router->post('/api/v1/contabilidad/generar-desde-finanzas/{movimiento_id}', [$contabilidadMapeoFinanzasController, 'generateFromFinance'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'contabilidad',
    'permission' => 'acct.asientos.crear',
]);

$discipuladoRutasController = new DiscipuladoRutasController();
$discipuladoPersonasController = new DiscipuladoPersonasController();

$router->get('/api/v1/discipulado/rutas', [$discipuladoRutasController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'discipulado',
    'permission' => 'disc.rutas.ver',
]);

$router->post('/api/v1/discipulado/rutas', [$discipuladoRutasController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'discipulado',
    'permission' => 'disc.rutas.crear',
]);

$router->add('PATCH', '/api/v1/discipulado/rutas/{id}', [$discipuladoRutasController, 'update'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'discipulado',
    'permission' => 'disc.rutas.editar',
]);

$router->post('/api/v1/discipulado/rutas/{id}/etapas', [$discipuladoRutasController, 'storeEtapa'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'discipulado',
    'permission' => 'disc.rutas.editar',
]);

$router->add('PATCH', '/api/v1/discipulado/etapas/{id}', [$discipuladoRutasController, 'updateEtapa'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'discipulado',
    'permission' => 'disc.rutas.editar',
]);

$router->post('/api/v1/discipulado/personas/{persona_id}/rutas', [$discipuladoPersonasController, 'assignRuta'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'discipulado',
    'permission' => 'disc.avance.editar',
]);

$router->get('/api/v1/discipulado/personas/{persona_id}/avance', [$discipuladoPersonasController, 'avance'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'discipulado',
    'permission' => 'disc.avance.ver',
]);

$router->post('/api/v1/discipulado/persona-etapas/{id}/completar', [$discipuladoPersonasController, 'completeEtapa'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'discipulado',
    'permission' => 'disc.avance.editar',
]);

$router->get('/api/v1/discipulado/personas/{persona_id}/mentorias', [$discipuladoPersonasController, 'mentorias'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'discipulado',
    'permission' => 'disc.mentorias.ver',
]);

$router->post('/api/v1/discipulado/personas/{persona_id}/mentorias', [$discipuladoPersonasController, 'storeMentoria'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'discipulado',
    'permission' => 'disc.mentorias.crear',
]);

$router->get('/api/v1/discipulado/personas/{persona_id}/registros-espirituales', [$discipuladoPersonasController, 'registros'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'discipulado',
    'permission' => 'disc.registros.ver',
]);

$router->post('/api/v1/discipulado/personas/{persona_id}/registros-espirituales', [$discipuladoPersonasController, 'storeRegistro'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'discipulado',
    'permission' => 'disc.registros.crear',
]);

$pastoralCasosController = new PastoralCasosController();
$pastoralOracionController = new PastoralOracionController();

$router->get('/api/v1/pastoral/casos', [$pastoralCasosController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'pastoral',
    'permission' => 'past.casos.ver',
]);

$router->get('/api/v1/pastoral/casos/{id}', [$pastoralCasosController, 'show'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'pastoral',
    'permission' => 'past.casos.ver',
]);

$router->post('/api/v1/pastoral/casos', [$pastoralCasosController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'pastoral',
    'permission' => 'past.casos.crear',
]);

$router->add('PATCH', '/api/v1/pastoral/casos/{id}', [$pastoralCasosController, 'update'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'pastoral',
    'permission' => 'past.casos.editar',
]);

$router->post('/api/v1/pastoral/casos/{id}/cerrar', [$pastoralCasosController, 'close'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'pastoral',
    'permission' => 'past.casos.cerrar',
]);

$router->get('/api/v1/pastoral/casos/{id}/sesiones', [$pastoralCasosController, 'sesiones'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'pastoral',
    'permission' => 'past.sesiones.ver',
]);

$router->post('/api/v1/pastoral/casos/{id}/sesiones', [$pastoralCasosController, 'storeSesion'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'pastoral',
    'permission' => 'past.sesiones.crear',
]);

$router->post('/api/v1/pastoral/casos/{id}/derivar', [$pastoralCasosController, 'derive'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'pastoral',
    'permission' => 'past.derivaciones.crear',
]);

$router->get('/api/v1/pastoral/oracion', [$pastoralOracionController, 'index'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'pastoral',
    'permission' => 'past.oracion.ver',
]);

$router->post('/api/v1/pastoral/oracion', [$pastoralOracionController, 'store'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'pastoral',
    'permission' => 'past.oracion.crear',
]);

$router->add('PATCH', '/api/v1/pastoral/oracion/{id}', [$pastoralOracionController, 'update'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'pastoral',
    'permission' => 'past.oracion.editar',
]);

$router->post('/api/v1/pastoral/oracion/{id}/cerrar', [$pastoralOracionController, 'close'], [
    AuthMiddleware::class,
    TenantMiddleware::class,
], [
    'module' => 'pastoral',
    'permission' => 'past.oracion.editar',
]);

return $router;
