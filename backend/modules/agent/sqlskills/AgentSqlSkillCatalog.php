<?php

declare(strict_types=1);

final class AgentSqlSkillCatalog
{
    public function create(
        int $tenantId,
        int $createdBy,
        string $name,
        string $description,
        string $moduleCode,
        string $requiredPermission,
        string $sqlTemplate,
        array $parameters
    ): int {
        $sql = "
            INSERT INTO agent_sql_skills (
                tenant_id,
                name,
                description,
                module_code,
                required_permission,
                sql_template,
                parameters_json,
                status,
                created_by
            ) VALUES (
                :tenant_id,
                :name,
                :description,
                :module_code,
                :required_permission,
                :sql_template,
                :parameters_json,
                'pending_approval',
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'name' => $name,
            'description' => $description,
            'module_code' => $moduleCode,
            'required_permission' => $requiredPermission,
            'sql_template' => $sqlTemplate,
            'parameters_json' => $this->json($parameters),
            'created_by' => $createdBy,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function findById(int $tenantId, int $skillId): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                name,
                description,
                module_code,
                required_permission,
                sql_template,
                parameters_json,
                status,
                created_by,
                approved_by,
                approved_at,
                created_at,
                updated_at
            FROM agent_sql_skills
            WHERE tenant_id = :tenant_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'id' => $skillId,
        ]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function findApprovedByName(int $tenantId, string $name): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                name,
                description,
                module_code,
                required_permission,
                sql_template,
                parameters_json,
                status,
                created_by,
                approved_by,
                approved_at,
                created_at,
                updated_at
            FROM agent_sql_skills
            WHERE tenant_id = :tenant_id
              AND name = :name
              AND status = 'approved'
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'name' => $name,
        ]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function updateStatus(int $tenantId, int $skillId, string $status, int $userId): void
    {
        $approvedAtSql = $status === 'approved' ? 'UTC_TIMESTAMP()' : 'NULL';

        $sql = "
            UPDATE agent_sql_skills
            SET status = :status,
                approved_by = :approved_by,
                approved_at = {$approvedAtSql},
                updated_at = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id
              AND id = :id
              AND deleted_at IS NULL
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'id' => $skillId,
            'status' => $status,
            'approved_by' => $userId,
        ]);
    }

    public function recordExecution(
        int $tenantId,
        int $skillId,
        int $userId,
        array $parameters,
        array $resultSummary,
        string $status,
        ?string $errorCode = null
    ): int {
        $sql = "
            INSERT INTO agent_sql_skill_executions (
                tenant_id,
                skill_id,
                user_id,
                parameters_json,
                result_summary_json,
                status,
                error_code
            ) VALUES (
                :tenant_id,
                :skill_id,
                :user_id,
                :parameters_json,
                :result_summary_json,
                :status,
                :error_code
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'skill_id' => $skillId,
            'user_id' => $userId,
            'parameters_json' => $this->json($parameters),
            'result_summary_json' => $this->json($resultSummary),
            'status' => $status,
            'error_code' => $errorCode,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}
