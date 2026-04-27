<?php

declare(strict_types=1);

final class ContabilidadAsientosController
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
        Response::success($this->service->asientos((int) AuthContext::tenantId()));
    }

    public function show(string $id): void
    {
        $asiento = $this->service->asiento((int) AuthContext::tenantId(), (int) $id);

        if ($asiento === null) {
            Response::error('ACCT_JOURNAL_NOT_FOUND', 'Asiento contable no encontrado.', [], 404);
            return;
        }

        Response::success($asiento);
    }

    public function store(): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateAsientoCreate($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $id = $this->service->createAsiento((int) AuthContext::tenantId(), (int) AuthContext::userId(), $input);
            Response::success(['id' => $id], 'Asiento contable creado correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible crear el asiento contable.', [], $this->statusFor($exception->getMessage()));
        } catch (Throwable) {
            Response::error('ACCT_JOURNAL_CREATE_ERROR', 'No fue posible crear el asiento contable.', [], 500);
        }
    }

    public function approve(string $id): void
    {
        try {
            $this->service->approveAsiento((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id);
            Response::success(['id' => (int) $id], 'Asiento contable aprobado correctamente.');
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible aprobar el asiento contable.', [], $this->statusFor($exception->getMessage()));
        } catch (Throwable) {
            Response::error('ACCT_JOURNAL_APPROVE_ERROR', 'No fue posible aprobar el asiento contable.', [], 500);
        }
    }

    public function cancel(string $id): void
    {
        $input = $this->jsonInput();

        try {
            $this->service->cancelAsiento(
                (int) AuthContext::tenantId(),
                (int) AuthContext::userId(),
                (int) $id,
                isset($input['motivo_anulacion']) ? trim((string) $input['motivo_anulacion']) : null
            );
            Response::success(['id' => (int) $id], 'Asiento contable anulado correctamente.');
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible anular el asiento contable.', [], $this->statusFor($exception->getMessage()));
        } catch (Throwable) {
            Response::error('ACCT_JOURNAL_CANCEL_ERROR', 'No fue posible anular el asiento contable.', [], 500);
        }
    }

    public function reverse(string $id): void
    {
        try {
            $reversalId = $this->service->reverseAsiento((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id);
            Response::success(['id' => $reversalId], 'Asiento contable reversado correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible reversar el asiento contable.', [], $this->statusFor($exception->getMessage()));
        } catch (Throwable) {
            Response::error('ACCT_JOURNAL_REVERSE_ERROR', 'No fue posible reversar el asiento contable.', [], 500);
        }
    }

    private function statusFor(string $code): int
    {
        return match ($code) {
            'ACCT_JOURNAL_NOT_FOUND', 'ACCT_ACCOUNT_NOT_FOUND' => 404,
            'ACCT_JOURNAL_DUPLICATE' => 409,
            'ACCT_PERIOD_CLOSED', 'ACCT_JOURNAL_NOT_DRAFT', 'ACCT_JOURNAL_NOT_APPROVED', 'ACCT_JOURNAL_ALREADY_CANCELLED' => 409,
            default => 422,
        };
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
