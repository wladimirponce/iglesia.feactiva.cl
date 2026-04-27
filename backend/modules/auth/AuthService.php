<?php

declare(strict_types=1);

final class AuthService
{
    public function __construct(
        private readonly AuthRepository $repository
    ) {
    }

    public function login(string $email, string $password, ?string $ipAddress, ?string $userAgent): ?array
    {
        $this->repository->cleanupExpiredSessions();

        $user = $this->repository->findActiveUserByEmail($email);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        $tenantId = $this->repository->findFirstActiveTenantForUser((int) $user['id']);

        if ($tenantId === null) {
            return null;
        }

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = self::hashToken($plainToken);
        $expiresAt = gmdate('Y-m-d H:i:s', time() + $this->tokenTtlSeconds());

        $this->repository->createSession(
            (int) $user['id'],
            $tenantId,
            $tokenHash,
            $expiresAt,
            $ipAddress,
            $userAgent
        );

        return [
            'token' => $plainToken,
            'expires_at' => $expiresAt,
            'user' => [
                'id' => (int) $user['id'],
                'email' => $user['email'],
            ],
            'tenant_id' => $tenantId,
        ];
    }

    public function logout(string $plainToken): void
    {
        $this->repository->revokeSessionByTokenHash(self::hashToken($plainToken));
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    private function tokenTtlSeconds(): int
    {
        $ttl = (int) env('AUTH_TOKEN_TTL_SECONDS', 86400);
        return max(300, min($ttl, 2592000));
    }
}
