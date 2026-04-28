<?php

declare(strict_types=1);

final class PersonEntityResolver
{
    public function resolve(int $tenantId, string $query): EntityResolutionResult
    {
        $query = trim($query);
        if ($query === '') {
            return EntityResolutionResult::notFound('person', $query);
        }

        $exact = $this->search($tenantId, $query, true);
        if (count($exact) === 1) {
            return $this->resolved($query, $exact[0]);
        }
        if (count($exact) > 1) {
            return EntityResolutionResult::ambiguous('person', $query, $exact);
        }

        $partial = $this->search($tenantId, $query, false);
        if (count($partial) === 1) {
            return $this->resolved($query, $partial[0]);
        }
        if (count($partial) > 1) {
            return EntityResolutionResult::ambiguous('person', $query, $partial);
        }

        return EntityResolutionResult::notFound('person', $query);
    }

    /** @return array<int, array<string, mixed>> */
    private function search(int $tenantId, string $query, bool $exact): array
    {
        $operator = $exact ? '=' : 'LIKE';
        $value = $exact ? $query : '%' . $query . '%';
        $sql = "
            SELECT
                id,
                nombres,
                apellidos,
                email,
                telefono,
                whatsapp
            FROM crm_personas
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
              AND (
                CONCAT(nombres, ' ', apellidos) {$operator} :query
                OR nombre_preferido {$operator} :query
                OR email {$operator} :query
                OR telefono {$operator} :query
                OR whatsapp {$operator} :query
              )
            ORDER BY id ASC
            LIMIT 6
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'query' => $value]);
        return $statement->fetchAll();
    }

    private function resolved(string $query, array $row): EntityResolutionResult
    {
        return EntityResolutionResult::resolved(
            'person',
            $query,
            (int) $row['id'],
            trim((string) $row['nombres'] . ' ' . (string) $row['apellidos'])
        );
    }
}
