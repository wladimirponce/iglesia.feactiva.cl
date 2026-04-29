<?php

declare(strict_types=1);

final class AgendaCreateItemTool implements AgentToolInterface
{
    public function name(): string { return 'agenda_create_item'; }
    public function moduleCode(): string { return 'agenda'; }
    public function requiredPermission(): string { return 'agenda.items.crear'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        foreach (['tipo', 'titulo', 'fecha_inicio'] as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                throw new RuntimeException('AGENT_TOOL_MISSING_AGENDA_DATA');
            }
        }
        $service = new AgendaService(new AgendaRepository(), new AgendaAuditLogger());
        $id = $service->create($tenantId, $userId, $input + ['modulo_origen' => 'agent']);
        $item = $service->show($tenantId, $id);

        if (($item['tipo'] ?? '') === 'meeting' && class_exists('GoogleOAuthService')) {
            $oauth = new GoogleOAuthService(new GoogleOAuthRepository(), new GoogleTokenCrypto(), new AgendaAuditLogger());
            if (!$oauth->hasScope($tenantId, $userId, GoogleOAuthService::SCOPE_CALENDAR)) {
                try {
                    $item['google_calendar'] = [
                        'connected' => false,
                        'auth_url' => $oauth->connectionUrl($tenantId, $userId, 'calendar'),
                    ];
                } catch (Throwable $throwable) {
                    $item['google_calendar'] = [
                        'connected' => false,
                        'auth_url' => null,
                        'error' => $throwable->getMessage(),
                    ];
                }
            }
        }

        return $item;
    }
}
