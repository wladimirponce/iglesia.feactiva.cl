<?php

declare(strict_types=1);

final class FinanzasCentrosCostoController
{
    private FinanzasCentrosCostoService $service;

    public function __construct()
    {
        $this->service = new FinanzasCentrosCostoService(new FinanzasCentrosCostoRepository());
    }

    public function index(): void
    {
        Response::success($this->service->list((int) AuthContext::tenantId()));
    }
}
