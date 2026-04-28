<?php

declare(strict_types=1);

final class OutboundDraftRepository
{
    public function create(?int $tenantId, ?int $userId, ?int $conversationId, array $input): int
    {
        $sql = "
            INSERT INTO outbound_message_drafts (
                tenant_id,
                created_by_user_id,
                conversation_id,
                recipient_phone,
                recipient_persona_id,
                channel,
                original_text,
                draft_text,
                status
            ) VALUES (
                :tenant_id,
                :created_by_user_id,
                :conversation_id,
                :recipient_phone,
                :recipient_persona_id,
                :channel,
                :original_text,
                :draft_text,
                'waiting_confirmation'
            )
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'created_by_user_id' => $userId,
            'conversation_id' => $conversationId,
            'recipient_phone' => $input['recipient_phone'] ?? null,
            'recipient_persona_id' => $input['recipient_persona_id'] ?? null,
            'channel' => $input['channel'] ?? 'whatsapp',
            'original_text' => $input['original_text'],
            'draft_text' => $input['draft_text'],
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                created_by_user_id,
                conversation_id,
                recipient_phone,
                recipient_persona_id,
                channel,
                original_text,
                draft_text,
                improved_text,
                status,
                approved_at,
                sent_at
            FROM outbound_message_drafts
            WHERE id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function improve(int $id, string $text): void
    {
        $statement = Database::connection()->prepare("
            UPDATE outbound_message_drafts
            SET improved_text = :improved_text,
                status = 'waiting_confirmation',
                updated_at = UTC_TIMESTAMP()
            WHERE id = :id
              AND deleted_at IS NULL
        ");
        $statement->execute(['id' => $id, 'improved_text' => $text]);
    }

    public function setStatus(int $id, string $status): void
    {
        $approvedSql = $status === 'approved' ? ', approved_at = UTC_TIMESTAMP()' : '';
        $sentSql = $status === 'sent' ? ', sent_at = UTC_TIMESTAMP()' : '';
        $statement = Database::connection()->prepare("
            UPDATE outbound_message_drafts
            SET status = :status,
                updated_at = UTC_TIMESTAMP()
                {$approvedSql}
                {$sentSql}
            WHERE id = :id
              AND deleted_at IS NULL
        ");
        $statement->execute(['id' => $id, 'status' => $status]);
    }
}
