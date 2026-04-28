<?php

declare(strict_types=1);

final class OntologyResolver
{
    public function __construct(
        private readonly OntologyRegistry $registry
    ) {
    }

    /** @param array{input_text?: string, tenant_id?: int, user_id?: int}|string $input */
    public function resolve(array|string $input): OntologyResolutionResult
    {
        $inputText = is_array($input) ? (string) ($input['input_text'] ?? '') : $input;
        $text = $this->normalize($inputText);
        $actionName = $this->detectAction($text);

        if ($actionName === null) {
            return OntologyResolutionResult::unresolved();
        }

        $action = $this->registry->action($actionName);
        if (!$action instanceof OntologyAction) {
            return OntologyResolutionResult::unhandled($actionName);
        }

        $fields = $this->extractFields($text, $inputText, $actionName);
        $missing = $this->missingFields($fields, $action->requiredFields);

        return new OntologyResolutionResult(
            true,
            $action->objectType,
            $action->name,
            $action->toolName,
            $action->requiredPermission,
            $fields,
            $missing,
            $action->requiresConfirmation,
            $action->sensitiveLevel
        );
    }

    private function detectAction(string $text): ?string
    {
        if (preg_match('/\b(busca|buscar|encuentra|encontrar)\s+(una\s+)?persona\b/u', $text) === 1) {
            return 'buscar_persona';
        }

        if (preg_match('/\b(crea|crear|registra|registrar)\s+(una\s+)?persona\b/u', $text) === 1) {
            return 'crear_persona';
        }

        if (preg_match('/\b(crea|crear)\s+(una\s+)?familia\b/u', $text) === 1) {
            return 'crear_familia';
        }

        if (preg_match('/\b(agrega|agregar|asigna|asignar)\b.*\bfamilia\b/u', $text) === 1) {
            return 'asignar_persona_familia';
        }

        if (preg_match('/\b(diezmo|diezmos)\b/u', $text) === 1) {
            return 'registrar_diezmo';
        }

        if (preg_match('/\b(ofrenda|ofrendas)\b/u', $text) === 1) {
            return 'registrar_ofrenda';
        }

        if (preg_match('/\b(egreso|gasto|pago)\b/u', $text) === 1) {
            return 'registrar_egreso';
        }

        if (preg_match('/\b(saldo|cuanto dinero hay|cuanto hay)\b/u', $text) === 1) {
            return 'consultar_saldo_financiero';
        }

        if (preg_match('/\b(balance contable|contabilidad|balance de comprobacion)\b/u', $text) === 1) {
            return 'consultar_balance_contable';
        }

        if (preg_match('/\b(asigna|asignar)\b.*\bdiscipulado\b/u', $text) === 1) {
            return 'asignar_discipulado';
        }

        if (preg_match('/\b(solicitud de oracion|solicitud de oración|peticion de oracion|petición de oración|oracion|oración)\b/u', $text) === 1) {
            return 'crear_solicitud_oracion';
        }

        if (preg_match('/\b(caso pastoral)\b/u', $text) === 1) {
            return 'crear_caso_pastoral';
        }

        if (preg_match('/\b(que tengo agendado|buscar recordatorio|ver recordatorios|agenda para)\b/u', $text) === 1) {
            return 'buscar_recordatorio';
        }

        if (preg_match('/\b(recuerdame|recuérdame|recordatorio|agenda)\b/u', $text) === 1) {
            return 'crear_recordatorio';
        }

        if (preg_match('/\b(exporta|exportar|descarga|descargar|grafico|gr[aá]fico|reporte avanzado)\b/u', $text) === 1) {
            return 'accion_no_implementada';
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function extractFields(string $text, string $originalText, string $actionName): array
    {
        return match ($actionName) {
            'buscar_persona' => $this->extractSearchPerson($text),
            'crear_persona' => $this->extractCreatePerson($originalText),
            'crear_familia' => $this->extractCreateFamily($originalText),
            'asignar_persona_familia' => $this->extractFamilyAssignment($text),
            'registrar_diezmo' => $this->extractIncome($text, $originalText, 'diezmo'),
            'registrar_ofrenda' => $this->extractIncome($text, $originalText, 'ofrenda'),
            'registrar_egreso' => $this->extractExpense($text, $originalText),
            'consultar_saldo_financiero' => ['fecha' => $this->extractDate($text) ?? date('Y-m-d')],
            'consultar_balance_contable' => $this->extractDateRange($text),
            'asignar_discipulado' => $this->extractDiscipulado($text),
            'crear_solicitud_oracion' => $this->extractPrayer($text, $originalText),
            'crear_caso_pastoral' => $this->extractPastoralCase($text, $originalText),
            'crear_recordatorio' => $this->extractReminder($text, $originalText),
            'buscar_recordatorio' => $this->extractDateRange($text),
            default => [],
        };
    }

    /** @return array<string, mixed> */
    private function extractSearchPerson(string $text): array
    {
        $query = preg_replace('/^.*?\bpersona\s+/u', '', $text);
        $query = trim(is_string($query) ? $query : '');
        return ['query' => $query !== '' ? $this->titleName($query) : null];
    }

    /** @return array<string, mixed> */
    private function extractCreatePerson(string $inputText): array
    {
        $clean = trim(preg_replace('/\b(crea|crear|registra|registrar)\s+(una\s+)?persona\b/iu', '', $inputText) ?? $inputText);
        $name = trim(preg_replace('/,\s*(telefono|tel[eé]fono|email|correo)\b.*$/iu', '', $clean) ?? $clean);
        $parts = preg_split('/\s+/', $name);
        $fields = ['estado_persona' => 'visita'];

        if (is_array($parts) && count($parts) >= 2) {
            $fields['nombres'] = $this->titleName($parts[0]);
            $fields['apellidos'] = $this->titleName(implode(' ', array_slice($parts, 1)));
        }

        if (preg_match('/\+?[0-9][0-9\s]{7,}/u', $inputText, $matches) === 1) {
            $fields['phone'] = trim($matches[0]);
        }

        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $inputText, $matches) === 1) {
            $fields['email'] = trim($matches[0]);
        }

        return $fields;
    }

    /** @return array<string, mixed> */
    private function extractCreateFamily(string $inputText): array
    {
        $name = trim(preg_replace('/\b(crea|crear)\s+(una\s+)?familia\b/iu', '', $inputText) ?? $inputText);
        $name = preg_replace('/,\s*(direccion|dirección|telefono|teléfono|email)\b.*$/iu', '', $name) ?? $name;
        return ['nombre_familia' => $this->titleName(trim($name))];
    }

    /** @return array<string, mixed> */
    private function extractFamilyAssignment(string $text): array
    {
        $fields = [];
        if (preg_match('/(?:agrega|agregar|asigna|asignar)\s+(.+?)\s+a\s+familia/u', $text, $matches) === 1) {
            $fields['persona_nombre'] = $this->titleName($matches[1]);
        }
        if (preg_match('/familia\s+(.+?)(?:\s+como|$)/u', $text, $matches) === 1) {
            $fields['familia_nombre'] = $this->titleName($matches[1]);
        }
        if (preg_match('/\bcomo\s+([a-z]+)\b/u', $text, $matches) === 1) {
            $fields['parentesco'] = trim($matches[1]);
        }
        return $fields;
    }

    /** @return array<string, mixed> */
    private function extractIncome(string $text, string $originalText, string $subtipo): array
    {
        $fields = [
            'monto' => $this->extractAmount($text),
            'fecha_movimiento' => $this->extractDate($text) ?? date('Y-m-d'),
            'medio_pago' => 'efectivo',
            'descripcion' => $originalText,
            'subtipo' => $subtipo,
        ];

        if (preg_match('/\ben\s+(.+?)(?:\s+de\s+|\s+para\s+|$)/u', $text, $matches) === 1) {
            $fields['cuenta_nombre'] = $this->titleName($matches[1]);
            $fields['cuenta_id'] = null;
        }

        if (preg_match('/\b(?:de|para)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})(?:\s+de\s+[0-9]|\s+por\s+[0-9]|$)/u', $text, $matches) === 1) {
            $name = trim($matches[1]);
            if (!in_array($name, ['caja', 'caja principal'], true)) {
                $fields['persona_nombre'] = $this->titleName($name);
            }
        }

        return $fields;
    }

    /** @return array<string, mixed> */
    private function extractExpense(string $text, string $originalText): array
    {
        $fields = [
            'monto' => $this->extractAmount($text),
            'fecha_movimiento' => $this->extractDate($text) ?? date('Y-m-d'),
            'medio_pago' => 'efectivo',
            'descripcion' => $originalText,
        ];

        if (preg_match('/\ben\s+(.+?)(?:\s+por\s+|$)/u', $text, $matches) === 1) {
            $fields['cuenta_nombre'] = $this->titleName($matches[1]);
            $fields['cuenta_id'] = null;
        }

        if (preg_match('/\bpor\s+(.+?)(?:\s+en\s+|$)/u', $text, $matches) === 1) {
            $fields['categoria_nombre'] = $this->titleName($matches[1]);
            $fields['categoria_id'] = null;
        }

        return $fields;
    }

    /** @return array<string, mixed> */
    private function extractDateRange(string $text): array
    {
        preg_match_all('/\b(20[0-9]{2}-[0-9]{2}-[0-9]{2})\b/u', $text, $matches);
        $dates = $matches[1] ?? [];
        if (count($dates) >= 2) {
            return ['fecha_inicio' => $dates[0], 'fecha_fin' => $dates[1]];
        }

        $month = $this->extractMonth($text);
        if ($month !== null) {
            $year = (int) date('Y');
            $start = sprintf('%04d-%02d-01', $year, $month);
            return ['fecha_inicio' => $start, 'fecha_fin' => date('Y-m-t', strtotime($start))];
        }

        $date = $this->extractDate($text) ?? date('Y-m-d');
        return ['fecha_inicio' => $date, 'fecha_fin' => $date];
    }

    /** @return array<string, mixed> */
    private function extractDiscipulado(string $text): array
    {
        $fields = [];
        if (preg_match('/asigna\s+(.+?)\s+a\s+discipulado/u', $text, $matches) === 1) {
            $fields['persona_nombre'] = $this->titleName($matches[1]);
        }
        if (preg_match('/discipulado\s+(.+)$/u', $text, $matches) === 1) {
            $fields['ruta_nombre'] = $this->titleName($matches[1]);
        }
        return $fields;
    }

    /** @return array<string, mixed> */
    private function extractPrayer(string $text, string $originalText): array
    {
        $fields = [
            'titulo' => 'Peticion de oracion',
            'privacidad' => 'privada',
        ];

        if (preg_match('/persona\s+([0-9]+)/u', $text, $matches) === 1) {
            $fields['persona_id'] = (int) $matches[1];
        } elseif (preg_match('/para\s+([^:]+)(?::|$)/u', $text, $matches) === 1) {
            $fields['persona_nombre'] = $this->titleName(trim($matches[1]));
        }

        if (str_contains($originalText, ':')) {
            $fields['detalle'] = trim(explode(':', $originalText, 2)[1]);
        } elseif (preg_match('/oraci[oó]n\s+(.+)$/u', $text, $matches) === 1) {
            $fields['detalle'] = trim($matches[1]);
        }

        return $fields;
    }

    /** @return array<string, mixed> */
    private function extractPastoralCase(string $text, string $originalText): array
    {
        $fields = [
            'titulo' => 'Caso pastoral',
            'tipo' => 'acompanamiento',
            'prioridad' => 'media',
            'descripcion_general' => str_contains($originalText, ':') ? trim(explode(':', $originalText, 2)[1]) : $originalText,
            'es_confidencial' => true,
        ];

        if (preg_match('/persona\s+([0-9]+)/u', $text, $matches) === 1) {
            $fields['persona_id'] = (int) $matches[1];
        } elseif (preg_match('/para\s+([^:]+)(?::|$)/u', $text, $matches) === 1) {
            $fields['persona_nombre'] = $this->titleName(trim($matches[1]));
        }

        return $fields;
    }

    /** @return array<string, mixed> */
    private function extractReminder(string $text, string $originalText): array
    {
        $title = trim(preg_replace('/\b(recuerdame|recuérdame|recordatorio|agenda)\b/u', '', $text) ?? $text);
        $title = trim(preg_replace('/\b(hoy|manana|mañana)\b.*$/u', '', $title) ?? $title);
        $date = $this->extractDate($text);
        $time = '09:00:00';
        if (preg_match('/\b(?:a\s+las\s+)?([01]?[0-9]|2[0-3])(?::([0-5][0-9]))?\b/u', $text, $matches) === 1) {
            $time = str_pad($matches[1], 2, '0', STR_PAD_LEFT) . ':' . ($matches[2] ?? '00') . ':00';
        }

        $fields = [
            'titulo' => $title !== '' ? $this->titleName($title) : null,
            'descripcion' => $originalText,
            'fecha_hora' => $date === null ? null : $date . ' ' . $time,
            'modulo_origen' => 'agent',
            'referencia_id' => null,
        ];

        if (preg_match('/\ba\s+(.+?)(?:\s+(?:hoy|manana|mañana|a las|[0-9]{1,2}(?::[0-9]{2})?)|$)/u', $text, $matches) === 1) {
            $fields['persona_nombre'] = $this->titleName($matches[1]);
        }

        return $fields;
    }

    private function extractAmount(string $text): ?float
    {
        if (preg_match('/\b([0-9]{1,3}(?:[.][0-9]{3})+|[0-9]+)(?:,\d+)?\b/u', $text, $matches) !== 1) {
            return null;
        }
        return (float) str_replace('.', '', $matches[1]);
    }

    private function extractDate(string $text): ?string
    {
        if (preg_match('/\b(20[0-9]{2}-[0-9]{2}-[0-9]{2})\b/u', $text, $matches) === 1) {
            return $matches[1];
        }
        if (preg_match('/\b(manana|mañana)\b/u', $text) === 1) {
            return date('Y-m-d', strtotime('+1 day'));
        }
        if (preg_match('/\bhoy\b/u', $text) === 1) {
            return date('Y-m-d');
        }
        return null;
    }

    private function extractMonth(string $text): ?int
    {
        $months = [
            'enero' => 1,
            'febrero' => 2,
            'marzo' => 3,
            'abril' => 4,
            'mayo' => 5,
            'junio' => 6,
            'julio' => 7,
            'agosto' => 8,
            'septiembre' => 9,
            'setiembre' => 9,
            'octubre' => 10,
            'noviembre' => 11,
            'diciembre' => 12,
        ];
        foreach ($months as $name => $month) {
            if (preg_match('/\b' . preg_quote($name, '/') . '\b/u', $text) === 1) {
                return $month;
            }
        }
        return null;
    }

    /** @param array<string, mixed> $fields @param array<int, string> $required */
    private function missingFields(array $fields, array $required): array
    {
        $missing = [];
        foreach ($required as $field) {
            if (!array_key_exists($field, $fields) || $fields[$field] === null || $fields[$field] === '' || $fields[$field] === 0 || $fields[$field] === 0.0) {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    private function normalize(string $value): string
    {
        return trim(mb_strtolower(preg_replace('/\s+/', ' ', $value) ?? $value, 'UTF-8'));
    }

    private function titleName(string $value): string
    {
        return mb_convert_case(trim($value), MB_CASE_TITLE, 'UTF-8');
    }
}
