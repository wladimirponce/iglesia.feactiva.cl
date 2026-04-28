<?php

declare(strict_types=1);

interface TextToSpeechInterface
{
    public function canSynthesize(): bool;

    /** @param array<string, mixed> $metadata */
    public function synthesize(string $text, array $metadata = []): array;
}
