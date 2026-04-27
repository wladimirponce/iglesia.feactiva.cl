<?php

declare(strict_types=1);

final class CrmFamiliasValidator
{
    private const TIPOS_RELACION_VALIDOS = [
        'padre',
        'madre',
        'hijo',
        'hija',
        'tutor',
        'otro',
    ];

    /** @return array<int, array{field: string, message: string}> */
    public function validateCreate(array $input): array
    {
        if (!isset($input['nombre_familia']) || trim((string) $input['nombre_familia']) === '') {
            return [['field' => 'nombre_familia', 'message' => 'Nombre de familia es requerido.']];
        }

        return [];
    }

    /** @return array<int, array{field: string, message: string}> */
    public function validateUpdate(array $input): array
    {
        if ($input === []) {
            return [['field' => 'body', 'message' => 'Debe enviar al menos un campo para actualizar.']];
        }

        if (array_key_exists('nombre_familia', $input) && trim((string) $input['nombre_familia']) === '') {
            return [['field' => 'nombre_familia', 'message' => 'Nombre de familia no puede estar vacio.']];
        }

        return [];
    }

    /** @return array<int, array{field: string, message: string}> */
    public function validateAddPersona(array $input): array
    {
        $errors = [];

        if (!isset($input['persona_id']) || (int) $input['persona_id'] < 1) {
            $errors[] = ['field' => 'persona_id', 'message' => 'Persona es requerida.'];
        }

        if (isset($input['tipo_relacion']) && !in_array($input['tipo_relacion'], self::TIPOS_RELACION_VALIDOS, true)) {
            $errors[] = ['field' => 'tipo_relacion', 'message' => 'Tipo de relacion invalido.'];
        }

        return $errors;
    }
}
