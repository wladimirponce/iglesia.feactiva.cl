<?php

declare(strict_types=1);

final class Response
{
    public static function success(array $data = [], ?string $message = null, array $meta = [], int $status = 200): void
    {
        self::json([
            'success' => true,
            'data' => $data,
            'meta' => $meta === [] ? new stdClass() : $meta,
            'message' => $message,
        ], $status);
    }

    public static function error(string $code, string $message, array $details = [], int $status = 400): void
    {
        if (self::looksSensitive($code) || self::looksSensitive($message)) {
            $code = 'INTERNAL_SERVER_ERROR';
            $message = 'Error interno del servidor.';
            $details = [];
            $status = 500;
        } elseif (in_array($status, [401, 403, 404, 409, 422], true)) {
            $standardCode = self::standardCodeForStatus($status);
            if ($code !== $standardCode) {
                $details = self::withReason($details, $code);
                $code = $standardCode;
            }
        }

        self::json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }

    private static function json(array $payload, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function standardCodeForStatus(int $status): string
    {
        return match ($status) {
            401 => 'UNAUTHENTICATED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'VALIDATION_ERROR',
            default => 'ERROR',
        };
    }

    private static function withReason(array $details, string $reason): array
    {
        $details[] = ['field' => 'reason', 'message' => $reason];
        return $details;
    }

    private static function looksSensitive(string $value): bool
    {
        return preg_match('/SQLSTATE|stack trace|PDOException|mysqli|database|constraint|syntax error/i', $value) === 1;
    }
}
