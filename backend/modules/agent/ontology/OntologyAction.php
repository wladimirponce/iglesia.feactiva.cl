<?php

declare(strict_types=1);

final class OntologyAction
{
    /** @param array<int, string> $requiredFields @param array<int, string> $optionalFields */
    public function __construct(
        public readonly string $name,
        public readonly string $objectType,
        public readonly string $toolName,
        public readonly string $requiredPermission,
        public readonly array $requiredFields,
        public readonly array $optionalFields,
        public readonly string $sensitiveLevel,
        public readonly bool $requiresConfirmation
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'object_type' => $this->objectType,
            'tool_name' => $this->toolName,
            'required_permission' => $this->requiredPermission,
            'required_fields' => $this->requiredFields,
            'optional_fields' => $this->optionalFields,
            'sensitive_level' => $this->sensitiveLevel,
            'requires_confirmation' => $this->requiresConfirmation,
        ];
    }
}
