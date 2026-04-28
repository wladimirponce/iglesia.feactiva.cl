<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/env.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../modules/auth/PermissionRepository.php';
require_once __DIR__ . '/../../../modules/agent/AgentRepository.php';
require_once __DIR__ . '/../../../modules/agent/AgentIntentRouter.php';
require_once __DIR__ . '/../../../modules/agent/AgentResponseComposer.php';
require_once __DIR__ . '/../../../modules/agent/AgentAuditLogger.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyObject.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyRelation.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyAction.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyPermission.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyResolutionResult.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyRegistry.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyResolver.php';
require_once __DIR__ . '/../../../modules/agent/tools/AgentToolInterface.php';
require_once __DIR__ . '/../../../modules/agent/tools/CrmCreatePersonTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/CrmUpdatePersonTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/CrmCreateFamilyTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/CrmAssignPersonToFamilyTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/FinanzasGetSummaryTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/FinanzasCreateIncomeTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/FinanzasCreateExpenseTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/FinanzasGetBalanceByDateTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/ContabilidadGetBalanceTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/DiscipuladoAssignRouteTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/DiscipuladoCompleteStageTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/CrmSearchPersonTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/PastoralCreateCaseTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/PastoralCreatePrayerRequestTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/ReminderCreateTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/ReminderSearchTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/AgentToolRegistry.php';
require_once __DIR__ . '/../../../modules/agent/AgentService.php';
require_once __DIR__ . '/../../../middlewares/IntegrationAuthMiddleware.php';

header('Content-Type: application/json; charset=utf-8');

function internalAgentJsonInput(): array
{
    $rawBody = file_get_contents('php://input');

    if ($rawBody === false || trim($rawBody) === '') {
        return [];
    }

    $decoded = json_decode($rawBody, true);

    return is_array($decoded) ? $decoded : [];
}

function internalAgentRequirePost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        Response::error('METHOD_NOT_ALLOWED', 'Metodo HTTP no permitido.', [], 405);
        exit;
    }
}

function internalAgentService(): AgentService
{
    return new AgentService(
        new AgentRepository(),
        new AgentIntentRouter(),
        new AgentResponseComposer(),
        new AgentAuditLogger(),
        new AgentToolRegistry(),
        new PermissionRepository(),
        new OntologyResolver(new OntologyRegistry())
    );
}
