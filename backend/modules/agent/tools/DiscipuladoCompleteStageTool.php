<?php

declare(strict_types=1);

final class DiscipuladoCompleteStageTool implements AgentToolInterface
{
    public function name(): string { return 'discipulado_complete_stage'; }
    public function moduleCode(): string { return 'discipulado'; }
    public function requiredPermission(): string { return 'disc.avance.editar'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $personaEtapaId = (int) ($input['persona_etapa_id'] ?? 0);
        if ($personaEtapaId < 1) {
            throw new RuntimeException('AGENT_TOOL_STAGE_REQUIRED');
        }
        $sql = "
            UPDATE disc_persona_etapas
            SET estado = 'completada', fecha_fin = CURDATE(), observacion = :observacion, updated_by = :updated_by, updated_at = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id AND id = :id
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId, 'id' => $personaEtapaId,
            'observacion' => is_string($input['observacion'] ?? null) ? trim($input['observacion']) : null,
            'updated_by' => $userId,
        ]);
        if ($statement->rowCount() < 1) {
            throw new RuntimeException('AGENT_TOOL_STAGE_NOT_FOUND');
        }
        return ['persona_etapa_id' => $personaEtapaId, 'estado' => 'completada'];
    }
}
