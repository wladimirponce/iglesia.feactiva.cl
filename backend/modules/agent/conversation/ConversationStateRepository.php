<?php

declare(strict_types=1);

final class ConversationStateRepository
{
    public function findActiveByPhone(string $phone): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                user_id,
                phone,
                conversation_id,
                state_key,
                state_json,
                status,
                expires_at,
                created_at,
                updated_at
            FROM agent_conversation_state
            WHERE phone = :phone
              AND status = 'active'
              AND expires_at > UTC_TIMESTAMP()
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT 1
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['phone' => $phone]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function create(?int $tenantId, ?int $userId, string $phone, ?int $conversationId, string $stateKey, array $state, int $ttlMinutes = 30): int
    {
        $sql = "
            INSERT INTO agent_conversation_state (
                tenant_id,
                user_id,
                phone,
                conversation_id,
                state_key,
                state_json,
                status,
                expires_at
            ) VALUES (
                :tenant_id,
                :user_id,
                :phone,
                :conversation_id,
                :state_key,
                :state_json,
                'active',
                :expires_at
            )
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'phone' => $phone,
            'conversation_id' => $conversationId,
            'state_key' => $stateKey,
            'state_json' => $this->json($state),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + ($ttlMinutes * 60)),
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, string $stateKey, array $state, string $status = 'active', int $ttlMinutes = 30): void
    {
        $sql = "
            UPDATE agent_conversation_state
            SET state_key = :state_key,
                state_json = :state_json,
                status = :status,
                expires_at = :expires_at,
                updated_at = UTC_TIMESTAMP()
            WHERE id = :id
              AND deleted_at IS NULL
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'id' => $id,
            'state_key' => $stateKey,
            'state_json' => $this->json($state),
            'status' => $status,
            'expires_at' => gmdate('Y-m-d H:i:s', time() + ($ttlMinutes * 60)),
        ]);
    }

    public function close(int $id, string $status): void
    {
        $sql = "
            UPDATE agent_conversation_state
            SET status = :status,
                updated_at = UTC_TIMESTAMP()
            WHERE id = :id
              AND deleted_at IS NULL
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['id' => $id, 'status' => $status]);
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}
