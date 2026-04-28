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
        $this->register(new CrmSearchPersonTool());
        $this->register(new PastoralCreatePrayerRequestTool());
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
