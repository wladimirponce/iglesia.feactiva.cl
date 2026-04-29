<?php

declare(strict_types=1);

if (!interface_exists('WhatsAppSenderInterface')) {
    interface WhatsAppSenderInterface
    {
        public function canSend(): bool;

        /** @param array<string, mixed> $metadata */
        public function sendText(string $toPhoneE164, string $messageText, array $metadata = []): array;

        /** @param array<string, mixed> $metadata */
        public function sendAudio(string $toPhoneE164, string $audioUrl, array $metadata = []): array;
    }
}
