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
            default => 'Todavia no puedo resolver esa solicitud. Pronto podre ayudarte con mas consultas y gestiones en FeActiva Iglesia.',
        };
    }

    private function composeToolResponse(string $intent, array $toolExecution): string
    {
        $status = (string) ($toolExecution['status'] ?? 'failed');
        $toolName = (string) ($toolExecution['tool_name'] ?? '');
        $output = is_array($toolExecution['output'] ?? null) ? $toolExecution['output'] : [];

        if ($status === 'blocked') {
            return 'No tienes permiso para ejecutar esta consulta o accion desde el agente.';
        }

        if ($status === 'failed') {
            if (($output['reason'] ?? '') === 'missing_prayer_data') {
                return 'Para crear la solicitud de oracion necesito al menos el ID de la persona y el detalle de la peticion.';
            }

            return 'No pude ejecutar esa herramienta en este momento.';
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

        if ($toolName === 'crm_search_person') {
            $results = is_array($output['results'] ?? null) ? $output['results'] : [];

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
            return sprintf(
                'Solicitud de oracion creada con ID %d para la persona %d.',
                (int) ($output['id'] ?? 0),
                (int) ($output['persona_id'] ?? 0)
            );
        }

        return $this->compose($intent);
    }
}
