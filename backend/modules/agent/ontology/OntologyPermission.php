<?php

declare(strict_types=1);

final class OntologyPermission
{
    public function __construct(
        public readonly string $actionName,
        public readonly string $permissionCode
    ) {
    }

    public function toArray(): array
    {
        return [
            'action' => $this->actionName,
            'permission' => $this->permissionCode,
        ];
    }
}
