<?php

declare(strict_types=1);

interface AgentToolInterface
{
    public function name(): string;

    public function moduleCode(): string;

    public function requiredPermission(): string;

    public function execute(int $tenantId, int $userId, array $input): array;
}
