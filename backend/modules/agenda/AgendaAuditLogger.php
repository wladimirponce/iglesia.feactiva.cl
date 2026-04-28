<?php

declare(strict_types=1);

final class AgendaAuditLogger
{
    public function log(int $tenantId, ?int $userId, ?int $agendaItemId, string $eventType, array $oldValues = [], array $newValues = []): void
    {
        $sql = "
            INSERT INTO agenda_audit_logs (
                tenant_id,
                user_id,
                agenda_item_id,
                event_type,
                event_description,
                old_values_json,
                new_values_json
            ) VALUES (
                :tenant_id,
                :user_id,
                :agenda_item_id,
                :event_type,
                :event_description,
                :old_values_json,
                :new_values_json
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'agenda_item_id' => $agendaItemId,
            'event_type' => $eventType,
            'event_description' => $eventType,
            'old_values_json' => $this->json($oldValues),
            'new_values_json' => $this->json($newValues),
        ]);
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}
