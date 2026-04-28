<?php

declare(strict_types=1);

final class AgendaController
{
    private AgendaValidator $validator;
    private AgendaService $service;

    public function __construct()
    {
        $this->validator = new AgendaValidator();
        $this->service = new AgendaService(new AgendaRepository(), new AgendaAuditLogger());
    }

    public function index(): void
    {
        Response::success($this->service->list((int) AuthContext::tenantId(), $_GET));
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
            Response::success(['id' => $id], 'Item de agenda creado.', [], 201);
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible crear item de agenda.', [], 422);
        }
    }

    public function show(string $id): void
    {
        try {
            Response::success($this->service->show((int) AuthContext::tenantId(), (int) $id));
        } catch (RuntimeException) {
            Response::error('AGENDA_ITEM_NOT_FOUND', 'Item de agenda no encontrado.', [], 404);
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
            Response::success(['id' => (int) $id], 'Item de agenda actualizado.');
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible actualizar item de agenda.', [], 422);
        }
    }

    public function cancel(string $id): void
    {
        try {
            $this->service->cancel((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id);
            Response::success(['id' => (int) $id], 'Item de agenda cancelado.');
        } catch (RuntimeException) {
            Response::error('AGENDA_ITEM_NOT_FOUND', 'Item de agenda no encontrado.', [], 404);
        }
    }

    public function complete(string $id): void
    {
        try {
            $this->service->complete((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id);
            Response::success(['id' => (int) $id], 'Item de agenda completado.');
        } catch (RuntimeException) {
            Response::error('AGENDA_ITEM_NOT_FOUND', 'Item de agenda no encontrado.', [], 404);
        }
    }

    public function day(): void
    {
        $fecha = (string) ($_GET['fecha'] ?? date('Y-m-d'));
        if (!$this->validator->validDate($fecha)) {
            Response::error('VALIDATION_ERROR', 'Fecha invalida.', [['field' => 'fecha']], 422);
            return;
        }
        Response::success($this->service->list((int) AuthContext::tenantId(), ['fecha' => $fecha]));
    }

    public function byPersona(string $personaId): void
    {
        Response::success($this->service->list((int) AuthContext::tenantId(), ['persona_id' => (int) $personaId]));
    }

    public function byFamilia(string $familiaId): void
    {
        Response::success($this->service->list((int) AuthContext::tenantId(), ['familia_id' => (int) $familiaId]));
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
