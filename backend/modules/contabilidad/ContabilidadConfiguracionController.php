<?php

declare(strict_types=1);

final class ContabilidadConfiguracionController
{
    private ContabilidadValidator $validator;
    private ContabilidadService $service;

    public function __construct()
    {
        $this->validator = new ContabilidadValidator();
        $this->service = new ContabilidadService(new ContabilidadRepository());
    }

    public function show(): void
    {
        $config = $this->service->configuracion((int) AuthContext::tenantId());

        if ($config === null) {
            Response::error('ACCT_CONFIG_NOT_FOUND', 'Configuracion contable no encontrada.', [], 404);
            return;
        }

        Response::success($config);
    }

    public function update(): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateConfiguracion($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $this->service->updateConfiguracion((int) AuthContext::tenantId(), (int) AuthContext::userId(), $input);
            Response::success($this->service->configuracion((int) AuthContext::tenantId()), 'Configuracion contable actualizada correctamente.');
        } catch (RuntimeException $exception) {
            $status = $exception->getMessage() === 'ACCT_CONFIG_NOT_FOUND' ? 404 : 422;
            Response::error($exception->getMessage(), 'No fue posible actualizar la configuracion contable.', [], $status);
        } catch (Throwable) {
            Response::error('ACCT_CONFIG_UPDATE_ERROR', 'No fue posible actualizar la configuracion contable.', [], 500);
        }
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
