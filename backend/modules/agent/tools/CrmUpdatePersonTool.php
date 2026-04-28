<?php

declare(strict_types=1);

final class CrmUpdatePersonTool implements AgentToolInterface
{
    public function name(): string { return 'crm_update_person'; }
    public function moduleCode(): string { return 'crm'; }
    public function requiredPermission(): string { return 'crm.personas.editar'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $personaId = (int) ($input['persona_id'] ?? 0);
        if ($personaId < 1) {
            throw new RuntimeException('AGENT_TOOL_PERSON_REQUIRED');
        }

        $allowed = ['nombres', 'apellidos', 'email', 'telefono', 'whatsapp', 'estado_persona'];
        $sets = [];
        $params = ['tenant_id' => $tenantId, 'persona_id' => $personaId, 'updated_by' => $userId];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $input)) {
                $sets[] = "$field = :$field";
                $params[$field] = is_string($input[$field]) ? trim($input[$field]) : $input[$field];
            }
        }

        if ($sets === []) {
            throw new RuntimeException('AGENT_TOOL_NO_PERSON_FIELDS');
        }

        $sql = 'UPDATE crm_personas SET ' . implode(', ', $sets) . ', updated_by = :updated_by, updated_at = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id AND id = :persona_id AND deleted_at IS NULL';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        if ($statement->rowCount() < 1) {
            throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        }

        return ['id' => $personaId, 'updated_fields' => array_values(array_intersect($allowed, array_keys($input)))];
    }
}
