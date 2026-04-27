<?php

declare(strict_types=1);

final class FinanzasReportesService
{
    public function __construct(
        private readonly FinanzasReportesRepository $repository
    ) {
    }

    public function resumen(int $tenantId, ?string $fechaInicio, ?string $fechaFin): array
    {
        $fechaInicio = $fechaInicio ?: date('Y-m-01');
        $fechaFin = $fechaFin ?: date('Y-m-d');

        $rows = $this->repository->resumen($tenantId, $fechaInicio, $fechaFin);
        $ingresos = 0.0;
        $egresos = 0.0;

        foreach ($rows as $row) {
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

    public function saldoCuentas(int $tenantId): array
    {
        return $this->repository->saldoCuentas($tenantId);
    }
}
