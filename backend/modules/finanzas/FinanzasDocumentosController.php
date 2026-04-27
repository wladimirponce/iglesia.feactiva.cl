<?php

declare(strict_types=1);

final class FinanzasDocumentosController
{
    private FinanzasDocumentosValidator $validator;
    private FinanzasDocumentosService $service;

    public function __construct()
    {
        $this->validator = new FinanzasDocumentosValidator();
        $this->service = new FinanzasDocumentosService(new FinanzasDocumentosRepository());
    }

    public function index(string $id): void
    {
        try {
            Response::success($this->service->listByMovimiento((int) AuthContext::tenantId(), (int) $id));
        } catch (RuntimeException) {
            Response::error('FIN_MOVEMENT_NOT_FOUND', 'Movimiento financiero no encontrado.', [], 404);
        }
    }

    public function store(string $id): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateCreate($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $documentoId = $this->service->create((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, $input);
            Response::success(['id' => $documentoId], 'Documento financiero creado correctamente.', [], 201);
        } catch (RuntimeException) {
            Response::error('FIN_MOVEMENT_NOT_FOUND', 'Movimiento financiero no encontrado.', [], 404);
        } catch (Throwable) {
            Response::error('FIN_DOCUMENT_CREATE_ERROR', 'No fue posible crear el documento financiero.', [], 500);
        }
    }

    public function destroy(string $id): void
    {
        try {
            $this->service->delete((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id);
            Response::success(['id' => (int) $id], 'Documento financiero eliminado correctamente.');
        } catch (RuntimeException) {
            Response::error('FIN_DOCUMENT_NOT_FOUND', 'Documento financiero no encontrado.', [], 404);
        } catch (Throwable) {
            Response::error('FIN_DOCUMENT_DELETE_ERROR', 'No fue posible eliminar el documento financiero.', [], 500);
        }
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
