<?php

declare(strict_types=1);

final class WhatsAppIdentityValidator
{
    /** @return array<int, array{field: string, message: string}> */
    public function validateIdentify(array $input): array
    {
        $errors = [];

        if (!isset($input['phone']) || trim((string) $input['phone']) === '') {
            $errors[] = ['field' => 'phone', 'message' => 'Phone es requerido.'];
        }

        return $errors;
    }
}
