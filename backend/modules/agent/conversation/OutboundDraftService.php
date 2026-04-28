<?php

declare(strict_types=1);

final class OutboundDraftService
{
    public function __construct(
        private readonly OutboundDraftRepository $repository,
        private readonly AgentAuditLogger $auditLogger
    ) {
    }

    public function create(?int $tenantId, ?int $userId, ?int $conversationId, array $input): int
    {
        $id = $this->repository->create($tenantId, $userId, $conversationId, $input);
        $this->audit('outbound_draft.created', $tenantId, $userId, $id);
        return $id;
    }

    public function find(int $id): ?array
    {
        return $this->repository->find($id);
    }

    public function improve(int $id): string
    {
        $draft = $this->repository->find($id);
        $text = trim((string) (($draft['improved_text'] ?? null) ?: ($draft['draft_text'] ?? '')));
        $improved = $this->improveText($text);
        $this->repository->improve($id, $improved);
        $this->audit('outbound_draft.improved', $draft['tenant_id'] ?? null, $draft['created_by_user_id'] ?? null, $id);
        return $improved;
    }

    public function approveAndMarkSent(int $id): void
    {
        $draft = $this->repository->find($id);
        $this->repository->setStatus($id, 'approved');
        $this->audit('outbound_draft.approved', $draft['tenant_id'] ?? null, $draft['created_by_user_id'] ?? null, $id);
        $this->repository->setStatus($id, 'sent');
        $this->audit('outbound_draft.sent', $draft['tenant_id'] ?? null, $draft['created_by_user_id'] ?? null, $id, ['simulated' => true]);
    }

    public function cancel(int $id): void
    {
        $draft = $this->repository->find($id);
        $this->repository->setStatus($id, 'cancelled');
        $this->audit('outbound_draft.cancelled', $draft['tenant_id'] ?? null, $draft['created_by_user_id'] ?? null, $id);
    }

    private function improveText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return 'Hola, te escribo para recordarte este mensaje.';
        }
        return 'Hola, ' . lcfirst(rtrim($text, '.')) . '. Muchas gracias.';
    }

    private function audit(string $event, ?int $tenantId, ?int $userId, int $draftId, array $metadata = []): void
    {
        if ($tenantId === null || $tenantId < 1 || $userId === null || $userId < 1) {
            return;
        }
        $this->auditLogger->logConversationEvent($tenantId, $userId, $event, $event, 'success', $metadata + ['draft_id' => $draftId]);
    }
}
