<?php

declare(strict_types=1);

final class FinanzasReportesController
{
    private FinanzasReportesService $service;

    public function __construct()
    {
        $this->service = new FinanzasReportesService(new FinanzasReportesRepository());
    }

    public function resumen(): void
    {
        Response::success($this->service->resumen(
            (int) AuthContext::tenantId(),
            isset($_GET['fecha_inicio']) ? (string) $_GET['fecha_inicio'] : null,
            isset($_GET['fecha_fin']) ? (string) $_GET['fecha_fin'] : null
        ));
    }

    public function saldoCuentas(): void
    {
        Response::success($this->service->saldoCuentas((int) AuthContext::tenantId()));
    }
}
