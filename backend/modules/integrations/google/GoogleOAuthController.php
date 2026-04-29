<?php

declare(strict_types=1);

final class GoogleOAuthController
{
    private GoogleOAuthValidator $validator;
    private GoogleOAuthService $service;

    public function __construct()
    {
        $this->validator = new GoogleOAuthValidator();
        $this->service = new GoogleOAuthService(
            new GoogleOAuthRepository(),
            new GoogleTokenCrypto(),
            new AgendaAuditLogger()
        );
    }

    public function authUrl(): void
    {
        $errors = $this->validator->validateAuthUrl($_GET);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            Response::success($this->service->authUrl($this->tenantId(), $this->userId(), (string) $_GET['service']));
        } catch (Throwable $throwable) {
            $this->auditFailure('google.oauth.failed', $throwable);
            Response::error('GOOGLE_AUTH_URL_ERROR', 'No fue posible generar la URL de autorizacion.', [], 500);
        }
    }

    public function callback(): void
    {
        $code = is_string($_GET['code'] ?? null) ? trim((string) $_GET['code']) : '';
        $state = is_string($_GET['state'] ?? null) ? trim((string) $_GET['state']) : '';
        if ($code === '' || $state === '') {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', [
                ['field' => 'code/state', 'message' => 'Code y state son requeridos.'],
            ], 422);
            return;
        }

        try {
            Response::success($this->service->callback($code, $state), 'Cuenta Google conectada.');
        } catch (Throwable) {
            Response::error('GOOGLE_OAUTH_CALLBACK_ERROR', 'No fue posible conectar la cuenta Google.', [], 500);
        }
    }

    public function status(): void
    {
        Response::success($this->service->status($this->tenantId(), $this->userId()));
    }

    public function disconnect(): void
    {
        try {
            $this->service->disconnect($this->tenantId(), $this->userId());
            Response::success(['disconnected' => true]);
        } catch (Throwable $throwable) {
            $this->auditFailure('google.oauth.failed', $throwable);
            Response::error('GOOGLE_DISCONNECT_ERROR', 'No fue posible desconectar la cuenta Google.', [], 500);
        }
    }

    private function tenantId(): int
    {
        return (int) AuthContext::tenantId();
    }

    private function userId(): int
    {
        return (int) AuthContext::userId();
    }

    private function auditFailure(string $eventType, Throwable $throwable): void
    {
        try {
            (new AgendaAuditLogger())->log($this->tenantId(), $this->userId(), null, $eventType, [], [
                'error' => $throwable->getMessage(),
            ]);
        } catch (Throwable) {
            // No bloquear la respuesta por una falla secundaria de auditoria.
        }
    }
}
