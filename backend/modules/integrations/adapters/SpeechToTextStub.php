<?php

declare(strict_types=1);

final class SpeechToTextStub implements SpeechToTextInterface
{
    public function canTranscribe(): bool
    {
        return true;
    }

    public function transcribe(string $mediaUrl, array $metadata = []): array
    {
        return [
            'success' => true,
            'simulated' => true,
            'transcription_text' => trim((string) ($metadata['fallback_text'] ?? 'Mensaje de audio recibido')),
            'media_url' => $mediaUrl,
        ];
    }
}
