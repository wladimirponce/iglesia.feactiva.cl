<?php

declare(strict_types=1);

final class CrmFamiliasController
{
    private CrmFamiliasValidator $validator;
    private CrmFamiliasService $service;

    public function __construct()
    {
        $repository = new CrmFamiliasRepository();
        $this->validator = new CrmFamiliasValidator();
        $this->service = new CrmFamiliasService($repository);
    }

    public function index(): void
    {
        Response::success($this->service->list((int) AuthContext::tenantId()));
    }

    public function store(): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateCreate($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $id = $this->service->create((int) AuthContext::tenantId(), (int) AuthContext::userId(), $input);
            Response::success(['id' => $id], 'Familia creada correctamente.', [], 201);
        } catch (Throwable) {
            Response::error('CRM_FAMILY_CREATE_ERROR', 'No fue posible crear la familia.', [], 500);
        }
    }

    public function show(string $id): void
    {
        try {
            Response::success($this->service->show((int) AuthContext::tenantId(), (int) $id));
        } catch (RuntimeException) {
            Response::error('CRM_FAMILY_NOT_FOUND', 'Familia no encontrada.', [], 404);
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
            $this->service->update((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, $input);
            Response::success(['id' => (int) $id], 'Familia actualizada correctamente.');
        } catch (RuntimeException) {
            Response::error('CRM_FAMILY_NOT_FOUND', 'Familia no encontrada.', [], 404);
        } catch (Throwable) {
            Response::error('CRM_FAMILY_UPDATE_ERROR', 'No fue posible actualizar la familia.', [], 500);
        }
    }

    public function addPersona(string $id): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateAddPersona($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $this->service->addPersona((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, $input);
            Response::success(['familia_id' => (int) $id, 'persona_id' => (int) $input['persona_id']], 'Persona agregada a la familia.');
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
            match ($message) {
                'CRM_FAMILY_PERSON_DUPLICATE' => Response::error('CRM_FAMILY_PERSON_DUPLICATE', 'La persona ya pertenece a esta familia.', [], 409),
                'CRM_PERSON_NOT_FOUND' => Response::error('CRM_PERSON_NOT_FOUND', 'Persona no encontrada.', [], 404),
                default => Response::error('CRM_FAMILY_NOT_FOUND', 'Familia no encontrada.', [], 404),
            };
        }
    }

    public function removePersona(string $id, string $personaId): void
    {
        try {
            $this->service->removePersona((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, (int) $personaId);
            Response::success(['familia_id' => (int) $id, 'persona_id' => (int) $personaId], 'Persona removida de la familia.');
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
            Response::error($message, $message === 'CRM_PERSON_NOT_FOUND' ? 'Persona no encontrada.' : 'Familia no encontrada.', [], 404);
        }
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
