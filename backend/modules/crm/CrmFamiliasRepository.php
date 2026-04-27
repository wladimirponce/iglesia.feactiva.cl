<?php

declare(strict_types=1);

final class CrmFamiliasRepository
{
    public function list(int $tenantId): array
    {
        $sql = "
            SELECT
                id,
                nombre_familia,
                direccion,
                ciudad,
                region,
                pais,
                telefono_principal,
                email_principal,
                observaciones,
                created_at,
                updated_at
            FROM crm_familias
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
            ORDER BY nombre_familia
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);

        return $statement->fetchAll();
    }

    public function find(int $tenantId, int $familiaId): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                nombre_familia,
                direccion,
                ciudad,
                region,
                pais,
                telefono_principal,
                email_principal,
                observaciones,
                created_at,
                updated_at
            FROM crm_familias
            WHERE tenant_id = :tenant_id
              AND id = :familia_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'familia_id' => $familiaId,
        ]);

        $familia = $statement->fetch();

        return $familia === false ? null : $familia;
    }

    public function listMiembros(int $tenantId, int $familiaId): array
    {
        $sql = "
            SELECT
                pf.persona_id,
                pf.parentesco AS tipo_relacion,
                pf.es_contacto_principal,
                pf.vive_en_hogar,
                p.nombres,
                p.apellidos,
                p.email,
                p.telefono,
                p.estado_persona
            FROM crm_persona_familia pf
            INNER JOIN crm_personas p
                ON p.id = pf.persona_id
                AND p.tenant_id = pf.tenant_id
            WHERE pf.tenant_id = :tenant_id
              AND pf.familia_id = :familia_id
              AND p.deleted_at IS NULL
            ORDER BY p.apellidos, p.nombres
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'familia_id' => $familiaId,
        ]);

        return $statement->fetchAll();
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
        $statement->execute([
            'tenant_id' => $tenantId,
            'persona_id' => $personaId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function relationExists(int $tenantId, int $familiaId, int $personaId): bool
    {
        $sql = "
            SELECT 1
            FROM crm_persona_familia
            WHERE tenant_id = :tenant_id
              AND familia_id = :familia_id
              AND persona_id = :persona_id
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'familia_id' => $familiaId,
            'persona_id' => $personaId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function create(int $tenantId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO crm_familias (
                tenant_id,
                nombre_familia,
                direccion,
                ciudad,
                region,
                pais,
                telefono_principal,
                email_principal,
                observaciones,
                created_by
            ) VALUES (
                :tenant_id,
                :nombre_familia,
                :direccion,
                :ciudad,
                :region,
                :pais,
                :telefono_principal,
                :email_principal,
                :observaciones,
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'nombre_familia' => $data['nombre_familia'],
            'direccion' => $data['direccion'],
            'ciudad' => $data['ciudad'],
            'region' => $data['region'],
            'pais' => $data['pais'],
            'telefono_principal' => $data['telefono_principal'],
            'email_principal' => $data['email_principal'],
            'observaciones' => $data['observaciones'],
            'created_by' => $userId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $tenantId, int $familiaId, int $userId, array $data): void
    {
        $assignments = [];
        $params = [
            'tenant_id' => $tenantId,
            'familia_id' => $familiaId,
            'updated_by' => $userId,
        ];

        foreach ($data as $field => $value) {
            $assignments[] = $field . ' = :' . $field;
            $params[$field] = $value;
        }

        $assignments[] = 'updated_by = :updated_by';

        $sql = "
            UPDATE crm_familias
            SET " . implode(', ', $assignments) . "
            WHERE tenant_id = :tenant_id
              AND id = :familia_id
              AND deleted_at IS NULL
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
    }

    public function addPersona(int $tenantId, int $familiaId, int $personaId, array $data): void
    {
        $sql = "
            INSERT INTO crm_persona_familia (
                tenant_id,
                persona_id,
                familia_id,
                parentesco,
                es_contacto_principal,
                vive_en_hogar
            ) VALUES (
                :tenant_id,
                :persona_id,
                :familia_id,
                :parentesco,
                :es_contacto_principal,
                :vive_en_hogar
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'persona_id' => $personaId,
            'familia_id' => $familiaId,
            'parentesco' => $data['parentesco'],
            'es_contacto_principal' => $data['es_contacto_principal'],
            'vive_en_hogar' => $data['vive_en_hogar'],
        ]);
    }

    public function removePersona(int $tenantId, int $familiaId, int $personaId): void
    {
        $sql = "
            DELETE FROM crm_persona_familia
            WHERE tenant_id = :tenant_id
              AND familia_id = :familia_id
              AND persona_id = :persona_id
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'familia_id' => $familiaId,
            'persona_id' => $personaId,
        ]);
    }

    public function audit(int $tenantId, int $userId, string $action, string $tableName, ?int $recordId, ?array $oldValues, ?array $newValues): void
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
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'new_values' => $newValues === null ? null : json_encode($newValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
