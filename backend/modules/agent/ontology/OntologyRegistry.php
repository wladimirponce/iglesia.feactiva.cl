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
        $this->registerObject(new OntologyObject('CuentaContable', 'contabilidad', 'Cuenta del plan contable.'));
        $this->registerObject(new OntologyObject('CasoPastoral', 'pastoral', 'Caso de seguimiento pastoral.'));
        $this->registerObject(new OntologyObject('RutaDiscipulado', 'discipulado', 'Ruta de crecimiento o discipulado.'));
        $this->registerObject(new OntologyObject('Recordatorio', 'agenda', 'Recordatorio agendado para seguimiento.'));

        $this->relations[] = new OntologyRelation('Persona', 'familia', 'Familia');
        $this->relations[] = new OntologyRelation('Persona', 'discipulado', 'RutaDiscipulado');
        $this->relations[] = new OntologyRelation('Persona', 'movimientos', 'MovimientoFinanciero');
        $this->relations[] = new OntologyRelation('Persona', 'casos_pastorales', 'CasoPastoral');

        $this->registerAction(new OntologyAction('crear_persona', 'Persona', 'crm_create_person', 'crm.personas.crear'));
        $this->registerAction(new OntologyAction('buscar_persona', 'Persona', 'crm_search_person', 'crm.personas.ver'));
        $this->registerAction(new OntologyAction('registrar_diezmo', 'MovimientoFinanciero', 'finanzas_create_income', 'fin.movimientos.crear'));
        $this->registerAction(new OntologyAction('crear_oracion', 'CasoPastoral', 'pastoral_create_prayer_request', 'past.oracion.crear'));
        $this->registerAction(new OntologyAction('crear_recordatorio', 'Recordatorio', 'reminder_create', 'agenda.recordatorios.crear'));
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
