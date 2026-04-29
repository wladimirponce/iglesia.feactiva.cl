<?php

declare(strict_types=1);

if (!interface_exists('CalendarProviderInterface')) {
    interface CalendarProviderInterface
    {
        public function canSync(): bool;

        /** @param array<string, mixed> $event */
        public function createEvent(array $event): array;

        /** @param array<string, mixed> $event */
        public function updateEvent(string $providerEventId, array $event): array;

        public function cancelEvent(string $providerEventId): array;
    }
}
