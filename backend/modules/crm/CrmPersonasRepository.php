<?php

declare(strict_types=1);

final class CrmPersonasRepository
{
    public function list(int $tenantId, int $limit, int $offset): array
    {
        $sql = "
            SELECT
                id,
                nombres,
                apellidos,
                email,
                telefono,
                whatsapp,
                estado_persona,
                fecha_ingreso,
                created_at
            FROM crm_personas
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
            ORDER BY apellidos, nombres
            LIMIT :limit OFFSET :offset
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->bindValue('tenant_id', $tenantId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function count(int $tenantId): int
    {
        $sql = "
            SELECT COUNT(id)
            FROM crm_personas
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function findById(int $tenantId, int $personaId): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                nombres,
                apellidos,
                nombre_preferido,
                tipo_documento,
                numero_documento,
                email,
                telefono,
                whatsapp,
                fecha_nacimiento,
                genero,
                estado_civil,
                direccion,
                ciudad,
                region,
                pais,
                estado_persona,
                fecha_primer_contacto,
                fecha_ingreso,
                fecha_membresia,
                origen_contacto,
                observaciones_generales,
                foto_url,
                created_at,
                updated_at
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

        $persona = $statement->fetch();

        return $persona === false ? null : $persona;
    }

    public function documentExists(int $tenantId, ?string $tipoDocumento, ?string $numeroDocumento): bool
    {
        if ($tipoDocumento === null || $tipoDocumento === '' || $numeroDocumento === null || $numeroDocumento === '') {
            return false;
        }

        $sql = "
            SELECT 1
            FROM crm_personas
            WHERE tenant_id = :tenant_id
              AND tipo_documento = :tipo_documento
              AND numero_documento = :numero_documento
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $numeroDocumento,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function documentExistsExcluding(int $tenantId, int $personaId, ?string $tipoDocumento, ?string $numeroDocumento): bool
    {
        if ($tipoDocumento === null || $tipoDocumento === '' || $numeroDocumento === null || $numeroDocumento === '') {
            return false;
        }

        $sql = "
            SELECT 1
            FROM crm_personas
            WHERE tenant_id = :tenant_id
              AND id <> :persona_id
              AND tipo_documento = :tipo_documento
              AND numero_documento = :numero_documento
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'persona_id' => $personaId,
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $numeroDocumento,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function create(int $tenantId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO crm_personas (
                tenant_id,
                nombres,
                apellidos,
                nombre_preferido,
                tipo_documento,
                numero_documento,
                email,
                telefono,
                whatsapp,
                fecha_nacimiento,
                genero,
                estado_civil,
                direccion,
                ciudad,
                region,
                pais,
                estado_persona,
                fecha_primer_contacto,
                fecha_ingreso,
                fecha_membresia,
                origen_contacto,
                observaciones_generales,
                foto_url,
                created_by
            ) VALUES (
                :tenant_id,
                :nombres,
                :apellidos,
                :nombre_preferido,
                :tipo_documento,
                :numero_documento,
                :email,
                :telefono,
                :whatsapp,
                :fecha_nacimiento,
                :genero,
                :estado_civil,
                :direccion,
                :ciudad,
                :region,
                :pais,
                :estado_persona,
                :fecha_primer_contacto,
                :fecha_ingreso,
                :fecha_membresia,
                :origen_contacto,
                :observaciones_generales,
                :foto_url,
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'nombre_preferido' => $data['nombre_preferido'],
            'tipo_documento' => $data['tipo_documento'],
            'numero_documento' => $data['numero_documento'],
            'email' => $data['email'],
            'telefono' => $data['telefono'],
            'whatsapp' => $data['whatsapp'],
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'genero' => $data['genero'],
            'estado_civil' => $data['estado_civil'],
            'direccion' => $data['direccion'],
            'ciudad' => $data['ciudad'],
            'region' => $data['region'],
            'pais' => $data['pais'],
            'estado_persona' => $data['estado_persona'],
            'fecha_primer_contacto' => $data['fecha_primer_contacto'],
            'fecha_ingreso' => $data['fecha_ingreso'],
            'fecha_membresia' => $data['fecha_membresia'],
            'origen_contacto' => $data['origen_contacto'],
            'observaciones_generales' => $data['observaciones_generales'],
            'foto_url' => $data['foto_url'],
            'created_by' => $userId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function createMembershipHistory(
        int $tenantId,
        int $personaId,
        ?string $estadoAnterior,
        string $estadoNuevo,
        int $userId,
        string $motivo
    ): void
    {
        $sql = "
            INSERT INTO crm_historial_membresia (
                tenant_id,
                persona_id,
                estado_anterior,
                estado_nuevo,
                fecha_cambio,
                motivo,
                observacion,
                created_by
            ) VALUES (
                :tenant_id,
                :persona_id,
                :estado_anterior,
                :estado_nuevo,
                UTC_DATE(),
                :motivo,
                NULL,
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'persona_id' => $personaId,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'motivo' => $motivo,
            'created_by' => $userId,
        ]);
    }

    public function auditCreated(int $tenantId, int $userId, int $personaId, array $newValues): void
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
                'crm.persona.created',
                'crm_personas',
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
            'record_id' => $personaId,
            'new_values' => json_encode($newValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    public function update(int $tenantId, int $personaId, int $userId, array $data): void
    {
        $assignments = [];
        $parameters = [
            'tenant_id' => $tenantId,
            'persona_id' => $personaId,
            'updated_by' => $userId,
        ];

        foreach ($data as $field => $value) {
            $assignments[] = $field . ' = :' . $field;
            $parameters[$field] = $value;
        }

        $assignments[] = 'updated_by = :updated_by';

        $sql = "
            UPDATE crm_personas
            SET " . implode(', ', $assignments) . "
            WHERE tenant_id = :tenant_id
              AND id = :persona_id
              AND deleted_at IS NULL
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute($parameters);
    }

    public function softDelete(int $tenantId, int $personaId, int $userId): void
    {
        $sql = "
            UPDATE crm_personas
            SET deleted_at = UTC_TIMESTAMP(),
                deleted_by = :deleted_by
            WHERE tenant_id = :tenant_id
              AND id = :persona_id
              AND deleted_at IS NULL
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'persona_id' => $personaId,
            'deleted_by' => $userId,
        ]);
    }

    public function auditUpdated(int $tenantId, int $userId, int $personaId, array $oldValues, array $newValues): void
    {
        $this->insertAudit($tenantId, $userId, 'crm.persona.updated', $personaId, $oldValues, $newValues);
    }

    public function auditDeleted(int $tenantId, int $userId, int $personaId, array $oldValues): void
    {
        $this->insertAudit($tenantId, $userId, 'crm.persona.deleted', $personaId, $oldValues, null);
    }

    private function insertAudit(int $tenantId, int $userId, string $action, int $personaId, ?array $oldValues, ?array $newValues): void
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
                'crm_personas',
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
            'record_id' => $personaId,
            'old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'new_values' => $newValues === null ? null : json_encode($newValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
