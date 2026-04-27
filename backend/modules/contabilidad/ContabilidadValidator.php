<?php

declare(strict_types=1);

final class ContabilidadValidator
{
    public function validateConfiguracion(array $input): array
    {
        $errors = [];

        if (isset($input['periodo_inicio_mes']) && ((int) $input['periodo_inicio_mes'] < 1 || (int) $input['periodo_inicio_mes'] > 12)) {
            $errors[] = ['field' => 'periodo_inicio_mes', 'message' => 'Mes de inicio invalido.'];
        }

        foreach (['pais_codigo', 'moneda_base'] as $field) {
            if (array_key_exists($field, $input) && trim((string) $input[$field]) === '') {
                $errors[] = ['field' => $field, 'message' => 'Campo requerido.'];
            }
        }

        return $errors;
    }

    public function validateCuentaCreate(array $input): array
    {
        $errors = [];

        foreach (['codigo', 'nombre', 'tipo', 'naturaleza'] as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                $errors[] = ['field' => $field, 'message' => 'Campo requerido.'];
            }
        }

        return array_merge($errors, $this->validateCuentaEnums($input));
    }

    public function validateCuentaUpdate(array $input): array
    {
        if ($input === []) {
            return [['field' => 'body', 'message' => 'Debe enviar al menos un campo.']];
        }

        return $this->validateCuentaEnums($input);
    }

    public function validatePeriodoCreate(array $input): array
    {
        $errors = [];

        foreach (['nombre', 'fecha_inicio', 'fecha_fin'] as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                $errors[] = ['field' => $field, 'message' => 'Campo requerido.'];
            }
        }

        foreach (['fecha_inicio', 'fecha_fin'] as $field) {
            if (isset($input[$field]) && !$this->isDate((string) $input[$field])) {
                $errors[] = ['field' => $field, 'message' => 'Fecha invalida.'];
            }
        }

        if (
            isset($input['fecha_inicio'], $input['fecha_fin'])
            && $this->isDate((string) $input['fecha_inicio'])
            && $this->isDate((string) $input['fecha_fin'])
            && (string) $input['fecha_inicio'] > (string) $input['fecha_fin']
        ) {
            $errors[] = ['field' => 'fecha_fin', 'message' => 'Fecha de fin debe ser posterior a fecha de inicio.'];
        }

        return $errors;
    }

    public function validateAsientoCreate(array $input): array
    {
        $errors = [];

        foreach (['numero', 'fecha_asiento', 'descripcion', 'lineas'] as $field) {
            if (!isset($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '')) {
                $errors[] = ['field' => $field, 'message' => 'Campo requerido.'];
            }
        }

        if (isset($input['fecha_asiento']) && !$this->isDate((string) $input['fecha_asiento'])) {
            $errors[] = ['field' => 'fecha_asiento', 'message' => 'Fecha invalida.'];
        }

        if (isset($input['origen']) && !in_array($input['origen'], ['manual', 'finanzas', 'ajuste', 'reversa', 'apertura', 'cierre'], true)) {
            $errors[] = ['field' => 'origen', 'message' => 'Origen invalido.'];
        }

        if (!isset($input['lineas']) || !is_array($input['lineas']) || count($input['lineas']) < 2) {
            $errors[] = ['field' => 'lineas', 'message' => 'El asiento debe tener al menos dos lineas.'];
            return $errors;
        }

        $totalDebe = 0.0;
        $totalHaber = 0.0;

        foreach ($input['lineas'] as $index => $linea) {
            if (!is_array($linea) || empty($linea['cuenta_id'])) {
                $errors[] = ['field' => "lineas.$index.cuenta_id", 'message' => 'Cuenta contable requerida.'];
                continue;
            }

            $debe = isset($linea['debe']) ? (float) $linea['debe'] : 0.0;
            $haber = isset($linea['haber']) ? (float) $linea['haber'] : 0.0;

            if ($debe < 0 || $haber < 0 || ($debe <= 0 && $haber <= 0) || ($debe > 0 && $haber > 0)) {
                $errors[] = ['field' => "lineas.$index", 'message' => 'Cada linea debe tener debe o haber, no ambos.'];
            }

            $totalDebe += $debe;
            $totalHaber += $haber;
        }

        if (round($totalDebe, 2) <= 0 || round($totalDebe, 2) !== round($totalHaber, 2)) {
            $errors[] = ['field' => 'lineas', 'message' => 'El asiento debe cuadrar en debe y haber.'];
        }

        return $errors;
    }

    public function validateMapeoCreate(array $input): array
    {
        $errors = [];

        foreach (['categoria_id', 'tipo_movimiento', 'cuenta_debe_id', 'cuenta_haber_id'] as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                $errors[] = ['field' => $field, 'message' => 'Campo requerido.'];
            }
        }

        return array_merge($errors, $this->validateMapeoEnums($input));
    }

    public function validateMapeoUpdate(array $input): array
    {
        if ($input === []) {
            return [['field' => 'body', 'message' => 'Debe enviar al menos un campo.']];
        }

        return $this->validateMapeoEnums($input);
    }

    private function validateCuentaEnums(array $input): array
    {
        $errors = [];

        if (isset($input['tipo']) && !in_array($input['tipo'], ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto', 'orden'], true)) {
            $errors[] = ['field' => 'tipo', 'message' => 'Tipo invalido.'];
        }

        if (isset($input['naturaleza']) && !in_array($input['naturaleza'], ['deudora', 'acreedora'], true)) {
            $errors[] = ['field' => 'naturaleza', 'message' => 'Naturaleza invalida.'];
        }

        return $errors;
    }

    private function validateMapeoEnums(array $input): array
    {
        if (isset($input['tipo_movimiento']) && !in_array($input['tipo_movimiento'], ['ingreso', 'egreso'], true)) {
            return [['field' => 'tipo_movimiento', 'message' => 'Tipo de movimiento invalido.']];
        }

        return [];
    }

    private function isDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }
}
