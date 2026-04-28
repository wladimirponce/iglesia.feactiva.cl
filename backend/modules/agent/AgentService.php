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
        private readonly PermissionRepository $permissionRepository,
        private readonly ?OntologyResolver $ontologyResolver = null,
        private readonly ?EntityResolver $entityResolver = null
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

            $ontology = $this->ontologyResolver?->resolve([
                'input_text' => $inputText,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
            ]);
            $ontologyData = $ontology instanceof OntologyResolutionResult ? $ontology->toArray() : null;
            $fallbackIntent = $this->intentRouter->detect($inputText);
            $intent = $ontology instanceof OntologyResolutionResult && $ontology->resolved
                ? (string) $ontology->action
                : $fallbackIntent;
            $this->repository->updateIntent($tenantId, $requestId, $intent);

            if ($ontology instanceof OntologyResolutionResult) {
                if ($ontology->resolved && $ontology->toolName === null) {
                    $this->auditLogger->logOntology($tenantId, $userId, $requestId, 'agent.ontology.unhandled_action', (string) $ontology->action, 'failed', [
                        'ontology' => $ontologyData,
                    ]);
                } elseif ($ontology->resolved) {
                    $this->auditLogger->logOntology($tenantId, $userId, $requestId, 'agent.ontology.resolved', (string) $ontology->action, 'success', [
                        'ontology' => $ontologyData,
                    ]);
                } else {
                    $this->auditLogger->logOntology($tenantId, $userId, $requestId, 'agent.ontology.unresolved', 'unresolved', 'failed', [
                        'input_text' => $inputText,
                    ]);
                }
            }

            $this->auditLogger->log($tenantId, $userId, $requestId, 'agent.intent.detected', [
                'normalized_intent' => $intent,
                'ontology' => $ontologyData,
            ]);

            if ($ontology instanceof OntologyResolutionResult && !$ontology->resolved && $fallbackIntent !== 'saludo') {
                $toolExecution = [
                    'tool_name' => null,
                    'module_code' => null,
                    'status' => 'unresolved',
                    'input' => ['input_text' => $inputText],
                    'output' => ['reason' => 'ontology_unresolved'],
                    'action_id' => null,
                ];
            } elseif ($ontology instanceof OntologyResolutionResult && $ontology->resolved) {
                $toolExecution = $this->executeToolForOntology($tenantId, $userId, $requestId, $ontology, $inputText);
            } else {
                $toolExecution = $this->executeToolForIntent($tenantId, $userId, $requestId, $intent, $inputText);
            }
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
        $route = $this->toolRoute($tenantId, $intent, $inputText);

        if ($route === null) {
            return null;
        }

        return $this->executeToolRoute($tenantId, $userId, $requestId, $route);
    }

    private function executeToolForOntology(
        int $tenantId,
        int $userId,
        int $requestId,
        OntologyResolutionResult $ontology,
        string $inputText
    ): ?array {
        if ($ontology->toolName === null) {
            return [
                'tool_name' => null,
                'module_code' => null,
                'status' => 'unhandled',
                'input' => ['input_text' => $inputText],
                'output' => ['reason' => 'ontology_unhandled_action'],
                'action_id' => null,
            ];
        }

        $input = $this->buildOntologyToolInput($tenantId, $userId, $requestId, $ontology, $inputText);
        $entityProblem = $this->entityResolutionProblem($tenantId, $userId, $requestId, $input);
        if ($entityProblem !== null) {
            return [
                'tool_name' => $ontology->toolName,
                'module_code' => null,
                'status' => 'failed',
                'input' => $input,
                'output' => $entityProblem,
                'action_id' => null,
            ];
        }
        unset($input['_entity_resolution']);

        $missing = $this->missingFields($input, $ontology->missingFields);

        if ($missing !== []) {
            $this->auditLogger->logOntology($tenantId, $userId, $requestId, 'agent.ontology.missing_fields', (string) $ontology->action, 'failed', [
                'ontology' => $ontology->toArray(),
                'missing_fields' => $missing,
            ]);

            return [
                'tool_name' => $ontology->toolName,
                'module_code' => null,
                'status' => 'failed',
                'input' => $input,
                'output' => [
                    'reason' => 'missing_ontology_data',
                    'missing_fields' => $missing,
                ],
                'action_id' => null,
            ];
        }

        return $this->executeToolRoute($tenantId, $userId, $requestId, [
            'tool_name' => $ontology->toolName,
            'input' => $input,
            'missing_data' => $missing !== [],
            'missing_reason' => 'missing_ontology_data',
            'missing_fields' => $missing,
        ]);
    }

    /** @param array{tool_name: string, input: array, missing_data?: bool, missing_reason?: string, missing_fields?: array} $route */
    private function executeToolRoute(
        int $tenantId,
        int $userId,
        int $requestId,
        array $route
    ): ?array {
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
            $this->auditLogger->logPermissionDenied($tenantId, $userId, $requestId, $tool->name(), $tool->requiredPermission(), [
                'module_code' => $tool->moduleCode(),
            ]);

            return [
                'tool_name' => $tool->name(),
                'module_code' => $tool->moduleCode(),
                'status' => 'blocked',
                'input' => $input,
                'output' => $output,
                'action_id' => null,
            ];
        }

        if (($route['missing_data'] ?? false) === true) {
            $output = [
                'reason' => (string) ($route['missing_reason'] ?? 'missing_required_data'),
                'missing_fields' => $route['missing_fields'] ?? [],
            ];
            $this->auditLogger->logOntology($tenantId, $userId, $requestId, 'agent.ontology.missing_fields', $tool->name(), 'failed', [
                'reason' => $output['reason'],
                'missing_fields' => $output['missing_fields'],
            ]);

            return [
                'tool_name' => $tool->name(),
                'module_code' => $tool->moduleCode(),
                'status' => 'failed',
                'input' => $input,
                'output' => $output,
                'action_id' => null,
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
            $this->targetTableForTool($tool->name(), $output),
            isset($output['id']) ? (int) $output['id'] : null
        );
        $this->auditLogger->logTool($tenantId, $userId, $requestId, $eventType, $tool->name(), $auditResult, [
            'action_id' => $actionId,
            'module_code' => $tool->moduleCode(),
        ]);

        if ($status === 'success' && $tool->name() === 'crm_search_person') {
            $output = $this->createPersonWhenSearchHasNoResults($tenantId, $userId, $requestId, $input, $output);
        }

        return [
            'tool_name' => $tool->name(),
            'module_code' => $tool->moduleCode(),
            'status' => $status,
            'input' => $input,
            'output' => $output,
            'action_id' => $actionId,
        ];
    }

    /** @return array<string, mixed> */
    private function buildOntologyToolInput(int $tenantId, int $userId, int $requestId, OntologyResolutionResult $ontology, string $inputText): array
    {
        $fields = $ontology->extractedFields;
        $action = (string) $ontology->action;

        if ($this->entityResolver instanceof EntityResolver) {
            $resolved = $this->entityResolver->resolveToolInput($tenantId, $ontology->toolName, $fields);
            $fields = $resolved['fields'];
            $fields['_entity_resolution'] = $resolved['results'];
        }

        if (isset($fields['cuenta_nombre']) && (!isset($fields['cuenta_id']) || $fields['cuenta_id'] === null)) {
            $fields['cuenta_id'] = $this->resolveCuentaId($tenantId, (string) $fields['cuenta_nombre']);
        }

        if (isset($fields['categoria_nombre']) && (!isset($fields['categoria_id']) || $fields['categoria_id'] === null)) {
            $tipo = $ontology->toolName === 'finanzas_create_expense' ? 'egreso' : 'ingreso';
            $fields['categoria_id'] = $this->resolveCategoriaId($tenantId, $tipo, (string) $fields['categoria_nombre']);
        }

        if (isset($fields['persona_nombre']) && (!isset($fields['persona_id']) || $fields['persona_id'] === null)) {
            $fields['persona_id'] = $this->resolvePersonaId($tenantId, (string) $fields['persona_nombre']);

            if ($fields['persona_id'] === null && $this->shouldAutoCreatePersonForAction($action)) {
                $created = $this->createMinimalPerson($tenantId, $userId, $requestId, (string) $fields['persona_nombre']);
                if ($created !== null) {
                    $fields['persona_id'] = $created['id'];
                    $fields['auto_created_person'] = $created;
                }
            }
        }

        if (isset($fields['familia_nombre']) && (!isset($fields['familia_id']) || $fields['familia_id'] === null)) {
            $fields['familia_id'] = $this->resolveFamiliaId($tenantId, (string) $fields['familia_nombre']);
        }

        if (isset($fields['ruta_nombre']) && (!isset($fields['ruta_id']) || $fields['ruta_id'] === null)) {
            $fields['ruta_id'] = $this->resolveRutaId($tenantId, (string) $fields['ruta_nombre']);
        }

        if ($action === 'registrar_diezmo' || $action === 'registrar_ofrenda') {
            $subtipo = $action === 'registrar_diezmo' ? 'diezmo' : 'ofrenda';
            $fields['cuenta_id'] = $fields['cuenta_id'] ?? $this->resolveDefaultCuentaId($tenantId);
            $fields['categoria_id'] = $fields['categoria_id'] ?? $this->resolveCategoriaId($tenantId, 'ingreso', $subtipo);
            $fields['centro_costo_id'] = $fields['centro_costo_id'] ?? null;
            $fields['persona_id'] = $fields['persona_id'] ?? null;
            $fields['fecha_movimiento'] = $fields['fecha_movimiento'] ?? date('Y-m-d');
            $fields['medio_pago'] = $fields['medio_pago'] ?? 'efectivo';
            $fields['descripcion'] = $fields['descripcion'] ?? $inputText;
            $fields['subtipo'] = $subtipo;
        }

        if ($action === 'registrar_egreso') {
            $fields['centro_costo_id'] = $fields['centro_costo_id'] ?? null;
            $fields['fecha_movimiento'] = $fields['fecha_movimiento'] ?? date('Y-m-d');
            $fields['medio_pago'] = $fields['medio_pago'] ?? 'efectivo';
            $fields['descripcion'] = $fields['descripcion'] ?? $inputText;
        }

        if ($action === 'crear_solicitud_oracion') {
            $fields['titulo'] = $fields['titulo'] ?? 'Peticion de oracion';
            $fields['privacidad'] = $fields['privacidad'] ?? 'privada';
            $fields['persona_id'] = $fields['persona_id'] ?? null;
        }

        if ($action === 'crear_caso_pastoral') {
            $fields['tipo'] = $fields['tipo'] ?? 'acompanamiento';
            $fields['prioridad'] = $fields['prioridad'] ?? 'media';
            $fields['descripcion_general'] = $fields['descripcion_general'] ?? $inputText;
            $fields['es_confidencial'] = $fields['es_confidencial'] ?? true;
        }

        if ($action === 'crear_recordatorio') {
            $fields['persona_id'] = $fields['persona_id'] ?? null;
            $fields['descripcion'] = $fields['descripcion'] ?? $inputText;
            $fields['modulo_origen'] = $fields['modulo_origen'] ?? 'agent';
            $fields['referencia_id'] = $fields['referencia_id'] ?? null;
        }

        if ($action === 'buscar_recordatorio') {
            $fields['persona_id'] = $fields['persona_id'] ?? null;
        }

        return $fields;
    }

    private function entityResolutionProblem(int $tenantId, int $userId, int $requestId, array $input): ?array
    {
        $results = is_array($input['_entity_resolution'] ?? null) ? $input['_entity_resolution'] : [];
        if ($results === []) {
            return null;
        }

        $ambiguous = [];
        $notFound = [];

        foreach ($results as $field => $result) {
            if (!$result instanceof EntityResolutionResult) {
                continue;
            }

            $this->auditLogger->logEntityResolution($tenantId, $userId, $requestId, (string) $field, $result);

            if ($result->ambiguous) {
                $ambiguous[(string) $field] = $result->toArray();
            } elseif (!$result->resolved) {
                $notFound[(string) $field] = $result->toArray();
            }
        }

        if ($ambiguous !== []) {
            return [
                'reason' => 'entity_ambiguous',
                'ambiguous_entities' => $ambiguous,
                'missing_fields' => array_keys($ambiguous),
            ];
        }

        if ($notFound !== []) {
            return [
                'reason' => 'entity_not_found',
                'not_found_entities' => $notFound,
                'missing_fields' => array_keys($notFound),
            ];
        }

        return null;
    }

    private function shouldAutoCreatePersonForAction(string $action): bool
    {
        return in_array($action, [
            'crear_solicitud_oracion',
            'crear_caso_pastoral',
            'crear_recordatorio',
            'asignar_persona_familia',
            'asignar_discipulado',
        ], true);
    }

    /** @param array<string, mixed> $input @param array<string, mixed> $output @return array<string, mixed> */
    private function createPersonWhenSearchHasNoResults(int $tenantId, int $userId, int $requestId, array $input, array $output): array
    {
        $results = is_array($output['results'] ?? null) ? $output['results'] : [];
        $query = trim((string) ($input['query'] ?? ''));

        if ($results !== [] || $query === '') {
            return $output;
        }

        $created = $this->createMinimalPerson($tenantId, $userId, $requestId, $query);
        if ($created === null) {
            $output['auto_create_person'] = [
                'status' => 'blocked',
                'reason' => 'missing_permission_or_invalid_name',
            ];
            return $output;
        }

        $output['auto_created_person'] = $created;
        $output['results'] = [[
            'id' => $created['id'],
            'nombres' => $created['nombres'],
            'apellidos' => $created['apellidos'],
            'email' => null,
            'telefono' => null,
            'whatsapp' => null,
            'estado_persona' => 'visita',
        ]];

        return $output;
    }

    /** @return array{id: int, nombres: string, apellidos: string}|null */
    private function createMinimalPerson(int $tenantId, int $userId, int $requestId, string $name): ?array
    {
        if (!$this->permissionRepository->userHasPermission($userId, $tenantId, 'crm.personas.crear')) {
            $this->auditLogger->logTool($tenantId, $userId, $requestId, 'agent.tool.blocked', 'crm_create_person', 'denied', [
                'required_permission' => 'crm.personas.crear',
                'reason' => 'auto_create_person_missing_permission',
            ]);
            return null;
        }

        $parts = preg_split('/\s+/', trim($name));
        if (!is_array($parts) || $parts === [] || trim((string) $parts[0]) === '') {
            return null;
        }

        $input = [
            'nombres' => mb_convert_case(trim((string) $parts[0]), MB_CASE_TITLE, 'UTF-8'),
            'apellidos' => count($parts) > 1
                ? mb_convert_case(trim(implode(' ', array_slice($parts, 1))), MB_CASE_TITLE, 'UTF-8')
                : 'Por completar',
            'estado_persona' => 'visita',
        ];

        $tool = $this->toolRegistry->get('crm_create_person');
        if (!$tool instanceof AgentToolInterface) {
            return null;
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
            'crm_create_person',
            'crm',
            $input,
            $output,
            $status,
            $status === 'success' ? 'crm_personas' : null,
            isset($output['id']) ? (int) $output['id'] : null
        );
        $this->auditLogger->logTool($tenantId, $userId, $requestId, $eventType, 'crm_create_person', $auditResult, [
            'action_id' => $actionId,
            'module_code' => 'crm',
            'auto_created' => true,
        ]);

        if ($status !== 'success' || !isset($output['id'])) {
            return null;
        }

        return [
            'id' => (int) $output['id'],
            'nombres' => (string) $output['nombres'],
            'apellidos' => (string) $output['apellidos'],
        ];
    }

    /** @return array{tool_name: string, input: array, missing_data?: bool}|null */
    private function toolRoute(int $tenantId, string $intent, string $inputText): ?array
    {
        if ($intent === 'consulta_finanzas') {
            return [
                'tool_name' => 'finanzas_get_summary',
                'input' => $this->extractDateRange($inputText),
            ];
        }

        if ($intent === 'finanzas_balance') {
            return [
                'tool_name' => 'finanzas_get_balance_by_date',
                'input' => ['fecha' => $this->extractSingleDate($inputText) ?? date('Y-m-d')],
            ];
        }

        if ($intent === 'finanzas_create_income') {
            $input = $this->extractFinanceMovementInput($tenantId, $inputText, 'ingreso');
            $missing = $this->missingFields($input, ['cuenta_id', 'categoria_id', 'monto']);

            return [
                'tool_name' => 'finanzas_create_income',
                'input' => $input,
                'missing_data' => $missing !== [],
                'missing_reason' => 'missing_finance_data',
                'missing_fields' => $missing,
            ];
        }

        if ($intent === 'finanzas_create_expense') {
            $input = $this->extractFinanceMovementInput($tenantId, $inputText, 'egreso');
            $missing = $this->missingFields($input, ['cuenta_id', 'categoria_id', 'monto']);

            return [
                'tool_name' => 'finanzas_create_expense',
                'input' => $input,
                'missing_data' => $missing !== [],
                'missing_reason' => 'missing_finance_data',
                'missing_fields' => $missing,
            ];
        }

        if ($intent === 'crm_create_person') {
            $input = $this->extractCreatePersonInput($inputText);
            $missing = $this->missingFields($input, ['nombres', 'apellidos']);

            return [
                'tool_name' => 'crm_create_person',
                'input' => $input,
                'missing_data' => $missing !== [],
                'missing_reason' => 'missing_person_data',
                'missing_fields' => $missing,
            ];
        }

        if ($intent === 'crm_update_person') {
            $input = $this->extractUpdatePersonInput($tenantId, $inputText);
            $missing = $this->missingFields($input, ['persona_id']);
            if (count(array_diff(array_keys($input), ['persona_id'])) < 1) {
                $missing[] = 'campos';
            }
            return [
                'tool_name' => 'crm_update_person',
                'input' => $input,
                'missing_data' => $missing !== [],
                'missing_reason' => 'missing_person_update_data',
                'missing_fields' => array_values(array_unique($missing)),
            ];
        }

        if ($intent === 'crm_create_family') {
            $input = $this->extractCreateFamilyInput($inputText);
            $missing = $this->missingFields($input, ['nombre_familia']);
            return [
                'tool_name' => 'crm_create_family',
                'input' => $input,
                'missing_data' => $missing !== [],
                'missing_reason' => 'missing_family_data',
                'missing_fields' => $missing,
            ];
        }

        if ($intent === 'crm_assign_person_to_family') {
            $input = $this->extractFamilyAssignInput($tenantId, $inputText);
            $missing = $this->missingFields($input, ['persona_id', 'familia_id']);
            return [
                'tool_name' => 'crm_assign_person_to_family',
                'input' => $input,
                'missing_data' => $missing !== [],
                'missing_reason' => 'missing_family_assign_data',
                'missing_fields' => $missing,
            ];
        }

        if ($intent === 'contabilidad_balance') {
            return [
                'tool_name' => 'contabilidad_get_balance',
                'input' => $this->extractDateRange($inputText),
            ];
        }

        if ($intent === 'discipulado_assign_route') {
            $input = $this->extractDiscipuladoAssignInput($tenantId, $inputText);
            $missing = $this->missingFields($input, ['persona_id', 'ruta_id']);
            return [
                'tool_name' => 'discipulado_assign_route',
                'input' => $input,
                'missing_data' => $missing !== [],
                'missing_reason' => 'missing_discipulado_data',
                'missing_fields' => $missing,
            ];
        }

        if ($intent === 'discipulado_complete_stage') {
            $input = $this->extractCompleteStageInput($inputText);
            $missing = $this->missingFields($input, ['persona_etapa_id']);
            return [
                'tool_name' => 'discipulado_complete_stage',
                'input' => $input,
                'missing_data' => $missing !== [],
                'missing_reason' => 'missing_stage_data',
                'missing_fields' => $missing,
            ];
        }

        if ($intent === 'pastoral_create_case') {
            $input = $this->extractPastoralCaseInput($tenantId, $inputText);
            $missing = $this->missingFields($input, ['persona_id', 'titulo']);
            return [
                'tool_name' => 'pastoral_create_case',
                'input' => $input,
                'missing_data' => $missing !== [],
                'missing_reason' => 'missing_pastoral_case_data',
                'missing_fields' => $missing,
            ];
        }

        if ($intent === 'reminder_create') {
            $input = $this->extractReminderInput($tenantId, $inputText);
            $missing = $this->missingFields($input, ['titulo', 'fecha_hora']);

            return [
                'tool_name' => 'reminder_create',
                'input' => $input,
                'missing_data' => $missing !== [],
                'missing_reason' => 'missing_reminder_data',
                'missing_fields' => $missing,
            ];
        }

        if ($intent === 'reminder_search') {
            $fecha = $this->extractSingleDate($inputText) ?? date('Y-m-d');
            return [
                'tool_name' => 'reminder_search',
                'input' => ['fecha_inicio' => $fecha, 'fecha_fin' => $fecha, 'persona_id' => null],
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

    private function targetTableForTool(string $toolName, array $output): ?string
    {
        if (!isset($output['id'])) {
            return null;
        }

        return match ($toolName) {
            'crm_create_person' => 'crm_personas',
            'crm_update_person' => 'crm_personas',
            'crm_create_family' => 'crm_familias',
            'crm_assign_person_to_family' => 'crm_persona_familia',
            'finanzas_create_income', 'finanzas_create_expense' => 'fin_movimientos',
            'discipulado_assign_route' => 'disc_persona_rutas',
            'discipulado_complete_stage' => 'disc_persona_etapas',
            'pastoral_create_case' => 'past_casos',
            'pastoral_create_prayer_request' => 'past_solicitudes_oracion',
            'reminder_create' => 'agenda_recordatorios',
            default => null,
        };
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

    private function extractSingleDate(string $inputText): ?string
    {
        if (preg_match('/\b(20[0-9]{2}-[0-9]{2}-[0-9]{2})\b/', $inputText, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/\b(ma[nñ]ana|manana)\b/iu', $inputText) === 1) {
            return date('Y-m-d', strtotime('+1 day'));
        }

        if (preg_match('/\bhoy\b/iu', $inputText) === 1) {
            return date('Y-m-d');
        }

        return null;
    }

    private function extractCreatePersonInput(string $inputText): array
    {
        $input = ['estado_persona' => 'visita'];
        $clean = trim(preg_replace('/\b(crea|crear|registra|registrar)\s+(una\s+)?persona\b/iu', '', $inputText) ?? $inputText);
        $clean = preg_replace('/,\s*(tel[eé]fono|telefono|email|correo)\b.*$/iu', '', $clean) ?? $clean;
        $parts = preg_split('/\s+/', trim($clean));

        if (is_array($parts) && count($parts) >= 2) {
            $input['nombres'] = ucfirst($parts[0]);
            $input['apellidos'] = implode(' ', array_slice($parts, 1));
        }

        if (preg_match('/\+?[0-9][0-9\s]{7,}/u', $inputText, $matches) === 1) {
            $input['phone'] = trim($matches[0]);
        }

        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $inputText, $matches) === 1) {
            $input['email'] = trim($matches[0]);
        }

        return $input;
    }

    private function extractFinanceMovementInput(int $tenantId, string $inputText, string $tipo): array
    {
        $subtipo = $tipo === 'ingreso' ? 'otro' : null;
        if (preg_match('/\b(diezmo|ofrenda|donacion|donaci[oó]n)\b/iu', $inputText, $matches) === 1) {
            $subtipo = strtolower(str_replace('ó', 'o', $matches[1]));
        }

        $monto = null;
        if (preg_match('/\b([0-9]{1,3}(?:[.][0-9]{3})+|[0-9]+)(?:,\d+)?\b/u', $inputText, $matches) === 1) {
            $monto = (float) str_replace('.', '', $matches[1]);
        }

        $categoriaQuery = $subtipo ?? ($tipo === 'ingreso' ? 'otro ingreso' : $this->textAfterKeyword($inputText, 'por') ?? 'otro egreso');
        $cuentaQuery = $this->textAfterKeyword($inputText, 'en') ?? 'Caja principal';

        return [
            'cuenta_id' => $this->resolveCuentaId($tenantId, $cuentaQuery) ?? $this->resolveDefaultCuentaId($tenantId),
            'categoria_id' => $this->resolveCategoriaId($tenantId, $tipo, $categoriaQuery),
            'centro_costo_id' => null,
            'persona_id' => null,
            'monto' => $monto,
            'fecha_movimiento' => $this->extractSingleDate($inputText) ?? date('Y-m-d'),
            'medio_pago' => 'efectivo',
            'descripcion' => $inputText,
            'subtipo' => $subtipo,
        ];
    }

    private function extractReminderInput(int $tenantId, string $inputText): array
    {
        $title = trim(preg_replace('/\b(recu[eé]rdame|recordatorio|agenda)\b/iu', '', $inputText) ?? $inputText);
        $fecha = $this->extractSingleDate($inputText) ?? null;
        $hora = '09:00:00';

        if (preg_match('/\b([01]?[0-9]|2[0-3])(?::([0-5][0-9]))?\b/u', $inputText, $matches) === 1) {
            $hora = str_pad($matches[1], 2, '0', STR_PAD_LEFT) . ':' . ($matches[2] ?? '00') . ':00';
        }

        $personaId = null;
        if (preg_match('/\ba\s+([A-ZÁÉÍÓÚÑ][\p{L}]+)(?:\s+([A-ZÁÉÍÓÚÑ][\p{L}]+))?/u', $inputText, $matches) === 1) {
            $personaId = $this->resolvePersonaId($tenantId, trim(($matches[1] ?? '') . ' ' . ($matches[2] ?? '')));
        }

        return [
            'persona_id' => $personaId,
            'titulo' => $title !== '' ? $title : null,
            'descripcion' => $inputText,
            'fecha_hora' => $fecha === null ? null : $fecha . ' ' . $hora,
            'modulo_origen' => 'agent',
            'referencia_id' => null,
        ];
    }

    private function extractUpdatePersonInput(int $tenantId, string $inputText): array
    {
        $input = [];
        if (preg_match('/persona\s+#?([0-9]+)/iu', $inputText, $m) === 1) {
            $input['persona_id'] = (int) $m[1];
        } elseif (preg_match('/persona\s+([^,]+)/iu', $inputText, $m) === 1) {
            $query = preg_replace('/\s+(tel[eé]fono|telefono|email|correo)\b.*$/iu', '', trim($m[1])) ?? trim($m[1]);
            $input['persona_id'] = $this->resolvePersonaId($tenantId, trim($query));
        }
        if (preg_match('/email\s+([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})/iu', $inputText, $m) === 1) {
            $input['email'] = trim($m[1]);
        }
        if (preg_match('/tel[eé]fono\s+(\+?[0-9][0-9\s]{7,})/iu', $inputText, $m) === 1) {
            $input['telefono'] = trim($m[1]);
            $input['whatsapp'] = trim($m[1]);
        }
        return $input;
    }

    private function extractCreateFamilyInput(string $inputText): array
    {
        $name = trim(preg_replace('/\b(crea|crear)\s+(una\s+)?familia\b/iu', '', $inputText) ?? $inputText);
        $name = preg_replace('/,\s*(direcci[oó]n|telefono|tel[eé]fono|email)\b.*$/iu', '', $name) ?? $name;
        return ['nombre_familia' => trim($name)];
    }

    private function extractFamilyAssignInput(int $tenantId, string $inputText): array
    {
        $parentesco = 'otro';
        if (preg_match('/\bcomo\s+(hijo|hija|padre|madre|conyuge|c[oó]nyuge|tutor|hermano|hermana|otro)\b/iu', $inputText, $m) === 1) {
            $parentesco = strtolower(str_replace(['ó'], ['o'], $m[1]));
        }
        $personaQuery = null;
        if (preg_match('/agrega\s+(.+?)\s+a\s+familia/iu', $inputText, $m) === 1) {
            $personaQuery = trim($m[1]);
        }
        $familiaQuery = null;
        if (preg_match('/familia\s+(.+?)(?:\s+como|$)/iu', $inputText, $m) === 1) {
            $familiaQuery = trim($m[1]);
        }
        return [
            'persona_id' => $personaQuery ? $this->resolvePersonaId($tenantId, $personaQuery) : null,
            'familia_id' => $familiaQuery ? $this->resolveFamiliaId($tenantId, $familiaQuery) : null,
            'parentesco' => $parentesco,
        ];
    }

    private function extractDiscipuladoAssignInput(int $tenantId, string $inputText): array
    {
        $personaQuery = null;
        if (preg_match('/asigna\s+(.+?)\s+a\s+discipulado/iu', $inputText, $m) === 1) {
            $personaQuery = trim($m[1]);
        }
        $rutaQuery = null;
        if (preg_match('/discipulado\s+(.+)$/iu', $inputText, $m) === 1) {
            $rutaQuery = trim($m[1]);
        }
        return [
            'persona_id' => $personaQuery ? $this->resolvePersonaId($tenantId, $personaQuery) : null,
            'ruta_id' => $rutaQuery ? $this->resolveRutaId($tenantId, $rutaQuery) : null,
            'mentor_persona_id' => null,
        ];
    }

    private function extractCompleteStageInput(string $inputText): array
    {
        $input = ['observacion' => $inputText];
        if (preg_match('/etapa\s+#?([0-9]+)/iu', $inputText, $m) === 1) {
            $input['persona_etapa_id'] = (int) $m[1];
        }
        return $input;
    }

    private function extractPastoralCaseInput(int $tenantId, string $inputText): array
    {
        $personaId = null;
        if (preg_match('/para\s+([^:]+)(?::|$)/iu', $inputText, $m) === 1) {
            $personaId = $this->resolvePersonaId($tenantId, trim($m[1]));
        }
        $detalle = str_contains($inputText, ':') ? trim(explode(':', $inputText, 2)[1]) : $inputText;
        return [
            'persona_id' => $personaId,
            'titulo' => 'Caso pastoral',
            'tipo' => 'acompanamiento',
            'prioridad' => 'media',
            'descripcion_general' => $detalle,
            'es_confidencial' => true,
        ];
    }

    private function missingFields(array $input, array $required): array
    {
        $missing = [];
        foreach ($required as $field) {
            if (!array_key_exists($field, $input) || $input[$field] === null || $input[$field] === '' || $input[$field] === 0 || $input[$field] === 0.0) {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    private function textAfterKeyword(string $inputText, string $keyword): ?string
    {
        if (preg_match('/\b' . preg_quote($keyword, '/') . '\s+([^,]+)/iu', $inputText, $matches) === 1) {
            return trim($matches[1]);
        }
        return null;
    }

    private function resolveCuentaId(int $tenantId, string $query): ?int
    {
        $statement = Database::connection()->prepare("
            SELECT id
            FROM fin_cuentas
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
              AND es_activa = 1
              AND nombre LIKE :query
            ORDER BY es_principal DESC, id ASC
            LIMIT 1
        ");
        $statement->execute(['tenant_id' => $tenantId, 'query' => '%' . trim($query) . '%']);
        $id = $statement->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private function resolveDefaultCuentaId(int $tenantId): ?int
    {
        $statement = Database::connection()->prepare("
            SELECT id
            FROM fin_cuentas
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
              AND es_activa = 1
            ORDER BY es_principal DESC, id ASC
            LIMIT 1
        ");
        $statement->execute(['tenant_id' => $tenantId]);
        $id = $statement->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private function resolveCategoriaId(int $tenantId, string $tipo, string $query): ?int
    {
        $statement = Database::connection()->prepare("
            SELECT id
            FROM fin_categorias
            WHERE tenant_id = :tenant_id
              AND tipo = :tipo
              AND deleted_at IS NULL
              AND es_activa = 1
              AND (nombre LIKE :query_nombre OR codigo LIKE :query_codigo)
            ORDER BY orden ASC, id ASC
            LIMIT 1
        ");
        $like = '%' . trim($query) . '%';
        $statement->execute([
            'tenant_id' => $tenantId,
            'tipo' => $tipo,
            'query_nombre' => $like,
            'query_codigo' => $like,
        ]);
        $id = $statement->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private function resolvePersonaId(int $tenantId, string $query): ?int
    {
        $statement = Database::connection()->prepare("
            SELECT id
            FROM crm_personas
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
              AND CONCAT(nombres, ' ', apellidos) LIKE :query
            ORDER BY id ASC
            LIMIT 1
        ");
        $statement->execute(['tenant_id' => $tenantId, 'query' => '%' . trim($query) . '%']);
        $id = $statement->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private function resolveFamiliaId(int $tenantId, string $query): ?int
    {
        $statement = Database::connection()->prepare("
            SELECT id
            FROM crm_familias
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
              AND nombre_familia LIKE :query
            ORDER BY id ASC
            LIMIT 1
        ");
        $statement->execute(['tenant_id' => $tenantId, 'query' => '%' . trim($query) . '%']);
        $id = $statement->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private function resolveRutaId(int $tenantId, string $query): ?int
    {
        $statement = Database::connection()->prepare("
            SELECT id
            FROM disc_rutas
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
              AND es_activa = 1
              AND nombre LIKE :query
            ORDER BY id ASC
            LIMIT 1
        ");
        $statement->execute(['tenant_id' => $tenantId, 'query' => '%' . trim($query) . '%']);
        $id = $statement->fetchColumn();
        return $id === false ? null : (int) $id;
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
