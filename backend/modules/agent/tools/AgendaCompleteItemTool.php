<?php

declare(strict_types=1);

final class AgendaCompleteItemTool implements AgentToolInterface
{
    public function name(): string { return 'agenda_complete_item'; }
    public function moduleCode(): string { return 'agenda'; }
    public function requiredPermission(): string { return 'agenda.items.completar'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $id = (int) ($input['agenda_item_id'] ?? 0);
        if ($id < 1) {
            throw new RuntimeException('AGENT_TOOL_MISSING_AGENDA_ITEM');
        }
        $service = new AgendaService(new AgendaRepository(), new AgendaAuditLogger());
        $service->complete($tenantId, $userId, $id);
        return $service->show($tenantId, $id);
    }
}
