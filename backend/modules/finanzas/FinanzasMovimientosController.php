<?php

declare(strict_types=1);

final class FinanzasMovimientosController
{
    private FinanzasMovimientosValidator $validator;
    private FinanzasMovimientosService $service;

    public function __construct()
    {
        $this->validator = new FinanzasMovimientosValidator();
        $this->service = new FinanzasMovimientosService(new FinanzasMovimientosRepository());
    }

    public function index(): void
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $result = $this->service->list((int) AuthContext::tenantId(), $page, $limit);

        Response::success($result['data'], null, $result['meta']);
    }

    public function show(string $id): void
    {
        $movimiento = $this->service->findById((int) AuthContext::tenantId(), (int) $id);

        if ($movimiento === null) {
            Response::error('FIN_MOVEMENT_NOT_FOUND', 'Movimiento financiero no encontrado.', [], 404);
            return;
        }

        Response::success($movimiento);
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
            Response::success(['id' => $id], 'Movimiento financiero creado correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible crear el movimiento financiero.', [], 422);
        } catch (Throwable) {
            Response::error('FIN_MOVEMENT_CREATE_ERROR', 'No fue posible crear el movimiento financiero.', [], 500);
        }
    }

    public function cancel(string $id): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateCancel($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $this->service->cancel((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, trim((string) $input['motivo_anulacion']));
            Response::success(['id' => (int) $id], 'Movimiento financiero anulado correctamente.');
        } catch (RuntimeException $exception) {
            $status = $exception->getMessage() === 'FIN_MOVEMENT_NOT_FOUND' ? 404 : 409;
            Response::error($exception->getMessage(), 'No fue posible anular el movimiento financiero.', [], $status);
        } catch (Throwable) {
            Response::error('FIN_MOVEMENT_CANCEL_ERROR', 'No fue posible anular el movimiento financiero.', [], 500);
        }
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
