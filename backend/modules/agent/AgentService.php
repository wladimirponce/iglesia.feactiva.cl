<?php

declare(strict_types=1);

final class AgentService
{
    public function __construct(
        private readonly AgentRepository $repository,
        private readonly AgentIntentRouter $intentRouter,
        private readonly AgentResponseComposer $responseComposer,
        private readonly AgentAuditLogger $auditLogger,
        private readonly AgentToolRegistry $toolRegistry,
        private readonly PermissionRepository $permissionRepository
    ) {
    }

    public function createRequest(int $tenantId, int $userId, string $source, string $inputText, ?int $conversationId = null): array
    {
        $this->repository->beginTransaction();

        try {
            $requestId = $this->repository->createRequest($tenantId, $userId, $source, $inputText, $conversationId);
            $this->auditLogger->log($tenantId, $userId, $requestId, 'agent.request.received', [
                'source' => $source,
                'conversation_id' => $conversationId,
            ]);

            $intent = $this->intentRouter->detect($inputText);
            $this->repository->updateIntent($tenantId, $requestId, $intent);
            $this->auditLogger->log($tenantId, $userId, $requestId, 'agent.intent.detected', [
                'normalized_intent' => $intent,
            ]);

            $toolExecution = $this->executeToolForIntent($tenantId, $userId, $requestId, $intent, $inputText);
            $responseText = $this->responseComposer->compose($intent, $toolExecution);
            $responseId = $this->repository->createResponse($tenantId, $requestId, $intent, $responseText);
            $this->auditLogger->log($tenantId, $userId, $requestId, 'agent.response.generated', [
                'response_id' => $responseId,
                'tool' => $toolExecution === null ? null : [
                    'name' => $toolExecution['tool_name'],
                    'status' => $toolExecution['status'],
                ],
            ]);

            $this->repository->completeRequest($tenantId, $requestId);
            $this->repository->commit();
        } catch (Throwable $exception) {
            $this->repository->rollBack();
            throw $exception;
        }

        return [
            'id' => $requestId,
            'status' => 'completed',
            'normalized_intent' => $intent,
            'tool' => $toolExecution,
            'response' => [
                'id' => $responseId,
                'text' => $responseText,
            ],
        ];
    }

    public function findRequest(int $tenantId, int $requestId): ?array
    {
        $request = $this->repository->findRequest($tenantId, $requestId);

        if ($request === null) {
            return null;
        }

        $response = $this->repository->findLatestResponse($tenantId, $requestId);

        return [
            'id' => (int) $request['id'],
            'tenant_id' => (int) $request['tenant_id'],
            'user_id' => $request['user_id'] === null ? null : (int) $request['user_id'],
            'source' => $request['source'],
            'request_type' => $request['request_type'],
            'input_text' => $request['input_text'],
            'normalized_intent' => $request['normalized_intent'],
            'status' => $request['status'],
            'created_at' => $request['created_at'],
            'updated_at' => $request['updated_at'],
            'completed_at' => $request['completed_at'],
            'response' => $response === null ? null : [
                'id' => (int) $response['id'],
                'text' => $response['response_text'],
                'status' => $response['status'],
                'created_at' => $response['created_at'],
                'sent_at' => $response['sent_at'],
            ],
        ];
    }

    private function executeToolForIntent(
        int $tenantId,
        int $userId,
        int $requestId,
        string $intent,
        string $inputText
    ): ?array {
        $route = $this->toolRoute($intent, $inputText);

        if ($route === null) {
            return null;
        }

        $tool = $this->toolRegistry->get($route['tool_name']);

        if (!$tool instanceof AgentToolInterface) {
            return null;
        }

        $input = $route['input'];

        if (!$this->permissionRepository->userHasPermission($userId, $tenantId, $tool->requiredPermission())) {
            $output = [
                'reason' => 'missing_permission',
                'required_permission' => $tool->requiredPermission(),
            ];
            $actionId = $this->repository->createAction(
                $tenantId,
                $requestId,
                $userId,
                $tool->name(),
                $tool->moduleCode(),
                $input,
                $output,
                'blocked'
            );
            $this->auditLogger->logTool($tenantId, $userId, $requestId, 'agent.tool.blocked', $tool->name(), 'denied', [
                'action_id' => $actionId,
                'required_permission' => $tool->requiredPermission(),
            ]);

            return [
                'tool_name' => $tool->name(),
                'module_code' => $tool->moduleCode(),
                'status' => 'blocked',
                'input' => $input,
                'output' => $output,
                'action_id' => $actionId,
            ];
        }

        if (($route['missing_data'] ?? false) === true) {
            $output = ['reason' => 'missing_prayer_data'];
            $actionId = $this->repository->createAction(
                $tenantId,
                $requestId,
                $userId,
                $tool->name(),
                $tool->moduleCode(),
                $input,
                $output,
                'failed'
            );
            $this->auditLogger->logTool($tenantId, $userId, $requestId, 'agent.tool.failed', $tool->name(), 'failed', [
                'action_id' => $actionId,
                'reason' => 'missing_prayer_data',
            ]);

            return [
                'tool_name' => $tool->name(),
                'module_code' => $tool->moduleCode(),
                'status' => 'failed',
                'input' => $input,
                'output' => $output,
                'action_id' => $actionId,
            ];
        }

        try {
            $output = $tool->execute($tenantId, $userId, $input);
            $status = 'success';
            $eventType = 'agent.tool.executed';
            $auditResult = 'success';
        } catch (Throwable $exception) {
            $output = [
                'reason' => get_class($exception) === RuntimeException::class ? $exception->getMessage() : 'AGENT_TOOL_ERROR',
            ];
            $status = 'failed';
            $eventType = 'agent.tool.failed';
            $auditResult = 'failed';
        }

        $actionId = $this->repository->createAction(
            $tenantId,
            $requestId,
            $userId,
            $tool->name(),
            $tool->moduleCode(),
            $input,
            $output,
            $status,
            $tool->name() === 'pastoral_create_prayer_request' && isset($output['id']) ? 'past_solicitudes_oracion' : null,
            isset($output['id']) ? (int) $output['id'] : null
        );
        $this->auditLogger->logTool($tenantId, $userId, $requestId, $eventType, $tool->name(), $auditResult, [
            'action_id' => $actionId,
            'module_code' => $tool->moduleCode(),
        ]);

        return [
            'tool_name' => $tool->name(),
            'module_code' => $tool->moduleCode(),
            'status' => $status,
            'input' => $input,
            'output' => $output,
            'action_id' => $actionId,
        ];
    }

    /** @return array{tool_name: string, input: array, missing_data?: bool}|null */
    private function toolRoute(string $intent, string $inputText): ?array
    {
        if ($intent === 'consulta_finanzas') {
            return [
                'tool_name' => 'finanzas_get_summary',
                'input' => $this->extractDateRange($inputText),
            ];
        }

        if ($intent === 'consulta_crm') {
            $query = $this->extractPersonQuery($inputText);

            if ($query === null) {
                return null;
            }

            return [
                'tool_name' => 'crm_search_person',
                'input' => ['query' => $query],
            ];
        }

        if ($intent === 'oracion') {
            $input = $this->extractPrayerInput($inputText);

            return [
                'tool_name' => 'pastoral_create_prayer_request',
                'input' => $input,
                'missing_data' => !isset($input['persona_id']) || trim((string) ($input['detalle'] ?? '')) === '',
            ];
        }

        return null;
    }

    private function extractDateRange(string $inputText): array
    {
        preg_match_all('/\b(20[0-9]{2}-[0-9]{2}-[0-9]{2})\b/', $inputText, $matches);
        $dates = $matches[1] ?? [];

        return [
            'fecha_inicio' => $dates[0] ?? date('Y-m-01'),
            'fecha_fin' => $dates[1] ?? date('Y-m-d'),
        ];
    }

    private function extractPersonQuery(string $inputText): ?string
    {
        if (preg_match('/buscar\s+persona\s+(.+)/iu', $inputText, $matches) === 1) {
            return trim($matches[1]);
        }

        if (preg_match('/persona\s+(.+)/iu', $inputText, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    private function extractPrayerInput(string $inputText): array
    {
        $input = [
            'titulo' => 'Peticion de oracion',
            'privacidad' => 'privada',
        ];

        if (preg_match('/persona\s+([0-9]+)/iu', $inputText, $matches) === 1) {
            $input['persona_id'] = (int) $matches[1];
        }

        if (str_contains($inputText, ':')) {
            $parts = explode(':', $inputText, 2);
            $input['detalle'] = trim($parts[1]);
        } elseif (preg_match('/oracion\s+(?:para\s+)?(?:persona\s+[0-9]+\s*)?(.+)/iu', $inputText, $matches) === 1) {
            $input['detalle'] = trim($matches[1]);
        }

        return $input;
    }
}
