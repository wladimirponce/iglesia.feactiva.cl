<?php

declare(strict_types=1);

final class GoogleCalendarProviderStub implements CalendarProviderInterface
{
    public function canSync(): bool
    {
        return true;
    }

    public function createEvent(array $event): array
    {
        return [
            'success' => true,
            'simulated' => true,
            'external_provider' => 'google_calendar_stub',
            'external_event_id' => 'stub-gcal-' . bin2hex(random_bytes(6)),
            'payload' => $event,
        ];
    }

    public function updateEvent(string $providerEventId, array $event): array
    {
        return [
            'success' => true,
            'simulated' => true,
            'external_provider' => 'google_calendar_stub',
            'external_event_id' => $providerEventId,
            'payload' => $event,
        ];
    }

    public function cancelEvent(string $providerEventId): array
    {
        return [
            'success' => true,
            'simulated' => true,
            'external_provider' => 'google_calendar_stub',
            'external_event_id' => $providerEventId,
        ];
    }
}
