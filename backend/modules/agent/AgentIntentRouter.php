<?php

declare(strict_types=1);

final class AgentIntentRouter
{
    public function detect(string $inputText): string
    {
        $text = mb_strtolower($this->normalize($inputText));

        if (preg_match('/\b(hola|buenos dias|buenas tardes|buenas noches|bendiciones|shalom)\b/u', $text) === 1) {
            return 'saludo';
        }

        if (preg_match('/\b(finanza|finanzas|resumen financiero|ingreso|ingresos|egreso|egresos|ofrenda|diezmo|saldo|cuenta|cuentas)\b/u', $text) === 1) {
            return 'consulta_finanzas';
        }

        if (preg_match('/\b(oracion|oración|orar|peticion|petición|intercede|intercesion|intercesión)\b/u', $text) === 1) {
            return 'oracion';
        }

        if (preg_match('/\b(persona|personas|miembro|miembros|visita|visitas|familia|familias|crm)\b/u', $text) === 1) {
            return 'consulta_crm';
        }

        return 'desconocido';
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
