<?php

declare(strict_types=1);

final class WhatsAppSenderStub implements WhatsAppSenderInterface
{
    public function canSend(): bool
    {
        return true;
    }

    public function sendText(string $toPhoneE164, string $messageText, array $metadata = []): array
    {
        return [
            'success' => true,
            'simulated' => true,
            'external_provider' => 'whatsapp_stub',
            'external_message_id' => 'stub-wa-' . bin2hex(random_bytes(6)),
            'payload' => [
                'to' => $toPhoneE164,
                'message_length' => strlen($messageText),
                'metadata' => $metadata,
            ],
        ];
    }

    public function sendAudio(string $toPhoneE164, string $audioUrl, array $metadata = []): array
    {
        return [
            'success' => true,
            'simulated' => true,
            'external_provider' => 'whatsapp_stub',
            'external_message_id' => 'stub-wa-audio-' . bin2hex(random_bytes(6)),
            'payload' => [
                'to' => $toPhoneE164,
                'audio_url' => $audioUrl,
                'metadata' => $metadata,
            ],
        ];
    }
}
