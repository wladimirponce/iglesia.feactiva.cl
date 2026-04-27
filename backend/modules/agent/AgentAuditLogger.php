<?php

declare(strict_types=1);

final class AgentAuditLogger
{
    public function log(int $tenantId, int $userId, int $requestId, string $action, array $metadata = []): void
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
                'agent.request',
                :event_description,
                :action,
                'success',
                'agent_request',
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
            'event_description' => $action,
            'action' => $action,
            'subject_id' => $requestId,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
