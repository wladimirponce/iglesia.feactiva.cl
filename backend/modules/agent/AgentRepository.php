<?php

declare(strict_types=1);

final class AgentRepository
{
    public function beginTransaction(): void
    {
        Database::connection()->beginTransaction();
    }

    public function commit(): void
    {
        Database::connection()->commit();
    }

    public function rollBack(): void
    {
        $pdo = Database::connection();

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    public function createRequest(int $tenantId, int $userId, string $source, string $inputText, ?int $conversationId = null): int
    {
        $payload = [
            'source' => $source,
            'input_text' => $inputText,
        ];

        $sql = "
            INSERT INTO agent_requests (
                tenant_id,
                conversation_id,
                user_id,
                source,
                request_type,
                input_payload,
                input_text,
                status
            ) VALUES (
                :tenant_id,
                :conversation_id,
                :user_id,
                :source,
                'agent.request',
                :input_payload,
                :input_text,
                'received'
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'source' => $source,
            'input_payload' => $this->json($payload),
            'input_text' => $inputText,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function updateIntent(int $tenantId, int $requestId, string $intent): void
    {
        $sql = "
            UPDATE agent_requests
            SET normalized_intent = :normalized_intent,
                status = 'processing',
                updated_at = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id
              AND id = :id
              AND deleted_at IS NULL
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'id' => $requestId,
            'normalized_intent' => $intent,
        ]);
    }

    public function createResponse(int $tenantId, int $requestId, string $intent, string $responseText): int
    {
        $payload = [
            'intent' => $intent,
            'response_text' => $responseText,
            'actions_executed' => false,
        ];

        $sql = "
            INSERT INTO agent_responses (
                tenant_id,
                request_id,
                output_payload,
                response_text,
                status
            ) VALUES (
                :tenant_id,
                :request_id,
                :output_payload,
                :response_text,
                'created'
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'request_id' => $requestId,
            'output_payload' => $this->json($payload),
            'response_text' => $responseText,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function createAction(
        int $tenantId,
        int $requestId,
        int $userId,
        string $actionName,
        string $moduleCode,
        array $input,
        array $output,
        string $status,
        ?string $targetTable = null,
        ?int $targetId = null
    ): int {
        $sql = "
            INSERT INTO agent_actions (
                tenant_id,
                request_id,
                actor_user_id,
                action_code,
                action_name,
                module_code,
                target_table,
                target_id,
                input_payload,
                input_json,
                result_payload,
                output_json,
                status,
                executed_at
            ) VALUES (
                :tenant_id,
                :request_id,
                :actor_user_id,
                :action_code,
                :action_name,
                :module_code,
                :target_table,
                :target_id,
                :input_payload,
                :input_json,
                :result_payload,
                :output_json,
                :status,
                UTC_TIMESTAMP()
            )
        ";

        $inputJson = $this->json($input);
        $outputJson = $this->json($output);
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'request_id' => $requestId,
            'actor_user_id' => $userId,
            'action_code' => $actionName,
            'action_name' => $actionName,
            'module_code' => $moduleCode,
            'target_table' => $targetTable,
            'target_id' => $targetId,
            'input_payload' => $inputJson,
            'input_json' => $inputJson,
            'result_payload' => $outputJson,
            'output_json' => $outputJson,
            'status' => $status,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function completeRequest(int $tenantId, int $requestId): void
    {
        $sql = "
            UPDATE agent_requests
            SET status = 'completed',
                completed_at = UTC_TIMESTAMP(),
                updated_at = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id
              AND id = :id
              AND deleted_at IS NULL
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'id' => $requestId,
        ]);
    }

    public function findRequest(int $tenantId, int $requestId): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                user_id,
                source,
                request_type,
                input_text,
                normalized_intent,
                status,
                created_at,
                updated_at,
                completed_at
            FROM agent_requests
            WHERE tenant_id = :tenant_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'id' => $requestId,
        ]);

        $request = $statement->fetch();

        return $request === false ? null : $request;
    }

    public function findLatestResponse(int $tenantId, int $requestId): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                request_id,
                response_text,
                status,
                created_at,
                sent_at
            FROM agent_responses
            WHERE tenant_id = :tenant_id
              AND request_id = :request_id
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'request_id' => $requestId,
        ]);

        $response = $statement->fetch();

        return $response === false ? null : $response;
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}
