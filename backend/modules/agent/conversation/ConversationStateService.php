<?php

declare(strict_types=1);

final class ConversationStateService
{
    public function __construct(
        private readonly ConversationStateRepository $repository,
        private readonly AgentAuditLogger $auditLogger
    ) {
    }

    public function active(string $phone): ?array
    {
        $state = $this->repository->findActiveByPhone($phone);
        if ($state !== null) {
            $state['state'] = $this->decode((string) $state['state_json']);
        }
        return $state;
    }

    public function create(?int $tenantId, ?int $userId, string $phone, ?int $conversationId, string $stateKey, array $state): int
    {
        $id = $this->repository->create($tenantId, $userId, $phone, $conversationId, $stateKey, $state);
        $this->audit($tenantId, $userId, 'agent.conversation.state_created', $stateKey, ['state_id' => $id, 'phone' => $this->maskPhone($phone)]);
        return $id;
    }

    public function update(array $current, string $stateKey, array $state): void
    {
        $this->repository->update((int) $current['id'], $stateKey, $state);
        $this->audit($current['tenant_id'] === null ? null : (int) $current['tenant_id'], $current['user_id'] === null ? null : (int) $current['user_id'], 'agent.conversation.state_updated', $stateKey, ['state_id' => (int) $current['id']]);
    }

    public function close(array $current, string $status = 'completed'): void
    {
        $this->repository->close((int) $current['id'], $status);
        $event = $status === 'completed' ? 'agent.conversation.state_completed' : 'agent.conversation.state_updated';
        $this->audit($current['tenant_id'] === null ? null : (int) $current['tenant_id'], $current['user_id'] === null ? null : (int) $current['user_id'], $event, (string) $current['state_key'], ['state_id' => (int) $current['id'], 'status' => $status]);
    }

    private function decode(string $json): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function maskPhone(string $phone): string
    {
        return strlen($phone) > 4 ? str_repeat('*', max(0, strlen($phone) - 4)) . substr($phone, -4) : '****';
    }

    private function audit(?int $tenantId, ?int $userId, string $event, string $action, array $metadata): void
    {
        if ($tenantId === null || $tenantId < 1 || $userId === null || $userId < 1) {
            return;
        }
        $this->auditLogger->logConversationEvent($tenantId, $userId, $event, $action, 'success', $metadata);
    }
}
