<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/env.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../helpers/PhoneNormalizer.php';
require_once __DIR__ . '/../../../modules/auth/PermissionRepository.php';
require_once __DIR__ . '/../../../modules/integrations/whatsapp/WhatsAppIdentityValidator.php';
require_once __DIR__ . '/../../../modules/integrations/whatsapp/WhatsAppIdentityRepository.php';
require_once __DIR__ . '/../../../modules/integrations/whatsapp/WhatsAppIdentityService.php';
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
require_once __DIR__ . '/../../../modules/agent/entities/EntityResolutionResult.php';
require_once __DIR__ . '/../../../modules/agent/entities/PersonEntityResolver.php';
require_once __DIR__ . '/../../../modules/agent/entities/FinancialAccountEntityResolver.php';
require_once __DIR__ . '/../../../modules/agent/entities/FinancialCategoryEntityResolver.php';
require_once __DIR__ . '/../../../modules/agent/entities/FamilyEntityResolver.php';
require_once __DIR__ . '/../../../modules/agent/entities/DiscipleshipRouteEntityResolver.php';
require_once __DIR__ . '/../../../modules/agent/entities/EntityResolver.php';
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

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    Response::error('METHOD_NOT_ALLOWED', 'Metodo HTTP no permitido.', [], 405);
    exit;
}

$middleware = new IntegrationAuthMiddleware();
$middleware->handle(static function (): void {
    try {
        handleInternalWhatsAppMessage();
    } catch (Throwable) {
        Response::error('WHATSAPP_MESSAGE_ERROR', 'No fue posible procesar el mensaje de WhatsApp.', [], 500);
    }
});

function handleInternalWhatsAppMessage(): void
{
    $input = internalWhatsappMessageJsonInput();
    $phone = trim((string) ($input['phone'] ?? ''));
    $messageText = trim((string) ($input['message_text'] ?? ''));
    $providerMessageId = internalWhatsappNullableString($input['provider_message_id'] ?? null);

    $errors = [];
    if ($phone === '') {
        $errors[] = ['field' => 'phone', 'message' => 'Phone es requerido.'];
    }
    if ($messageText === '') {
        $errors[] = ['field' => 'message_text', 'message' => 'Message text es requerido.'];
    }
    if ($errors !== []) {
        Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
        return;
    }

    $identityService = new WhatsAppIdentityService(new WhatsAppIdentityRepository());

    try {
        $identity = $identityService->identify($phone, 'CL', internalWhatsappIpAddress(), $_SERVER['HTTP_USER_AGENT'] ?? null);
    } catch (RuntimeException $exception) {
        if ($exception->getMessage() === 'WHATSAPP_INVALID_PHONE') {
            Response::error('VALIDATION_ERROR', 'Phone invalido.', [
                ['field' => 'phone', 'message' => 'Debe enviar un numero valido en formato WhatsApp/E.164.'],
            ], 422);
            return;
        }

        throw $exception;
    }

    if (($identity['found'] ?? false) !== true || ($identity['user'] ?? null) === null) {
        Response::success([
            'found' => false,
            'response_text' => 'No encontre tu usuario asociado a este numero. Por favor contacta al administrador de tu iglesia.',
            'agent_request_id' => null,
        ]);
        return;
    }

    $tenantId = isset($identity['tenant_id']) ? (int) $identity['tenant_id'] : 0;
    $user = is_array($identity['user']) ? $identity['user'] : [];
    $userId = isset($user['id']) ? (int) $user['id'] : 0;
    $normalizedPhone = (string) ($identity['phone'] ?? '');

    if ($tenantId < 1 || $userId < 1) {
        Response::success([
            'found' => true,
            'response_text' => 'Tu numero esta asociado a mas de una iglesia. Por favor contacta al administrador para definir la iglesia activa.',
            'agent_request_id' => null,
        ]);
        return;
    }

    $conversationId = internalWhatsappFindOrCreateConversation($tenantId, $userId, $phone, $normalizedPhone);
    internalWhatsappCreateMessage(
        $tenantId,
        $conversationId,
        $userId,
        'inbound',
        $messageText,
        $providerMessageId,
        'received',
        ['source' => 'whatsapp_webhook']
    );
    internalWhatsappAudit($tenantId, $userId, null, 'whatsapp.message.received', 'success', [
        'conversation_id' => $conversationId,
        'provider_message_id_present' => $providerMessageId !== null,
    ]);

    $agentService = new AgentService(
        new AgentRepository(),
        new AgentIntentRouter(),
        new AgentResponseComposer(),
        new AgentAuditLogger(),
        new AgentToolRegistry(),
        new PermissionRepository(),
        new OntologyResolver(new OntologyRegistry()),
        new EntityResolver()
    );
    $agentResult = $agentService->createRequest($tenantId, $userId, 'whatsapp', $messageText, $conversationId);
    $responseText = (string) ($agentResult['response']['text'] ?? '');
    $agentRequestId = (int) ($agentResult['id'] ?? 0);

    internalWhatsappCreateMessage(
        $tenantId,
        $conversationId,
        $userId,
        'outbound',
        $responseText,
        null,
        'queued',
        ['agent_request_id' => $agentRequestId]
    );
    internalWhatsappAudit($tenantId, $userId, $agentRequestId, 'whatsapp.message.responded', 'success', [
        'conversation_id' => $conversationId,
    ]);

    Response::success([
        'found' => true,
        'response_text' => $responseText,
        'agent_request_id' => $agentRequestId,
    ]);
}

function internalWhatsappJson(array $value): string
{
    return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
}

function internalWhatsappMessageJsonInput(): array
{
    $rawBody = file_get_contents('php://input');

    if ($rawBody === false || trim($rawBody) === '') {
        return [];
    }

    $decoded = json_decode($rawBody, true);

    return is_array($decoded) ? $decoded : [];
}

function internalWhatsappNullableString(mixed $value): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);
    return $value === '' ? null : $value;
}

function internalWhatsappIpAddress(): ?string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    return is_string($ip) && $ip !== '' ? $ip : null;
}

function internalWhatsappFindOrCreateConversation(int $tenantId, int $userId, string $phone, string $normalizedPhone): int
{
    $findSql = "
        SELECT
            id
        FROM wa_conversations
        WHERE tenant_id = :tenant_id
          AND user_id = :user_id
          AND whatsapp_phone_normalized = :whatsapp_phone_normalized
          AND status = 'open'
          AND deleted_at IS NULL
        ORDER BY id DESC
        LIMIT 1
    ";

    $find = Database::connection()->prepare($findSql);
    $find->execute([
        'tenant_id' => $tenantId,
        'user_id' => $userId,
        'whatsapp_phone_normalized' => $normalizedPhone,
    ]);
    $conversationId = $find->fetchColumn();

    if ($conversationId !== false) {
        $updateSql = "
            UPDATE wa_conversations
            SET last_message_at = UTC_TIMESTAMP(),
                updated_at = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id
              AND id = :id
        ";
        $update = Database::connection()->prepare($updateSql);
        $update->execute([
            'tenant_id' => $tenantId,
            'id' => (int) $conversationId,
        ]);

        return (int) $conversationId;
    }

    $insertSql = "
        INSERT INTO wa_conversations (
            tenant_id,
            user_id,
            whatsapp_phone,
            whatsapp_phone_normalized,
            provider,
            status,
            last_message_at
        ) VALUES (
            :tenant_id,
            :user_id,
            :whatsapp_phone,
            :whatsapp_phone_normalized,
            'whatsapp',
            'open',
            UTC_TIMESTAMP()
        )
    ";

    $insert = Database::connection()->prepare($insertSql);
    $insert->execute([
        'tenant_id' => $tenantId,
        'user_id' => $userId,
        'whatsapp_phone' => $phone,
        'whatsapp_phone_normalized' => $normalizedPhone,
    ]);

    return (int) Database::connection()->lastInsertId();
}

function internalWhatsappCreateMessage(
    int $tenantId,
    int $conversationId,
    int $userId,
    string $direction,
    string $body,
    ?string $providerMessageId,
    string $status,
    array $payload
): int {
    $sql = "
        INSERT INTO wa_messages (
            tenant_id,
            conversation_id,
            user_id,
            direction,
            message_type,
            provider_message_id,
            body,
            payload,
            status,
            sent_at,
            received_at
        ) VALUES (
            :tenant_id,
            :conversation_id,
            :user_id,
            :direction,
            'text',
            :provider_message_id,
            :body,
            :payload,
            :status,
            CASE WHEN :direction_sent = 'outbound' THEN UTC_TIMESTAMP() ELSE NULL END,
            CASE WHEN :direction_received = 'inbound' THEN UTC_TIMESTAMP() ELSE NULL END
        )
    ";

    $statement = Database::connection()->prepare($sql);
    $statement->execute([
        'tenant_id' => $tenantId,
        'conversation_id' => $conversationId,
        'user_id' => $userId,
        'direction' => $direction,
        'provider_message_id' => $providerMessageId,
        'body' => $body,
        'payload' => internalWhatsappJson($payload),
        'status' => $status,
        'direction_sent' => $direction,
        'direction_received' => $direction,
    ]);

    return (int) Database::connection()->lastInsertId();
}

function internalWhatsappAudit(int $tenantId, int $userId, ?int $requestId, string $eventType, string $result, array $metadata): void
{
    $sql = "
        INSERT INTO agent_audit_logs (
            tenant_id,
            user_id,
            request_id,
            event_type,
            event_description,
            action,
            result,
            subject_type,
            subject_id,
            metadata,
            ip_address,
            user_agent
        ) VALUES (
            :tenant_id,
            :user_id,
            :request_id,
            :event_type,
            :event_description,
            :action,
            :result,
            'wa_message',
            :subject_id,
            :metadata,
            :ip_address,
            :user_agent
        )
    ";

    $statement = Database::connection()->prepare($sql);
    $statement->execute([
        'tenant_id' => $tenantId,
        'user_id' => $userId,
        'request_id' => $requestId,
        'event_type' => $eventType,
        'event_description' => $eventType,
        'action' => $eventType,
        'result' => $result,
        'subject_id' => $requestId,
        'metadata' => internalWhatsappJson($metadata),
        'ip_address' => internalWhatsappIpAddress(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}
