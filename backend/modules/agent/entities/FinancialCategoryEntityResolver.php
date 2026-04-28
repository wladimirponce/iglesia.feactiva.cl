<?php

declare(strict_types=1);

final class FinancialCategoryEntityResolver
{
    public function resolve(int $tenantId, string $tipo, string $query): EntityResolutionResult
    {
        $query = trim($query);
        if ($query === '') {
            return EntityResolutionResult::notFound('financial_category', $query);
        }

        $exact = $this->search($tenantId, $tipo, $query, true);
        if (count($exact) === 1) {
            return $this->resolved($query, $exact[0]);
        }
        if (count($exact) > 1) {
            return EntityResolutionResult::ambiguous('financial_category', $query, $exact);
        }

        $partial = $this->search($tenantId, $tipo, $query, false);
        if (count($partial) === 1) {
            return $this->resolved($query, $partial[0]);
        }
        if (count($partial) > 1) {
            return EntityResolutionResult::ambiguous('financial_category', $query, $partial);
        }

        return EntityResolutionResult::notFound('financial_category', $query);
    }

    /** @return array<int, array<string, mixed>> */
    private function search(int $tenantId, string $tipo, string $query, bool $exact): array
    {
        $operator = $exact ? '=' : 'LIKE';
        $value = $exact ? $query : '%' . $query . '%';
        $sql = "
            SELECT
                id,
                tipo,
                codigo,
                nombre
            FROM fin_categorias
            WHERE tenant_id = :tenant_id
              AND tipo = :tipo
              AND deleted_at IS NULL
              AND es_activa = 1
              AND (nombre {$operator} :query OR codigo {$operator} :query)
            ORDER BY orden ASC, id ASC
            LIMIT 6
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'tipo' => $tipo,
            'query' => $value,
        ]);
        return $statement->fetchAll();
    }

    private function resolved(string $query, array $row): EntityResolutionResult
    {
        return EntityResolutionResult::resolved('financial_category', $query, (int) $row['id'], (string) $row['nombre']);
    }
}
