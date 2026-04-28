<?php

declare(strict_types=1);

final class AgentSqlSkillExecutor
{
    public function __construct(
        private readonly AgentSqlSkillCatalog $catalog,
        private readonly AgentSqlSafetyGuard $safetyGuard,
        private readonly PermissionRepository $permissionRepository,
        private readonly AgentAuditLogger $auditLogger
    ) {
    }

    public function executeById(int $tenantId, int $userId, int $skillId, array $parameters): array
    {
        $skill = $this->catalog->findById($tenantId, $skillId);

        if ($skill === null) {
            return $this->blocked($tenantId, $userId, $skillId, $parameters, 'SQL_SKILL_NOT_FOUND');
        }

        return $this->executeSkill($tenantId, $userId, $skill, $parameters);
    }

    public function executeByName(int $tenantId, int $userId, string $name, array $parameters): array
    {
        $skill = $this->catalog->findApprovedByName($tenantId, $name);

        if ($skill === null) {
            return $this->blocked($tenantId, $userId, 0, $parameters, 'SQL_SKILL_NOT_FOUND');
        }

        return $this->executeSkill($tenantId, $userId, $skill, $parameters);
    }

    private function executeSkill(int $tenantId, int $userId, array $skill, array $parameters): array
    {
        $skillId = (int) $skill['id'];

        if ($skill['status'] !== 'approved') {
            return $this->blocked($tenantId, $userId, $skillId, $parameters, 'SQL_SKILL_NOT_APPROVED');
        }

        if (!$this->permissionRepository->userHasPermission($userId, $tenantId, (string) $skill['required_permission'])) {
            return $this->blocked($tenantId, $userId, $skillId, $parameters, 'SQL_SKILL_PERMISSION_DENIED');
        }

        $parametersDefinition = $this->decodeParameters($skill);

        try {
            $this->safetyGuard->assertSafe((string) $skill['sql_template'], $parametersDefinition);
            $this->assertRequiredParameters($parametersDefinition, $parameters);
            $rows = $this->runSelect($tenantId, (string) $skill['sql_template'], $parameters);
            $summary = $this->summary($rows);
            $executionId = $this->catalog->recordExecution($tenantId, $skillId, $userId, $parameters, $summary, 'success');

            $this->auditLogger->logSqlSkill($tenantId, $userId, 'agent.sql_skill.executed', (string) $skill['name'], 'success', $skillId, [
                'execution_id' => $executionId,
                'module_code' => $skill['module_code'],
                'rows_count' => $summary['rows_count'],
            ]);

            return [
                'success' => true,
                'execution_id' => $executionId,
                'skill_id' => $skillId,
                'rows' => $rows,
                'summary' => $summary,
            ];
        } catch (Throwable $throwable) {
            $errorCode = $throwable instanceof RuntimeException ? $throwable->getMessage() : 'SQL_SKILL_EXECUTION_FAILED';
            $status = str_starts_with($errorCode, 'SQL_SKILL_') ? 'blocked' : 'failed';
            $summary = ['error_code' => $errorCode];
            $executionId = $this->catalog->recordExecution($tenantId, $skillId, $userId, $parameters, $summary, $status, $errorCode);
            $eventType = $status === 'blocked' ? 'agent.sql_skill.blocked' : 'agent.sql_skill.failed';

            $this->auditLogger->logSqlSkill($tenantId, $userId, $eventType, (string) $skill['name'], $status, $skillId, [
                'execution_id' => $executionId,
                'error_code' => $errorCode,
            ]);

            return [
                'success' => false,
                'execution_id' => $executionId,
                'skill_id' => $skillId,
                'status' => $status,
                'error_code' => $errorCode,
            ];
        }
    }

    private function runSelect(int $tenantId, string $sqlTemplate, array $parameters): array
    {
        $statement = Database::connection()->prepare($sqlTemplate);
        $statement->bindValue(':tenant_id', $tenantId, PDO::PARAM_INT);

        foreach ($parameters as $name => $value) {
            if (!is_string($name) || $name === 'tenant_id') {
                continue;
            }

            $paramName = ':' . $name;
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $statement->bindValue($paramName, $value, $type);
        }

        $statement->execute();

        $rows = [];

        while (($row = $statement->fetch()) !== false) {
            $rows[] = $row;

            if (count($rows) >= 100) {
                break;
            }
        }

        return $rows;
    }

    private function assertRequiredParameters(array $parametersDefinition, array $parameters): void
    {
        foreach ($this->safetyGuard->requiredParameterNames($parametersDefinition) as $name) {
            if (!array_key_exists($name, $parameters) || $parameters[$name] === null || $parameters[$name] === '') {
                throw new RuntimeException('SQL_SKILL_MISSING_PARAMETER_' . strtoupper($name));
            }
        }
    }

    private function blocked(int $tenantId, int $userId, int $skillId, array $parameters, string $errorCode): array
    {
        if ($skillId > 0) {
            $executionId = $this->catalog->recordExecution($tenantId, $skillId, $userId, $parameters, ['error_code' => $errorCode], 'blocked', $errorCode);
        } else {
            $executionId = null;
        }

        $this->auditLogger->logSqlSkill($tenantId, $userId, 'agent.sql_skill.blocked', 'agent.sql_skill.blocked', 'blocked', $skillId, [
            'execution_id' => $executionId,
            'error_code' => $errorCode,
        ]);

        return [
            'success' => false,
            'execution_id' => $executionId,
            'skill_id' => $skillId,
            'status' => 'blocked',
            'error_code' => $errorCode,
        ];
    }

    private function summary(array $rows): array
    {
        return [
            'rows_count' => count($rows),
            'columns' => isset($rows[0]) ? array_keys($rows[0]) : [],
            'sample_rows' => array_slice($rows, 0, 10),
            'truncated' => count($rows) >= 100,
        ];
    }

    private function decodeParameters(array $skill): array
    {
        $decoded = json_decode((string) ($skill['parameters_json'] ?? '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }
}
