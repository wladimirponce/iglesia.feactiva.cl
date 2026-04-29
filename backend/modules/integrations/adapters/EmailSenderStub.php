<?php

declare(strict_types=1);

final class EmailSenderStub implements EmailSenderInterface
{
    public function canSend(): bool
    {
        return true;
    }

    public function send(string $toEmail, string $subject, string $body, array $metadata = []): array
    {
        return [
            'success' => true,
            'simulated' => true,
            'external_provider' => 'email_stub',
            'external_message_id' => 'stub-email-' . bin2hex(random_bytes(6)),
            'payload' => [
                'to' => $toEmail,
                'subject' => $subject,
                'body_length' => strlen($body),
                'metadata' => $metadata,
            ],
        ];
    }
}
