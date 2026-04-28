<?php

declare(strict_types=1);

final class FinanzasGetBalanceByDateTool implements AgentToolInterface
{
    public function name(): string { return 'finanzas_get_balance_by_date'; }

    public function moduleCode(): string { return 'finanzas'; }

    public function requiredPermission(): string { return 'fin.reportes.ver'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $fecha = trim((string) ($input['fecha'] ?? date('Y-m-d')));
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $fecha) {
            throw new RuntimeException('AGENT_TOOL_INVALID_DATE');
        }

        $sql = "
            SELECT
                c.id,
                c.nombre,
                c.tipo,
                c.moneda,
                c.saldo_inicial,
                c.saldo_inicial + COALESCE(SUM(
                    CASE
                        WHEN m.tipo = 'ingreso' AND m.estado <> 'anulado' AND m.fecha_movimiento <= :fecha_movimiento_sum THEN m.monto
                        WHEN m.tipo = 'egreso' AND m.estado <> 'anulado' AND m.fecha_movimiento <= :fecha_movimiento_sum_egreso THEN -m.monto
                        ELSE 0
                    END
                ), 0) AS saldo
            FROM fin_cuentas c
            LEFT JOIN fin_movimientos m
                ON m.cuenta_id = c.id
                AND m.tenant_id = c.tenant_id
            WHERE c.tenant_id = :tenant_id
              AND c.deleted_at IS NULL
              AND c.es_activa = 1
            GROUP BY c.id, c.nombre, c.tipo, c.moneda, c.saldo_inicial
            ORDER BY c.nombre ASC
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'fecha_movimiento_sum' => $fecha,
            'fecha_movimiento_sum_egreso' => $fecha,
        ]);

        $cuentas = array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'nombre' => (string) $row['nombre'],
            'tipo' => (string) $row['tipo'],
            'moneda' => (string) $row['moneda'],
            'saldo' => (float) $row['saldo'],
        ], $statement->fetchAll());

        return [
            'fecha' => $fecha,
            'cuentas' => $cuentas,
            'saldo_total' => array_sum(array_map(static fn (array $cuenta): float => (float) $cuenta['saldo'], $cuentas)),
        ];
    }
}
