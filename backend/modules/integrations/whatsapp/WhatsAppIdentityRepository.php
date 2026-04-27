<?php

declare(strict_types=1);

final class WhatsAppIdentityRepository
{
    /** @return array{id: int|string, name: string, email: string, phone: string}|null */
    public function findActiveUserByPhone(string $normalizedPhone): ?array
    {
        $sql = "
            SELECT
                id,
                name,
                email,
                phone
            FROM auth_users
            WHERE phone = :phone
              AND is_active = 1
              AND deleted_at IS NULL
            ORDER BY id ASC
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['phone' => $normalizedPhone]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    /** @return array<int, array{id: int|string, name: string, status: string}> */
    public function activeTenantsForUser(int $userId): array
    {
        $sql = "
            SELECT
                t.id,
                t.name,
                t.status
            FROM auth_user_tenants ut
            INNER JOIN saas_tenants t
                ON t.id = ut.tenant_id
            WHERE ut.user_id = :user_id
              AND ut.status = 'active'
              AND t.status IN ('active', 'trial')
              AND t.deleted_at IS NULL
            ORDER BY t.id ASC
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function auditIdentifyAttempt(
        ?int $tenantId,
        ?int $userId,
        string $eventDescription,
        array $metadata,
        ?string $ipAddress,
        ?string $userAgent
    ): void {
        $sql = "
            INSERT INTO agent_audit_logs (
                tenant_id,
                user_id,
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
                'whatsapp.identify',
                :event_description,
                'whatsapp.identity.identify',
                :result,
                'auth_user',
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
            'event_description' => $eventDescription,
            'result' => match ($eventDescription) {
                'found' => 'success',
                'multiple_tenants' => 'denied',
                default => 'failed',
            },
            'subject_id' => $userId,
            'metadata' => $this->json($metadata),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}
