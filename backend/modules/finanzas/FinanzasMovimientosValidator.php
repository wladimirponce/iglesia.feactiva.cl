<?php

declare(strict_types=1);

final class FinanzasMovimientosValidator
{
    /** @return array<int, array{field: string, message: string}> */
    public function validateCreate(array $input): array
    {
        $errors = [];

        foreach (['cuenta_id', 'categoria_id', 'tipo', 'descripcion', 'monto', 'fecha_movimiento', 'fecha_contable'] as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                $errors[] = ['field' => $field, 'message' => 'Campo requerido.'];
            }
        }

        if (isset($input['tipo']) && !in_array($input['tipo'], ['ingreso', 'egreso'], true)) {
            $errors[] = ['field' => 'tipo', 'message' => 'Tipo invalido.'];
        }

        if (isset($input['monto']) && (float) $input['monto'] <= 0) {
            $errors[] = ['field' => 'monto', 'message' => 'Monto debe ser mayor que cero.'];
        }

        foreach (['fecha_movimiento', 'fecha_contable'] as $field) {
            if (isset($input[$field]) && trim((string) $input[$field]) !== '' && !$this->isValidDate((string) $input[$field])) {
                $errors[] = ['field' => $field, 'message' => 'Fecha invalida.'];
            }
        }

        if (isset($input['medio_pago']) && $input['medio_pago'] !== '' && !in_array($input['medio_pago'], [
            'efectivo',
            'transferencia',
            'tarjeta_debito',
            'tarjeta_credito',
            'cheque',
            'paypal',
            'stripe',
            'flow',
            'mercadopago',
            'otro',
        ], true)) {
            $errors[] = ['field' => 'medio_pago', 'message' => 'Medio de pago invalido.'];
        }

        return $errors;
    }

    /** @return array<int, array{field: string, message: string}> */
    public function validateCancel(array $input): array
    {
        if (!isset($input['motivo_anulacion']) || trim((string) $input['motivo_anulacion']) === '') {
            return [['field' => 'motivo_anulacion', 'message' => 'Motivo de anulacion es requerido.']];
        }

        return [];
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }
}
