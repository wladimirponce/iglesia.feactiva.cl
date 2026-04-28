<?php

declare(strict_types=1);

final class CrmCreateFamilyTool implements AgentToolInterface
{
    public function name(): string { return 'crm_create_family'; }
    public function moduleCode(): string { return 'crm'; }
    public function requiredPermission(): string { return 'crm.familias.crear'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $nombre = trim((string) ($input['nombre_familia'] ?? ''));
        if ($nombre === '') {
            throw new RuntimeException('AGENT_TOOL_FAMILY_NAME_REQUIRED');
        }

        $sql = "
            INSERT INTO crm_familias (
                tenant_id, nombre_familia, direccion, telefono_principal, email_principal, created_by
            ) VALUES (
                :tenant_id, :nombre_familia, :direccion, :telefono_principal, :email_principal, :created_by
            )
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'nombre_familia' => $nombre,
            'direccion' => $this->nullable($input['direccion'] ?? null),
            'telefono_principal' => $this->nullable($input['telefono_principal'] ?? null),
            'email_principal' => $this->nullable($input['email_principal'] ?? null),
            'created_by' => $userId,
        ]);

        return ['id' => (int) Database::connection()->lastInsertId(), 'nombre_familia' => $nombre];
    }

    private function nullable(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
