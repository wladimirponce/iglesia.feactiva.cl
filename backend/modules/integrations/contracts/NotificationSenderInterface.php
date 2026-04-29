<?php

declare(strict_types=1);

if (!interface_exists('NotificationSenderInterface')) {
    interface NotificationSenderInterface
    {
        public function canSend(): bool;

        /** @param array<string, mixed> $payload */
        public function send(array $payload): array;
    }
}
