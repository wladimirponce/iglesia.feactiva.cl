<?php

declare(strict_types=1);

final class FinanzasReportesRepository
{
    public function resumen(int $tenantId, string $fechaInicio, string $fechaFin): array
    {
        $sql = "
            SELECT
                tipo,
                COALESCE(SUM(monto), 0) AS total
            FROM fin_movimientos
            WHERE tenant_id = :tenant_id
              AND estado <> 'anulado'
              AND fecha_movimiento BETWEEN :fecha_inicio AND :fecha_fin
            GROUP BY tipo
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);

        return $statement->fetchAll();
    }

    public function saldoCuentas(int $tenantId): array
    {
        $sql = "
            SELECT
                c.id,
                c.nombre,
                c.tipo,
                c.moneda,
                c.saldo_inicial,
                c.saldo_inicial
                + COALESCE(SUM(
                    CASE
                        WHEN m.tipo = 'ingreso' AND m.estado <> 'anulado' THEN m.monto
                        WHEN m.tipo = 'egreso' AND m.estado <> 'anulado' THEN -m.monto
                        ELSE 0
                    END
                ), 0) AS saldo_actual
            FROM fin_cuentas c
            LEFT JOIN fin_movimientos m
                ON m.cuenta_id = c.id
                AND m.tenant_id = c.tenant_id
            WHERE c.tenant_id = :tenant_id
              AND c.deleted_at IS NULL
            GROUP BY c.id, c.nombre, c.tipo, c.moneda, c.saldo_inicial
            ORDER BY c.nombre
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);

        return $statement->fetchAll();
    }
}
