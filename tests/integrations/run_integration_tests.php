<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/config/env.php';
require_once dirname(__DIR__, 2) . '/backend/core/Database.php';
require_once dirname(__DIR__, 2) . '/backend/modules/agenda/AgendaAuditLogger.php';
require_once dirname(__DIR__, 2) . '/backend/modules/agenda/AgendaRepository.php';
require_once dirname(__DIR__, 2) . '/backend/modules/agenda/AgendaService.php';
require_once dirname(__DIR__, 2) . '/backend/modules/integrations/contracts/CalendarProviderInterface.php';
require_once dirname(__DIR__, 2) . '/backend/modules/integrations/contracts/EmailSenderInterface.php';
require_once dirname(__DIR__, 2) . '/backend/modules/integrations/contracts/WhatsAppSenderInterface.php';
require_once dirname(__DIR__, 2) . '/backend/modules/integrations/adapters/GoogleCalendarProviderStub.php';
require_once dirname(__DIR__, 2) . '/backend/modules/integrations/adapters/EmailSenderStub.php';
require_once dirname(__DIR__, 2) . '/backend/modules/integrations/adapters/WhatsAppSenderStub.php';
require_once dirname(__DIR__, 2) . '/backend/modules/integrations/google/GoogleCalendarRepository.php';
require_once dirname(__DIR__, 2) . '/backend/modules/integrations/google/GoogleCalendarService.php';
require_once dirname(__DIR__, 2) . '/backend/modules/integrations/google/GoogleTokenCrypto.php';
require_once dirname(__DIR__, 2) . '/backend/modules/integrations/google/GoogleOAuthRepository.php';
require_once dirname(__DIR__, 2) . '/backend/modules/integrations/google/GoogleOAuthService.php';

$cases = require __DIR__ . '/integration_test_cases.php';

$baseUrl = rtrim((string) env('TEST_AGENT_BASE_URL', 'https://iglesia.feactiva.cl'), '/');
$authEmail = (string) env('TEST_AUTH_EMAIL', '');
$authPassword = (string) env('TEST_AUTH_PASSWORD', '');
$tenantId = (int) env('TEST_TENANT_ID', 1);
$userId = (int) env('TEST_USER_ID', 1);
$testEmail = (string) env('TEST_INTEGRATION_EMAIL', 'qa@example.com');
$externalPhone = (string) env('TEST_EXTERNAL_PHONE', '+56958359091');
$whatsAppPhone = (string) env('TEST_WHATSAPP_PHONE', '');
$integrationKey = (string) env('WHATSAPP_INTEGRATION_KEY', '');
$environment = (string) env('APP_ENV', 'unknown');

$context = [
    'token' => null,
    'tenant_id' => $tenantId,
    'user_id' => $userId,
    'calendar_account_id' => null,
    'meeting_item_id' => null,
];

$results = [];
$sequence = 1;

foreach ($cases as $case) {
    $result = runCase($case, $sequence, $context);
    $results[] = $result;
    $sequence++;
}

$reportPath = writeReport($results, $baseUrl, $environment);
echo "Reporte generado: {$reportPath}" . PHP_EOL;

function runCase(array $case, int $number, array &$context): array
{
    $startedAt = microtime(true);
    try {
        if (($case['type'] ?? '') === 'login') {
            return withTiming(runLoginCase($case, $number, $context), $startedAt);
        }

        if (($case['type'] ?? '') === 'http') {
            return withTiming(runHttpCase($case, $number, $context), $startedAt);
        }

        if (($case['type'] ?? '') === 'whatsapp_audio_http') {
            return withTiming(runWhatsAppAudioCase($case, $number), $startedAt);
        }

        if (($case['type'] ?? '') === 'agent_meeting_oauth_link') {
            return withTiming(runAgentMeetingOAuthLinkCase($case, $number, $context), $startedAt);
        }

        return withTiming(runDirectCase($case, $number, $context), $startedAt);
    } catch (Throwable $exception) {
        return withTiming([
            'number' => $number,
            'name' => (string) $case['name'],
            'type' => (string) ($case['type'] ?? 'unknown'),
            'http_status' => null,
            'success' => false,
            'expected' => (string) ($case['expected'] ?? ''),
            'result' => 'FAIL',
            'observation' => $exception->getMessage(),
            'input' => [],
            'response_json' => [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => basename($exception->getFile()),
                'line' => $exception->getLine(),
            ],
        ], $startedAt);
    }
}

function runLoginCase(array $case, int $number, array &$context): array
{
    global $baseUrl, $authEmail, $authPassword;

    if ($authEmail === '' || $authPassword === '') {
        return skipped($number, $case, 'TEST_AUTH_EMAIL/TEST_AUTH_PASSWORD no estan configurados.');
    }

    $payload = ['email' => $authEmail, 'password' => $authPassword];
    $http = request('POST', $baseUrl . '/api/v1/auth/login', $payload);
    $decoded = decodeJson($http['body']);

    if (($decoded['success'] ?? null) === true && isset($decoded['data']['token'])) {
        $context['token'] = (string) $decoded['data']['token'];
        $context['tenant_id'] = (int) ($decoded['data']['tenant_id'] ?? $context['tenant_id']);
        $context['user_id'] = (int) ($decoded['data']['user']['id'] ?? $context['user_id']);
    }

    return resultFromHttp($number, $case, $http, $decoded, ['email' => $authEmail, 'password' => '[REDACTED]']);
}

function runHttpCase(array $case, int $number, array &$context): array
{
    global $baseUrl;

    if (($case['auth'] ?? false) === true && empty($context['token'])) {
        return skipped($number, $case, 'No hay token auth; configura TEST_AUTH_EMAIL/TEST_AUTH_PASSWORD o revisa el login.');
    }

    $headers = [];
    if (($case['auth'] ?? false) === true) {
        $headers[] = 'Authorization: Bearer ' . $context['token'];
    }

    $http = request((string) ($case['method'] ?? 'GET'), $baseUrl . (string) $case['path'], null, $headers);
    $decoded = decodeJson($http['body']);
    return resultFromHttp($number, $case, $http, $decoded, ['path' => $case['path']]);
}

function runWhatsAppAudioCase(array $case, int $number): array
{
    global $baseUrl, $whatsAppPhone, $integrationKey;

    if ($whatsAppPhone === '' || $integrationKey === '') {
        return skipped($number, $case, 'TEST_WHATSAPP_PHONE o WHATSAPP_INTEGRATION_KEY no estan configurados.');
    }

    $payload = [
        'phone' => $whatsAppPhone,
        'message_text' => '',
        'message_type' => 'audio',
        'media_url' => 'https://example.com/audio-test.ogg',
        'provider_message_id' => 'qa-integrations-audio-' . date('YmdHis'),
    ];

    $http = request('POST', $baseUrl . '/internal/whatsapp/message.php', $payload, [
        'X-Integration-Key: ' . $integrationKey,
    ]);
    $decoded = decodeJson($http['body']);
    return resultFromHttp($number, $case, $http, $decoded, sanitizePayload($payload));
}

function runDirectCase(array $case, int $number, array &$context): array
{
    global $testEmail, $externalPhone;

    $type = (string) $case['type'];
    $response = match ($type) {
        'google_auth_link_direct' => createGoogleAuthLinkDirect($context),
        'calendar_placeholder' => createCalendarPlaceholder($context, $testEmail),
        'calendar_meeting' => createCalendarMeeting($context),
        'calendar_cancel' => cancelCalendarMeeting($context),
        'email_stub_direct' => sendEmailDirect($testEmail),
        'email_notification_future' => createEmailNotification($context, $testEmail, false),
        'email_notification_due' => createEmailNotification($context, $testEmail, true),
        'whatsapp_notification_future' => createWhatsAppNotification($context, $externalPhone),
        'whatsapp_invalid_phone' => createInvalidWhatsAppNotification($context),
        default => throw new RuntimeException('Tipo de prueba no soportado: ' . $type),
    };

    $passed = ($response['success'] ?? false) === true;
    if (($response['result'] ?? null) === 'WARN') {
        return [
            'number' => $number,
            'name' => (string) $case['name'],
            'type' => $type,
            'http_status' => null,
            'success' => null,
            'expected' => (string) ($case['expected'] ?? ''),
            'result' => 'WARN',
            'observation' => (string) ($response['warning'] ?? 'Prueba omitida.'),
            'input' => sanitizeRecursive($response['input'] ?? []),
            'response_json' => sanitizeRecursive($response),
        ];
    }

    return [
        'number' => $number,
        'name' => (string) $case['name'],
        'type' => $type,
        'http_status' => null,
        'success' => $passed,
        'expected' => (string) ($case['expected'] ?? ''),
        'result' => $passed ? 'PASS' : 'FAIL',
        'observation' => $passed ? 'Condiciones esperadas cumplidas.' : (string) ($response['error'] ?? 'Fallo directo.'),
        'input' => sanitizeRecursive($response['input'] ?? []),
        'response_json' => sanitizeRecursive($response),
    ];
}

function createGoogleAuthLinkDirect(array $context): array
{
    try {
        $oauth = new GoogleOAuthService(new GoogleOAuthRepository(), new GoogleTokenCrypto(), new AgendaAuditLogger());
        $url = $oauth->connectionUrl((int) $context['tenant_id'], (int) $context['user_id'], 'calendar');
        $decodedUrl = urldecode($url);

        return [
            'success' => str_starts_with($url, 'https://accounts.google.com/o/oauth2/v2/auth?')
                && str_contains($decodedUrl, GoogleOAuthService::SCOPE_CALENDAR)
                && str_contains($url, 'access_type=offline')
                && str_contains($url, 'prompt=consent')
                && str_contains($url, 'state='),
            'auth_url' => $url,
            'input' => [
                'tenant_id' => (int) $context['tenant_id'],
                'user_id' => (int) $context['user_id'],
                'service' => 'calendar',
            ],
        ];
    } catch (Throwable $exception) {
        return [
            'result' => 'WARN',
            'warning' => 'No se pudo generar auth_url. Configura GOOGLE_CLIENT_ID, GOOGLE_REDIRECT_URI y GOOGLE_TOKEN_ENCRYPTION_KEY/JWT_SECRET. Error: ' . $exception->getMessage(),
        ];
    }
}

function runAgentMeetingOAuthLinkCase(array $case, int $number, array $context): array
{
    global $baseUrl, $whatsAppPhone, $integrationKey;

    if ($whatsAppPhone === '' || $integrationKey === '') {
        return skipped($number, $case, 'TEST_WHATSAPP_PHONE o WHATSAPP_INTEGRATION_KEY no estan configurados.');
    }

    revokeGoogleOAuthForUser((int) $context['tenant_id'], (int) $context['user_id']);
    clearConversationStateForPhone($whatsAppPhone);

    $payload = [
        'phone' => $whatsAppPhone,
        'message_text' => 'agenda reunion QA OAuth manana a las 19',
        'provider_message_id' => 'qa-integrations-oauth-link-' . date('YmdHis'),
    ];

    $http = request('POST', $baseUrl . '/internal/whatsapp/message.php', $payload, [
        'X-Integration-Key: ' . $integrationKey,
    ]);
    $decoded = decodeJson($http['body']);
    $base = resultFromHttp($number, $case, $http, $decoded, sanitizePayload($payload));
    if ($base['result'] !== 'PASS') {
        return $base;
    }

    $text = (string) ($decoded['data']['response_text'] ?? '');
    $hasOAuthLink = str_contains($text, 'https://accounts.google.com/o/oauth2/v2/auth?');
    $hasConfigWarning = str_contains($text, 'falta configurar la conexion OAuth de Google');

    if (!$hasOAuthLink && !$hasConfigWarning) {
        $base['result'] = 'FAIL';
        $base['observation'] = 'La respuesta no incluyo auth_url ni aviso de configuracion OAuth faltante.';
        return $base;
    }

    $base['observation'] = $hasOAuthLink
        ? 'Respuesta incluye auth_url OAuth de Google.'
        : 'Respuesta incluye aviso controlado de configuracion OAuth faltante.';

    return $base;
}

function createCalendarPlaceholder(array &$context, string $email): array
{
    $service = calendarService();
    $id = $service->connectAccountPlaceholder((int) $context['tenant_id'], (int) $context['user_id'], $email);
    $context['calendar_account_id'] = $id;

    return [
        'success' => $id > 0,
        'calendar_account_id' => $id,
        'input' => ['email' => $email],
    ];
}

function revokeGoogleOAuthForUser(int $tenantId, int $userId): void
{
    try {
        (new GoogleOAuthRepository())->disconnect($tenantId, $userId);
    } catch (Throwable) {
        // La prueba de respuesta del agente no debe fallar por limpieza previa.
    }
}

function clearConversationStateForPhone(string $phone): void
{
    $digits = preg_replace('/\D+/', '', $phone);
    if (!is_string($digits) || $digits === '') {
        return;
    }

    $variants = array_values(array_unique(array_filter([
        $phone,
        $digits,
        '+' . $digits,
        strlen($digits) > 9 ? substr($digits, -9) : null,
    ])));
    $placeholders = [];
    $params = [];
    foreach ($variants as $index => $variant) {
        $key = 'phone_' . $index;
        $placeholders[] = ':' . $key;
        $params[$key] = $variant;
    }

    try {
        $statement = Database::connection()->prepare("
            UPDATE agent_conversation_state
            SET status = 'cancelled',
                updated_at = NOW()
            WHERE status = 'active'
              AND deleted_at IS NULL
              AND phone IN (" . implode(', ', $placeholders) . ")
        ");
        $statement->execute($params);
    } catch (Throwable) {
        // Limpieza defensiva para aislar la prueba.
    }
}

function createCalendarMeeting(array &$context): array
{
    ensureCalendarAccount($context);
    $agenda = agendaService();
    $id = $agenda->create((int) $context['tenant_id'], (int) $context['user_id'], [
        'tipo' => 'meeting',
        'titulo' => 'QA Google Calendar Stub',
        'descripcion' => 'Reunion creada por runner QA.',
        'fecha_inicio' => gmdate('Y-m-d H:i:s', time() + 86400),
        'fecha_fin' => gmdate('Y-m-d H:i:s', time() + 90000),
        'modulo_origen' => 'qa_integrations',
    ]);
    $context['meeting_item_id'] = $id;

    $event = latestCalendarEvent((int) $context['tenant_id'], $id);

    return [
        'success' => $id > 0 && ($event['sync_status'] ?? null) === 'synced' && str_starts_with((string) ($event['external_event_id'] ?? ''), 'stub-gcal-'),
        'agenda_item_id' => $id,
        'calendar_event' => $event,
    ];
}

function cancelCalendarMeeting(array &$context): array
{
    if (empty($context['meeting_item_id'])) {
        createCalendarMeeting($context);
    }

    agendaService()->cancel((int) $context['tenant_id'], (int) $context['user_id'], (int) $context['meeting_item_id']);
    $event = latestCalendarEvent((int) $context['tenant_id'], (int) $context['meeting_item_id']);

    return [
        'success' => ($event['sync_status'] ?? null) === 'cancelled',
        'agenda_item_id' => $context['meeting_item_id'],
        'calendar_event' => $event,
    ];
}

function sendEmailDirect(string $email): array
{
    $response = (new EmailSenderStub())->send($email, 'QA Email Stub', 'Mensaje QA de integraciones.', [
        'source' => 'integration_test_runner',
    ]);
    return ['success' => ($response['success'] ?? false) === true && ($response['simulated'] ?? false) === true] + $response;
}

function createEmailNotification(array &$context, string $email, bool $dueNow): array
{
    $agenda = agendaService();
    $itemId = $agenda->create((int) $context['tenant_id'], (int) $context['user_id'], [
        'tipo' => 'task',
        'titulo' => $dueNow ? 'QA Email Due' : 'QA Email Future',
        'descripcion' => 'Item QA para email stub.',
        'fecha_inicio' => gmdate('Y-m-d H:i:s', time() + 3600),
        'modulo_origen' => 'qa_integrations',
    ]);

    $notificationId = $agenda->createNotification((int) $context['tenant_id'], (int) $context['user_id'], $itemId, [
        'channel' => 'email',
        'recipient_type' => 'email',
        'recipient_email' => $email,
        'message_text' => 'Mensaje QA email stub.',
        'scheduled_at' => $dueNow ? gmdate('Y-m-d H:i:s', time() - 60) : gmdate('Y-m-d H:i:s', time() + 86400),
    ]);

    $notification = findNotification((int) $context['tenant_id'], $notificationId);
    $expectedStatus = $dueNow ? 'sent' : 'scheduled';
    $expectedDelivery = $dueNow ? 'sent' : 'pending';

    return [
        'success' => ($notification['status'] ?? null) === $expectedStatus && ($notification['delivery_status'] ?? null) === $expectedDelivery,
        'agenda_item_id' => $itemId,
        'notification' => $notification,
    ];
}

function createWhatsAppNotification(array &$context, string $phone): array
{
    $agenda = agendaService();
    $itemId = $agenda->create((int) $context['tenant_id'], (int) $context['user_id'], [
        'tipo' => 'whatsapp_send',
        'titulo' => 'QA WhatsApp Future',
        'descripcion' => 'Item QA para WhatsApp programado.',
        'fecha_inicio' => gmdate('Y-m-d H:i:s', time() + 3600),
        'modulo_origen' => 'qa_integrations',
    ]);

    $notificationId = $agenda->createNotification((int) $context['tenant_id'], (int) $context['user_id'], $itemId, [
        'channel' => 'whatsapp',
        'recipient_type' => 'phone',
        'recipient_phone' => $phone,
        'message_text' => 'Mensaje QA WhatsApp scheduled.',
        'scheduled_at' => gmdate('Y-m-d H:i:s', time() + 86400),
    ]);

    $notification = findNotification((int) $context['tenant_id'], $notificationId);
    return [
        'success' => ($notification['status'] ?? null) === 'scheduled' && ($notification['delivery_status'] ?? null) === 'pending',
        'agenda_item_id' => $itemId,
        'notification' => $notification,
    ];
}

function createInvalidWhatsAppNotification(array &$context): array
{
    try {
        $agenda = agendaService();
        $itemId = $agenda->create((int) $context['tenant_id'], (int) $context['user_id'], [
            'tipo' => 'whatsapp_send',
            'titulo' => 'QA WhatsApp Invalid',
            'descripcion' => 'Item QA para validacion E.164.',
            'fecha_inicio' => gmdate('Y-m-d H:i:s', time() + 3600),
            'modulo_origen' => 'qa_integrations',
        ]);
        $agenda->createNotification((int) $context['tenant_id'], (int) $context['user_id'], $itemId, [
            'channel' => 'whatsapp',
            'recipient_type' => 'phone',
            'recipient_phone' => '123',
            'message_text' => 'No debe crearse.',
            'scheduled_at' => gmdate('Y-m-d H:i:s', time() + 86400),
        ]);
    } catch (RuntimeException $exception) {
        return [
            'success' => $exception->getMessage() === 'AGENDA_INVALID_E164_PHONE',
            'blocked_with' => $exception->getMessage(),
        ];
    }

    return ['success' => false, 'error' => 'No bloqueo telefono invalido.'];
}

function ensureCalendarAccount(array &$context): void
{
    if (!empty($context['calendar_account_id'])) {
        return;
    }
    createCalendarPlaceholder($context, (string) env('TEST_INTEGRATION_EMAIL', 'qa@example.com'));
}

function calendarService(): GoogleCalendarService
{
    return new GoogleCalendarService(new GoogleCalendarRepository(), new GoogleCalendarProviderStub(), new AgendaAuditLogger());
}

function agendaService(): AgendaService
{
    return new AgendaService(new AgendaRepository(), new AgendaAuditLogger());
}

function latestCalendarEvent(int $tenantId, int $agendaItemId): ?array
{
    $statement = Database::connection()->prepare("
        SELECT id, tenant_id, agenda_item_id, calendar_account_id, provider, external_event_id, sync_status, last_error, created_at, updated_at
        FROM calendar_events
        WHERE tenant_id = :tenant_id
          AND agenda_item_id = :agenda_item_id
        ORDER BY id DESC
        LIMIT 1
    ");
    $statement->execute(['tenant_id' => $tenantId, 'agenda_item_id' => $agendaItemId]);
    $row = $statement->fetch();
    return $row === false ? null : $row;
}

function findNotification(int $tenantId, int $notificationId): ?array
{
    $statement = Database::connection()->prepare("
        SELECT id, tenant_id, agenda_item_id, channel, recipient_type, recipient_phone, recipient_email, external_provider, external_message_id, message_text, scheduled_at, sent_at, status, delivery_status, attempts, delivery_response_json, created_at, updated_at
        FROM agenda_notifications
        WHERE tenant_id = :tenant_id
          AND id = :id
        LIMIT 1
    ");
    $statement->execute(['tenant_id' => $tenantId, 'id' => $notificationId]);
    $row = $statement->fetch();
    return $row === false ? null : $row;
}

function request(string $method, string $url, ?array $payload = null, array $headers = []): array
{
    $headers[] = 'Accept: application/json';
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        $body = json_encode(['curl_error' => $error], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    return ['status' => $status, 'body' => (string) $body, 'curl_error' => $error];
}

function resultFromHttp(int $number, array $case, array $http, ?array $decoded, array $input): array
{
    $assertion = evaluateAssertions($case['assertions'] ?? [], $http['status'], $decoded);
    return [
        'number' => $number,
        'name' => (string) $case['name'],
        'type' => (string) ($case['type'] ?? 'http'),
        'http_status' => $http['status'],
        'success' => $decoded['success'] ?? null,
        'expected' => (string) ($case['expected'] ?? ''),
        'result' => $assertion['result'],
        'observation' => $assertion['observation'],
        'input' => sanitizeRecursive($input),
        'response_json' => sanitizeRecursive($decoded ?? ['raw' => substr($http['body'], 0, 2000)]),
    ];
}

function evaluateAssertions(array $assertions, int $httpStatus, ?array $response): array
{
    $failures = [];
    foreach ($assertions as $assertion) {
        $authUrl = (string) ($response['data']['auth_url'] ?? '');
        $ok = match ($assertion) {
            'http_200' => $httpStatus === 200,
            'http_422' => $httpStatus === 422,
            'success_true' => ($response['success'] ?? null) === true,
            'success_false' => ($response['success'] ?? null) === false,
            'token_present' => is_string($response['data']['token'] ?? null) && (string) $response['data']['token'] !== '',
            'auth_url_present' => str_starts_with($authUrl, 'https://accounts.google.com/o/oauth2/v2/auth?'),
            'auth_url_calendar_scope' => str_contains(urldecode($authUrl), 'https://www.googleapis.com/auth/calendar.events'),
            'auth_url_gmail_scope' => str_contains(urldecode($authUrl), 'https://www.googleapis.com/auth/gmail.send'),
            'auth_url_offline' => str_contains($authUrl, 'access_type=offline'),
            'auth_url_prompt_consent' => str_contains($authUrl, 'prompt=consent'),
            'response_mode_audio' => ($response['data']['response_mode'] ?? null) === 'audio',
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

function skipped(int $number, array $case, string $reason): array
{
    return [
        'number' => $number,
        'name' => (string) $case['name'],
        'type' => (string) ($case['type'] ?? 'unknown'),
        'http_status' => null,
        'success' => null,
        'expected' => (string) ($case['expected'] ?? ''),
        'result' => 'WARN',
        'observation' => $reason,
        'input' => [],
        'response_json' => null,
    ];
}

function withTiming(array $result, float $startedAt): array
{
    $result['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
    return $result;
}

function decodeJson(string $body): ?array
{
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

function writeReport(array $results, string $baseUrl, string $environment): string
{
    $dir = __DIR__ . '/results';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $path = $dir . '/integration_test_report_' . date('Ymd_His') . '.md';
    $pass = count(array_filter($results, static fn (array $row): bool => $row['result'] === 'PASS'));
    $fail = count(array_filter($results, static fn (array $row): bool => $row['result'] === 'FAIL'));
    $warn = count(array_filter($results, static fn (array $row): bool => $row['result'] === 'WARN'));

    $lines = [
        '# Reporte QA Integraciones',
        '',
        'Fecha: ' . date('Y-m-d H:i:s'),
        'Ambiente: ' . $environment,
        'Base URL: ' . $baseUrl,
        'Total pruebas: ' . count($results),
        'PASS: ' . $pass,
        'FAIL: ' . $fail,
        'WARN: ' . $warn,
        '',
        '## Resumen ejecutivo',
        '',
        '| # | Caso | Tipo | HTTP | Success | Resultado | Observacion |',
        '|---:|---|---|---:|---|---|---|',
    ];

    foreach ($results as $result) {
        $lines[] = sprintf(
            '| %d | %s | %s | %s | %s | %s | %s |',
            $result['number'],
            mdEscape($result['name']),
            mdEscape($result['type']),
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
        $lines[] = '- Tipo: ' . $result['type'];
        $lines[] = '- HTTP: ' . ($result['http_status'] === null ? '-' : (string) $result['http_status']);
        $lines[] = '- Success: ' . boolText($result['success']);
        $lines[] = '- Resultado esperado: ' . $result['expected'];
        $lines[] = '- Resultado: ' . $result['result'];
        $lines[] = '- Observaciones: ' . $result['observation'];
        $lines[] = '- Duracion ms: ' . ($result['duration_ms'] ?? '-');
        $lines[] = '';
        $lines[] = 'Input sanitizado:';
        $lines[] = '```json';
        $lines[] = json_encode($result['input'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        $lines[] = '```';
        $lines[] = '';
        $lines[] = 'Respuesta sanitizada:';
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

function sanitizeRecursive(mixed $value): mixed
{
    if (!is_array($value)) {
        return $value;
    }

    $out = [];
    foreach ($value as $key => $item) {
        $lower = strtolower((string) $key);
        if (str_contains($lower, 'token') || str_contains($lower, 'secret') || str_contains($lower, 'key') || $lower === 'authorization') {
            $out[$key] = '[REDACTED]';
            continue;
        }
        if (str_contains($lower, 'phone')) {
            $out[$key] = is_string($item) ? maskPhone($item) : $item;
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
