<?php

declare(strict_types=1);

final class AuthController
{
    private AuthValidator $validator;
    private AuthService $service;

    public function __construct()
    {
        $repository = new AuthRepository();
        $this->validator = new AuthValidator();
        $this->service = new AuthService($repository);
    }

    public function login(): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateLogin($input);

        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $result = $this->service->login(
                trim((string) $input['email']),
                (string) $input['password'],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
        } catch (Throwable) {
            Response::error('AUTH_LOGIN_ERROR', 'No fue posible iniciar sesion.', [], 500);
            return;
        }

        if ($result === null) {
            Response::error('UNAUTHENTICATED', 'Credenciales invalidas.', [], 401);
            return;
        }

        Response::success($result, 'Login correcto.');
    }

    public function logout(): void
    {
        $token = $this->bearerToken();

        if ($token === null) {
            Response::error('UNAUTHENTICATED', 'Debe iniciar sesion.', [], 401);
            return;
        }

        try {
            $this->service->logout($token);
        } catch (Throwable) {
            Response::error('AUTH_LOGOUT_ERROR', 'No fue posible cerrar sesion.', [], 500);
            return;
        }

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => new stdClass(),
            'meta' => new stdClass(),
            'message' => 'Logout correcto.',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function me(): void
    {
        Response::success([
            'user_id' => AuthContext::userId(),
            'email' => AuthContext::email(),
            'tenant_id' => AuthContext::tenantId(),
        ]);
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');

        if ($rawBody === false || trim($rawBody) === '') {
            return [];
        }

        $decoded = json_decode($rawBody, true);

        return is_array($decoded) ? $decoded : [];
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
