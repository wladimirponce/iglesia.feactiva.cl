<?php

declare(strict_types=1);

final class FamilyEntityResolver
{
    public function resolve(int $tenantId, string $query): EntityResolutionResult
    {
        $query = trim(preg_replace('/^familia\s+/iu', '', trim($query)) ?? $query);
        if ($query === '') {
            return EntityResolutionResult::notFound('family', $query);
        }

        $exact = $this->search($tenantId, $query, true);
        if (count($exact) === 1) {
            return $this->resolved($query, $exact[0]);
        }
        if (count($exact) > 1) {
            return EntityResolutionResult::ambiguous('family', $query, $exact);
        }

        $partial = $this->search($tenantId, $query, false);
        if (count($partial) === 1) {
            return $this->resolved($query, $partial[0]);
        }
        if (count($partial) > 1) {
            return EntityResolutionResult::ambiguous('family', $query, $partial);
        }

        return EntityResolutionResult::notFound('family', $query);
    }

    /** @return array<int, array<string, mixed>> */
    private function search(int $tenantId, string $query, bool $exact): array
    {
        $operator = $exact ? '=' : 'LIKE';
        $value = $exact ? $query : '%' . $query . '%';
        $sql = "
            SELECT
                id,
                nombre_familia,
                telefono_principal,
                email_principal
            FROM crm_familias
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
              AND nombre_familia {$operator} :query
            ORDER BY id ASC
            LIMIT 6
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'query' => $value]);
        return $statement->fetchAll();
    }

    private function resolved(string $query, array $row): EntityResolutionResult
    {
        return EntityResolutionResult::resolved('family', $query, (int) $row['id'], (string) $row['nombre_familia']);
    }
}
