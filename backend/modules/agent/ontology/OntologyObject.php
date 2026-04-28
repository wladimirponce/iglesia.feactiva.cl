<?php

declare(strict_types=1);

final class OntologyObject
{
    public function __construct(
        public readonly string $name,
        public readonly string $moduleCode,
        public readonly string $description
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'module_code' => $this->moduleCode,
            'description' => $this->description,
        ];
    }
}
