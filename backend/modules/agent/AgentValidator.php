<?php

declare(strict_types=1);

final class AgentValidator
{
    private const SOURCES_VALIDOS = ['whatsapp', 'web', 'api'];

    /** @return array<int, array{field: string, message: string}> */
    public function validateCreate(array $input, int $authUserId, int $authTenantId): array
    {
        $errors = [];

        if (!isset($input['source']) || trim((string) $input['source']) === '') {
            $errors[] = ['field' => 'source', 'message' => 'Source es requerido.'];
        } elseif (!in_array((string) $input['source'], self::SOURCES_VALIDOS, true)) {
            $errors[] = ['field' => 'source', 'message' => 'Source invalido.'];
        }

        if (!isset($input['input_text']) || trim((string) $input['input_text']) === '') {
            $errors[] = ['field' => 'input_text', 'message' => 'Input text es requerido.'];
        } elseif (mb_strlen(trim((string) $input['input_text'])) > 4000) {
            $errors[] = ['field' => 'input_text', 'message' => 'Input text excede el largo permitido.'];
        }

        if (!isset($input['user_id']) || (int) $input['user_id'] < 1) {
            $errors[] = ['field' => 'user_id', 'message' => 'User id es requerido.'];
        } elseif ((int) $input['user_id'] !== $authUserId) {
            $errors[] = ['field' => 'user_id', 'message' => 'User id no corresponde al usuario autenticado.'];
        }

        if (!isset($input['tenant_id']) || (int) $input['tenant_id'] < 1) {
            $errors[] = ['field' => 'tenant_id', 'message' => 'Tenant id es requerido.'];
        } elseif ((int) $input['tenant_id'] !== $authTenantId) {
            $errors[] = ['field' => 'tenant_id', 'message' => 'Tenant id no corresponde a la iglesia activa.'];
        }

        return $errors;
    }
}
