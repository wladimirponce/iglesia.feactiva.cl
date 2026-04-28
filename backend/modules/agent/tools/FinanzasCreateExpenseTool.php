<?php

declare(strict_types=1);

final class FinanzasCreateExpenseTool implements AgentToolInterface
{
    public function name(): string { return 'finanzas_create_expense'; }

    public function moduleCode(): string { return 'finanzas'; }

    public function requiredPermission(): string { return 'fin.movimientos.crear'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $tool = new FinanzasCreateIncomeTool();
        return $tool->createMovement($tenantId, $userId, $input, 'egreso');
    }
}
