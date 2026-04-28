<?php

declare(strict_types=1);

final class AgentToolRegistry
{
    /** @var array<string, AgentToolInterface> */
    private array $tools;

    public function __construct()
    {
        $this->tools = [];
        $this->register(new FinanzasGetSummaryTool());
        $this->register(new FinanzasCreateIncomeTool());
        $this->register(new FinanzasCreateExpenseTool());
        $this->register(new FinanzasGetBalanceByDateTool());
        $this->register(new CrmCreatePersonTool());
        $this->register(new CrmUpdatePersonTool());
        $this->register(new CrmSearchPersonTool());
        $this->register(new CrmCreateFamilyTool());
        $this->register(new CrmAssignPersonToFamilyTool());
        $this->register(new ContabilidadGetBalanceTool());
        $this->register(new DiscipuladoAssignRouteTool());
        $this->register(new DiscipuladoCompleteStageTool());
        $this->register(new PastoralCreateCaseTool());
        $this->register(new PastoralCreatePrayerRequestTool());
        $this->register(new ReminderCreateTool());
        $this->register(new ReminderSearchTool());
        $this->register(new AgendaCreateItemTool());
        $this->register(new AgendaSearchItemsTool());
        $this->register(new AgendaCreateWhatsappNotificationTool());
        $this->register(new AgendaGetDayScheduleTool());
        $this->register(new AgendaCompleteItemTool());
        $this->register(new AgendaCancelItemTool());
    }

    public function get(string $name): ?AgentToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    private function register(AgentToolInterface $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }
}
