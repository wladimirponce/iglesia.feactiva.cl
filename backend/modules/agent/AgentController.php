<?php

declare(strict_types=1);

final class AgentController
{
    private AgentValidator $validator;
    private AgentService $service;

    public function __construct()
    {
        $repository = new AgentRepository();
        $this->validator = new AgentValidator();
        $this->service = new AgentService(
            $repository,
            new AgentIntentRouter(),
            new AgentResponseComposer(),
            new AgentAuditLogger(),
            new AgentToolRegistry(),
            new PermissionRepository()
        );
    }

    public function store(): void
    {
        $tenantId = AuthContext::tenantId();
        $userId = AuthContext::userId();

        if ($tenantId === null || $userId === null) {
            Response::error('UNAUTHENTICATED', 'Debe iniciar sesion.', [], 401);
            return;
        }

        $input = $this->jsonInput();
        $errors = $this->validator->validateCreate($input, $userId, $tenantId);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $result = $this->service->createRequest(
                $tenantId,
                $userId,
                (string) $input['source'],
                trim((string) $input['input_text'])
            );
        } catch (Throwable) {
            Response::error('AGENT_REQUEST_ERROR', 'No fue posible procesar la solicitud del agente.', [], 500);
            return;
        }

        Response::success($result, 'Solicitud de agente procesada.', [], 201);
    }

    public function show(string $id): void
    {
        $tenantId = AuthContext::tenantId();
        $requestId = (int) $id;

        if ($tenantId === null) {
            Response::error('TENANT_ACCESS_DENIED', 'No tiene acceso a esta iglesia.', [], 403);
            return;
        }

        if ($requestId < 1) {
            Response::error('AGENT_REQUEST_NOT_FOUND', 'Solicitud de agente no encontrada.', [], 404);
            return;
        }

        try {
            $request = $this->service->findRequest($tenantId, $requestId);
        } catch (Throwable) {
            Response::error('AGENT_REQUEST_ERROR', 'No fue posible consultar la solicitud del agente.', [], 500);
            return;
        }

        if ($request === null) {
            Response::error('AGENT_REQUEST_NOT_FOUND', 'Solicitud de agente no encontrada.', [], 404);
            return;
        }

        Response::success($request);
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');

        if ($rawBody === false || trim($rawBody) === '') {
            return [];
        }

        $decoded = json_decode($rawBody, true);

        return is_array($decoded) ? $decoded : [];
    }
}
