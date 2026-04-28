<?php

declare(strict_types=1);

final class FinanzasGetSummaryTool implements AgentToolInterface
{
    public function name(): string
    {
        return 'finanzas_get_summary';
    }

    public function moduleCode(): string
    {
        return 'finanzas';
    }

    public function requiredPermission(): string
    {
        return 'fin.reportes.ver';
    }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $fechaInicio = $this->dateOrDefault($input['fecha_inicio'] ?? null, date('Y-m-01'));
        $fechaFin = $this->dateOrDefault($input['fecha_fin'] ?? null, date('Y-m-d'));

        if ($fechaFin < $fechaInicio) {
            throw new RuntimeException('AGENT_TOOL_INVALID_DATE_RANGE');
        }

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

        $ingresos = 0.0;
        $egresos = 0.0;

        foreach ($statement->fetchAll() as $row) {
            if ($row['tipo'] === 'ingreso') {
                $ingresos = (float) $row['total'];
            }

            if ($row['tipo'] === 'egreso') {
                $egresos = (float) $row['total'];
            }
        }

        return [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'saldo_neto' => $ingresos - $egresos,
        ];
    }

    private function dateOrDefault(mixed $value, string $default): string
    {
        if (!is_string($value) || trim($value) === '') {
            return $default;
        }

        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $value) {
            throw new RuntimeException('AGENT_TOOL_INVALID_DATE');
        }

        return $value;
    }
}
