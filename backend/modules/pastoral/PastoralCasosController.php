<?php

declare(strict_types=1);

final class PastoralCasosController
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
            Response::success($this->service->casos((int) AuthContext::tenantId(), (int) AuthContext::userId()));
        } catch (Throwable) {
            Response::error('PAST_CASES_LIST_ERROR', 'No fue posible obtener casos pastorales.', [], 500);
        }
    }

    public function show(string $id): void
    {
        try {
            Response::success($this->service->caso((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id));
        } catch (RuntimeException $exception) {
            $this->runtimeError($exception, 'No fue posible obtener el caso pastoral.');
        } catch (Throwable) {
            Response::error('PAST_CASE_SHOW_ERROR', 'No fue posible obtener el caso pastoral.', [], 500);
        }
    }

    public function store(): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateCasoCreate($input);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }
        try {
            $id = $this->service->createCaso((int) AuthContext::tenantId(), (int) AuthContext::userId(), $input);
            Response::success(['id' => $id], 'Caso pastoral creado correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            $this->runtimeError($exception, 'No fue posible crear el caso pastoral.');
        } catch (Throwable) {
            Response::error('PAST_CASE_CREATE_ERROR', 'No fue posible crear el caso pastoral.', [], 500);
        }
    }

    public function update(string $id): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateCasoUpdate($input);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }
        try {
            $this->service->updateCaso((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, $input);
            Response::success(['id' => (int) $id], 'Caso pastoral actualizado correctamente.');
        } catch (RuntimeException $exception) {
            $this->runtimeError($exception, 'No fue posible actualizar el caso pastoral.');
        } catch (Throwable) {
            Response::error('PAST_CASE_UPDATE_ERROR', 'No fue posible actualizar el caso pastoral.', [], 500);
        }
    }

    public function close(string $id): void
    {
        try {
            $this->service->closeCaso((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id);
            Response::success(['id' => (int) $id], 'Caso pastoral cerrado correctamente.');
        } catch (RuntimeException $exception) {
            $this->runtimeError($exception, 'No fue posible cerrar el caso pastoral.');
        } catch (Throwable) {
            Response::error('PAST_CASE_CLOSE_ERROR', 'No fue posible cerrar el caso pastoral.', [], 500);
        }
    }

    public function sesiones(string $id): void
    {
        try {
            Response::success($this->service->sesiones((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id));
        } catch (RuntimeException $exception) {
            $this->runtimeError($exception, 'No fue posible obtener sesiones pastorales.');
        } catch (Throwable) {
            Response::error('PAST_SESSIONS_LIST_ERROR', 'No fue posible obtener sesiones pastorales.', [], 500);
        }
    }

    public function storeSesion(string $id): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateSesionCreate($input);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }
        try {
            $sessionId = $this->service->createSesion((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, $input);
            Response::success(['id' => $sessionId], 'Sesion pastoral creada correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            $this->runtimeError($exception, 'No fue posible crear la sesion pastoral.');
        } catch (Throwable) {
            Response::error('PAST_SESSION_CREATE_ERROR', 'No fue posible crear la sesion pastoral.', [], 500);
        }
    }

    public function derive(string $id): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateDerivacionCreate($input);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }
        try {
            $derivacionId = $this->service->createDerivacion((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, $input);
            Response::success(['id' => $derivacionId], 'Derivacion pastoral creada correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            $this->runtimeError($exception, 'No fue posible crear la derivacion pastoral.');
        } catch (Throwable) {
            Response::error('PAST_DERIVATION_CREATE_ERROR', 'No fue posible crear la derivacion pastoral.', [], 500);
        }
    }

    private function runtimeError(RuntimeException $exception, string $message): void
    {
        $code = $exception->getMessage();
        $status = match ($code) {
            'PAST_CONFIDENTIAL_ACCESS_DENIED' => 403,
            'PAST_CASE_NOT_FOUND', 'CRM_PERSON_NOT_FOUND', 'PAST_RESPONSABLE_NOT_FOUND', 'PAST_DERIVADO_USER_NOT_FOUND' => 404,
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
