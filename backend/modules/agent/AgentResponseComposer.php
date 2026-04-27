<?php

declare(strict_types=1);

final class AgentResponseComposer
{
    public function compose(string $intent): string
    {
        return match ($intent) {
            'saludo' => 'Hola, soy el asistente de FeActiva Iglesia. Pronto podré ayudarte con consultas y gestiones.',
            'consulta_finanzas' => 'Puedes revisar el resumen financiero en el módulo Finanzas. Pronto podré entregarte el detalle por aquí.',
            'consulta_crm' => 'Puedes revisar la información de personas y familias en el módulo CRM. Pronto podré ayudarte a consultarla desde aquí.',
            'oracion' => 'Puedes registrar y revisar solicitudes de oración en el módulo Pastoral. Pronto podré ayudarte a gestionarlas por aquí.',
            default => 'Todavía no puedo resolver esa solicitud. Pronto podré ayudarte con más consultas y gestiones en FeActiva Iglesia.',
        };
    }
}
