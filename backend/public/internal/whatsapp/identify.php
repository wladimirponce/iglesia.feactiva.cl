<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/env.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../helpers/PhoneNormalizer.php';
require_once __DIR__ . '/../../../modules/integrations/whatsapp/WhatsAppIdentityValidator.php';
require_once __DIR__ . '/../../../modules/integrations/whatsapp/WhatsAppIdentityRepository.php';
require_once __DIR__ . '/../../../modules/integrations/whatsapp/WhatsAppIdentityService.php';
require_once __DIR__ . '/../../../modules/integrations/whatsapp/WhatsAppIdentityController.php';
require_once __DIR__ . '/../../../middlewares/IntegrationAuthMiddleware.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    Response::error('METHOD_NOT_ALLOWED', 'Metodo HTTP no permitido.', [], 405);
    exit;
}

$middleware = new IntegrationAuthMiddleware();
$middleware->handle(static function (): void {
    try {
        $controller = new WhatsAppIdentityController();
        $controller->identify();
    } catch (Throwable) {
        auditInternalIdentifyError();
        Response::error('WHATSAPP_IDENTIFY_ERROR', 'No fue posible identificar el usuario.', [], 500);
    }
});

function auditInternalIdentifyError(): void
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
                'error',
                'whatsapp.identity.identify',
                'failed',
                'integration',
                NULL,
                :metadata,
                :ip_address,
                :user_agent
            )
        ";

        $metadata = json_encode([
            'endpoint' => '/internal/whatsapp/identify.php',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'metadata' => $metadata,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    } catch (Throwable) {
        // Keep the endpoint JSON-only even if audit storage is unavailable.
    }
}
