<?php

declare(strict_types=1);

final class WhatsAppIdentityController
{
    private WhatsAppIdentityValidator $validator;
    private WhatsAppIdentityService $service;

    public function __construct()
    {
        $repository = new WhatsAppIdentityRepository();
        $this->validator = new WhatsAppIdentityValidator();
        $this->service = new WhatsAppIdentityService($repository);
    }

    public function identify(): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateIdentify($input);

        if ($errors !== []) {
            try {
                $this->service->identify(
                    (string) ($input['phone'] ?? ''),
                    strtoupper((string) ($input['country_code'] ?? 'CL')),
                    $this->ipAddress(),
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                );
            } catch (Throwable) {
                // The service audits invalid attempts before throwing.
            }

            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }

        try {
            $result = $this->service->identify(
                (string) $input['phone'],
                strtoupper((string) ($input['country_code'] ?? 'CL')),
                $this->ipAddress(),
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
        } catch (RuntimeException $exception) {
            match ($exception->getMessage()) {
                'WHATSAPP_INVALID_PHONE' => Response::error('VALIDATION_ERROR', 'Phone invalido.', [
                    ['field' => 'phone', 'message' => 'Debe enviar un numero valido en formato WhatsApp/E.164.'],
                ], 422),
                default => Response::error('WHATSAPP_IDENTIFY_ERROR', 'No fue posible identificar el usuario.', [], 500),
            };
            return;
        } catch (Throwable) {
            Response::error('WHATSAPP_IDENTIFY_ERROR', 'No fue posible identificar el usuario.', [], 500);
            return;
        }

        Response::success($result);
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

    private function ipAddress(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        return is_string($ip) && $ip !== '' ? $ip : null;
    }
}
