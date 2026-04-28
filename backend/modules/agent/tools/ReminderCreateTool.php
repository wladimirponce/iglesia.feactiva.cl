<?php

declare(strict_types=1);

final class ReminderCreateTool implements AgentToolInterface
{
    public function name(): string { return 'reminder_create'; }

    public function moduleCode(): string { return 'agenda'; }

    public function requiredPermission(): string { return 'agenda.recordatorios.crear'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $titulo = trim((string) ($input['titulo'] ?? 'Recordatorio'));
        $descripcion = $this->nullable($input['descripcion'] ?? null);
        $fechaHora = trim((string) ($input['fecha_hora'] ?? ''));
        $personaId = isset($input['persona_id']) ? (int) $input['persona_id'] : null;
        $moduloOrigen = $this->nullable($input['modulo_origen'] ?? 'agent');
        $referenciaId = isset($input['referencia_id']) ? (int) $input['referencia_id'] : null;

        if ($titulo === '' || $fechaHora === '') {
            throw new RuntimeException('AGENT_TOOL_MISSING_REMINDER_DATA');
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $fechaHora);
        if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d H:i:s') !== $fechaHora) {
            throw new RuntimeException('AGENT_TOOL_INVALID_DATETIME');
        }

        if ($personaId !== null && $personaId > 0) {
            $statement = Database::connection()->prepare("SELECT id FROM crm_personas WHERE tenant_id = :tenant_id AND id = :id AND deleted_at IS NULL LIMIT 1");
            $statement->execute(['tenant_id' => $tenantId, 'id' => $personaId]);
            if ($statement->fetchColumn() === false) {
                throw new RuntimeException('CRM_PERSON_NOT_FOUND');
            }
        }

        $sql = "
            INSERT INTO agenda_recordatorios (
                tenant_id,
                user_id,
                persona_id,
                titulo,
                descripcion,
                fecha_hora,
                estado,
                modulo_origen,
                referencia_id
            ) VALUES (
                :tenant_id,
                :user_id,
                :persona_id,
                :titulo,
                :descripcion,
                :fecha_hora,
                'pendiente',
                :modulo_origen,
                :referencia_id
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'persona_id' => $personaId !== null && $personaId > 0 ? $personaId : null,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'fecha_hora' => $fechaHora,
            'modulo_origen' => $moduloOrigen,
            'referencia_id' => $referenciaId !== null && $referenciaId > 0 ? $referenciaId : null,
        ]);

        return [
            'id' => (int) Database::connection()->lastInsertId(),
            'titulo' => $titulo,
            'fecha_hora' => $fechaHora,
            'estado' => 'pendiente',
            'persona_id' => $personaId,
        ];
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
