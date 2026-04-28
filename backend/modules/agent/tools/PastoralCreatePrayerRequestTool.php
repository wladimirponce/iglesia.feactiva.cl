<?php

declare(strict_types=1);

final class PastoralCreatePrayerRequestTool implements AgentToolInterface
{
    public function name(): string
    {
        return 'pastoral_create_prayer_request';
    }

    public function moduleCode(): string
    {
        return 'pastoral';
    }

    public function requiredPermission(): string
    {
        return 'past.oracion.crear';
    }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $personaId = isset($input['persona_id']) ? (int) $input['persona_id'] : 0;
        $titulo = trim((string) ($input['titulo'] ?? 'Peticion de oracion'));
        $detalle = trim((string) ($input['detalle'] ?? ''));
        $privacidad = trim((string) ($input['privacidad'] ?? 'privada'));

        if ($detalle === '') {
            throw new RuntimeException('AGENT_TOOL_PRAYER_DATA_REQUIRED');
        }

        if (!in_array($privacidad, ['privada', 'equipo_pastoral', 'publica'], true)) {
            throw new RuntimeException('AGENT_TOOL_INVALID_PRIVACY');
        }

        if ($personaId > 0 && !$this->personExists($tenantId, $personaId)) {
            $personaId = 0;
        }

        $sql = "
            INSERT INTO past_solicitudes_oracion (
                tenant_id,
                persona_id,
                nombre_solicitante,
                contacto_solicitante,
                titulo,
                detalle,
                categoria,
                privacidad,
                estado,
                created_by
            ) VALUES (
                :tenant_id,
                :persona_id,
                :nombre_solicitante,
                :contacto_solicitante,
                :titulo,
                :detalle,
                :categoria,
                :privacidad,
                'recibida',
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'persona_id' => $personaId > 0 ? $personaId : null,
            'nombre_solicitante' => $this->nullable($input['nombre_solicitante'] ?? null),
            'contacto_solicitante' => $this->nullable($input['contacto_solicitante'] ?? null),
            'titulo' => $titulo,
            'detalle' => $detalle,
            'categoria' => $this->nullable($input['categoria'] ?? null),
            'privacidad' => $privacidad,
            'created_by' => $userId,
        ]);

        return [
            'id' => (int) Database::connection()->lastInsertId(),
            'persona_id' => $personaId > 0 ? $personaId : null,
            'titulo' => $titulo,
            'privacidad' => $privacidad,
            'estado' => 'recibida',
        ];
    }

    private function personExists(int $tenantId, int $personaId): bool
    {
        $personaSql = "
            SELECT id
            FROM crm_personas
            WHERE tenant_id = :tenant_id
              AND id = :persona_id
              AND deleted_at IS NULL
            LIMIT 1
        ";
        $personaStatement = Database::connection()->prepare($personaSql);
        $personaStatement->execute([
            'tenant_id' => $tenantId,
            'persona_id' => $personaId,
        ]);

        return $personaStatement->fetchColumn() !== false;
    }

    private function nullable(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }
}
