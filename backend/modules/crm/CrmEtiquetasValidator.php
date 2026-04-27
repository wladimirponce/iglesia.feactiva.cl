<?php

declare(strict_types=1);

final class CrmEtiquetasValidator
{
    /** @return array<int, array{field: string, message: string}> */
    public function validateCreate(array $input): array
    {
        if (!isset($input['nombre']) || trim((string) $input['nombre']) === '') {
            return [['field' => 'nombre', 'message' => 'Nombre es requerido.']];
        }

        return [];
    }

    /** @return array<int, array{field: string, message: string}> */
    public function validateUpdate(array $input): array
    {
        if ($input === []) {
            return [['field' => 'body', 'message' => 'Debe enviar al menos un campo para actualizar.']];
        }

        if (array_key_exists('nombre', $input) && trim((string) $input['nombre']) === '') {
            return [['field' => 'nombre', 'message' => 'Nombre no puede estar vacio.']];
        }

        return [];
    }
}
