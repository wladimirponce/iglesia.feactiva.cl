<?php

declare(strict_types=1);

final class AgentService
{
    public function __construct(
        private readonly AgentRepository $repository,
        private readonly AgentIntentRouter $intentRouter,
        private readonly AgentResponseComposer $responseComposer,
        private readonly AgentAuditLogger $auditLogger
    ) {
    }

    public function createRequest(int $tenantId, int $userId, string $source, string $inputText): array
    {
        $this->repository->beginTransaction();

        try {
            $requestId = $this->repository->createRequest($tenantId, $userId, $source, $inputText);
            $this->auditLogger->log($tenantId, $userId, $requestId, 'agent.request.received', [
                'source' => $source,
            ]);

            $intent = $this->intentRouter->detect($inputText);
            $this->repository->updateIntent($tenantId, $requestId, $intent);
            $this->auditLogger->log($tenantId, $userId, $requestId, 'agent.intent.detected', [
                'normalized_intent' => $intent,
            ]);

            $responseText = $this->responseComposer->compose($intent);
            $responseId = $this->repository->createResponse($tenantId, $requestId, $intent, $responseText);
            $this->auditLogger->log($tenantId, $userId, $requestId, 'agent.response.generated', [
                'response_id' => $responseId,
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
}
