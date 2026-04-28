<?php

declare(strict_types=1);

final class FinancialAccountEntityResolver
{
    public function resolve(int $tenantId, string $query): EntityResolutionResult
    {
        $query = trim($query);
        if ($query === '') {
            return EntityResolutionResult::notFound('financial_account', $query);
        }

        $exact = $this->search($tenantId, $query, true);
        if (count($exact) === 1) {
            return $this->resolved($query, $exact[0]);
        }
        if (count($exact) > 1) {
            return EntityResolutionResult::ambiguous('financial_account', $query, $exact);
        }

        $partial = $this->search($tenantId, $query, false);
        if (count($partial) === 1) {
            return $this->resolved($query, $partial[0]);
        }
        if (count($partial) > 1) {
            return EntityResolutionResult::ambiguous('financial_account', $query, $partial);
        }

        return EntityResolutionResult::notFound('financial_account', $query);
    }

    /** @return array<int, array<string, mixed>> */
    private function search(int $tenantId, string $query, bool $exact): array
    {
        $operator = $exact ? '=' : 'LIKE';
        $value = $exact ? $query : '%' . $query . '%';
        $sql = "
            SELECT
                id,
                nombre,
                tipo,
                banco,
                es_principal
            FROM fin_cuentas
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
              AND es_activa = 1
              AND (nombre {$operator} :query OR tipo {$operator} :query OR banco {$operator} :query)
            ORDER BY es_principal DESC, id ASC
            LIMIT 6
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'query' => $value]);
        return $statement->fetchAll();
    }

    private function resolved(string $query, array $row): EntityResolutionResult
    {
        return EntityResolutionResult::resolved('financial_account', $query, (int) $row['id'], (string) $row['nombre']);
    }
}
