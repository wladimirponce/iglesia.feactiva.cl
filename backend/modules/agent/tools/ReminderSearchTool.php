<?php

declare(strict_types=1);

final class ReminderSearchTool implements AgentToolInterface
{
    public function name(): string { return 'reminder_search'; }
    public function moduleCode(): string { return 'agenda'; }
    public function requiredPermission(): string { return 'agenda.recordatorios.ver'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $inicio = trim((string) ($input['fecha_inicio'] ?? date('Y-m-d')));
        $fin = trim((string) ($input['fecha_fin'] ?? $inicio));
        $personaId = isset($input['persona_id']) ? (int) $input['persona_id'] : null;
        $sql = "
            SELECT id, persona_id, titulo, descripcion, fecha_hora, estado, modulo_origen, referencia_id
            FROM agenda_recordatorios
            WHERE tenant_id = :tenant_id
              AND user_id = :user_id
              AND deleted_at IS NULL
              AND fecha_hora BETWEEN :inicio AND :fin
              AND (:persona_id IS NULL OR persona_id = :persona_id_filter)
            ORDER BY fecha_hora ASC, id ASC
            LIMIT 20
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId, 'user_id' => $userId,
            'inicio' => $inicio . ' 00:00:00', 'fin' => $fin . ' 23:59:59',
            'persona_id' => $personaId !== null && $personaId > 0 ? $personaId : null,
            'persona_id_filter' => $personaId !== null && $personaId > 0 ? $personaId : null,
        ]);
        return ['fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'recordatorios' => $statement->fetchAll()];
    }
}
