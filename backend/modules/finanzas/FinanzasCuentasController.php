<?php

declare(strict_types=1);

final class FinanzasCuentasController
{
    private FinanzasCuentasService $service;

    public function __construct()
    {
        $this->service = new FinanzasCuentasService(new FinanzasCuentasRepository());
    }

    public function index(): void
    {
        Response::success($this->service->list((int) AuthContext::tenantId()));
    }
}
