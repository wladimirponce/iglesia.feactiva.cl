<?php

declare(strict_types=1);

final class PastoralOracionController
{
    private PastoralValidator $validator;
    private PastoralService $service;

    public function __construct()
    {
        $this->validator = new PastoralValidator();
        $this->service = new PastoralService(new PastoralRepository(), new PermissionRepository());
    }

    public function index(): void
    {
        try {
            Response::success($this->service->oraciones((int) AuthContext::tenantId()));
        } catch (Throwable) {
            Response::error('PAST_PRAYER_LIST_ERROR', 'No fue posible obtener solicitudes de oracion.', [], 500);
        }
    }

    public function store(): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateOracionCreate($input);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }
        try {
            $id = $this->service->createOracion((int) AuthContext::tenantId(), (int) AuthContext::userId(), $input);
            Response::success(['id' => $id], 'Solicitud de oracion creada correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            $this->runtimeError($exception, 'No fue posible crear la solicitud de oracion.');
        } catch (Throwable) {
            Response::error('PAST_PRAYER_CREATE_ERROR', 'No fue posible crear la solicitud de oracion.', [], 500);
        }
    }

    public function update(string $id): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateOracionUpdate($input);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }
        try {
            $this->service->updateOracion((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, $input);
            Response::success(['id' => (int) $id], 'Solicitud de oracion actualizada correctamente.');
        } catch (RuntimeException $exception) {
            $this->runtimeError($exception, 'No fue posible actualizar la solicitud de oracion.');
        } catch (Throwable) {
            Response::error('PAST_PRAYER_UPDATE_ERROR', 'No fue posible actualizar la solicitud de oracion.', [], 500);
        }
    }

    public function close(string $id): void
    {
        try {
            $this->service->closeOracion((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id);
            Response::success(['id' => (int) $id], 'Solicitud de oracion cerrada correctamente.');
        } catch (RuntimeException $exception) {
            $this->runtimeError($exception, 'No fue posible cerrar la solicitud de oracion.');
        } catch (Throwable) {
            Response::error('PAST_PRAYER_CLOSE_ERROR', 'No fue posible cerrar la solicitud de oracion.', [], 500);
        }
    }

    private function runtimeError(RuntimeException $exception, string $message): void
    {
        $code = $exception->getMessage();
        $status = match ($code) {
            'PAST_PRAYER_REQUEST_NOT_FOUND', 'CRM_PERSON_NOT_FOUND' => 404,
            default => 400,
        };
        Response::error($code, $message, [], $status);
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
