<?php

declare(strict_types=1);

final class ContabilidadReportesController
{
    private ContabilidadService $service;

    public function __construct()
    {
        $this->service = new ContabilidadService(new ContabilidadRepository());
    }

    public function libroDiario(): void
    {
        $this->respondReport(static fn (ContabilidadService $service): array => $service->libroDiario(
            (int) AuthContext::tenantId(),
            (int) AuthContext::userId(),
            $_GET
        ));
    }

    public function libroMayor(): void
    {
        $this->respondReport(static fn (ContabilidadService $service): array => $service->libroMayor(
            (int) AuthContext::tenantId(),
            (int) AuthContext::userId(),
            $_GET
        ));
    }

    public function balanceComprobacion(): void
    {
        $this->respondReport(static fn (ContabilidadService $service): array => $service->balanceComprobacion(
            (int) AuthContext::tenantId(),
            (int) AuthContext::userId(),
            $_GET
        ));
    }

    public function estadoResultados(): void
    {
        $this->respondReport(static fn (ContabilidadService $service): array => $service->estadoResultados(
            (int) AuthContext::tenantId(),
            (int) AuthContext::userId(),
            $_GET
        ));
    }

    private function respondReport(callable $callback): void
    {
        try {
            $result = $callback($this->service);
            Response::success($result['data'], null, $result['meta']);
        } catch (RuntimeException $exception) {
            $status = $exception->getMessage() === 'ACCT_ACCOUNT_NOT_FOUND' ? 404 : 422;
            Response::error($exception->getMessage(), 'No fue posible generar el reporte contable.', [], $status);
        } catch (Throwable) {
            Response::error('ACCT_REPORT_ERROR', 'No fue posible generar el reporte contable.', [], 500);
        }
    }
}
