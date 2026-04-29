<?php

declare(strict_types=1);

final class TextToSpeechStub implements TextToSpeechInterface
{
    public function canSynthesize(): bool
    {
        return true;
    }

    public function synthesize(string $text, array $metadata = []): array
    {
        $hash = substr(hash('sha256', $text . json_encode($metadata)), 0, 16);
        return [
            'success' => true,
            'simulated' => true,
            'audio_url' => '/internal/generated/audio/stub-' . $hash . '.mp3',
            'payload' => [
                'text_length' => strlen($text),
                'metadata' => $metadata,
            ],
        ];
    }
}
