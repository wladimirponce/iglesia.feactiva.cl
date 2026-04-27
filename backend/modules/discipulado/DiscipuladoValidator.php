<?php

declare(strict_types=1);

final class DiscipuladoValidator
{
    public function validateRutaCreate(array $input): array
    {
        return empty(trim((string) ($input['nombre'] ?? '')))
            ? [['field' => 'nombre', 'message' => 'Nombre es requerido.']]
            : [];
    }

    public function validateUpdate(array $input): array
    {
        return $input === [] ? [['field' => 'body', 'message' => 'Debe enviar al menos un campo.']] : [];
    }

    public function validateEtapaCreate(array $input): array
    {
        return empty(trim((string) ($input['nombre'] ?? '')))
            ? [['field' => 'nombre', 'message' => 'Nombre es requerido.']]
            : [];
    }

    public function validateAssignRuta(array $input): array
    {
        $errors = [];
        if (empty(trim((string) ($input['ruta_id'] ?? '')))) {
            $errors[] = ['field' => 'ruta_id', 'message' => 'Ruta es requerida.'];
        }
        if (isset($input['estado']) && !in_array($input['estado'], ['pendiente', 'en_progreso', 'completada', 'pausada', 'cancelada'], true)) {
            $errors[] = ['field' => 'estado', 'message' => 'Estado invalido.'];
        }

        return $errors;
    }

    public function validateMentoriaCreate(array $input): array
    {
        $errors = [];
        foreach (['mentor_persona_id', 'fecha_mentoria'] as $field) {
            if (empty(trim((string) ($input[$field] ?? '')))) {
                $errors[] = ['field' => $field, 'message' => 'Campo requerido.'];
            }
        }
        if (isset($input['modalidad']) && !in_array($input['modalidad'], ['presencial', 'online', 'telefono', 'whatsapp', 'otro'], true)) {
            $errors[] = ['field' => 'modalidad', 'message' => 'Modalidad invalida.'];
        }

        return $errors;
    }

    public function validateRegistroCreate(array $input): array
    {
        $errors = [];
        foreach (['tipo', 'fecha_evento'] as $field) {
            if (empty(trim((string) ($input[$field] ?? '')))) {
                $errors[] = ['field' => $field, 'message' => 'Campo requerido.'];
            }
        }
        if (isset($input['tipo']) && !in_array($input['tipo'], ['conversion', 'profesion_fe', 'bautismo', 'santa_cena', 'recepcion_membresia', 'presentacion_nino', 'matrimonio', 'otro'], true)) {
            $errors[] = ['field' => 'tipo', 'message' => 'Tipo invalido.'];
        }

        return $errors;
    }
}
