<?php

declare(strict_types=1);

final class OntologyRegistry
{
    /** @var array<string, OntologyObject> */
    private array $objects = [];

    /** @var array<string, OntologyAction> */
    private array $actions = [];

    /** @var array<int, OntologyRelation> */
    private array $relations = [];

    public function __construct()
    {
        $this->registerObject(new OntologyObject('Persona', 'crm', 'Persona registrada en el CRM de la iglesia.'));
        $this->registerObject(new OntologyObject('Familia', 'crm', 'Grupo familiar del CRM.'));
        $this->registerObject(new OntologyObject('MovimientoFinanciero', 'finanzas', 'Ingreso o egreso financiero.'));
        $this->registerObject(new OntologyObject('CuentaFinanciera', 'finanzas', 'Cuenta de caja, banco u otro medio financiero.'));
        $this->registerObject(new OntologyObject('CuentaContable', 'contabilidad', 'Cuenta del plan contable.'));
        $this->registerObject(new OntologyObject('CasoPastoral', 'pastoral', 'Caso de seguimiento pastoral.'));
        $this->registerObject(new OntologyObject('SolicitudOracion', 'pastoral', 'Solicitud de oracion pastoral.'));
        $this->registerObject(new OntologyObject('RutaDiscipulado', 'discipulado', 'Ruta de crecimiento o discipulado.'));
        $this->registerObject(new OntologyObject('Recordatorio', 'agenda', 'Recordatorio agendado para seguimiento.'));
        $this->registerObject(new OntologyObject('AgendaItem', 'agenda', 'Item de agenda, llamada, reunion, tarea o seguimiento.'));
        $this->registerObject(new OntologyObject('AgendaNotification', 'agenda', 'Notificacion programada de agenda.'));

        $this->relations[] = new OntologyRelation('Persona', 'pertenece_a', 'Familia');
        $this->relations[] = new OntologyRelation('Persona', 'tiene', 'MovimientoFinanciero');
        $this->relations[] = new OntologyRelation('Persona', 'tiene', 'CasoPastoral');
        $this->relations[] = new OntologyRelation('Persona', 'participa_en', 'RutaDiscipulado');
        $this->relations[] = new OntologyRelation('Persona', 'tiene', 'SolicitudOracion');
        $this->relations[] = new OntologyRelation('MovimientoFinanciero', 'afecta', 'CuentaFinanciera');
        $this->relations[] = new OntologyRelation('MovimientoFinanciero', 'puede_generar', 'AsientoContable');

        $this->registerAction(new OntologyAction('buscar_persona', 'Persona', 'crm_search_person', 'crm.personas.ver', ['query'], [], 'low', false));
        $this->registerAction(new OntologyAction('crear_persona', 'Persona', 'crm_create_person', 'crm.personas.crear', ['nombres', 'apellidos'], ['phone', 'email', 'estado_persona'], 'medium', false));
        $this->registerAction(new OntologyAction('crear_familia', 'Familia', 'crm_create_family', 'crm.familias.crear', ['nombre_familia'], ['direccion', 'telefono_principal', 'email_principal'], 'medium', false));
        $this->registerAction(new OntologyAction('asignar_persona_familia', 'Familia', 'crm_assign_person_to_family', 'crm.familias.editar', ['persona_id', 'familia_id'], ['parentesco'], 'medium', false));
        $this->registerAction(new OntologyAction('registrar_diezmo', 'MovimientoFinanciero', 'finanzas_create_income', 'fin.movimientos.crear', ['monto', 'cuenta_id'], ['persona_id', 'persona_nombre', 'fecha_movimiento', 'medio_pago', 'descripcion'], 'medium', false));
        $this->registerAction(new OntologyAction('registrar_ofrenda', 'MovimientoFinanciero', 'finanzas_create_income', 'fin.movimientos.crear', ['monto', 'cuenta_id'], ['persona_id', 'persona_nombre', 'fecha_movimiento', 'medio_pago', 'descripcion'], 'medium', false));
        $this->registerAction(new OntologyAction('registrar_egreso', 'MovimientoFinanciero', 'finanzas_create_expense', 'fin.movimientos.crear', ['monto', 'cuenta_id', 'categoria_id'], ['fecha_movimiento', 'medio_pago', 'descripcion'], 'medium', false));
        $this->registerAction(new OntologyAction('consultar_saldo_financiero', 'MovimientoFinanciero', 'finanzas_get_balance_by_date', 'fin.reportes.ver', ['fecha'], [], 'low', false));
        $this->registerAction(new OntologyAction('consultar_balance_contable', 'CuentaContable', 'contabilidad_get_balance', 'acct.reportes.ver', ['fecha_inicio', 'fecha_fin'], [], 'low', false));
        $this->registerAction(new OntologyAction('asignar_discipulado', 'RutaDiscipulado', 'discipulado_assign_route', 'disc.avance.editar', ['persona_id', 'ruta_id'], ['mentor_persona_id'], 'medium', false));
        $this->registerAction(new OntologyAction('crear_solicitud_oracion', 'SolicitudOracion', 'pastoral_create_prayer_request', 'past.oracion.crear', ['detalle'], ['persona_id', 'titulo', 'privacidad'], 'medium', false));
        $this->registerAction(new OntologyAction('crear_caso_pastoral', 'CasoPastoral', 'pastoral_create_case', 'past.casos.crear', ['persona_id', 'titulo'], ['tipo', 'prioridad', 'descripcion_general', 'es_confidencial'], 'high', true));
        $this->registerAction(new OntologyAction('crear_recordatorio', 'AgendaItem', 'agenda_create_item', 'agenda.items.crear', ['titulo', 'fecha_inicio'], ['persona_id', 'familia_id', 'descripcion', 'tipo', 'prioridad'], 'low', false));
        $this->registerAction(new OntologyAction('agendar_llamada', 'AgendaItem', 'agenda_create_item', 'agenda.items.crear', ['titulo', 'fecha_inicio'], ['persona_id', 'persona_nombre', 'descripcion', 'tipo'], 'low', false));
        $this->registerAction(new OntologyAction('agendar_reunion', 'AgendaItem', 'agenda_create_item', 'agenda.items.crear', ['titulo', 'fecha_inicio'], ['persona_id', 'familia_id', 'familia_nombre', 'descripcion', 'tipo'], 'low', false));
        $this->registerAction(new OntologyAction('programar_whatsapp', 'AgendaNotification', 'agenda_create_whatsapp_notification', 'agenda.notifications.crear', ['fecha_inicio', 'message_text'], ['persona_id', 'persona_nombre', 'phone'], 'medium', false));
        $this->registerAction(new OntologyAction('consultar_agenda_dia', 'AgendaItem', 'agenda_get_day_schedule', 'agenda.items.ver', ['fecha'], [], 'low', false));
        $this->registerAction(new OntologyAction('completar_agenda_item', 'AgendaItem', 'agenda_complete_item', 'agenda.items.completar', ['agenda_item_id'], ['persona_id', 'persona_nombre', 'query'], 'medium', false));
        $this->registerAction(new OntologyAction('cancelar_agenda_item', 'AgendaItem', 'agenda_cancel_item', 'agenda.items.cancelar', ['agenda_item_id'], ['persona_id', 'persona_nombre', 'query'], 'medium', false));
        $this->registerAction(new OntologyAction('buscar_recordatorio', 'AgendaItem', 'agenda_get_day_schedule', 'agenda.items.ver', ['fecha'], ['persona_id'], 'low', false));
    }

    public function object(string $name): ?OntologyObject
    {
        return $this->objects[$name] ?? null;
    }

    public function action(string $name): ?OntologyAction
    {
        return $this->actions[$name] ?? null;
    }

    /** @return array<int, OntologyRelation> */
    public function relations(): array
    {
        return $this->relations;
    }

    private function registerObject(OntologyObject $object): void
    {
        $this->objects[$object->name] = $object;
    }

    private function registerAction(OntologyAction $action): void
    {
        $this->actions[$action->name] = $action;
    }
}
