<?php

declare(strict_types=1);

final class ContabilidadPeriodosController
{
    private ContabilidadValidator $validator;
    private ContabilidadService $service;

    public function __construct()
    {
        $this->validator = new ContabilidadValidator();
        $this->service = new ContabilidadService(new ContabilidadRepository());
    }

    public function index(): void
    {
        Response::success($this->service->periodos((int) AuthContext::tenantId()));
    }

    public function store(): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validatePeriodoCreate($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $id = $this->service->createPeriodo((int) AuthContext::tenantId(), (int) AuthContext::userId(), $input);
            Response::success(['id' => $id], 'Periodo contable creado correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            $status = $exception->getMessage() === 'ACCT_PERIOD_DUPLICATE' ? 409 : 422;
            Response::error($exception->getMessage(), 'No fue posible crear el periodo contable.', [], $status);
        } catch (Throwable) {
            Response::error('ACCT_PERIOD_CREATE_ERROR', 'No fue posible crear el periodo contable.', [], 500);
        }
    }

    public function close(string $id): void
    {
        try {
            $this->service->closePeriodo((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id);
            Response::success(['id' => (int) $id], 'Periodo contable cerrado correctamente.');
        } catch (RuntimeException $exception) {
            $status = $exception->getMessage() === 'ACCT_PERIOD_NOT_FOUND' ? 404 : 409;
            Response::error($exception->getMessage(), 'No fue posible cerrar el periodo contable.', [], $status);
        } catch (Throwable) {
            Response::error('ACCT_PERIOD_CLOSE_ERROR', 'No fue posible cerrar el periodo contable.', [], 500);
        }
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
