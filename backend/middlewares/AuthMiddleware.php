<?php

declare(strict_types=1);

final class AuthMiddleware
{
    public function handle(callable $next): void
    {
        $token = $this->bearerToken();

        if ($token === null) {
            Response::error('UNAUTHENTICATED', 'Debe iniciar sesion.', [], 401);
            return;
        }

        try {
            $repository = new AuthRepository();
            $repository->cleanupExpiredSessions();
            $session = $repository->findValidSessionByTokenHash(AuthService::hashToken($token));
        } catch (Throwable) {
            Response::error('AUTH_SESSION_ERROR', 'No fue posible validar la sesion.', [], 500);
            return;
        }

        if ($session === null) {
            Response::error('UNAUTHENTICATED', 'Sesion invalida o expirada.', [], 401);
            return;
        }

        AuthContext::setUser((int) $session['user_id'], (string) $session['email']);

        if ($session['tenant_id'] !== null) {
            AuthContext::setTenantId((int) $session['tenant_id']);
        }

        $next();
    }

    private function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        if ($header === null && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        }

        if (!is_string($header) || !preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches)) {
            return null;
        }

        return trim($matches[1]);
    }
}
