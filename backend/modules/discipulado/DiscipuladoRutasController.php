<?php

declare(strict_types=1);

final class DiscipuladoRutasController
{
    private DiscipuladoValidator $validator;
    private DiscipuladoService $service;

    public function __construct()
    {
        $this->validator = new DiscipuladoValidator();
        $this->service = new DiscipuladoService(new DiscipuladoRepository());
    }

    public function index(): void
    {
        Response::success($this->service->rutas((int) AuthContext::tenantId()));
    }

    public function store(): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateRutaCreate($input);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }
        try {
            $id = $this->service->createRuta((int) AuthContext::tenantId(), (int) AuthContext::userId(), $input);
            Response::success(['id' => $id], 'Ruta de discipulado creada correctamente.', [], 201);
        } catch (Throwable) {
            Response::error('DISC_RUTA_CREATE_ERROR', 'No fue posible crear la ruta.', [], 500);
        }
    }

    public function update(string $id): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateUpdate($input);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }
        try {
            $this->service->updateRuta((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, $input);
            Response::success(['id' => (int) $id], 'Ruta de discipulado actualizada correctamente.');
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible actualizar la ruta.', [], $exception->getMessage() === 'DISC_RUTA_NOT_FOUND' ? 404 : 422);
        } catch (Throwable) {
            Response::error('DISC_RUTA_UPDATE_ERROR', 'No fue posible actualizar la ruta.', [], 500);
        }
    }

    public function storeEtapa(string $id): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateEtapaCreate($input);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }
        try {
            $etapaId = $this->service->createEtapa((int) AuthContext::tenantId(), (int) $id, $input);
            Response::success(['id' => $etapaId], 'Etapa de discipulado creada correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible crear la etapa.', [], 404);
        } catch (Throwable) {
            Response::error('DISC_ETAPA_CREATE_ERROR', 'No fue posible crear la etapa.', [], 500);
        }
    }

    public function updateEtapa(string $id): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateUpdate($input);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }
        try {
            $this->service->updateEtapa((int) AuthContext::tenantId(), (int) $id, $input);
            Response::success(['id' => (int) $id], 'Etapa de discipulado actualizada correctamente.');
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible actualizar la etapa.', [], 404);
        } catch (Throwable) {
            Response::error('DISC_ETAPA_UPDATE_ERROR', 'No fue posible actualizar la etapa.', [], 500);
        }
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
