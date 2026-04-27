<?php

declare(strict_types=1);

final class ContabilidadCuentasController
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
        Response::success($this->service->cuentas((int) AuthContext::tenantId()));
    }

    public function store(): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateCuentaCreate($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $id = $this->service->createCuenta((int) AuthContext::tenantId(), (int) AuthContext::userId(), $input);
            Response::success(['id' => $id], 'Cuenta contable creada correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            $status = $exception->getMessage() === 'ACCT_ACCOUNT_DUPLICATE' ? 409 : 422;
            Response::error($exception->getMessage(), 'No fue posible crear la cuenta contable.', [], $status);
        } catch (Throwable) {
            Response::error('ACCT_ACCOUNT_CREATE_ERROR', 'No fue posible crear la cuenta contable.', [], 500);
        }
    }

    public function update(string $id): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateCuentaUpdate($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $this->service->updateCuenta((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, $input);
            Response::success(['id' => (int) $id], 'Cuenta contable actualizada correctamente.');
        } catch (RuntimeException $exception) {
            $status = match ($exception->getMessage()) {
                'ACCT_ACCOUNT_NOT_FOUND' => 404,
                'ACCT_ACCOUNT_DUPLICATE' => 409,
                default => 422,
            };
            Response::error($exception->getMessage(), 'No fue posible actualizar la cuenta contable.', [], $status);
        } catch (Throwable) {
            Response::error('ACCT_ACCOUNT_UPDATE_ERROR', 'No fue posible actualizar la cuenta contable.', [], 500);
        }
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
