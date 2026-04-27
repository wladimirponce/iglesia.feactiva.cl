<?php

declare(strict_types=1);

final class CrmPersonasValidator
{
    private const ESTADOS_VALIDOS = [
        'visita',
        'nuevo_asistente',
        'miembro',
        'lider',
        'servidor',
        'inactivo',
        'trasladado',
        'fallecido',
    ];

    private const GENEROS_VALIDOS = [
        'masculino',
        'femenino',
        'otro',
        'no_informa',
    ];

    private const ESTADOS_CIVILES_VALIDOS = [
        'soltero',
        'casado',
        'viudo',
        'divorciado',
        'separado',
        'no_informa',
    ];

    /** @return array<int, array{field: string, message: string}> */
    public function validateCreate(array $input): array
    {
        $errors = [];

        if (!isset($input['nombres']) || trim((string) $input['nombres']) === '') {
            $errors[] = ['field' => 'nombres', 'message' => 'Nombres es requerido.'];
        }

        if (!isset($input['apellidos']) || trim((string) $input['apellidos']) === '') {
            $errors[] = ['field' => 'apellidos', 'message' => 'Apellidos es requerido.'];
        }

        if (isset($input['email']) && trim((string) $input['email']) !== '' && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['field' => 'email', 'message' => 'Email invalido.'];
        }

        if (isset($input['estado_persona']) && !in_array($input['estado_persona'], self::ESTADOS_VALIDOS, true)) {
            $errors[] = ['field' => 'estado_persona', 'message' => 'Estado de persona invalido.'];
        }

        if (isset($input['genero']) && $input['genero'] !== null && $input['genero'] !== '' && !in_array($input['genero'], self::GENEROS_VALIDOS, true)) {
            $errors[] = ['field' => 'genero', 'message' => 'Genero invalido.'];
        }

        if (isset($input['estado_civil']) && $input['estado_civil'] !== null && $input['estado_civil'] !== '' && !in_array($input['estado_civil'], self::ESTADOS_CIVILES_VALIDOS, true)) {
            $errors[] = ['field' => 'estado_civil', 'message' => 'Estado civil invalido.'];
        }

        foreach (['fecha_nacimiento', 'fecha_primer_contacto', 'fecha_ingreso', 'fecha_membresia'] as $field) {
            if (isset($input[$field]) && $input[$field] !== null && $input[$field] !== '' && !$this->isValidDate((string) $input[$field])) {
                $errors[] = ['field' => $field, 'message' => 'Fecha invalida.'];
            }
        }

        return $errors;
    }

    /** @return array<int, array{field: string, message: string}> */
    public function validateUpdate(array $input): array
    {
        $errors = [];

        if ($input === []) {
            return [
                ['field' => 'body', 'message' => 'Debe enviar al menos un campo para actualizar.'],
            ];
        }

        if (array_key_exists('nombres', $input) && trim((string) $input['nombres']) === '') {
            $errors[] = ['field' => 'nombres', 'message' => 'Nombres no puede estar vacio.'];
        }

        if (array_key_exists('apellidos', $input) && trim((string) $input['apellidos']) === '') {
            $errors[] = ['field' => 'apellidos', 'message' => 'Apellidos no puede estar vacio.'];
        }

        if (isset($input['email']) && trim((string) $input['email']) !== '' && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['field' => 'email', 'message' => 'Email invalido.'];
        }

        if (isset($input['estado_persona']) && !in_array($input['estado_persona'], self::ESTADOS_VALIDOS, true)) {
            $errors[] = ['field' => 'estado_persona', 'message' => 'Estado de persona invalido.'];
        }

        if (isset($input['genero']) && $input['genero'] !== null && $input['genero'] !== '' && !in_array($input['genero'], self::GENEROS_VALIDOS, true)) {
            $errors[] = ['field' => 'genero', 'message' => 'Genero invalido.'];
        }

        if (isset($input['estado_civil']) && $input['estado_civil'] !== null && $input['estado_civil'] !== '' && !in_array($input['estado_civil'], self::ESTADOS_CIVILES_VALIDOS, true)) {
            $errors[] = ['field' => 'estado_civil', 'message' => 'Estado civil invalido.'];
        }

        foreach (['fecha_nacimiento', 'fecha_primer_contacto', 'fecha_ingreso', 'fecha_membresia'] as $field) {
            if (isset($input[$field]) && $input[$field] !== null && $input[$field] !== '' && !$this->isValidDate((string) $input[$field])) {
                $errors[] = ['field' => $field, 'message' => 'Fecha invalida.'];
            }
        }

        return $errors;
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }
}
