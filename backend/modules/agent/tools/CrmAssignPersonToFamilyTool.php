<?php

declare(strict_types=1);

final class CrmAssignPersonToFamilyTool implements AgentToolInterface
{
    public function name(): string { return 'crm_assign_person_to_family'; }
    public function moduleCode(): string { return 'crm'; }
    public function requiredPermission(): string { return 'crm.familias.editar'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $familiaId = (int) ($input['familia_id'] ?? 0);
        $personaId = (int) ($input['persona_id'] ?? 0);
        $parentesco = trim((string) ($input['parentesco'] ?? 'otro'));

        if ($familiaId < 1 || $personaId < 1) {
            throw new RuntimeException('AGENT_TOOL_FAMILY_ASSIGN_DATA_REQUIRED');
        }
        if (!in_array($parentesco, ['jefe_hogar','conyuge','hijo','hija','padre','madre','tutor','hermano','hermana','abuelo','abuela','otro'], true)) {
            $parentesco = 'otro';
        }

        $this->ensureExists($tenantId, 'crm_familias', $familiaId);
        $this->ensureExists($tenantId, 'crm_personas', $personaId);

        $sql = "
            INSERT INTO crm_persona_familia (tenant_id, persona_id, familia_id, parentesco)
            VALUES (:tenant_id, :persona_id, :familia_id, :parentesco)
            ON DUPLICATE KEY UPDATE parentesco = VALUES(parentesco), updated_at = UTC_TIMESTAMP()
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'persona_id' => $personaId,
            'familia_id' => $familiaId,
            'parentesco' => $parentesco,
        ]);

        return ['familia_id' => $familiaId, 'persona_id' => $personaId, 'parentesco' => $parentesco];
    }

    private function ensureExists(int $tenantId, string $table, int $id): void
    {
        $sql = $table === 'crm_familias'
            ? 'SELECT id FROM crm_familias WHERE tenant_id = :tenant_id AND id = :id AND deleted_at IS NULL LIMIT 1'
            : 'SELECT id FROM crm_personas WHERE tenant_id = :tenant_id AND id = :id AND deleted_at IS NULL LIMIT 1';
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'id' => $id]);
        if ($statement->fetchColumn() === false) {
            throw new RuntimeException('AGENT_TOOL_ENTITY_NOT_FOUND');
        }
    }
}
