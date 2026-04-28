<?php

declare(strict_types=1);

final class CrmSearchPersonTool implements AgentToolInterface
{
    public function name(): string
    {
        return 'crm_search_person';
    }

    public function moduleCode(): string
    {
        return 'crm';
    }

    public function requiredPermission(): string
    {
        return 'crm.personas.ver';
    }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $query = trim((string) ($input['query'] ?? ''));

        if ($query === '' || mb_strlen($query) < 2) {
            throw new RuntimeException('AGENT_TOOL_QUERY_REQUIRED');
        }

        $like = '%' . $query . '%';
        $sql = "
            SELECT
                id,
                nombres,
                apellidos,
                email,
                telefono,
                whatsapp,
                estado_persona
            FROM crm_personas
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
              AND (
                    nombres LIKE :query_nombres
                 OR apellidos LIKE :query_apellidos
                 OR email LIKE :query_email
                 OR telefono LIKE :query_telefono
                 OR whatsapp LIKE :query_whatsapp
              )
            ORDER BY apellidos ASC, nombres ASC
            LIMIT 10
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'query_nombres' => $like,
            'query_apellidos' => $like,
            'query_email' => $like,
            'query_telefono' => $like,
            'query_whatsapp' => $like,
        ]);

        return [
            'query' => $query,
            'results' => array_map(static fn (array $row): array => [
                'id' => (int) $row['id'],
                'nombres' => (string) $row['nombres'],
                'apellidos' => (string) $row['apellidos'],
                'email' => $row['email'],
                'telefono' => $row['telefono'],
                'whatsapp' => $row['whatsapp'],
                'estado_persona' => (string) $row['estado_persona'],
            ], $statement->fetchAll()),
        ];
    }
}
