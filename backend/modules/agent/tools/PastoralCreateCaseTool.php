<?php

declare(strict_types=1);

final class PastoralCreateCaseTool implements AgentToolInterface
{
    public function name(): string { return 'pastoral_create_case'; }
    public function moduleCode(): string { return 'pastoral'; }
    public function requiredPermission(): string { return 'past.casos.crear'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $personaId = (int) ($input['persona_id'] ?? 0);
        $titulo = trim((string) ($input['titulo'] ?? 'Caso pastoral'));
        $tipo = trim((string) ($input['tipo'] ?? 'acompanamiento'));
        $prioridad = trim((string) ($input['prioridad'] ?? 'media'));
        if ($personaId < 1 || $titulo === '') {
            throw new RuntimeException('AGENT_TOOL_PASTORAL_CASE_DATA_REQUIRED');
        }
        if (!in_array($tipo, ['consejeria','oracion','visita','crisis','acompanamiento','disciplinario','otro'], true)) {
            $tipo = 'acompanamiento';
        }
        if (!in_array($prioridad, ['baja','media','alta','critica'], true)) {
            $prioridad = 'media';
        }

        $sql = "
            INSERT INTO past_casos (
                tenant_id, persona_id, tipo, titulo, descripcion_general, prioridad, estado, fecha_apertura, es_confidencial, created_by
            ) VALUES (
                :tenant_id, :persona_id, :tipo, :titulo, :descripcion_general, :prioridad, 'abierto', CURDATE(), :es_confidencial, :created_by
            )
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId, 'persona_id' => $personaId, 'tipo' => $tipo, 'titulo' => $titulo,
            'descripcion_general' => is_string($input['descripcion_general'] ?? null) ? trim($input['descripcion_general']) : null,
            'prioridad' => $prioridad, 'es_confidencial' => !empty($input['es_confidencial']) ? 1 : 0, 'created_by' => $userId,
        ]);
        return ['id' => (int) Database::connection()->lastInsertId(), 'persona_id' => $personaId, 'titulo' => $titulo, 'tipo' => $tipo];
    }
}
