<?php

declare(strict_types=1);

final class CrmContactosValidator
{
    private const TIPOS_VALIDOS = [
        'llamada',
        'whatsapp',
        'email',
        'visita',
        'reunion',
        'mensaje_app',
        'otro',
    ];

    /** @return array<int, array{field: string, message: string}> */
    public function validateCreate(array $input): array
    {
        $errors = [];

        if (isset($input['tipo_contacto']) && !in_array($input['tipo_contacto'], self::TIPOS_VALIDOS, true)) {
            $errors[] = ['field' => 'tipo_contacto', 'message' => 'Tipo de contacto invalido.'];
        }

        if (!isset($input['fecha_contacto']) || trim((string) $input['fecha_contacto']) === '') {
            $errors[] = ['field' => 'fecha_contacto', 'message' => 'Fecha de contacto es requerida.'];
        } elseif (!$this->isValidDateTime((string) $input['fecha_contacto'])) {
            $errors[] = ['field' => 'fecha_contacto', 'message' => 'Fecha de contacto invalida.'];
        }

        if (!empty($input['requiere_seguimiento']) && empty($input['fecha_seguimiento'])) {
            $errors[] = ['field' => 'fecha_seguimiento', 'message' => 'Fecha de seguimiento es requerida.'];
        }

        if (isset($input['fecha_seguimiento']) && $input['fecha_seguimiento'] !== '' && !$this->isValidDate((string) $input['fecha_seguimiento'])) {
            $errors[] = ['field' => 'fecha_seguimiento', 'message' => 'Fecha de seguimiento invalida.'];
        }

        return $errors;
    }

    private function isValidDateTime(string $dateTime): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTime);
        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d H:i:s') === $dateTime;
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }
}
