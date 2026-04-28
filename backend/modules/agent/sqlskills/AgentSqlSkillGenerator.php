<?php

declare(strict_types=1);

final class AgentSqlSkillGenerator
{
    public function __construct(
        private readonly AgentSqlSkillCatalog $catalog,
        private readonly AgentSqlSafetyGuard $safetyGuard,
        private readonly AgentAuditLogger $auditLogger
    ) {
    }

    public function generateFromDefinition(int $tenantId, int $userId, array $definition): array
    {
        $name = $this->requiredString($definition, 'name');
        $description = $this->optionalString($definition, 'description');
        $moduleCode = $this->requiredString($definition, 'module_code');
        $requiredPermission = $this->requiredString($definition, 'required_permission');
        $sqlTemplate = $this->requiredString($definition, 'sql_template');
        $parameters = $definition['parameters_json'] ?? $definition['parameters'] ?? [];

        if (!is_array($parameters)) {
            throw new RuntimeException('SQL_SKILL_INVALID_PARAMETERS');
        }

        $this->safetyGuard->assertSafe($sqlTemplate, $parameters);

        $skillId = $this->catalog->create(
            $tenantId,
            $userId,
            $name,
            $description,
            $moduleCode,
            $requiredPermission,
            $sqlTemplate,
            $parameters
        );

        $this->auditLogger->logSqlSkill(
            $tenantId,
            $userId,
            'agent.sql_skill.generated',
            'agent.sql_skill.generated',
            'success',
            $skillId,
            [
                'name' => $name,
                'module_code' => $moduleCode,
                'required_permission' => $requiredPermission,
            ]
        );

        return [
            'id' => $skillId,
            'status' => 'pending_approval',
        ];
    }

    private function requiredString(array $definition, string $key): string
    {
        $value = $definition[$key] ?? null;

        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException('SQL_SKILL_MISSING_' . strtoupper($key));
        }

        return trim($value);
    }

    private function optionalString(array $definition, string $key): string
    {
        $value = $definition[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
