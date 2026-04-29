<?php

declare(strict_types=1);

final class GoogleOAuthService
{
    public const SCOPE_CALENDAR = 'https://www.googleapis.com/auth/calendar.events';
    public const SCOPE_GMAIL = 'https://www.googleapis.com/auth/gmail.send';

    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function __construct(
        private readonly GoogleOAuthRepository $repository,
        private readonly GoogleTokenCrypto $crypto,
        private readonly AgendaAuditLogger $auditLogger
    ) {
    }

    public function authUrl(int $tenantId, int $userId, string $service): array
    {
        $scopes = $this->scopesFor($service);

        $this->auditLogger->log($tenantId, $userId, null, 'google.oauth.auth_url_generated', [], [
            'service' => $service,
            'scopes' => $scopes,
        ]);

        return [
            'auth_url' => $this->buildAuthUrl($tenantId, $userId, $service, $scopes),
            'service' => $service,
            'scopes' => $scopes,
            'expires_in_seconds' => 600,
        ];
    }

    public function connectionUrl(int $tenantId, int $userId, string $service = 'both'): string
    {
        return $this->buildAuthUrl($tenantId, $userId, $service, $this->scopesFor($service));
    }

    public function hasScope(int $tenantId, int $userId, string $scope): bool
    {
        $status = $this->repository->status($tenantId, $userId);
        $scopes = is_array($status['scopes'] ?? null) ? $status['scopes'] : [];

        return ($status['connected'] ?? false) === true && in_array($scope, $scopes, true);
    }

    public function callback(string $code, string $state): array
    {
        $statePayload = $this->verifyState($state);
        $tenantId = (int) $statePayload['tenant_id'];
        $userId = (int) $statePayload['user_id'];
        $scopes = is_array($statePayload['scopes'] ?? null) ? $statePayload['scopes'] : [];

        try {
            $tokens = $this->exchangeCode($code);
            $grantedScopes = isset($tokens['scope']) && is_string($tokens['scope'])
                ? preg_split('/\s+/', trim($tokens['scope'])) ?: $scopes
                : $scopes;
            $expiresAt = null;
            if (isset($tokens['expires_in']) && is_numeric($tokens['expires_in'])) {
                $expiresAt = gmdate('Y-m-d H:i:s', time() + (int) $tokens['expires_in']);
            }

            $accountId = $this->repository->upsertTokens(
                $tenantId,
                $userId,
                $grantedScopes,
                null,
                $this->crypto->encrypt(isset($tokens['access_token']) ? (string) $tokens['access_token'] : null),
                $this->crypto->encrypt(isset($tokens['refresh_token']) ? (string) $tokens['refresh_token'] : null),
                $expiresAt
            );

            $this->auditLogger->log($tenantId, $userId, null, 'google.oauth.connected', [], [
                'google_oauth_account_id' => $accountId,
                'scopes' => $grantedScopes,
                'has_refresh_token' => isset($tokens['refresh_token']),
            ]);

            return [
                'connected' => true,
                'google_oauth_account_id' => $accountId,
                'calendar_connected' => in_array(self::SCOPE_CALENDAR, $grantedScopes, true),
                'gmail_connected' => in_array(self::SCOPE_GMAIL, $grantedScopes, true),
            ];
        } catch (Throwable $throwable) {
            $this->repository->markError($tenantId, $userId);
            $this->auditLogger->log($tenantId, $userId, null, 'google.oauth.failed', [], [
                'error' => $throwable->getMessage(),
            ]);
            throw $throwable;
        }
    }

    public function status(int $tenantId, int $userId): array
    {
        return $this->repository->status($tenantId, $userId);
    }

    public function disconnect(int $tenantId, int $userId): void
    {
        $this->repository->disconnect($tenantId, $userId);
        $this->auditLogger->log($tenantId, $userId, null, 'google.oauth.disconnected', [], [
            'provider' => 'google',
        ]);
    }

    /** @return array<int, string> */
    public function scopesFor(string $service): array
    {
        return match ($service) {
            'calendar' => [self::SCOPE_CALENDAR],
            'gmail' => [self::SCOPE_GMAIL],
            'both' => [self::SCOPE_CALENDAR, self::SCOPE_GMAIL],
            default => throw new InvalidArgumentException('GOOGLE_INVALID_SERVICE'),
        };
    }

    private function buildState(int $tenantId, int $userId, string $service, array $scopes): string
    {
        $payload = [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'service' => $service,
            'scopes' => $scopes,
            'nonce' => bin2hex(random_bytes(16)),
            'exp' => time() + 600,
        ];
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}');
        $signature = hash_hmac('sha256', $payloadEncoded, $this->stateSecret(), true);
        return $payloadEncoded . '.' . $this->base64UrlEncode($signature);
    }

    private function buildAuthUrl(int $tenantId, int $userId, string $service, array $scopes): string
    {
        $params = [
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $this->buildState($tenantId, $userId, $service, $scopes),
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    private function verifyState(string $state): array
    {
        $parts = explode('.', $state);
        if (count($parts) !== 2) {
            throw new RuntimeException('GOOGLE_INVALID_STATE');
        }

        [$payloadEncoded, $signatureEncoded] = $parts;
        $expected = $this->base64UrlEncode(hash_hmac('sha256', $payloadEncoded, $this->stateSecret(), true));
        if (!hash_equals($expected, $signatureEncoded)) {
            throw new RuntimeException('GOOGLE_INVALID_STATE_SIGNATURE');
        }

        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        if (!is_array($payload) || (int) ($payload['exp'] ?? 0) < time()) {
            throw new RuntimeException('GOOGLE_STATE_EXPIRED');
        }

        return $payload;
    }

    private function exchangeCode(string $code): array
    {
        $payload = [
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ];

        $body = http_build_query($payload);
        if (function_exists('curl_init')) {
            $ch = curl_init(self::TOKEN_URL);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_TIMEOUT => 15,
            ]);
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($response === false || $status < 200 || $status >= 300) {
                throw new RuntimeException('GOOGLE_TOKEN_EXCHANGE_FAILED' . ($error !== '' ? ':' . $error : ''));
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => $body,
                    'timeout' => 15,
                ],
            ]);
            $response = file_get_contents(self::TOKEN_URL, false, $context);
            if ($response === false) {
                throw new RuntimeException('GOOGLE_TOKEN_EXCHANGE_FAILED');
            }
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded) || !isset($decoded['access_token'])) {
            throw new RuntimeException('GOOGLE_TOKEN_RESPONSE_INVALID');
        }

        return $decoded;
    }

    private function clientId(): string
    {
        $value = (string) env('GOOGLE_CLIENT_ID', '');
        if ($value === '') {
            throw new RuntimeException('GOOGLE_CLIENT_ID_MISSING');
        }
        return $value;
    }

    private function clientSecret(): string
    {
        $value = (string) env('GOOGLE_CLIENT_SECRET', '');
        if ($value === '') {
            throw new RuntimeException('GOOGLE_CLIENT_SECRET_MISSING');
        }
        return $value;
    }

    private function redirectUri(): string
    {
        $value = (string) env('GOOGLE_REDIRECT_URI', '');
        if ($value === '') {
            throw new RuntimeException('GOOGLE_REDIRECT_URI_MISSING');
        }
        return $value;
    }

    private function stateSecret(): string
    {
        $secret = (string) (env('GOOGLE_TOKEN_ENCRYPTION_KEY') ?: env('APP_KEY') ?: env('JWT_SECRET') ?: '');
        if ($secret === '') {
            throw new RuntimeException('GOOGLE_STATE_SECRET_MISSING');
        }
        return $secret;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }
}
