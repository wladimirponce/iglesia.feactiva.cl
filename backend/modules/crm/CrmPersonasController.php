<?php

declare(strict_types=1);

final class CrmPersonasController
{
    private CrmPersonasValidator $validator;
    private CrmPersonasService $service;

    public function __construct()
    {
        $repository = new CrmPersonasRepository();
        $this->validator = new CrmPersonasValidator();
        $this->service = new CrmPersonasService($repository);
    }

    public function index(): void
    {
        $tenantId = AuthContext::tenantId();

        if ($tenantId === null) {
            Response::error('TENANT_ACCESS_DENIED', 'No tiene acceso a esta iglesia.', [], 403);
            return;
        }

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $result = $this->service->list($tenantId, $page, $limit);

        Response::success($result['data'], null, $result['meta']);
    }

    public function show(string $id): void
    {
        $tenantId = AuthContext::tenantId();
        $personaId = (int) $id;

        if ($tenantId === null) {
            Response::error('TENANT_ACCESS_DENIED', 'No tiene acceso a esta iglesia.', [], 403);
            return;
        }

        if ($personaId < 1) {
            Response::error('CRM_PERSON_NOT_FOUND', 'Persona no encontrada.', [], 404);
            return;
        }

        $persona = $this->service->findById($tenantId, $personaId);

        if ($persona === null) {
            Response::error('CRM_PERSON_NOT_FOUND', 'Persona no encontrada.', [], 404);
            return;
        }

        Response::success($persona);
    }

    public function store(): void
    {
        $tenantId = AuthContext::tenantId();
        $userId = AuthContext::userId();

        if ($tenantId === null || $userId === null) {
            Response::error('UNAUTHENTICATED', 'Debe iniciar sesion.', [], 401);
            return;
        }

        $input = $this->jsonInput();
        $errors = $this->validator->validateCreate($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $personaId = $this->service->create($tenantId, $userId, $input);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'CRM_DUPLICATE_DOCUMENT') {
                Response::error('CRM_DUPLICATE_DOCUMENT', 'Ya existe una persona con ese documento.', [], 409);
                return;
            }

            Response::error('CRM_CREATE_ERROR', 'No fue posible crear la persona.', [], 500);
            return;
        } catch (Throwable) {
            Response::error('CRM_CREATE_ERROR', 'No fue posible crear la persona.', [], 500);
            return;
        }

        Response::success(['id' => $personaId], 'Persona creada correctamente.', [], 201);
    }

    public function update(string $id): void
    {
        $tenantId = AuthContext::tenantId();
        $userId = AuthContext::userId();
        $personaId = (int) $id;

        if ($tenantId === null || $userId === null) {
            Response::error('UNAUTHENTICATED', 'Debe iniciar sesion.', [], 401);
            return;
        }

        if ($personaId < 1) {
            Response::error('CRM_PERSON_NOT_FOUND', 'Persona no encontrada.', [], 404);
            return;
        }

        $input = $this->jsonInput();
        $errors = $this->validator->validateUpdate($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $this->service->update($tenantId, $userId, $personaId, $input);
        } catch (RuntimeException $exception) {
            match ($exception->getMessage()) {
                'CRM_PERSON_NOT_FOUND' => Response::error('CRM_PERSON_NOT_FOUND', 'Persona no encontrada.', [], 404),
                'CRM_DUPLICATE_DOCUMENT' => Response::error('CRM_DUPLICATE_DOCUMENT', 'Ya existe una persona con ese documento.', [], 409),
                'CRM_EMPTY_UPDATE' => Response::error('VALIDATION_ERROR', 'Debe enviar al menos un campo valido para actualizar.', [], 422),
                default => Response::error('CRM_UPDATE_ERROR', 'No fue posible actualizar la persona.', [], 500),
            };
            return;
        } catch (Throwable) {
            Response::error('CRM_UPDATE_ERROR', 'No fue posible actualizar la persona.', [], 500);
            return;
        }

        Response::success(['id' => $personaId], 'Persona actualizada correctamente.');
    }

    public function destroy(string $id): void
    {
        $tenantId = AuthContext::tenantId();
        $userId = AuthContext::userId();
        $personaId = (int) $id;

        if ($tenantId === null || $userId === null) {
            Response::error('UNAUTHENTICATED', 'Debe iniciar sesion.', [], 401);
            return;
        }

        if ($personaId < 1) {
            Response::error('CRM_PERSON_NOT_FOUND', 'Persona no encontrada.', [], 404);
            return;
        }

        try {
            $this->service->delete($tenantId, $userId, $personaId);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'CRM_PERSON_NOT_FOUND') {
                Response::error('CRM_PERSON_NOT_FOUND', 'Persona no encontrada.', [], 404);
                return;
            }

            Response::error('CRM_DELETE_ERROR', 'No fue posible eliminar la persona.', [], 500);
            return;
        } catch (Throwable) {
            Response::error('CRM_DELETE_ERROR', 'No fue posible eliminar la persona.', [], 500);
            return;
        }

        Response::success(['id' => $personaId], 'Persona eliminada correctamente.');
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');

        if ($rawBody === false || trim($rawBody) === '') {
            return [];
        }

        $decoded = json_decode($rawBody, true);

        return is_array($decoded) ? $decoded : [];
    }
}
