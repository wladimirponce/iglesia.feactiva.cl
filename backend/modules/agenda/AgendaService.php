<?php

declare(strict_types=1);

final class AgendaService
{
    public function __construct(
        private readonly AgendaRepository $repository,
        private readonly AgendaAuditLogger $auditLogger
    ) {
    }

    public function list(int $tenantId, array $filters = []): array
    {
        return $this->repository->list($tenantId, $filters);
    }

    public function show(int $tenantId, int $id): array
    {
        $item = $this->repository->find($tenantId, $id);
        if ($item === null) {
            throw new RuntimeException('AGENDA_ITEM_NOT_FOUND');
        }
        return $item;
    }

    public function create(int $tenantId, int $userId, array $input): int
    {
        $this->assertReferences($tenantId, $input);
        $id = $this->repository->create($tenantId, $userId, $input);
        $this->auditLogger->log($tenantId, $userId, $id, 'agenda.item.created', [], $input + ['id' => $id]);
        return $id;
    }

    public function update(int $tenantId, int $userId, int $id, array $input): void
    {
        $old = $this->show($tenantId, $id);
        $this->assertReferences($tenantId, $input);
        $this->repository->update($tenantId, $id, $input);
        $this->auditLogger->log($tenantId, $userId, $id, 'agenda.item.updated', $old, $input);
    }

    public function complete(int $tenantId, int $userId, int $id): void
    {
        $old = $this->show($tenantId, $id);
        $this->repository->setStatus($tenantId, $id, 'completed');
        $this->auditLogger->log($tenantId, $userId, $id, 'agenda.item.completed', $old, ['estado' => 'completed']);
    }

    public function cancel(int $tenantId, int $userId, int $id): void
    {
        $old = $this->show($tenantId, $id);
        $this->repository->setStatus($tenantId, $id, 'cancelled');
        $this->auditLogger->log($tenantId, $userId, $id, 'agenda.item.cancelled', $old, ['estado' => 'cancelled']);
    }

    public function createNotification(int $tenantId, int $userId, int $agendaItemId, array $input): int
    {
        $this->show($tenantId, $agendaItemId);
        $id = $this->repository->createNotification($tenantId, $agendaItemId, $input);
        $this->auditLogger->log($tenantId, $userId, $agendaItemId, 'agenda.notification.scheduled', [], $input + ['id' => $id]);
        return $id;
    }

    private function assertReferences(int $tenantId, array $input): void
    {
        if (isset($input['persona_id']) && (int) $input['persona_id'] > 0 && !$this->repository->personaExists($tenantId, (int) $input['persona_id'])) {
            throw new RuntimeException('AGENDA_PERSONA_NOT_FOUND');
        }
        if (isset($input['familia_id']) && (int) $input['familia_id'] > 0 && !$this->repository->familiaExists($tenantId, (int) $input['familia_id'])) {
            throw new RuntimeException('AGENDA_FAMILIA_NOT_FOUND');
        }
    }
}
