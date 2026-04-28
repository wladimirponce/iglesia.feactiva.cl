<?php

declare(strict_types=1);

final class AgendaGetDayScheduleTool implements AgentToolInterface
{
    public function name(): string { return 'agenda_get_day_schedule'; }
    public function moduleCode(): string { return 'agenda'; }
    public function requiredPermission(): string { return 'agenda.items.ver'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $fecha = trim((string) ($input['fecha'] ?? date('Y-m-d')));
        $items = (new AgendaRepository())->list($tenantId, ['fecha' => $fecha]);
        return ['fecha' => $fecha, 'items' => $items];
    }
}
