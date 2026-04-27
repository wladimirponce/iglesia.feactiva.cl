<?php

declare(strict_types=1);

final class CrmEtiquetasController
{
    private CrmEtiquetasValidator $validator;
    private CrmEtiquetasService $service;

    public function __construct()
    {
        $repository = new CrmEtiquetasRepository();
        $this->validator = new CrmEtiquetasValidator();
        $this->service = new CrmEtiquetasService($repository);
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
            Response::success(['id' => $id], 'Etiqueta creada correctamente.', [], 201);
        } catch (Throwable) {
            Response::error('CRM_TAG_CREATE_ERROR', 'No fue posible crear la etiqueta.', [], 500);
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
            Response::success(['id' => (int) $id], 'Etiqueta actualizada correctamente.');
        } catch (RuntimeException) {
            Response::error('CRM_TAG_NOT_FOUND', 'Etiqueta no encontrada.', [], 404);
        } catch (Throwable) {
            Response::error('CRM_TAG_UPDATE_ERROR', 'No fue posible actualizar la etiqueta.', [], 500);
        }
    }

    public function destroy(string $id): void
    {
        try {
            $this->service->delete((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id);
            Response::success(['id' => (int) $id], 'Etiqueta eliminada correctamente.');
        } catch (RuntimeException) {
            Response::error('CRM_TAG_NOT_FOUND', 'Etiqueta no encontrada.', [], 404);
        } catch (Throwable) {
            Response::error('CRM_TAG_DELETE_ERROR', 'No fue posible eliminar la etiqueta.', [], 500);
        }
    }

    public function assignToPersona(string $id): void
    {
        $input = $this->jsonInput();
        $etiquetaId = isset($input['etiqueta_id']) ? (int) $input['etiqueta_id'] : 0;

        if ($etiquetaId < 1) {
            Response::error('VALIDATION_ERROR', 'Etiqueta es requerida.', [], 422);
            return;
        }

        try {
            $this->service->assign((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, $etiquetaId);
            Response::success(['persona_id' => (int) $id, 'etiqueta_id' => $etiquetaId], 'Etiqueta asignada correctamente.');
        } catch (RuntimeException $exception) {
            $code = $exception->getMessage();
            Response::error($code, $code === 'CRM_PERSON_NOT_FOUND' ? 'Persona no encontrada.' : 'Etiqueta no encontrada.', [], 404);
        }
    }

    public function removeFromPersona(string $id, string $etiquetaId): void
    {
        try {
            $this->service->remove((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, (int) $etiquetaId);
            Response::success(['persona_id' => (int) $id, 'etiqueta_id' => (int) $etiquetaId], 'Etiqueta removida correctamente.');
        } catch (RuntimeException $exception) {
            $code = $exception->getMessage();
            Response::error($code, $code === 'CRM_PERSON_NOT_FOUND' ? 'Persona no encontrada.' : 'Etiqueta no encontrada.', [], 404);
        }
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
