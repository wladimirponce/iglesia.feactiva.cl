<?php

declare(strict_types=1);

final class GoogleOAuthRepository
{
    public function upsertTokens(int $tenantId, int $userId, array $scopes, ?string $email, ?string $accessTokenEncrypted, ?string $refreshTokenEncrypted, ?string $expiresAt): int
    {
        $existing = $this->findLatest($tenantId, $userId);
        if ($existing !== null) {
            $sql = "
                UPDATE google_oauth_accounts
                SET email = :email,
                    scopes_json = :scopes_json,
                    access_token_encrypted = :access_token_encrypted,
                    refresh_token_encrypted = COALESCE(:refresh_token_encrypted, refresh_token_encrypted),
                    token_expires_at = :token_expires_at,
                    status = 'active',
                    revoked_at = NULL,
                    updated_at = UTC_TIMESTAMP()
                WHERE tenant_id = :tenant_id
                  AND user_id = :user_id
                  AND id = :id
                  AND deleted_at IS NULL
            ";
            $statement = Database::connection()->prepare($sql);
            $statement->execute([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'id' => (int) $existing['id'],
                'email' => $email,
                'scopes_json' => $this->json($scopes),
                'access_token_encrypted' => $accessTokenEncrypted,
                'refresh_token_encrypted' => $refreshTokenEncrypted,
                'token_expires_at' => $expiresAt,
            ]);
            return (int) $existing['id'];
        }

        $sql = "
            INSERT INTO google_oauth_accounts (
                tenant_id,
                user_id,
                provider,
                email,
                scopes_json,
                access_token_encrypted,
                refresh_token_encrypted,
                token_expires_at,
                status
            ) VALUES (
                :tenant_id,
                :user_id,
                'google',
                :email,
                :scopes_json,
                :access_token_encrypted,
                :refresh_token_encrypted,
                :token_expires_at,
                'active'
            )
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'email' => $email,
            'scopes_json' => $this->json($scopes),
            'access_token_encrypted' => $accessTokenEncrypted,
            'refresh_token_encrypted' => $refreshTokenEncrypted,
            'token_expires_at' => $expiresAt,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function status(int $tenantId, int $userId): array
    {
        $account = $this->findLatest($tenantId, $userId);
        if ($account === null || (string) $account['status'] !== 'active') {
            return [
                'connected' => false,
                'calendar_connected' => false,
                'gmail_connected' => false,
                'email' => null,
                'status' => $account['status'] ?? null,
            ];
        }

        $scopes = json_decode((string) $account['scopes_json'], true);
        $scopes = is_array($scopes) ? $scopes : [];
        return [
            'connected' => true,
            'calendar_connected' => in_array(GoogleOAuthService::SCOPE_CALENDAR, $scopes, true),
            'gmail_connected' => in_array(GoogleOAuthService::SCOPE_GMAIL, $scopes, true),
            'email' => $account['email'],
            'status' => $account['status'],
            'token_expires_at' => $account['token_expires_at'],
            'scopes' => $scopes,
        ];
    }

    public function disconnect(int $tenantId, int $userId): void
    {
        $sql = "
            UPDATE google_oauth_accounts
            SET status = 'revoked',
                revoked_at = UTC_TIMESTAMP(),
                updated_at = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id
              AND user_id = :user_id
              AND provider = 'google'
              AND deleted_at IS NULL
              AND status <> 'revoked'
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'user_id' => $userId]);
    }

    public function markError(int $tenantId, int $userId): void
    {
        $sql = "
            UPDATE google_oauth_accounts
            SET status = 'error',
                updated_at = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id
              AND user_id = :user_id
              AND provider = 'google'
              AND deleted_at IS NULL
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'user_id' => $userId]);
    }

    private function findLatest(int $tenantId, int $userId): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                user_id,
                email,
                scopes_json,
                token_expires_at,
                status
            FROM google_oauth_accounts
            WHERE tenant_id = :tenant_id
              AND user_id = :user_id
              AND provider = 'google'
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT 1
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'user_id' => $userId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    private function json(array $value): string
    {
        return json_encode(array_values($value), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';
    }
}
