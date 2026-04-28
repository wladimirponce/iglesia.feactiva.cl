<?php

declare(strict_types=1);

final class OntologyResolutionResult
{
    /** @param array<string, mixed> $extractedFields @param array<int, string> $missingFields */
    public function __construct(
        public readonly bool $resolved,
        public readonly ?string $objectType,
        public readonly ?string $action,
        public readonly ?string $toolName,
        public readonly ?string $requiredPermission,
        public readonly array $extractedFields,
        public readonly array $missingFields,
        public readonly bool $requiresConfirmation,
        public readonly string $sensitiveLevel
    ) {
    }

    public static function unresolved(): self
    {
        return new self(false, null, null, null, null, [], [], false, 'low');
    }

    public static function unhandled(string $action): self
    {
        return new self(true, null, $action, null, null, [], [], false, 'low');
    }

    public function hasMissingFields(): bool
    {
        return $this->missingFields !== [];
    }

    public function toArray(): array
    {
        return [
            'resolved' => $this->resolved,
            'object_type' => $this->objectType,
            'action' => $this->action,
            'tool_name' => $this->toolName,
            'required_permission' => $this->requiredPermission,
            'extracted_fields' => $this->extractedFields,
            'missing_fields' => $this->missingFields,
            'requires_confirmation' => $this->requiresConfirmation,
            'sensitive_level' => $this->sensitiveLevel,
        ];
    }
}
