<?php

declare(strict_types=1);

final class FinanzasCategoriasRepository
{
    public function list(int $tenantId, ?string $tipo): array
    {
        $whereTipo = $tipo === null ? '' : ' AND tipo = :tipo';
        $sql = "
            SELECT
                id,
                tipo,
                codigo,
                nombre,
                descripcion,
                es_sistema,
                es_activa,
                orden,
                created_at,
                updated_at
            FROM fin_categorias
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
              {$whereTipo}
            ORDER BY tipo, orden, nombre
        ";

        $statement = Database::connection()->prepare($sql);
        $params = ['tenant_id' => $tenantId];

        if ($tipo !== null) {
            $params['tipo'] = $tipo;
        }

        $statement->execute($params);

        return $statement->fetchAll();
    }
}
