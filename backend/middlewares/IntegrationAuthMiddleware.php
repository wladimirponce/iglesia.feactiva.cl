<?php

declare(strict_types=1);

final class IntegrationAuthMiddleware
{
    public function handle(callable $next): void
    {
        $expectedKey = (string) env('WHATSAPP_INTEGRATION_KEY', '');
        $providedKey = $this->header('X-Integration-Key');

        if ($expectedKey === '' || $providedKey === null || !hash_equals($expectedKey, $providedKey)) {
            $this->auditUnauthorized($providedKey !== null, $expectedKey !== '');
            $this->unauthorized();
            return;
        }

        $next();
    }

    private function header(string $name): ?string
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $_SERVER[$serverKey] ?? null;

        if ($value === null && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $value = $headers[$name] ?? $headers[strtolower($name)] ?? null;
        }

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function unauthorized(): void
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'INTEGRATION_UNAUTHORIZED',
                'message' => 'Integración no autorizada.',
                'details' => [],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function auditUnauthorized(bool $providedKeyPresent, bool $expectedKeyConfigured): void
    {
        try {
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
                    NULL,
                    NULL,
                    'whatsapp.identify',
                    'unauthorized',
                    'whatsapp.identity.identify',
                    'denied',
                    'integration',
                    NULL,
                    :metadata,
                    :ip_address,
                    :user_agent
                )
            ";

            $metadata = json_encode([
                'provided_key_present' => $providedKeyPresent,
                'expected_key_configured' => $expectedKeyConfigured,
                'configured_origin' => (string) env('WHATSAPP_WEBHOOK_ORIGIN', ''),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';

            $statement = Database::connection()->prepare($sql);
            $statement->execute([
                'metadata' => $metadata,
                'ip_address' => $this->ipAddress(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Throwable) {
            // Authorization failure must still return a controlled 401 if audit storage is unavailable.
        }
    }

    private function ipAddress(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        return is_string($ip) && $ip !== '' ? $ip : null;
    }
}
