<?php

declare(strict_types=1);

final class FinanzasDocumentosValidator
{
    private const TIPOS_VALIDOS = [
        'comprobante_ingreso',
        'comprobante_egreso',
        'boleta',
        'factura',
        'recibo',
        'transferencia',
        'cartola',
        'otro',
    ];

    /** @return array<int, array{field: string, message: string}> */
    public function validateCreate(array $input): array
    {
        $errors = [];

        if (isset($input['tipo_documento']) && !in_array($input['tipo_documento'], self::TIPOS_VALIDOS, true)) {
            $errors[] = ['field' => 'tipo_documento', 'message' => 'Tipo de documento invalido.'];
        }

        if (isset($input['fecha_documento']) && $input['fecha_documento'] !== '' && !$this->isValidDate((string) $input['fecha_documento'])) {
            $errors[] = ['field' => 'fecha_documento', 'message' => 'Fecha de documento invalida.'];
        }

        if (isset($input['archivo_size']) && $input['archivo_size'] !== '' && (int) $input['archivo_size'] < 0) {
            $errors[] = ['field' => 'archivo_size', 'message' => 'Tamano de archivo invalido.'];
        }

        return $errors;
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }
}
