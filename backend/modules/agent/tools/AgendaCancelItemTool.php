<?php

declare(strict_types=1);

final class AgendaCancelItemTool implements AgentToolInterface
{
    public function name(): string { return 'agenda_cancel_item'; }
    public function moduleCode(): string { return 'agenda'; }
    public function requiredPermission(): string { return 'agenda.items.cancelar'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $id = (int) ($input['agenda_item_id'] ?? 0);
        if ($id < 1) {
            throw new RuntimeException('AGENT_TOOL_MISSING_AGENDA_ITEM');
        }
        $service = new AgendaService(new AgendaRepository(), new AgendaAuditLogger());
        $service->cancel($tenantId, $userId, $id);
        return $service->show($tenantId, $id);
    }
}
