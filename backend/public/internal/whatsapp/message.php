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
require_once __DIR__ . '/../../../modules/integrations/contracts/NotificationSenderInterface.php';
require_once __DIR__ . '/../../../modules/integrations/contracts/WhatsAppSenderInterface.php';
require_once __DIR__ . '/../../../modules/integrations/contracts/EmailSenderInterface.php';
require_once __DIR__ . '/../../../modules/integrations/contracts/CalendarProviderInterface.php';
require_once __DIR__ . '/../../../modules/integrations/contracts/SpeechToTextInterface.php';
require_once __DIR__ . '/../../../modules/integrations/contracts/TextToSpeechInterface.php';
require_once __DIR__ . '/../../../modules/integrations/adapters/WhatsAppSenderStub.php';
require_once __DIR__ . '/../../../modules/integrations/adapters/EmailSenderStub.php';
require_once __DIR__ . '/../../../modules/integrations/adapters/GoogleCalendarProviderStub.php';
require_once __DIR__ . '/../../../modules/integrations/adapters/SpeechToTextStub.php';
require_once __DIR__ . '/../../../modules/integrations/adapters/TextToSpeechStub.php';
require_once __DIR__ . '/../../../modules/integrations/google/GoogleCalendarRepository.php';
require_once __DIR__ . '/../../../modules/integrations/google/GoogleCalendarService.php';
require_once __DIR__ . '/../../../modules/agenda/AgendaAuditLogger.php';
require_once __DIR__ . '/../../../modules/agenda/AgendaValidator.php';
require_once __DIR__ . '/../../../modules/agenda/AgendaRepository.php';
require_once __DIR__ . '/../../../modules/agenda/AgendaService.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyObject.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyRelation.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyAction.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyPermission.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyResolutionResult.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyRegistry.php';
require_once __DIR__ . '/../../../modules/agent/ontology/OntologyResolver.php';
require_once __DIR__ . '/../../../modules/agent/datetime/DateTimeResolver.php';
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
require_once __DIR__ . '/../../../modules/agent/tools/AgendaCreateItemTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/AgendaSearchItemsTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/AgendaCreateWhatsappNotificationTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/AgendaGetDayScheduleTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/AgendaCompleteItemTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/AgendaCancelItemTool.php';
require_once __DIR__ . '/../../../modules/agent/tools/AgentToolRegistry.php';
require_once __DIR__ . '/../../../modules/agent/conversation/ConversationStateRepository.php';
require_once __DIR__ . '/../../../modules/agent/conversation/ConversationStateService.php';
require_once __DIR__ . '/../../../modules/agent/conversation/ConversationStateResolver.php';
require_once __DIR__ . '/../../../modules/agent/conversation/OutboundDraftRepository.php';
require_once __DIR__ . '/../../../modules/agent/conversation/OutboundDraftService.php';
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
    } catch (Throwable $throwable) {
        internalWhatsappDebugLog('WHATSAPP_MESSAGE_EXCEPTION', [
            'exception_class' => get_class($throwable),
            'message' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => array_slice(explode("\n", $throwable->getTraceAsString()), 0, 8),
        ]);
        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $details = $debug
            ? [
                'exception' => get_class($throwable),
                'message'   => $throwable->getMessage(),
                'file'      => basename($throwable->getFile()),
                'line'      => $throwable->getLine(),
            ]
            : [];
        Response::error('WHATSAPP_MESSAGE_ERROR', 'No fue posible procesar el mensaje de WhatsApp.', $details, 500);
    }
});

function handleInternalWhatsAppMessage(): void
{
    $input = internalWhatsappMessageJsonInput();
    $phone = trim((string) ($input['phone'] ?? ''));
    $messageText = trim((string) ($input['message_text'] ?? ''));
    $messageType = internalWhatsappMessageType($input['message_type'] ?? 'text');
    $mediaUrl = internalWhatsappNullableString($input['media_url'] ?? null);
    $providerMessageId = internalWhatsappNullableString($input['provider_message_id'] ?? null);

    $errors = [];
    if ($phone === '') {
        $errors[] = ['field' => 'phone', 'message' => 'Phone es requerido.'];
    }
    if ($messageType === 'text' && $messageText === '') {
        $errors[] = ['field' => 'message_text', 'message' => 'Message text es requerido.'];
    }
    if ($messageType === 'audio' && $mediaUrl === null) {
        $errors[] = ['field' => 'media_url', 'message' => 'Media URL es requerida para audio.'];
    }
    if ($errors !== []) {
        Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
        return;
    }

    $identityService = new WhatsAppIdentityService(new WhatsAppIdentityRepository());
    $stateService = new ConversationStateService(new ConversationStateRepository(), new AgentAuditLogger());
    $stateResolver = new ConversationStateResolver();
    $draftService = new OutboundDraftService(new OutboundDraftRepository(), new AgentAuditLogger());

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
        $normalizedPhone = PhoneNormalizer::normalize($phone, 'CL');
        $activeState = $stateService->active($normalizedPhone);
        if ($activeState !== null) {
            $responseText = internalWhatsappHandleConversationState($activeState, $messageText, $stateService, $stateResolver, $draftService);
            Response::success(['found' => false, 'response_text' => $responseText, 'agent_request_id' => null]);
            return;
        }
        $stateService->create(null, null, $normalizedPhone, null, 'onboarding_waiting_confirmation', ['phone' => $normalizedPhone]);
        Response::success([
            'found' => false,
            'response_text' => 'Hola, veo que eres nuevo por aca. ¿Quieres registrarte?',
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
    $transcriptionText = null;
    $transcriptionStatus = null;
    $responseMode = 'text';
    if ($messageType === 'audio') {
        internalWhatsappAudit($tenantId, $userId, null, 'whatsapp.audio.received', 'success', [
            'conversation_id' => $conversationId,
            'media_url_present' => $mediaUrl !== null,
        ]);
        $transcription = (new SpeechToTextStub())->transcribe((string) $mediaUrl, [
            'fallback_text' => $messageText !== '' ? $messageText : null,
            'provider_message_id_present' => $providerMessageId !== null,
        ]);
        $transcriptionText = trim((string) ($transcription['transcription_text'] ?? ''));
        $transcriptionStatus = ($transcription['success'] ?? false) === true ? 'completed' : 'failed';
        if ($transcriptionText === '') {
            $transcriptionText = 'Mensaje de audio recibido';
        }
        $messageText = $transcriptionText;
        $responseMode = 'audio';
        internalWhatsappAudit($tenantId, $userId, null, 'whatsapp.audio.transcribed', $transcriptionStatus === 'completed' ? 'success' : 'failed', [
            'conversation_id' => $conversationId,
            'simulated' => $transcription['simulated'] ?? false,
        ]);
    }

    $activeState = $stateService->active($normalizedPhone);
    if ($activeState !== null) {
        if ((string) $activeState['state_key'] === 'agenda_waiting_missing_fields') {
            $state = is_array($activeState['state'] ?? null) ? $activeState['state'] : [];
            $messageText = trim((string) ($state['pending_text'] ?? '') . ' ' . $messageText);
            $stateService->close($activeState, 'completed');
        } else {
        $responseText = internalWhatsappHandleConversationState($activeState, $messageText, $stateService, $stateResolver, $draftService);
        $audioResponseUrl = internalWhatsappAudioResponseUrl($responseMode, $responseText, $tenantId, $userId, null, $conversationId);
        internalWhatsappCreateMessage($tenantId, $conversationId, $userId, 'outbound', $responseText, null, 'queued', [
            'conversation_state_id' => (int) $activeState['id'],
            'message_type' => $responseMode === 'audio' ? 'audio' : 'text',
            'response_mode' => $responseMode,
            'audio_response_url' => $audioResponseUrl,
        ]);
        Response::success([
            'found' => true,
            'response_text' => $responseText,
            'response_mode' => $responseMode,
            'audio_url' => $audioResponseUrl,
            'agent_request_id' => null,
        ]);
        return;
        }
    }

    $draftIntent = $stateResolver->detectOutboundDraft($messageText);
    if ($draftIntent !== null) {
        $recipientPhone = internalWhatsappDraftRecipientPhone((string) ($draftIntent['recipient_text'] ?? ''));
        $draftId = $draftService->create($tenantId, $userId, $conversationId, [
            'original_text' => $messageText,
            'draft_text' => $draftIntent['message_text'],
            'recipient_phone' => $recipientPhone,
            'channel' => 'whatsapp',
        ]);
        $stateService->create($tenantId, $userId, $normalizedPhone, $conversationId, 'whatsapp_draft_waiting_confirmation', ['draft_id' => $draftId]);
        $responseText = 'Este mensaje enviare: ' . $draftIntent['message_text'] . ' ¿Esta bien asi o prefieres que lo mejore?';
        $audioResponseUrl = internalWhatsappAudioResponseUrl($responseMode, $responseText, $tenantId, $userId, null, $conversationId);
        internalWhatsappCreateMessage($tenantId, $conversationId, $userId, 'outbound', $responseText, null, 'queued', [
            'draft_id' => $draftId,
            'message_type' => $responseMode === 'audio' ? 'audio' : 'text',
            'response_mode' => $responseMode,
            'audio_response_url' => $audioResponseUrl,
        ]);
        Response::success([
            'found' => true,
            'response_text' => $responseText,
            'response_mode' => $responseMode,
            'audio_url' => $audioResponseUrl,
            'agent_request_id' => null,
        ]);
        return;
    }

    internalWhatsappCreateMessage(
        $tenantId,
        $conversationId,
        $userId,
        'inbound',
        $messageText,
        $providerMessageId,
        'received',
        [
            'source' => 'whatsapp_webhook',
            'message_type' => $messageType,
            'media_url' => $mediaUrl,
            'transcription_text' => $transcriptionText,
            'transcription_status' => $transcriptionStatus,
            'response_mode' => $responseMode,
        ]
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
    $audioResponseUrl = null;
    if ($responseMode === 'audio') {
        $speech = (new TextToSpeechStub())->synthesize($responseText, [
            'conversation_id' => $conversationId,
            'agent_request_id' => $agentRequestId,
        ]);
        $audioResponseUrl = (string) ($speech['audio_url'] ?? '');
        internalWhatsappAudit($tenantId, $userId, $agentRequestId, 'whatsapp.audio.response_generated', ($speech['success'] ?? false) === true ? 'success' : 'failed', [
            'conversation_id' => $conversationId,
            'simulated' => $speech['simulated'] ?? false,
            'audio_url_present' => $audioResponseUrl !== '',
        ]);
    }
    $tool = is_array($agentResult['tool'] ?? null) ? $agentResult['tool'] : [];
    $output = is_array($tool['output'] ?? null) ? $tool['output'] : [];
    if (($tool['status'] ?? '') === 'failed' && is_array($output['missing_fields'] ?? null) && $output['missing_fields'] !== []) {
        $stateService->create($tenantId, $userId, $normalizedPhone, $conversationId, 'agenda_waiting_missing_fields', [
            'pending_text' => $messageText,
            'missing_fields' => $output['missing_fields'],
        ]);
    }

    internalWhatsappCreateMessage(
        $tenantId,
        $conversationId,
        $userId,
        'outbound',
        $responseText,
        null,
        'queued',
        [
            'agent_request_id' => $agentRequestId,
            'message_type' => $responseMode === 'audio' ? 'audio' : 'text',
            'response_mode' => $responseMode,
            'audio_response_url' => $audioResponseUrl,
        ]
    );
    internalWhatsappAudit($tenantId, $userId, $agentRequestId, 'whatsapp.message.responded', 'success', [
        'conversation_id' => $conversationId,
    ]);

    Response::success([
        'found' => true,
        'response_text' => $responseText,
        'response_mode' => $responseMode,
        'audio_url' => $audioResponseUrl,
        'agent_request_id' => $agentRequestId,
    ]);
}

function internalWhatsappJson(array $value): string
{
    return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
}

function internalWhatsappDebugLog(string $event, array $context): void
{
    $path = __DIR__ . '/whatsapp_internal_debug.log';
    $line = '[' . gmdate('Y-m-d H:i:s') . '] ' . $event . ': ' . (json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}') . PHP_EOL;
    @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
}

function internalWhatsappAudioResponseUrl(string $responseMode, string $responseText, int $tenantId, int $userId, ?int $requestId, int $conversationId): ?string
{
    if ($responseMode !== 'audio') {
        return null;
    }

    $speech = (new TextToSpeechStub())->synthesize($responseText, [
        'conversation_id' => $conversationId,
        'agent_request_id' => $requestId,
    ]);
    $audioUrl = (string) ($speech['audio_url'] ?? '');
    internalWhatsappAudit($tenantId, $userId, $requestId, 'whatsapp.audio.response_generated', ($speech['success'] ?? false) === true ? 'success' : 'failed', [
        'conversation_id' => $conversationId,
        'simulated' => $speech['simulated'] ?? false,
        'audio_url_present' => $audioUrl !== '',
    ]);

    return $audioUrl !== '' ? $audioUrl : null;
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

function internalWhatsappMessageType(mixed $value): string
{
    $value = is_string($value) ? strtolower(trim($value)) : 'text';
    return in_array($value, ['text', 'audio', 'image', 'document'], true) ? $value : 'text';
}

function internalWhatsappDraftRecipientPhone(string $recipientText): ?string
{
    $phone = PhoneNormalizer::toE164($recipientText, 'CL');
    if ($phone !== null) {
        return $phone;
    }

    $digits = preg_replace('/\D+/', '', $recipientText);
    if (!is_string($digits) || $digits === '') {
        return null;
    }

    return PhoneNormalizer::toE164($digits, 'CL');
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
    $messageType = in_array((string) ($payload['message_type'] ?? 'text'), ['text', 'audio', 'image', 'document'], true)
        ? (string) ($payload['message_type'] ?? 'text')
        : 'text';
    $responseMode = in_array((string) ($payload['response_mode'] ?? ''), ['text', 'audio'], true)
        ? (string) $payload['response_mode']
        : ($messageType === 'audio' ? 'audio' : 'text');
    $sql = "
        INSERT INTO wa_messages (
            tenant_id,
            conversation_id,
            user_id,
            direction,
            message_type,
            provider_message_id,
            body,
            media_url,
            transcription_text,
            transcription_status,
            response_mode,
            audio_response_url,
            payload,
            status,
            sent_at,
            received_at
        ) VALUES (
            :tenant_id,
            :conversation_id,
            :user_id,
            :direction,
            :message_type,
            :provider_message_id,
            :body,
            :media_url,
            :transcription_text,
            :transcription_status,
            :response_mode,
            :audio_response_url,
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
        'message_type' => $messageType,
        'provider_message_id' => $providerMessageId,
        'body' => $body,
        'media_url' => $payload['media_url'] ?? null,
        'transcription_text' => $payload['transcription_text'] ?? null,
        'transcription_status' => $payload['transcription_status'] ?? ($messageType === 'audio' && $direction === 'inbound' ? 'pending' : 'not_required'),
        'response_mode' => $responseMode,
        'audio_response_url' => $payload['audio_response_url'] ?? null,
        'payload' => internalWhatsappJson($payload),
        'status' => $status,
        'direction_sent' => $direction,
        'direction_received' => $direction,
    ]);

    return (int) Database::connection()->lastInsertId();
}

function internalWhatsappHandleConversationState(
    array $activeState,
    string $messageText,
    ConversationStateService $stateService,
    ConversationStateResolver $stateResolver,
    OutboundDraftService $draftService
): string {
    $stateKey = (string) $activeState['state_key'];
    $state = is_array($activeState['state'] ?? null) ? $activeState['state'] : [];

    if ($stateKey === 'onboarding_waiting_confirmation') {
        if ($stateResolver->isAffirmative($messageText)) {
            $stateService->update($activeState, 'onboarding_waiting_name_email', $state);
            return 'Claro, dame tu nombre completo y correo.';
        }
        if ($stateResolver->isNegative($messageText)) {
            $stateService->close($activeState, 'cancelled');
            return 'Entendido. Si necesitas registrarte mas adelante, escribeme nuevamente.';
        }
        return '¿Quieres registrarte? Responde si o no.';
    }

    if ($stateKey === 'onboarding_waiting_name_email') {
        $data = $stateResolver->extractNameEmail($messageText);
        if ($data === null) {
            return 'Necesito tu nombre completo y un correo valido.';
        }
        $tenantId = (int) env('DEFAULT_TENANT_ID', '1');
        internalWhatsappCreateBasicUserAndPerson($tenantId, (string) $state['phone'], $data['name'], $data['email']);
        $stateService->close($activeState, 'completed');
        return 'Listo, ya quedaste registrado.';
    }

    if ($stateKey === 'whatsapp_draft_waiting_confirmation') {
        $draftId = (int) ($state['draft_id'] ?? 0);
        if ($stateResolver->wantsImproveAndSend($messageText)) {
            $improved = $draftService->improve($draftId);
            $draftService->approveAndMarkSent($draftId);
            $stateService->close($activeState, 'completed');
            return 'Mejore y envie: ' . $improved . ' ¿Necesitas algo mas?';
        }
        if ($stateResolver->wantsImprove($messageText)) {
            $improved = $draftService->improve($draftId);
            return 'Te propongo: ' . $improved . ' ¿Lo envio?';
        }
        if ($stateResolver->isNegative($messageText)) {
            $draftService->cancel($draftId);
            $stateService->close($activeState, 'cancelled');
            return 'Perfecto, cancele el envio.';
        }
        if ($stateResolver->isAffirmative($messageText)) {
            $draftService->approveAndMarkSent($draftId);
            $stateService->close($activeState, 'completed');
            return 'Enviado. ¿Necesitas algo mas?';
        }
        return '¿Lo envio, lo mejoro o lo cancelo?';
    }

    return 'Tengo una conversacion pendiente, pero no pude procesarla. Escribe cancelar para cerrarla.';
}

function internalWhatsappCreateBasicUserAndPerson(int $tenantId, string $phone, string $name, string $email): void
{
    $parts = preg_split('/\s+/', trim($name));
    $firstName = is_array($parts) && isset($parts[0]) ? $parts[0] : $name;
    $lastName = is_array($parts) && count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'Por completar';

    $pdo = Database::connection();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare("
            INSERT INTO auth_users (name, email, phone, password_hash, is_active)
            VALUES (:name, :email, :phone, :password_hash, 1)
            ON DUPLICATE KEY UPDATE phone = VALUES(phone)
        ");
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
        ]);
        $userId = (int) $pdo->lastInsertId();

        if ($userId > 0) {
            $statement = $pdo->prepare("
                INSERT IGNORE INTO auth_user_tenants (user_id, tenant_id, status)
                VALUES (:user_id, :tenant_id, 'active')
            ");
            $statement->execute(['user_id' => $userId, 'tenant_id' => $tenantId]);
        }

        $statement = $pdo->prepare("
            INSERT INTO crm_personas (tenant_id, nombres, apellidos, email, whatsapp, estado_persona)
            VALUES (:tenant_id, :nombres, :apellidos, :email, :whatsapp, 'visita')
        ");
        $statement->execute([
            'tenant_id' => $tenantId,
            'nombres' => mb_convert_case($firstName, MB_CASE_TITLE, 'UTF-8'),
            'apellidos' => mb_convert_case($lastName, MB_CASE_TITLE, 'UTF-8'),
            'email' => $email,
            'whatsapp' => $phone,
        ]);
        $pdo->commit();
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $throwable;
    }
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
