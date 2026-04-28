<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

internalAgentRequirePost();

$middleware = new IntegrationAuthMiddleware();
$middleware->handle(static function (): void {
    $input = internalAgentJsonInput();
    $tenantId = (int) ($input['tenant_id'] ?? 0);
    $userId = (int) ($input['user_id'] ?? 0);
    $messageText = trim((string) ($input['message_text'] ?? $input['input_text'] ?? ''));

    $errors = [];
    if ($tenantId < 1) {
        $errors[] = ['field' => 'tenant_id', 'message' => 'Tenant id es requerido.'];
    }
    if ($userId < 1) {
        $errors[] = ['field' => 'user_id', 'message' => 'User id es requerido.'];
    }
    if ($messageText === '') {
        $errors[] = ['field' => 'message_text', 'message' => 'Message text es requerido.'];
    }
    if ($errors !== []) {
        Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
        return;
    }

    $resolver = new OntologyResolver(new OntologyRegistry());
    $result = $resolver->resolve([
        'tenant_id' => $tenantId,
        'user_id' => $userId,
        'input_text' => $messageText,
    ]);

    Response::success($result->toArray());
});
