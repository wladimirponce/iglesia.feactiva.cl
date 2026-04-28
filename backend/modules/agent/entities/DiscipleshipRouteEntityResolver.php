<?php

declare(strict_types=1);

final class DiscipleshipRouteEntityResolver
{
    public function resolve(int $tenantId, string $query): EntityResolutionResult
    {
        $query = trim($query);
        if ($query === '') {
            return EntityResolutionResult::notFound('discipleship_route', $query);
        }

        $exact = $this->search($tenantId, $query, true);
        if (count($exact) === 1) {
            return $this->resolved($query, $exact[0]);
        }
        if (count($exact) > 1) {
            return EntityResolutionResult::ambiguous('discipleship_route', $query, $exact);
        }

        $partial = $this->search($tenantId, $query, false);
        if (count($partial) === 1) {
            return $this->resolved($query, $partial[0]);
        }
        if (count($partial) > 1) {
            return EntityResolutionResult::ambiguous('discipleship_route', $query, $partial);
        }

        return EntityResolutionResult::notFound('discipleship_route', $query);
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
                publico_objetivo
            FROM disc_rutas
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
              AND es_activa = 1
              AND nombre {$operator} :query
            ORDER BY id ASC
            LIMIT 6
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'query' => $value]);
        return $statement->fetchAll();
    }

    private function resolved(string $query, array $row): EntityResolutionResult
    {
        return EntityResolutionResult::resolved('discipleship_route', $query, (int) $row['id'], (string) $row['nombre']);
    }
}
