<?php

declare(strict_types=1);

final class GoogleCalendarService
{
    public function __construct(
        private readonly GoogleCalendarRepository $repository,
        private readonly CalendarProviderInterface $provider,
        private readonly AgendaAuditLogger $auditLogger
    ) {
    }

    public function connectAccountPlaceholder(int $tenantId, int $userId, string $email): int
    {
        $id = $this->repository->createAccountPlaceholder($tenantId, $userId, $email);
        $this->auditLogger->log($tenantId, $userId, null, 'calendar.account.connected', [], [
            'calendar_account_id' => $id,
            'provider' => 'google',
            'placeholder' => true,
        ]);
        return $id;
    }

    public function createEventFromAgendaItem(int $tenantId, int $userId, array $agendaItem): ?int
    {
        $account = $this->repository->findActiveAccountForUser($tenantId, $userId);
        if ($account === null) {
            return null;
        }

        $eventId = $this->repository->createEvent($tenantId, (int) $agendaItem['id'], (int) $account['id']);
        $payload = $this->eventPayload($agendaItem, $account);
        $response = $this->provider->createEvent($payload);

        if (($response['success'] ?? false) === true) {
            $externalEventId = (string) ($response['external_event_id'] ?? ('stub-gcal-' . $eventId));
            $this->repository->markEventSynced($tenantId, $eventId, $externalEventId);
            $this->auditLogger->log($tenantId, $userId, (int) $agendaItem['id'], 'calendar.event.created', [], [
                'calendar_event_id' => $eventId,
                'external_event_id' => $externalEventId,
                'simulated' => $response['simulated'] ?? false,
            ]);
            return $eventId;
        }

        $error = (string) ($response['error'] ?? 'CALENDAR_PROVIDER_FAILED');
        $this->repository->markEventFailed($tenantId, $eventId, $error);
        $this->auditLogger->log($tenantId, $userId, (int) $agendaItem['id'], 'calendar.event.failed', [], [
            'calendar_event_id' => $eventId,
            'error' => $error,
        ]);
        return $eventId;
    }

    public function updateEventFromAgendaItem(int $tenantId, int $userId, array $agendaItem): void
    {
        $this->auditLogger->log($tenantId, $userId, (int) $agendaItem['id'], 'calendar.event.update_requested', [], [
            'provider' => 'google',
            'placeholder' => true,
        ]);
    }

    public function cancelEventFromAgendaItem(int $tenantId, int $userId, int $agendaItemId): void
    {
        $this->repository->markEventCancelled($tenantId, $agendaItemId);
        $this->auditLogger->log($tenantId, $userId, $agendaItemId, 'calendar.event.cancelled', [], [
            'provider' => 'google',
        ]);
    }

    private function eventPayload(array $agendaItem, array $account): array
    {
        return [
            'calendar_id' => $account['calendar_id'] ?? 'primary',
            'summary' => $agendaItem['titulo'] ?? 'Agenda FeActiva',
            'description' => $agendaItem['descripcion'] ?? '',
            'start' => $agendaItem['fecha_inicio'] ?? null,
            'end' => $agendaItem['fecha_fin'] ?? null,
            'agenda_item_id' => $agendaItem['id'] ?? null,
        ];
    }
}
