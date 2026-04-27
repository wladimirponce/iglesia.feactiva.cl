<?php

declare(strict_types=1);

final class PastoralValidator
{
    public function validateCasoCreate(array $input): array
    {
        $errors = [];
        foreach (['persona_id', 'titulo', 'fecha_apertura'] as $field) {
            if (empty(trim((string) ($input[$field] ?? '')))) {
                $errors[] = ['field' => $field, 'message' => 'Campo requerido.'];
            }
        }

        $this->enum($errors, $input, 'tipo', ['consejeria', 'oracion', 'visita', 'crisis', 'acompanamiento', 'disciplinario', 'otro']);
        $this->enum($errors, $input, 'prioridad', ['baja', 'media', 'alta', 'critica']);
        $this->enum($errors, $input, 'estado', ['abierto', 'en_seguimiento', 'cerrado', 'derivado']);

        return $errors;
    }

    public function validateCasoUpdate(array $input): array
    {
        $errors = $input === [] ? [['field' => 'body', 'message' => 'Debe enviar al menos un campo.']] : [];
        $this->enum($errors, $input, 'tipo', ['consejeria', 'oracion', 'visita', 'crisis', 'acompanamiento', 'disciplinario', 'otro']);
        $this->enum($errors, $input, 'prioridad', ['baja', 'media', 'alta', 'critica']);
        $this->enum($errors, $input, 'estado', ['abierto', 'en_seguimiento', 'cerrado', 'derivado']);
        return $errors;
    }

    public function validateSesionCreate(array $input): array
    {
        $errors = [];
        if (empty(trim((string) ($input['fecha_sesion'] ?? '')))) {
            $errors[] = ['field' => 'fecha_sesion', 'message' => 'Campo requerido.'];
        }
        $this->enum($errors, $input, 'modalidad', ['presencial', 'online', 'telefono', 'whatsapp', 'otro']);
        return $errors;
    }

    public function validateOracionCreate(array $input): array
    {
        $errors = [];
        if (empty(trim((string) ($input['titulo'] ?? '')))) {
            $errors[] = ['field' => 'titulo', 'message' => 'Campo requerido.'];
        }
        $this->enum($errors, $input, 'privacidad', ['privada', 'equipo_pastoral', 'publica']);
        $this->enum($errors, $input, 'estado', ['recibida', 'en_oracion', 'respondida', 'cerrada']);
        return $errors;
    }

    public function validateOracionUpdate(array $input): array
    {
        $errors = $input === [] ? [['field' => 'body', 'message' => 'Debe enviar al menos un campo.']] : [];
        $this->enum($errors, $input, 'privacidad', ['privada', 'equipo_pastoral', 'publica']);
        $this->enum($errors, $input, 'estado', ['recibida', 'en_oracion', 'respondida', 'cerrada']);
        return $errors;
    }

    public function validateDerivacionCreate(array $input): array
    {
        $errors = [];
        if (empty(trim((string) ($input['motivo'] ?? '')))) {
            $errors[] = ['field' => 'motivo', 'message' => 'Campo requerido.'];
        }
        $this->enum($errors, $input, 'tipo_derivacion', ['pastor', 'psicologo', 'orientador', 'diacono', 'lider', 'externo', 'otro']);
        return $errors;
    }

    private function enum(array &$errors, array $input, string $field, array $allowed): void
    {
        if (isset($input[$field]) && !in_array($input[$field], $allowed, true)) {
            $errors[] = ['field' => $field, 'message' => 'Valor invalido.'];
        }
    }
}
