<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = array_filter(array_map('trim', explode(',', (string) env(
    'CORS_ALLOWED_ORIGINS',
    env('APP_ENV', 'local') === 'production'
        ? ''
        : 'http://localhost:9090,http://127.0.0.1:9090,http://localhost:8000,http://127.0.0.1:8000'
))));

if (is_string($origin) && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(in_array($origin, $allowedOrigins, true) ? 204 : 403);
    exit;
}

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/AuthContext.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AutoMigrator.php';
AutoMigrator::checkAndRun();
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../helpers/PhoneNormalizer.php';
require_once __DIR__ . '/../modules/auth/AuthValidator.php';
require_once __DIR__ . '/../modules/auth/AuthRepository.php';
require_once __DIR__ . '/../modules/auth/PermissionRepository.php';
require_once __DIR__ . '/../modules/auth/AuthService.php';
require_once __DIR__ . '/../modules/auth/AuthController.php';
require_once __DIR__ . '/../modules/crm/CrmPersonasValidator.php';
require_once __DIR__ . '/../modules/crm/CrmPersonasRepository.php';
require_once __DIR__ . '/../modules/crm/CrmPersonasService.php';
require_once __DIR__ . '/../modules/crm/CrmPersonasController.php';
require_once __DIR__ . '/../modules/crm/CrmContactosValidator.php';
require_once __DIR__ . '/../modules/crm/CrmContactosRepository.php';
require_once __DIR__ . '/../modules/crm/CrmContactosService.php';
require_once __DIR__ . '/../modules/crm/CrmContactosController.php';
require_once __DIR__ . '/../modules/crm/CrmEtiquetasValidator.php';
require_once __DIR__ . '/../modules/crm/CrmEtiquetasRepository.php';
require_once __DIR__ . '/../modules/crm/CrmEtiquetasService.php';
require_once __DIR__ . '/../modules/crm/CrmEtiquetasController.php';
require_once __DIR__ . '/../modules/crm/CrmFamiliasValidator.php';
require_once __DIR__ . '/../modules/crm/CrmFamiliasRepository.php';
require_once __DIR__ . '/../modules/crm/CrmFamiliasService.php';
require_once __DIR__ . '/../modules/crm/CrmFamiliasController.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasCuentasRepository.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasCuentasService.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasCuentasController.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasCategoriasRepository.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasCategoriasService.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasCategoriasController.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasCentrosCostoRepository.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasCentrosCostoService.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasCentrosCostoController.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasMovimientosValidator.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasMovimientosRepository.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasMovimientosService.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasMovimientosController.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasDocumentosValidator.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasDocumentosRepository.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasDocumentosService.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasDocumentosController.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasReportesRepository.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasReportesService.php';
require_once __DIR__ . '/../modules/finanzas/FinanzasReportesController.php';
require_once __DIR__ . '/../modules/contabilidad/ContabilidadValidator.php';
require_once __DIR__ . '/../modules/contabilidad/ContabilidadRepository.php';
require_once __DIR__ . '/../modules/contabilidad/ContabilidadService.php';
require_once __DIR__ . '/../modules/contabilidad/ContabilidadConfiguracionController.php';
require_once __DIR__ . '/../modules/contabilidad/ContabilidadCuentasController.php';
require_once __DIR__ . '/../modules/contabilidad/ContabilidadPeriodosController.php';
require_once __DIR__ . '/../modules/contabilidad/ContabilidadAsientosController.php';
require_once __DIR__ . '/../modules/contabilidad/ContabilidadReportesController.php';
require_once __DIR__ . '/../modules/contabilidad/ContabilidadMapeoFinanzasController.php';
require_once __DIR__ . '/../modules/discipulado/DiscipuladoValidator.php';
require_once __DIR__ . '/../modules/discipulado/DiscipuladoRepository.php';
require_once __DIR__ . '/../modules/discipulado/DiscipuladoService.php';
require_once __DIR__ . '/../modules/discipulado/DiscipuladoRutasController.php';
require_once __DIR__ . '/../modules/discipulado/DiscipuladoPersonasController.php';
require_once __DIR__ . '/../modules/pastoral/PastoralValidator.php';
require_once __DIR__ . '/../modules/pastoral/PastoralRepository.php';
require_once __DIR__ . '/../modules/pastoral/PastoralService.php';
require_once __DIR__ . '/../modules/pastoral/PastoralCasosController.php';
require_once __DIR__ . '/../modules/pastoral/PastoralOracionController.php';
require_once __DIR__ . '/../modules/agenda/AgendaAuditLogger.php';
require_once __DIR__ . '/../modules/agenda/AgendaValidator.php';
require_once __DIR__ . '/../modules/agenda/AgendaRepository.php';
require_once __DIR__ . '/../modules/agenda/AgendaService.php';
require_once __DIR__ . '/../modules/agenda/AgendaController.php';
require_once __DIR__ . '/../modules/agent/AgentValidator.php';
require_once __DIR__ . '/../modules/agent/AgentRepository.php';
require_once __DIR__ . '/../modules/agent/AgentIntentRouter.php';
require_once __DIR__ . '/../modules/agent/AgentResponseComposer.php';
require_once __DIR__ . '/../modules/agent/AgentAuditLogger.php';
require_once __DIR__ . '/../modules/agent/ontology/OntologyObject.php';
require_once __DIR__ . '/../modules/agent/ontology/OntologyRelation.php';
require_once __DIR__ . '/../modules/agent/ontology/OntologyAction.php';
require_once __DIR__ . '/../modules/agent/ontology/OntologyPermission.php';
require_once __DIR__ . '/../modules/agent/ontology/OntologyResolutionResult.php';
require_once __DIR__ . '/../modules/agent/ontology/OntologyRegistry.php';
require_once __DIR__ . '/../modules/agent/ontology/OntologyResolver.php';
require_once __DIR__ . '/../modules/agent/datetime/DateTimeResolver.php';
require_once __DIR__ . '/../modules/agent/entities/EntityResolutionResult.php';
require_once __DIR__ . '/../modules/agent/entities/PersonEntityResolver.php';
require_once __DIR__ . '/../modules/agent/entities/FinancialAccountEntityResolver.php';
require_once __DIR__ . '/../modules/agent/entities/FinancialCategoryEntityResolver.php';
require_once __DIR__ . '/../modules/agent/entities/FamilyEntityResolver.php';
require_once __DIR__ . '/../modules/agent/entities/DiscipleshipRouteEntityResolver.php';
require_once __DIR__ . '/../modules/agent/entities/EntityResolver.php';
require_once __DIR__ . '/../modules/agent/tools/AgentToolInterface.php';
require_once __DIR__ . '/../modules/agent/tools/CrmCreatePersonTool.php';
require_once __DIR__ . '/../modules/agent/tools/CrmUpdatePersonTool.php';
require_once __DIR__ . '/../modules/agent/tools/CrmCreateFamilyTool.php';
require_once __DIR__ . '/../modules/agent/tools/CrmAssignPersonToFamilyTool.php';
require_once __DIR__ . '/../modules/agent/tools/FinanzasGetSummaryTool.php';
require_once __DIR__ . '/../modules/agent/tools/FinanzasCreateIncomeTool.php';
require_once __DIR__ . '/../modules/agent/tools/FinanzasCreateExpenseTool.php';
require_once __DIR__ . '/../modules/agent/tools/FinanzasGetBalanceByDateTool.php';
require_once __DIR__ . '/../modules/agent/tools/ContabilidadGetBalanceTool.php';
require_once __DIR__ . '/../modules/agent/tools/DiscipuladoAssignRouteTool.php';
require_once __DIR__ . '/../modules/agent/tools/DiscipuladoCompleteStageTool.php';
require_once __DIR__ . '/../modules/agent/tools/CrmSearchPersonTool.php';
require_once __DIR__ . '/../modules/agent/tools/PastoralCreateCaseTool.php';
require_once __DIR__ . '/../modules/agent/tools/PastoralCreatePrayerRequestTool.php';
require_once __DIR__ . '/../modules/agent/tools/ReminderCreateTool.php';
require_once __DIR__ . '/../modules/agent/tools/ReminderSearchTool.php';
require_once __DIR__ . '/../modules/agent/tools/AgendaCreateItemTool.php';
require_once __DIR__ . '/../modules/agent/tools/AgendaSearchItemsTool.php';
require_once __DIR__ . '/../modules/agent/tools/AgendaCreateWhatsappNotificationTool.php';
require_once __DIR__ . '/../modules/agent/tools/AgendaGetDayScheduleTool.php';
require_once __DIR__ . '/../modules/agent/tools/AgendaCompleteItemTool.php';
require_once __DIR__ . '/../modules/agent/tools/AgendaCancelItemTool.php';
require_once __DIR__ . '/../modules/agent/tools/AgentToolRegistry.php';
require_once __DIR__ . '/../modules/agent/sqlskills/AgentSqlSafetyGuard.php';
require_once __DIR__ . '/../modules/agent/sqlskills/AgentSqlSkillCatalog.php';
require_once __DIR__ . '/../modules/agent/sqlskills/AgentSqlSkillGenerator.php';
require_once __DIR__ . '/../modules/agent/sqlskills/AgentSqlSkillApproval.php';
require_once __DIR__ . '/../modules/agent/sqlskills/AgentSqlSkillExecutor.php';
require_once __DIR__ . '/../modules/agent/AgentService.php';
require_once __DIR__ . '/../modules/agent/AgentController.php';
require_once __DIR__ . '/../modules/integrations/whatsapp/WhatsAppIdentityValidator.php';
require_once __DIR__ . '/../modules/integrations/whatsapp/WhatsAppIdentityRepository.php';
require_once __DIR__ . '/../modules/integrations/whatsapp/WhatsAppIdentityService.php';
require_once __DIR__ . '/../modules/integrations/whatsapp/WhatsAppIdentityController.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/IntegrationAuthMiddleware.php';
require_once __DIR__ . '/../middlewares/TenantMiddleware.php';
require_once __DIR__ . '/../middlewares/ModuleMiddleware.php';
require_once __DIR__ . '/../middlewares/PermissionMiddleware.php';

$router = require __DIR__ . '/../routes/api.php';
$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'
);
