<?php

declare(strict_types=1);

final class AgendaValidator
{
    public function validateCreate(array $input): array
    {
        $errors = [];
        foreach (['tipo', 'titulo', 'fecha_inicio'] as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                $errors[] = ['field' => $field, 'message' => 'Campo requerido.'];
            }
        }
        if (isset($input['tipo']) && !in_array($input['tipo'], ['reminder','call','meeting','whatsapp_send','task','followup'], true)) {
            $errors[] = ['field' => 'tipo', 'message' => 'Tipo invalido.'];
        }
        if (isset($input['fecha_inicio']) && !$this->validDateTime((string) $input['fecha_inicio'])) {
            $errors[] = ['field' => 'fecha_inicio', 'message' => 'Fecha invalida.'];
        }
        return $errors;
    }

    public function validateUpdate(array $input): array
    {
        $errors = [];
        if (isset($input['tipo']) && !in_array($input['tipo'], ['reminder','call','meeting','whatsapp_send','task','followup'], true)) {
            $errors[] = ['field' => 'tipo', 'message' => 'Tipo invalido.'];
        }
        if (isset($input['fecha_inicio']) && !$this->validDateTime((string) $input['fecha_inicio'])) {
            $errors[] = ['field' => 'fecha_inicio', 'message' => 'Fecha invalida.'];
        }
        if (isset($input['estado']) && !in_array($input['estado'], ['pending','completed','cancelled','expired'], true)) {
            $errors[] = ['field' => 'estado', 'message' => 'Estado invalido.'];
        }
        return $errors;
    }

    public function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
    }

    private function validDateTime(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        return $date instanceof DateTimeImmutable && $date->format('Y-m-d H:i:s') === $value;
    }
}
