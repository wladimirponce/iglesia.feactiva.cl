<?php

declare(strict_types=1);

final class CrmContactosRepository
{
    public function personaExists(int $tenantId, int $personaId): bool
    {
        $sql = "
            SELECT 1
            FROM crm_personas
            WHERE tenant_id = :tenant_id
              AND id = :persona_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'persona_id' => $personaId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function listByPersona(int $tenantId, int $personaId): array
    {
        $sql = "
            SELECT
                id,
                persona_id,
                tipo_contacto,
                fecha_contacto,
                asunto,
                resumen,
                resultado,
                requiere_seguimiento,
                fecha_seguimiento,
                created_at,
                created_by
            FROM crm_contactos_historial
            WHERE tenant_id = :tenant_id
              AND persona_id = :persona_id
            ORDER BY fecha_contacto DESC, id DESC
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'persona_id' => $personaId,
        ]);

        return $statement->fetchAll();
    }

    public function create(int $tenantId, int $personaId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO crm_contactos_historial (
                tenant_id,
                persona_id,
                tipo_contacto,
                fecha_contacto,
                asunto,
                resumen,
                resultado,
                requiere_seguimiento,
                fecha_seguimiento,
                created_by
            ) VALUES (
                :tenant_id,
                :persona_id,
                :tipo_contacto,
                :fecha_contacto,
                :asunto,
                :resumen,
                :resultado,
                :requiere_seguimiento,
                :fecha_seguimiento,
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'persona_id' => $personaId,
            'tipo_contacto' => $data['tipo_contacto'],
            'fecha_contacto' => $data['fecha_contacto'],
            'asunto' => $data['asunto'],
            'resumen' => $data['resumen'],
            'resultado' => $data['resultado'],
            'requiere_seguimiento' => $data['requiere_seguimiento'],
            'fecha_seguimiento' => $data['fecha_seguimiento'],
            'created_by' => $userId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function auditCreated(int $tenantId, int $userId, int $contactoId, array $newValues): void
    {
        $sql = "
            INSERT INTO audit_logs (
                tenant_id,
                user_id,
                module_code,
                action,
                table_name,
                record_id,
                old_values,
                new_values,
                ip_address,
                user_agent
            ) VALUES (
                :tenant_id,
                :user_id,
                'crm',
                'crm.contacto.created',
                'crm_contactos_historial',
                :record_id,
                NULL,
                :new_values,
                :ip_address,
                :user_agent
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'record_id' => $contactoId,
            'new_values' => json_encode($newValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
