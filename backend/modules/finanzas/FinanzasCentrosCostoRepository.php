<?php

declare(strict_types=1);

final class FinanzasCentrosCostoRepository
{
    public function list(int $tenantId): array
    {
        $sql = "
            SELECT
                id,
                codigo,
                nombre,
                descripcion,
                responsable_persona_id,
                es_activo,
                created_at,
                updated_at
            FROM fin_centros_costo
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
            ORDER BY codigo, nombre
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);

        return $statement->fetchAll();
    }
}
