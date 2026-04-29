<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/config/env.php';

$cases = require __DIR__ . '/agent_secretary_test_cases.php';

$baseUrl = rtrim((string) env('TEST_AGENT_BASE_URL', 'https://iglesia.feactiva.cl'), '/');
$endpoint = $baseUrl . '/internal/whatsapp/message.php';
$integrationKey = (string) env('WHATSAPP_INTEGRATION_KEY', '');
$testPhone = (string) env('TEST_WHATSAPP_PHONE', '');
$unknownPhone = (string) env('TEST_UNKNOWN_PHONE', '');
$environment = (string) env('APP_ENV', 'unknown');

if ($testPhone === '') {
    fwrite(STDERR, "Falta TEST_WHATSAPP_PHONE en .env\n");
    exit(1);
}

if ($integrationKey === '') {
    fwrite(STDERR, "Falta WHATSAPP_INTEGRATION_KEY en .env\n");
    exit(1);
}

$results = [];
$sequence = 1;

foreach ($cases as $case) {
    $phone = isset($case['phone_env']) && $case['phone_env'] === 'TEST_UNKNOWN_PHONE' ? $unknownPhone : $testPhone;
    if (($case['skip_if_missing_phone'] ?? false) === true && $phone === '') {
        $results[] = skippedResult($sequence++, $case, 'TEST_UNKNOWN_PHONE no esta definido en .env');
        continue;
    }

    $payload = [
        'phone' => $phone,
        'message_text' => (string) ($case['message_text'] ?? ''),
        'provider_message_id' => 'qa-agent-secretary-' . date('YmdHis') . '-' . $sequence,
    ];
    if (isset($case['payload']) && is_array($case['payload'])) {
        $payload = array_merge($payload, $case['payload']);
    }

    $auth = (string) ($case['auth'] ?? 'valid');
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($auth === 'valid') {
        $headers[] = 'X-Integration-Key: ' . $integrationKey;
    } elseif ($auth === 'invalid') {
        $headers[] = 'X-Integration-Key: invalid-test-key';
    }

    $http = postJson($endpoint, $payload, $headers);
    $decoded = json_decode($http['body'], true);
    $decoded = is_array($decoded) ? $decoded : null;
    $assertion = evaluateAssertions($case['assertions'] ?? [], $http['status'], $decoded);

    $results[] = [
        'number' => $sequence++,
        'name' => (string) $case['name'],
        'payload' => sanitizePayload($payload),
        'http_status' => $http['status'],
        'success' => is_array($decoded) ? ($decoded['success'] ?? null) : null,
        'found' => is_array($decoded) ? ($decoded['data']['found'] ?? null) : null,
        'response_text' => is_array($decoded) ? ($decoded['data']['response_text'] ?? null) : null,
        'agent_request_id' => is_array($decoded) ? ($decoded['data']['agent_request_id'] ?? null) : null,
        'expected' => (string) ($case['expected'] ?? ''),
        'result' => $assertion['result'],
        'observation' => $assertion['observation'],
        'response_json' => sanitizeResponse($decoded, $http['body']),
    ];
}

$reportPath = writeReport($results, $baseUrl, $testPhone, $environment);
echo "Reporte generado: {$reportPath}" . PHP_EOL;

function postJson(string $url, array $payload, array $headers): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        $body = json_encode(['curl_error' => $error], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    return ['status' => $status, 'body' => (string) $body, 'curl_error' => $error];
}

function evaluateAssertions(array $assertions, int $httpStatus, ?array $response): array
{
    $failures = [];
    foreach ($assertions as $assertion) {
        $ok = match ($assertion) {
            'http_200' => $httpStatus === 200,
            'http_401' => $httpStatus === 401,
            'success_true' => ($response['success'] ?? null) === true,
            'success_false' => ($response['success'] ?? null) === false,
            'found_true' => ($response['data']['found'] ?? null) === true,
            'found_false' => ($response['data']['found'] ?? null) === false,
            'response_text_not_empty' => trim((string) ($response['data']['response_text'] ?? '')) !== '',
            'response_mode_audio' => ($response['data']['response_mode'] ?? null) === 'audio',
            'integration_unauthorized' => isIntegrationUnauthorized($response),
            default => true,
        };

        if (!$ok) {
            $failures[] = $assertion;
        }
    }

    if ($failures === []) {
        return ['result' => 'PASS', 'observation' => 'Condiciones esperadas cumplidas.'];
    }

    return ['result' => 'FAIL', 'observation' => 'Fallaron asserts: ' . implode(', ', $failures)];
}

function isIntegrationUnauthorized(?array $response): bool
{
    if ($response === null) {
        return false;
    }

    $error = $response['error'] ?? [];
    if (!is_array($error)) {
        return false;
    }

    $code = (string) ($error['code'] ?? '');
    $message = (string) ($error['message'] ?? '');
    $details = $error['details'] ?? [];
    $encodedDetails = is_array($details) ? json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';

    return str_contains($code, 'INTEGRATION_UNAUTHORIZED')
        || str_contains($message, 'Integracion no autorizada')
        || str_contains((string) $encodedDetails, 'INTEGRATION_UNAUTHORIZED');
}

function skippedResult(int $number, array $case, string $reason): array
{
    return [
        'number' => $number,
        'name' => (string) $case['name'],
        'payload' => [],
        'http_status' => null,
        'success' => null,
        'found' => null,
        'response_text' => null,
        'agent_request_id' => null,
        'expected' => (string) ($case['expected'] ?? ''),
        'result' => 'WARN',
        'observation' => $reason,
        'response_json' => null,
    ];
}

function writeReport(array $results, string $baseUrl, string $testPhone, string $environment): string
{
    $dir = __DIR__ . '/results';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $timestamp = date('Ymd_His');
    $path = $dir . '/agent_secretary_test_report_' . $timestamp . '.md';
    $pass = count(array_filter($results, static fn (array $r): bool => $r['result'] === 'PASS'));
    $fail = count(array_filter($results, static fn (array $r): bool => $r['result'] === 'FAIL'));
    $warn = count(array_filter($results, static fn (array $r): bool => $r['result'] === 'WARN'));

    $lines = [];
    $lines[] = '# Reporte QA Agente Secretario';
    $lines[] = '';
    $lines[] = 'Fecha: ' . date('Y-m-d H:i:s');
    $lines[] = 'Ambiente: ' . $environment;
    $lines[] = 'Base URL: ' . $baseUrl;
    $lines[] = 'Telefono de prueba: ' . maskPhone($testPhone);
    $lines[] = 'Total pruebas: ' . count($results);
    $lines[] = 'PASS: ' . $pass;
    $lines[] = 'FAIL: ' . $fail;
    $lines[] = 'WARN: ' . $warn;
    $lines[] = '';
    $lines[] = '## Resumen ejecutivo';
    $lines[] = '';
    $lines[] = '| # | Caso | HTTP | Success | Resultado | Observacion |';
    $lines[] = '|---:|---|---:|---|---|---|';
    foreach ($results as $result) {
        $lines[] = sprintf(
            '| %d | %s | %s | %s | %s | %s |',
            $result['number'],
            mdEscape($result['name']),
            $result['http_status'] === null ? '-' : (string) $result['http_status'],
            boolText($result['success']),
            $result['result'],
            mdEscape($result['observation'])
        );
    }

    $lines[] = '';
    $lines[] = '## Detalle por prueba';
    foreach ($results as $result) {
        $lines[] = '';
        $lines[] = '### Caso ' . $result['number'] . ' - ' . $result['name'];
        $lines[] = '';
        $lines[] = 'Input:';
        $lines[] = '```json';
        $lines[] = json_encode($result['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        $lines[] = '```';
        $lines[] = '';
        $lines[] = '- HTTP: ' . ($result['http_status'] === null ? '-' : (string) $result['http_status']);
        $lines[] = '- Success: ' . boolText($result['success']);
        $lines[] = '- Found: ' . boolText($result['found']);
        $lines[] = '- Response text: ' . ($result['response_text'] === null ? '-' : (string) $result['response_text']);
        $lines[] = '- Agent request id: ' . ($result['agent_request_id'] === null ? '-' : (string) $result['agent_request_id']);
        $lines[] = '- Resultado esperado: ' . $result['expected'];
        $lines[] = '- Resultado: ' . $result['result'];
        $lines[] = '- Observaciones: ' . $result['observation'];
        $lines[] = '';
        $lines[] = 'JSON respuesta sanitizado:';
        $lines[] = '```json';
        $lines[] = json_encode($result['response_json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'null';
        $lines[] = '```';
    }

    file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
    return $path;
}

function sanitizePayload(array $payload): array
{
    if (isset($payload['phone'])) {
        $payload['phone'] = maskPhone((string) $payload['phone']);
    }
    return $payload;
}

function sanitizeResponse(?array $decoded, string $raw): mixed
{
    if ($decoded === null) {
        return ['raw' => substr($raw, 0, 2000)];
    }
    return sanitizeRecursive($decoded);
}

function sanitizeRecursive(mixed $value): mixed
{
    if (!is_array($value)) {
        return $value;
    }
    $out = [];
    foreach ($value as $key => $item) {
        $lower = strtolower((string) $key);
        if (str_contains($lower, 'token') || str_contains($lower, 'key') || str_contains($lower, 'secret')) {
            $out[$key] = '[REDACTED]';
            continue;
        }
        if (in_array($lower, ['phone', 'whatsapp_phone', 'recipient_phone'], true)) {
            $out[$key] = maskPhone((string) $item);
            continue;
        }
        $out[$key] = sanitizeRecursive($item);
    }
    return $out;
}

function maskPhone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    if (!is_string($digits) || strlen($digits) < 4) {
        return '****';
    }
    return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
}

function boolText(mixed $value): string
{
    if ($value === true) {
        return 'true';
    }
    if ($value === false) {
        return 'false';
    }
    return '-';
}

function mdEscape(string $value): string
{
    return str_replace('|', '\\|', $value);
}
