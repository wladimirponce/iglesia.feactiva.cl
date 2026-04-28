<?php

declare(strict_types=1);

final class AgendaSearchItemsTool implements AgentToolInterface
{
    public function name(): string { return 'agenda_search_items'; }
    public function moduleCode(): string { return 'agenda'; }
    public function requiredPermission(): string { return 'agenda.items.ver'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $filters = [];
        if (isset($input['fecha'])) {
            $filters['fecha'] = $input['fecha'];
        }
        if (isset($input['persona_id'])) {
            $filters['persona_id'] = (int) $input['persona_id'];
        }
        if (isset($input['familia_id'])) {
            $filters['familia_id'] = (int) $input['familia_id'];
        }
        return ['items' => (new AgendaRepository())->list($tenantId, $filters)];
    }
}
