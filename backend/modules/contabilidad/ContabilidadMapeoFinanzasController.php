<?php

declare(strict_types=1);

final class ContabilidadMapeoFinanzasController
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
        Response::success($this->service->mapeosFinanzas((int) AuthContext::tenantId()));
    }

    public function store(): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateMapeoCreate($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $id = $this->service->createMapeoFinanzas((int) AuthContext::tenantId(), (int) AuthContext::userId(), $input);
            Response::success(['id' => $id], 'Mapeo finanzas-contabilidad creado correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible crear el mapeo finanzas-contabilidad.', [], $this->statusFor($exception->getMessage()));
        } catch (Throwable) {
            Response::error('ACCT_MAPEO_CREATE_ERROR', 'No fue posible crear el mapeo finanzas-contabilidad.', [], 500);
        }
    }

    public function update(string $id): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateMapeoUpdate($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $this->service->updateMapeoFinanzas((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, $input);
            Response::success(['id' => (int) $id], 'Mapeo finanzas-contabilidad actualizado correctamente.');
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible actualizar el mapeo finanzas-contabilidad.', [], $this->statusFor($exception->getMessage()));
        } catch (Throwable) {
            Response::error('ACCT_MAPEO_UPDATE_ERROR', 'No fue posible actualizar el mapeo finanzas-contabilidad.', [], 500);
        }
    }

    public function generateFromFinance(string $movimientoId): void
    {
        try {
            $asientoId = $this->service->generarDesdeFinanzas((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $movimientoId);
            Response::success(['id' => $asientoId], 'Asiento contable generado desde Finanzas correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible generar el asiento desde Finanzas.', [], $this->statusFor($exception->getMessage()));
        } catch (Throwable) {
            Response::error('ACCT_GENERATE_FROM_FINANCE_ERROR', 'No fue posible generar el asiento desde Finanzas.', [], 500);
        }
    }

    private function statusFor(string $code): int
    {
        return match ($code) {
            'ACCT_MAPEO_NOT_FOUND', 'FIN_MOVEMENT_NOT_FOUND', 'FIN_CATEGORY_NOT_FOUND', 'ACCT_DEBIT_ACCOUNT_NOT_FOUND', 'ACCT_CREDIT_ACCOUNT_NOT_FOUND' => 404,
            'ACCT_MAPEO_DUPLICATE', 'FIN_MOVEMENT_CANCELLED', 'ACCT_JOURNAL_ALREADY_EXISTS_FOR_FINANCE', 'ACCT_PERIOD_CLOSED' => 409,
            default => 422,
        };
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
