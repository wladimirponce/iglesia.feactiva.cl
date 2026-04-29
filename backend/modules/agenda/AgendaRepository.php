<?php

declare(strict_types=1);

final class AgendaRepository
{
    public function list(int $tenantId, array $filters = []): array
    {
        $where = ['tenant_id = :tenant_id', 'deleted_at IS NULL'];
        $params = ['tenant_id' => $tenantId];

        if (isset($filters['fecha'])) {
            $where[] = 'fecha_inicio >= :desde AND fecha_inicio < :hasta';
            $params['desde'] = $filters['fecha'] . ' 00:00:00';
            $params['hasta'] = date('Y-m-d H:i:s', strtotime($filters['fecha'] . ' +1 day'));
        }
        if (isset($filters['persona_id'])) {
            $where[] = 'persona_id = :persona_id';
            $params['persona_id'] = (int) $filters['persona_id'];
        }
        if (isset($filters['familia_id'])) {
            $where[] = 'familia_id = :familia_id';
            $params['familia_id'] = (int) $filters['familia_id'];
        }

        $sql = "
            SELECT
                id,
                tenant_id,
                created_by_user_id,
                assigned_to_user_id,
                persona_id,
                familia_id,
                tipo,
                titulo,
                descripcion,
                fecha_inicio,
                fecha_fin,
                estado,
                prioridad,
                modulo_origen,
                referencia_id,
                requires_confirmation,
                confirmed_at,
                completed_at,
                cancelled_at,
                created_at,
                updated_at
            FROM agenda_items
            WHERE " . implode(' AND ', $where) . "
            ORDER BY fecha_inicio ASC, id ASC
            LIMIT 100
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function find(int $tenantId, int $id): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                created_by_user_id,
                assigned_to_user_id,
                persona_id,
                familia_id,
                tipo,
                titulo,
                descripcion,
                fecha_inicio,
                fecha_fin,
                estado,
                prioridad,
                modulo_origen,
                referencia_id,
                requires_confirmation,
                confirmed_at,
                completed_at,
                cancelled_at,
                created_at,
                updated_at
            FROM agenda_items
            WHERE tenant_id = :tenant_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'id' => $id]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function create(int $tenantId, int $userId, array $input): int
    {
        $sql = "
            INSERT INTO agenda_items (
                tenant_id,
                created_by_user_id,
                assigned_to_user_id,
                persona_id,
                familia_id,
                tipo,
                titulo,
                descripcion,
                fecha_inicio,
                fecha_fin,
                estado,
                prioridad,
                modulo_origen,
                referencia_id,
                requires_confirmation,
                confirmed_at
            ) VALUES (
                :tenant_id,
                :created_by_user_id,
                :assigned_to_user_id,
                :persona_id,
                :familia_id,
                :tipo,
                :titulo,
                :descripcion,
                :fecha_inicio,
                :fecha_fin,
                'pending',
                :prioridad,
                :modulo_origen,
                :referencia_id,
                :requires_confirmation,
                :confirmed_at
            )
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'created_by_user_id' => $userId,
            'assigned_to_user_id' => isset($input['assigned_to_user_id']) ? (int) $input['assigned_to_user_id'] : null,
            'persona_id' => isset($input['persona_id']) ? (int) $input['persona_id'] : null,
            'familia_id' => isset($input['familia_id']) ? (int) $input['familia_id'] : null,
            'tipo' => $input['tipo'],
            'titulo' => $input['titulo'],
            'descripcion' => $input['descripcion'] ?? null,
            'fecha_inicio' => $input['fecha_inicio'],
            'fecha_fin' => $input['fecha_fin'] ?? null,
            'prioridad' => $input['prioridad'] ?? 'medium',
            'modulo_origen' => $input['modulo_origen'] ?? 'saas',
            'referencia_id' => isset($input['referencia_id']) ? (int) $input['referencia_id'] : null,
            'requires_confirmation' => !empty($input['requires_confirmation']) ? 1 : 0,
            'confirmed_at' => !empty($input['requires_confirmation']) ? null : gmdate('Y-m-d H:i:s'),
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $tenantId, int $id, array $input): void
    {
        $allowed = ['assigned_to_user_id','persona_id','familia_id','tipo','titulo','descripcion','fecha_inicio','fecha_fin','estado','prioridad','modulo_origen','referencia_id','requires_confirmation'];
        $sets = [];
        $params = ['tenant_id' => $tenantId, 'id' => $id];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $input)) {
                $sets[] = $field . ' = :' . $field;
                $params[$field] = $input[$field];
            }
        }
        if ($sets === []) {
            return;
        }
        $sql = "UPDATE agenda_items SET " . implode(', ', $sets) . ", updated_at = UTC_TIMESTAMP() WHERE tenant_id = :tenant_id AND id = :id AND deleted_at IS NULL";
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
    }

    public function setStatus(int $tenantId, int $id, string $status): void
    {
        $column = $status === 'completed' ? 'completed_at' : 'cancelled_at';
        $sql = "
            UPDATE agenda_items
            SET estado = :estado,
                {$column} = UTC_TIMESTAMP(),
                updated_at = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id
              AND id = :id
              AND deleted_at IS NULL
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'id' => $id, 'estado' => $status]);
    }

    public function createNotification(int $tenantId, int $agendaItemId, array $input): int
    {
        $sql = "
            INSERT INTO agenda_notifications (
                tenant_id,
                agenda_item_id,
                channel,
                recipient_type,
                recipient_user_id,
                recipient_persona_id,
                recipient_phone,
                recipient_email,
                external_provider,
                external_message_id,
                message_text,
                scheduled_at,
                status,
                delivery_status,
                delivery_response_json
            ) VALUES (
                :tenant_id,
                :agenda_item_id,
                :channel,
                :recipient_type,
                :recipient_user_id,
                :recipient_persona_id,
                :recipient_phone,
                :recipient_email,
                :external_provider,
                :external_message_id,
                :message_text,
                :scheduled_at,
                'scheduled',
                :delivery_status,
                :delivery_response_json
            )
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'agenda_item_id' => $agendaItemId,
            'channel' => $input['channel'] ?? 'whatsapp',
            'recipient_type' => $input['recipient_type'],
            'recipient_user_id' => $input['recipient_user_id'] ?? null,
            'recipient_persona_id' => $input['recipient_persona_id'] ?? null,
            'recipient_phone' => $input['recipient_phone'] ?? null,
            'recipient_email' => $input['recipient_email'] ?? null,
            'external_provider' => $input['external_provider'] ?? null,
            'external_message_id' => $input['external_message_id'] ?? null,
            'message_text' => $input['message_text'],
            'scheduled_at' => $input['scheduled_at'],
            'delivery_status' => $input['delivery_status'] ?? 'pending',
            'delivery_response_json' => isset($input['delivery_response_json']) ? $this->json($input['delivery_response_json']) : null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function markNotificationDelivery(int $tenantId, int $notificationId, string $deliveryStatus, ?string $externalProvider, ?string $externalMessageId, array $response): void
    {
        $status = $deliveryStatus === 'sent' ? 'sent' : 'failed';
        $sql = "
            UPDATE agenda_notifications
            SET status = :status,
                delivery_status = :delivery_status,
                external_provider = :external_provider,
                external_message_id = :external_message_id,
                delivery_response_json = :delivery_response_json,
                sent_at = CASE WHEN :delivery_status_sent = 'sent' THEN UTC_TIMESTAMP() ELSE sent_at END,
                attempts = attempts + 1,
                updated_at = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id
              AND id = :id
              AND deleted_at IS NULL
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'id' => $notificationId,
            'status' => $status,
            'delivery_status' => $deliveryStatus,
            'external_provider' => $externalProvider,
            'external_message_id' => $externalMessageId,
            'delivery_response_json' => $this->json($response),
            'delivery_status_sent' => $deliveryStatus,
        ]);
    }

    public function personaExists(int $tenantId, int $personaId): bool
    {
        $statement = Database::connection()->prepare("SELECT id FROM crm_personas WHERE tenant_id = :tenant_id AND id = :id AND deleted_at IS NULL LIMIT 1");
        $statement->execute(['tenant_id' => $tenantId, 'id' => $personaId]);
        return $statement->fetchColumn() !== false;
    }

    public function familiaExists(int $tenantId, int $familiaId): bool
    {
        $statement = Database::connection()->prepare("SELECT id FROM crm_familias WHERE tenant_id = :tenant_id AND id = :id AND deleted_at IS NULL LIMIT 1");
        $statement->execute(['tenant_id' => $tenantId, 'id' => $familiaId]);
        return $statement->fetchColumn() !== false;
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}
