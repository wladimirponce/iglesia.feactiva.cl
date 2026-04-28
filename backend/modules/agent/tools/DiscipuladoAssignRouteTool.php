<?php

declare(strict_types=1);

final class DiscipuladoAssignRouteTool implements AgentToolInterface
{
    public function name(): string { return 'discipulado_assign_route'; }
    public function moduleCode(): string { return 'discipulado'; }
    public function requiredPermission(): string { return 'disc.avance.editar'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $personaId = (int) ($input['persona_id'] ?? 0);
        $rutaId = (int) ($input['ruta_id'] ?? 0);
        $mentorId = isset($input['mentor_persona_id']) ? (int) $input['mentor_persona_id'] : null;
        if ($personaId < 1 || $rutaId < 1) {
            throw new RuntimeException('AGENT_TOOL_DISCIPLESHIP_DATA_REQUIRED');
        }

        $sql = "
            INSERT INTO disc_persona_rutas (
                tenant_id, persona_id, ruta_id, mentor_persona_id, estado, fecha_inicio, created_by
            ) VALUES (
                :tenant_id, :persona_id, :ruta_id, :mentor_persona_id, 'en_progreso', CURDATE(), :created_by
            )
            ON DUPLICATE KEY UPDATE estado = 'en_progreso', mentor_persona_id = VALUES(mentor_persona_id), updated_by = VALUES(created_by), updated_at = UTC_TIMESTAMP()
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId, 'persona_id' => $personaId, 'ruta_id' => $rutaId,
            'mentor_persona_id' => $mentorId !== null && $mentorId > 0 ? $mentorId : null, 'created_by' => $userId,
        ]);

        return ['persona_id' => $personaId, 'ruta_id' => $rutaId, 'mentor_persona_id' => $mentorId];
    }
}
