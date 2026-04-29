<?php

declare(strict_types=1);

if (!interface_exists('SpeechToTextInterface')) {
    interface SpeechToTextInterface
    {
        public function canTranscribe(): bool;

        /** @param array<string, mixed> $metadata */
        public function transcribe(string $mediaUrl, array $metadata = []): array;
    }
}
