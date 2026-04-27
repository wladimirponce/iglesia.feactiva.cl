<?php

declare(strict_types=1);

final class AuthRepository
{
    public function findActiveUserByEmail(string $email): ?array
    {
        $sql = "
            SELECT id, email, password_hash
            FROM auth_users
            WHERE email = :email
              AND is_active = 1
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'email' => $email,
        ]);

        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function findFirstActiveTenantForUser(int $userId): ?int
    {
        $sql = "
            SELECT ut.tenant_id
            FROM auth_user_tenants ut
            INNER JOIN saas_tenants t
                ON t.id = ut.tenant_id
            WHERE ut.user_id = :user_id
              AND ut.status = 'active'
              AND t.status IN ('active', 'trial')
              AND t.deleted_at IS NULL
            ORDER BY ut.tenant_id ASC
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'user_id' => $userId,
        ]);

        $tenant = $statement->fetch();

        return $tenant === false ? null : (int) $tenant['tenant_id'];
    }

    public function createSession(
        int $userId,
        ?int $tenantId,
        string $tokenHash,
        string $expiresAt,
        ?string $ipAddress,
        ?string $userAgent
    ): void {
        $sql = "
            INSERT INTO auth_sessions (
                user_id,
                tenant_id,
                token_hash,
                ip_address,
                user_agent,
                expires_at
            ) VALUES (
                :user_id,
                :tenant_id,
                :token_hash,
                :ip_address,
                :user_agent,
                :expires_at
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'token_hash' => $tokenHash,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findValidSessionByTokenHash(string $tokenHash): ?array
    {
        $sql = "
            SELECT
                s.id AS session_id,
                s.user_id,
                s.tenant_id,
                u.email
            FROM auth_sessions s
            INNER JOIN auth_users u
                ON u.id = s.user_id
            LEFT JOIN auth_user_tenants ut
                ON ut.user_id = s.user_id
                AND ut.tenant_id = s.tenant_id
            LEFT JOIN saas_tenants t
                ON t.id = s.tenant_id
            WHERE s.token_hash = :token_hash
              AND s.revoked_at IS NULL
              AND s.expires_at > UTC_TIMESTAMP()
              AND u.is_active = 1
              AND u.deleted_at IS NULL
              AND (
                  s.tenant_id IS NULL
                  OR (
                      ut.status = 'active'
                      AND t.status IN ('active', 'trial')
                      AND t.deleted_at IS NULL
                  )
              )
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'token_hash' => $tokenHash,
        ]);

        $session = $statement->fetch();

        return $session === false ? null : $session;
    }

    public function cleanupExpiredSessions(): int
    {
        $sql = "
            UPDATE auth_sessions
            SET revoked_at = UTC_TIMESTAMP()
            WHERE revoked_at IS NULL
              AND expires_at <= UTC_TIMESTAMP()
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute();

        return $statement->rowCount();
    }

    public function revokeSessionByTokenHash(string $tokenHash): void
    {
        $sql = "
            UPDATE auth_sessions
            SET revoked_at = UTC_TIMESTAMP()
            WHERE token_hash = :token_hash
              AND revoked_at IS NULL
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'token_hash' => $tokenHash,
        ]);
    }
}
