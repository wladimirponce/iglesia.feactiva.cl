<?php

declare(strict_types=1);

final class OntologyAction
{
    public function __construct(
        public readonly string $name,
        public readonly string $objectName,
        public readonly string $toolName,
        public readonly string $permissionCode
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'object' => $this->objectName,
            'tool' => $this->toolName,
            'permission' => $this->permissionCode,
        ];
    }
}
