<?php

declare(strict_types=1);

final class CrmCreatePersonTool implements AgentToolInterface
{
    public function name(): string { return 'crm_create_person'; }

    public function moduleCode(): string { return 'crm'; }

    public function requiredPermission(): string { return 'crm.personas.crear'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $nombres = trim((string) ($input['nombres'] ?? ''));
        $apellidos = trim((string) ($input['apellidos'] ?? ''));
        $phone = $this->nullable($input['phone'] ?? null);
        $email = $this->nullable($input['email'] ?? null);
        $estado = trim((string) ($input['estado_persona'] ?? 'visita'));

        if ($nombres === '' || $apellidos === '') {
            throw new RuntimeException('AGENT_TOOL_MISSING_PERSON_NAME');
        }

        if (!in_array($estado, ['visita', 'nuevo_asistente', 'miembro', 'lider', 'servidor', 'inactivo', 'trasladado', 'fallecido'], true)) {
            throw new RuntimeException('AGENT_TOOL_INVALID_PERSON_STATUS');
        }

        $sql = "
            INSERT INTO crm_personas (
                tenant_id,
                nombres,
                apellidos,
                email,
                telefono,
                whatsapp,
                estado_persona,
                created_by
            ) VALUES (
                :tenant_id,
                :nombres,
                :apellidos,
                :email,
                :telefono,
                :whatsapp,
                :estado_persona,
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'email' => $email,
            'telefono' => $phone,
            'whatsapp' => $phone,
            'estado_persona' => $estado,
            'created_by' => $userId,
        ]);

        return [
            'id' => (int) Database::connection()->lastInsertId(),
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'email' => $email,
            'phone' => $phone,
            'estado_persona' => $estado,
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
