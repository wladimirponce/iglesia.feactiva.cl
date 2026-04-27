<?php

declare(strict_types=1);

final class CrmEtiquetasRepository
{
    public function list(int $tenantId): array
    {
        $sql = "
            SELECT id, nombre, descripcion, color, created_at, updated_at
            FROM crm_etiquetas
            WHERE tenant_id = :tenant_id
            ORDER BY nombre
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);
        return $statement->fetchAll();
    }

    public function find(int $tenantId, int $etiquetaId): ?array
    {
        $sql = "
            SELECT id, tenant_id, nombre, descripcion, color, created_at, updated_at
            FROM crm_etiquetas
            WHERE tenant_id = :tenant_id
              AND id = :etiqueta_id
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'etiqueta_id' => $etiquetaId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

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
        $statement->execute(['tenant_id' => $tenantId, 'persona_id' => $personaId]);
        return $statement->fetchColumn() !== false;
    }

    public function create(int $tenantId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO crm_etiquetas (tenant_id, nombre, descripcion, color, created_by)
            VALUES (:tenant_id, :nombre, :descripcion, :color, :created_by)
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
            'color' => $data['color'],
            'created_by' => $userId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $tenantId, int $etiquetaId, array $data): void
    {
        $assignments = [];
        $params = ['tenant_id' => $tenantId, 'etiqueta_id' => $etiquetaId];

        foreach ($data as $field => $value) {
            $assignments[] = $field . ' = :' . $field;
            $params[$field] = $value;
        }

        $sql = "
            UPDATE crm_etiquetas
            SET " . implode(', ', $assignments) . "
            WHERE tenant_id = :tenant_id
              AND id = :etiqueta_id
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
    }

    public function delete(int $tenantId, int $etiquetaId): void
    {
        $sql = "
            DELETE FROM crm_etiquetas
            WHERE tenant_id = :tenant_id
              AND id = :etiqueta_id
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'etiqueta_id' => $etiquetaId]);
    }

    public function assignToPersona(int $tenantId, int $personaId, int $etiquetaId, int $userId): void
    {
        $sql = "
            INSERT IGNORE INTO crm_persona_etiquetas (tenant_id, persona_id, etiqueta_id, created_by)
            VALUES (:tenant_id, :persona_id, :etiqueta_id, :created_by)
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'persona_id' => $personaId,
            'etiqueta_id' => $etiquetaId,
            'created_by' => $userId,
        ]);
    }

    public function removeFromPersona(int $tenantId, int $personaId, int $etiquetaId): void
    {
        $sql = "
            DELETE FROM crm_persona_etiquetas
            WHERE tenant_id = :tenant_id
              AND persona_id = :persona_id
              AND etiqueta_id = :etiqueta_id
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'persona_id' => $personaId, 'etiqueta_id' => $etiquetaId]);
    }

    public function audit(int $tenantId, int $userId, string $action, string $table, ?int $recordId, ?array $oldValues, ?array $newValues): void
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
                :action,
                :table_name,
                :record_id,
                :old_values,
                :new_values,
                :ip_address,
                :user_agent
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'new_values' => $newValues === null ? null : json_encode($newValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
