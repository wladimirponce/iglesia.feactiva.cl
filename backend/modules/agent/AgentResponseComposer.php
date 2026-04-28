<?php

declare(strict_types=1);

final class AgentResponseComposer
{
    public function compose(string $intent, ?array $toolExecution = null): string
    {
        if ($toolExecution !== null) {
            return $this->composeToolResponse($intent, $toolExecution);
        }

        return match ($intent) {
            'saludo' => 'Hola, soy el asistente de FeActiva Iglesia. Pronto podre ayudarte con consultas y gestiones.',
            'consulta_finanzas' => 'Puedes revisar el resumen financiero en el modulo Finanzas. Pronto podre entregarte el detalle por aqui.',
            'consulta_crm' => 'Puedes revisar la informacion de personas y familias en el modulo CRM. Pronto podre ayudarte a consultarla desde aqui.',
            'oracion' => 'Puedes registrar y revisar solicitudes de oracion en el modulo Pastoral. Pronto podre ayudarte a gestionarlas por aqui.',
            default => 'Puedo ayudarte con: personas, familias, finanzas, discipulado o pastoral.',
        };
    }

    private function composeToolResponse(string $intent, array $toolExecution): string
    {
        $status = (string) ($toolExecution['status'] ?? 'failed');
        $toolName = (string) ($toolExecution['tool_name'] ?? '');
        $output = is_array($toolExecution['output'] ?? null) ? $toolExecution['output'] : [];

        if ($status === 'unresolved') {
            return 'Puedo ayudarte con: personas, familias, finanzas, discipulado o pastoral.';
        }

        if ($status === 'unhandled') {
            return 'Esa funcion aun no esta disponible.';
        }

        if ($status === 'blocked') {
            return 'No tienes permisos para realizar esta accion.';
        }

        if ($status === 'failed') {
            $reason = (string) ($output['reason'] ?? '');
            $missingFields = is_array($output['missing_fields'] ?? null) ? implode(', ', $output['missing_fields']) : '';

            if ($reason === 'missing_prayer_data') {
                return 'Para crear la solicitud de oracion necesito al menos la persona y el detalle de la peticion.';
            }

            if ($reason === 'entity_ambiguous') {
                return 'Encontre varias coincidencias. Indica el ID exacto que debo usar.';
            }

            if ($reason === 'entity_not_found') {
                return 'No encontre una entidad necesaria para ejecutar esa accion. Indica el nombre exacto o el ID.';
            }

            if (in_array($reason, [
                'missing_finance_data', 'missing_person_data', 'missing_reminder_data',
                'missing_person_update_data', 'missing_family_data', 'missing_family_assign_data',
                'missing_discipulado_data', 'missing_stage_data', 'missing_pastoral_case_data',
                'missing_ontology_data',
            ], true)) {
                return 'Me faltan datos para ejecutar esa accion. Indica: ' . ($missingFields !== '' ? $missingFields : 'datos obligatorios') . '.';
            }

            return 'No pude ejecutar esa herramienta en este momento.';
        }

        if ($toolName === 'crm_create_person') {
            return sprintf(
                'Persona creada: #%d %s %s.',
                (int) ($output['id'] ?? 0),
                (string) ($output['nombres'] ?? ''),
                (string) ($output['apellidos'] ?? '')
            );
        }

        if ($toolName === 'crm_update_person') {
            return sprintf('Persona #%d actualizada.', (int) ($output['id'] ?? 0));
        }

        if ($toolName === 'crm_create_family') {
            return sprintf('Familia creada: #%d %s.', (int) ($output['id'] ?? 0), (string) ($output['nombre_familia'] ?? ''));
        }

        if ($toolName === 'crm_assign_person_to_family') {
            return sprintf('Persona %d agregada a familia %d como %s.', (int) ($output['persona_id'] ?? 0), (int) ($output['familia_id'] ?? 0), (string) ($output['parentesco'] ?? 'otro'));
        }

        if ($toolName === 'finanzas_get_summary') {
            return sprintf(
                'Resumen financiero del %s al %s: ingresos %s, egresos %s, saldo neto %s.',
                (string) ($output['fecha_inicio'] ?? ''),
                (string) ($output['fecha_fin'] ?? ''),
                number_format((float) ($output['ingresos'] ?? 0), 0, ',', '.'),
                number_format((float) ($output['egresos'] ?? 0), 0, ',', '.'),
                number_format((float) ($output['saldo_neto'] ?? 0), 0, ',', '.')
            );
        }

        if ($toolName === 'finanzas_create_income') {
            return sprintf(
                'Ingreso registrado con ID %d por %s.',
                (int) ($output['id'] ?? 0),
                number_format((float) ($output['monto'] ?? 0), 0, ',', '.')
            );
        }

        if ($toolName === 'finanzas_create_expense') {
            return sprintf(
                'Egreso registrado con ID %d por %s.',
                (int) ($output['id'] ?? 0),
                number_format((float) ($output['monto'] ?? 0), 0, ',', '.')
            );
        }

        if ($toolName === 'finanzas_get_balance_by_date') {
            return sprintf(
                'Saldo total al %s: %s.',
                (string) ($output['fecha'] ?? ''),
                number_format((float) ($output['saldo_total'] ?? 0), 0, ',', '.')
            );
        }

        if ($toolName === 'contabilidad_get_balance') {
            $cuentas = is_array($output['cuentas'] ?? null) ? count($output['cuentas']) : 0;
            return sprintf('Balance contable del %s al %s generado con %d cuentas.', (string) ($output['fecha_inicio'] ?? ''), (string) ($output['fecha_fin'] ?? ''), $cuentas);
        }

        if ($toolName === 'discipulado_assign_route') {
            return sprintf('Ruta de discipulado %d asignada a persona %d.', (int) ($output['ruta_id'] ?? 0), (int) ($output['persona_id'] ?? 0));
        }

        if ($toolName === 'discipulado_complete_stage') {
            return sprintf('Etapa de discipulado %d marcada como completada.', (int) ($output['persona_etapa_id'] ?? 0));
        }

        if ($toolName === 'pastoral_create_case') {
            return sprintf('Caso pastoral creado con ID %d.', (int) ($output['id'] ?? 0));
        }

        if ($toolName === 'crm_search_person') {
            $results = is_array($output['results'] ?? null) ? $output['results'] : [];

            if (is_array($output['auto_created_person'] ?? null)) {
                $person = $output['auto_created_person'];
                return sprintf(
                    'No encontre esa persona, asi que la cree como visita: #%d %s %s.',
                    (int) ($person['id'] ?? 0),
                    (string) ($person['nombres'] ?? ''),
                    (string) ($person['apellidos'] ?? '')
                );
            }

            if ($results === []) {
                return 'No encontre personas que coincidan con esa busqueda.';
            }

            $lines = array_map(static fn (array $person): string => sprintf(
                '#%d %s %s',
                (int) $person['id'],
                (string) $person['nombres'],
                (string) $person['apellidos']
            ), array_slice($results, 0, 5));

            return 'Encontre estas personas: ' . implode('; ', $lines) . '.';
        }

        if ($toolName === 'pastoral_create_prayer_request') {
            $personaText = ($output['persona_id'] ?? null) === null
                ? 'sin persona asociada'
                : 'para la persona ' . (string) ((int) $output['persona_id']);

            return sprintf(
                'Solicitud de oracion creada con ID %d %s.',
                (int) ($output['id'] ?? 0),
                $personaText
            );
        }

        if ($toolName === 'reminder_create') {
            return sprintf(
                'Recordatorio creado con ID %d para %s.',
                (int) ($output['id'] ?? 0),
                (string) ($output['fecha_hora'] ?? '')
            );
        }

        if ($toolName === 'reminder_search') {
            $items = is_array($output['recordatorios'] ?? null) ? $output['recordatorios'] : [];
            if ($items === []) {
                return 'No tienes recordatorios en ese periodo.';
            }
            return 'Tienes ' . count($items) . ' recordatorio(s) en ese periodo.';
        }

        return $this->compose($intent);
    }
}
