<?php

declare(strict_types=1);

final class AgentAuditLogger
{
    public function log(int $tenantId, int $userId, int $requestId, string $action, array $metadata = []): void
    {
        $this->logEvent($tenantId, $userId, $requestId, 'agent.request', $action, $action, 'success', $metadata);
    }

    public function logTool(
        int $tenantId,
        int $userId,
        int $requestId,
        string $eventType,
        string $action,
        string $result,
        array $metadata = []
    ): void {
        $this->logEvent($tenantId, $userId, $requestId, $eventType, $eventType, $action, $result, $metadata);
    }

    public function logOntology(
        int $tenantId,
        int $userId,
        int $requestId,
        string $eventType,
        string $action,
        string $result,
        array $metadata = []
    ): void {
        $this->logEvent($tenantId, $userId, $requestId, $eventType, $eventType, $action, $result, $metadata);
    }

    public function logPermissionDenied(
        int $tenantId,
        int $userId,
        int $requestId,
        string $action,
        string $requiredPermission,
        array $metadata = []
    ): void {
        $this->logEvent($tenantId, $userId, $requestId, 'agent.permission.denied', 'agent.permission.denied', $action, 'denied', $metadata + [
            'required_permission' => $requiredPermission,
        ]);
    }

    public function logSqlSkill(
        int $tenantId,
        int $userId,
        string $eventType,
        string $action,
        string $result,
        int $skillId,
        array $metadata = []
    ): void {
        $this->logEvent(
            $tenantId,
            $userId,
            null,
            $eventType,
            $eventType,
            $action,
            $result,
            $metadata,
            'agent_sql_skill',
            $skillId > 0 ? $skillId : null
        );
    }

    private function logEvent(
        int $tenantId,
        int $userId,
        ?int $requestId,
        string $eventType,
        string $eventDescription,
        string $action,
        string $result,
        array $metadata = [],
        string $subjectType = 'agent_request',
        ?int $subjectId = null
    ): void
    {
        $sql = "
            INSERT INTO agent_audit_logs (
                tenant_id,
                user_id,
                request_id,
                event_type,
                event_description,
                action,
                result,
                subject_type,
                subject_id,
                metadata,
                ip_address,
                user_agent
            ) VALUES (
                :tenant_id,
                :user_id,
                :request_id,
                :event_type,
                :event_description,
                :action,
                :result,
                :subject_type,
                :subject_id,
                :metadata,
                :ip_address,
                :user_agent
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'request_id' => $requestId,
            'event_type' => $eventType,
            'event_description' => $eventDescription,
            'action' => $action,
            'result' => $result,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId ?? $requestId,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
