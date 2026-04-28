<?php

declare(strict_types=1);

final class AgentSqlSkillApproval
{
    public function __construct(
        private readonly AgentSqlSkillCatalog $catalog,
        private readonly AgentSqlSafetyGuard $safetyGuard,
        private readonly AgentAuditLogger $auditLogger
    ) {
    }

    public function approve(int $tenantId, int $userId, int $skillId): array
    {
        $skill = $this->requireSkill($tenantId, $skillId);
        $this->safetyGuard->assertSafe($skill['sql_template'], $this->decodeParameters($skill));
        $this->catalog->updateStatus($tenantId, $skillId, 'approved', $userId);

        $this->auditLogger->logSqlSkill($tenantId, $userId, 'agent.sql_skill.approved', 'agent.sql_skill.approved', 'success', $skillId, [
            'name' => $skill['name'],
        ]);

        return ['id' => $skillId, 'status' => 'approved'];
    }

    public function reject(int $tenantId, int $userId, int $skillId): array
    {
        $skill = $this->requireSkill($tenantId, $skillId);
        $this->catalog->updateStatus($tenantId, $skillId, 'rejected', $userId);

        $this->auditLogger->logSqlSkill($tenantId, $userId, 'agent.sql_skill.rejected', 'agent.sql_skill.rejected', 'success', $skillId, [
            'name' => $skill['name'],
        ]);

        return ['id' => $skillId, 'status' => 'rejected'];
    }

    public function deprecate(int $tenantId, int $userId, int $skillId): array
    {
        $skill = $this->requireSkill($tenantId, $skillId);
        $this->catalog->updateStatus($tenantId, $skillId, 'deprecated', $userId);

        $this->auditLogger->logSqlSkill($tenantId, $userId, 'agent.sql_skill.deprecated', 'agent.sql_skill.deprecated', 'success', $skillId, [
            'name' => $skill['name'],
        ]);

        return ['id' => $skillId, 'status' => 'deprecated'];
    }

    private function requireSkill(int $tenantId, int $skillId): array
    {
        $skill = $this->catalog->findById($tenantId, $skillId);

        if ($skill === null) {
            throw new RuntimeException('SQL_SKILL_NOT_FOUND');
        }

        return $skill;
    }

    private function decodeParameters(array $skill): array
    {
        $decoded = json_decode((string) ($skill['parameters_json'] ?? '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }
}
