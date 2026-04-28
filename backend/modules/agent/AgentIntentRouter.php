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

        if (preg_match('/\b(crea|crear|registra|registrar)\s+(una\s+)?persona\b/u', $text) === 1) {
            return 'crm_create_person';
        }

        if (preg_match('/\b(actualiza|editar|edita|cambia)\s+(la\s+)?persona\b/u', $text) === 1) {
            return 'crm_update_person';
        }

        if (preg_match('/\b(crea|crear)\s+(una\s+)?familia\b/u', $text) === 1) {
            return 'crm_create_family';
        }

        if (preg_match('/\b(agrega|asigna|vincula).*\bfamilia\b/u', $text) === 1) {
            return 'crm_assign_person_to_family';
        }

        if (preg_match('/\b(qu[eé] tengo agendado|agenda para|recordatorios para)\b/u', $text) === 1) {
            return 'reminder_search';
        }

        if (preg_match('/\b(recu[eé]rdame|recordatorio|agenda)\b/u', $text) === 1) {
            return 'reminder_create';
        }

        if (preg_match('/\b(balance contable|balance de comprobaci[oó]n|contabilidad)\b/u', $text) === 1) {
            return 'contabilidad_balance';
        }

        if (preg_match('/\b(asigna|asignar).*\bdiscipulado\b/u', $text) === 1) {
            return 'discipulado_assign_route';
        }

        if (preg_match('/\b(completa|completar).*\b(etapa|discipulado)\b/u', $text) === 1) {
            return 'discipulado_complete_stage';
        }

        if (preg_match('/\b(crea|crear)\s+(un\s+)?caso pastoral\b/u', $text) === 1) {
            return 'pastoral_create_case';
        }

        if (preg_match('/\b(cu[aá]nto dinero hay|saldo al d[ií]a|saldo al|balance al)\b/u', $text) === 1) {
            return 'finanzas_balance';
        }

        if (preg_match('/\b(registra|registrar|crea|crear)\s+(un\s+|una\s+)?(diezmo|ofrenda|donacion|donaci[oó]n|ingreso)\b/u', $text) === 1) {
            return 'finanzas_create_income';
        }

        if (preg_match('/\b(registra|registrar|crea|crear)\s+(un\s+|una\s+)?(egreso|gasto|pago)\b/u', $text) === 1) {
            return 'finanzas_create_expense';
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
