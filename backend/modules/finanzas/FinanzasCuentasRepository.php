<?php

declare(strict_types=1);

final class FinanzasCuentasRepository
{
    public function list(int $tenantId): array
    {
        $sql = "
            SELECT
                id,
                nombre,
                tipo,
                banco,
                numero_cuenta,
                moneda,
                saldo_inicial,
                fecha_saldo_inicial,
                es_principal,
                es_activa,
                created_at,
                updated_at
            FROM fin_cuentas
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
            ORDER BY es_principal DESC, nombre
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);

        return $statement->fetchAll();
    }
}
