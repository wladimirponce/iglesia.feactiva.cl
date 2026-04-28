<?php

declare(strict_types=1);

interface EmailSenderInterface
{
    public function canSend(): bool;

    /** @param array<string, mixed> $metadata */
    public function send(string $toEmail, string $subject, string $body, array $metadata = []): array;
}
