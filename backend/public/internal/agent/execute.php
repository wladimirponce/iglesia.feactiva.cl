<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

internalAgentRequirePost();

$middleware = new IntegrationAuthMiddleware();
$middleware->handle(static function (): void {
    try {
        $input = internalAgentJsonInput();
        $tenantId = (int) ($input['tenant_id'] ?? 0);
        $userId = (int) ($input['user_id'] ?? 0);
        $conversationId = isset($input['conversation_id']) && $input['conversation_id'] !== null
            ? (int) $input['conversation_id']
            : null;
        $messageText = trim((string) ($input['message_text'] ?? $input['input_text'] ?? ''));

        $errors = [];
        if ($tenantId < 1) {
            $errors[] = ['field' => 'tenant_id', 'message' => 'Tenant id es requerido.'];
        }
        if ($userId < 1) {
            $errors[] = ['field' => 'user_id', 'message' => 'User id es requerido.'];
        }
        if ($messageText === '') {
            $errors[] = ['field' => 'message_text', 'message' => 'Message text es requerido.'];
        }
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        $conversationId = internalAgentExistingConversationId($tenantId, $conversationId);
        $result = internalAgentService()->createRequest($tenantId, $userId, 'orchestrator', $messageText, $conversationId);

        Response::success([
            'agent_request_id' => $result['id'],
            'status' => $result['status'],
            'normalized_intent' => $result['normalized_intent'],
            'tool' => $result['tool'],
            'response_text' => (string) ($result['response']['text'] ?? ''),
        ]);
    } catch (Throwable) {
        Response::error('AGENT_EXECUTION_ERROR', 'No fue posible ejecutar el agente.', [], 500);
    }
});

function internalAgentExistingConversationId(int $tenantId, ?int $conversationId): ?int
{
    if ($conversationId === null || $conversationId < 1) {
        return null;
    }

    $statement = Database::connection()->prepare("
        SELECT id
        FROM wa_conversations
        WHERE tenant_id = :tenant_id
          AND id = :id
          AND deleted_at IS NULL
        LIMIT 1
    ");
    $statement->execute([
        'tenant_id' => $tenantId,
        'id' => $conversationId,
    ]);

    $id = $statement->fetchColumn();
    return $id === false ? null : (int) $id;
}
