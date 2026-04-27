<?php

declare(strict_types=1);

final class FinanzasDocumentosRepository
{
    public function movimientoExists(int $tenantId, int $movimientoId): bool
    {
        $sql = "
            SELECT 1
            FROM fin_movimientos
            WHERE tenant_id = :tenant_id
              AND id = :movimiento_id
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'movimiento_id' => $movimientoId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function listByMovimiento(int $tenantId, int $movimientoId): array
    {
        $sql = "
            SELECT
                id,
                movimiento_id,
                tipo_documento,
                numero_documento,
                fecha_documento,
                archivo_url,
                archivo_nombre,
                archivo_mime,
                archivo_size,
                descripcion,
                created_at,
                created_by
            FROM fin_documentos
            WHERE tenant_id = :tenant_id
              AND movimiento_id = :movimiento_id
            ORDER BY created_at DESC, id DESC
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'movimiento_id' => $movimientoId,
        ]);

        return $statement->fetchAll();
    }

    public function find(int $tenantId, int $documentoId): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                movimiento_id,
                tipo_documento,
                numero_documento,
                fecha_documento,
                archivo_url,
                archivo_nombre,
                archivo_mime,
                archivo_size,
                descripcion,
                created_at,
                created_by
            FROM fin_documentos
            WHERE tenant_id = :tenant_id
              AND id = :documento_id
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'documento_id' => $documentoId,
        ]);

        $documento = $statement->fetch();

        return $documento === false ? null : $documento;
    }

    public function create(int $tenantId, int $movimientoId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO fin_documentos (
                tenant_id,
                movimiento_id,
                tipo_documento,
                numero_documento,
                fecha_documento,
                archivo_url,
                archivo_nombre,
                archivo_mime,
                archivo_size,
                descripcion,
                created_by
            ) VALUES (
                :tenant_id,
                :movimiento_id,
                :tipo_documento,
                :numero_documento,
                :fecha_documento,
                :archivo_url,
                :archivo_nombre,
                :archivo_mime,
                :archivo_size,
                :descripcion,
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'movimiento_id' => $movimientoId,
            'tipo_documento' => $data['tipo_documento'],
            'numero_documento' => $data['numero_documento'],
            'fecha_documento' => $data['fecha_documento'],
            'archivo_url' => $data['archivo_url'],
            'archivo_nombre' => $data['archivo_nombre'],
            'archivo_mime' => $data['archivo_mime'],
            'archivo_size' => $data['archivo_size'],
            'descripcion' => $data['descripcion'],
            'created_by' => $userId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function delete(int $tenantId, int $documentoId): void
    {
        $sql = "
            DELETE FROM fin_documentos
            WHERE tenant_id = :tenant_id
              AND id = :documento_id
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'documento_id' => $documentoId,
        ]);
    }

    public function audit(int $tenantId, int $userId, string $action, int $documentoId, ?array $oldValues, ?array $newValues): void
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
                'finanzas',
                :action,
                'fin_documentos',
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
            'record_id' => $documentoId,
            'old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'new_values' => $newValues === null ? null : json_encode($newValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
