<?php

declare(strict_types=1);

final class CrmContactosController
{
    private CrmContactosValidator $validator;
    private CrmContactosService $service;

    public function __construct()
    {
        $repository = new CrmContactosRepository();
        $this->validator = new CrmContactosValidator();
        $this->service = new CrmContactosService($repository);
    }

    public function index(string $id): void
    {
        $tenantId = AuthContext::tenantId();
        $personaId = (int) $id;

        if ($tenantId === null || $personaId < 1) {
            Response::error('CRM_PERSON_NOT_FOUND', 'Persona no encontrada.', [], 404);
            return;
        }

        try {
            Response::success($this->service->listByPersona($tenantId, $personaId));
        } catch (RuntimeException) {
            Response::error('CRM_PERSON_NOT_FOUND', 'Persona no encontrada.', [], 404);
        }
    }

    public function store(string $id): void
    {
        $tenantId = AuthContext::tenantId();
        $userId = AuthContext::userId();
        $personaId = (int) $id;

        if ($tenantId === null || $userId === null || $personaId < 1) {
            Response::error('CRM_PERSON_NOT_FOUND', 'Persona no encontrada.', [], 404);
            return;
        }

        $input = $this->jsonInput();
        $errors = $this->validator->validateCreate($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $contactoId = $this->service->create($tenantId, $personaId, $userId, $input);
        } catch (RuntimeException) {
            Response::error('CRM_PERSON_NOT_FOUND', 'Persona no encontrada.', [], 404);
            return;
        } catch (Throwable) {
            Response::error('CRM_CONTACT_CREATE_ERROR', 'No fue posible crear el contacto.', [], 500);
            return;
        }

        Response::success(['id' => $contactoId], 'Contacto creado correctamente.', [], 201);
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
