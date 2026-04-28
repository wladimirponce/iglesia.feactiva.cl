<?php

declare(strict_types=1);

final class EntityResolutionResult
{
    /** @param array<int, array<string, mixed>> $options */
    public function __construct(
        public readonly string $entityType,
        public readonly string $query,
        public readonly bool $resolved,
        public readonly bool $ambiguous,
        public readonly ?int $id,
        public readonly ?string $displayName,
        public readonly array $options = []
    ) {
    }

    public static function resolved(string $entityType, string $query, int $id, string $displayName): self
    {
        return new self($entityType, $query, true, false, $id, $displayName, []);
    }

    /** @param array<int, array<string, mixed>> $options */
    public static function ambiguous(string $entityType, string $query, array $options): self
    {
        return new self($entityType, $query, false, true, null, null, $options);
    }

    public static function notFound(string $entityType, string $query): self
    {
        return new self($entityType, $query, false, false, null, null, []);
    }

    public function status(): string
    {
        if ($this->resolved) {
            return 'resolved';
        }

        return $this->ambiguous ? 'ambiguous' : 'not_found';
    }

    public function toArray(): array
    {
        return [
            'entity_type' => $this->entityType,
            'query' => $this->query,
            'resolved' => $this->resolved,
            'ambiguous' => $this->ambiguous,
            'id' => $this->id,
            'display_name' => $this->displayName,
            'options' => $this->options,
        ];
    }
}
