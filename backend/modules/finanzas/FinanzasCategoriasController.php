<?php

declare(strict_types=1);

final class FinanzasCategoriasController
{
    private FinanzasCategoriasService $service;

    public function __construct()
    {
        $this->service = new FinanzasCategoriasService(new FinanzasCategoriasRepository());
    }

    public function index(): void
    {
        $tipo = isset($_GET['tipo']) && $_GET['tipo'] !== '' ? (string) $_GET['tipo'] : null;

        try {
            Response::success($this->service->list((int) AuthContext::tenantId(), $tipo));
        } catch (RuntimeException) {
            Response::error('FIN_INVALID_CATEGORY_TYPE', 'Tipo de categoria invalido.', [], 422);
        }
    }
}
