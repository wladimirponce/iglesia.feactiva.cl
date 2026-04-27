<?php

declare(strict_types=1);

final class AuthValidator
{
    /** @return array<int, array{field: string, message: string}> */
    public function validateLogin(array $input): array
    {
        $errors = [];

        if (!isset($input['email']) || trim((string) $input['email']) === '') {
            $errors[] = [
                'field' => 'email',
                'message' => 'Email es requerido.',
            ];
        } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = [
                'field' => 'email',
                'message' => 'Email invalido.',
            ];
        }

        if (!isset($input['password']) || (string) $input['password'] === '') {
            $errors[] = [
                'field' => 'password',
                'message' => 'Password es requerido.',
            ];
        }

        return $errors;
    }
}
