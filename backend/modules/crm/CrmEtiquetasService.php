<?php

declare(strict_types=1);

final class CrmEtiquetasService
{
    public function __construct(
        private readonly CrmEtiquetasRepository $repository
    ) {
    }

    public function list(int $tenantId): array
    {
        return $this->repository->list($tenantId);
    }

    public function create(int $tenantId, int $userId, array $input): int
    {
        $data = $this->normalize($input, true);
        $id = $this->repository->create($tenantId, $userId, $data);
        $this->repository->audit($tenantId, $userId, 'crm.etiqueta.created', 'crm_etiquetas', $id, null, $data + ['id' => $id]);
        return $id;
    }

    public function update(int $tenantId, int $userId, int $etiquetaId, array $input): void
    {
        $old = $this->repository->find($tenantId, $etiquetaId);
        if ($old === null) {
            throw new RuntimeException('CRM_TAG_NOT_FOUND');
        }

        $data = $this->normalize($input, false);
        if ($data === []) {
            throw new RuntimeException('CRM_EMPTY_UPDATE');
        }

        $this->repository->update($tenantId, $etiquetaId, $data);
        $new = $this->repository->find($tenantId, $etiquetaId);
        $this->repository->audit($tenantId, $userId, 'crm.etiqueta.updated', 'crm_etiquetas', $etiquetaId, $old, $new);
    }

    public function delete(int $tenantId, int $userId, int $etiquetaId): void
    {
        $old = $this->repository->find($tenantId, $etiquetaId);
        if ($old === null) {
            throw new RuntimeException('CRM_TAG_NOT_FOUND');
        }

        $this->repository->delete($tenantId, $etiquetaId);
        $this->repository->audit($tenantId, $userId, 'crm.etiqueta.deleted', 'crm_etiquetas', $etiquetaId, $old, null);
    }

    public function assign(int $tenantId, int $userId, int $personaId, int $etiquetaId): void
    {
        $this->guardPersonaAndEtiqueta($tenantId, $personaId, $etiquetaId);
        $this->repository->assignToPersona($tenantId, $personaId, $etiquetaId, $userId);
        $this->repository->audit($tenantId, $userId, 'crm.etiqueta.assigned', 'crm_persona_etiquetas', null, null, ['persona_id' => $personaId, 'etiqueta_id' => $etiquetaId]);
    }

    public function remove(int $tenantId, int $userId, int $personaId, int $etiquetaId): void
    {
        $this->guardPersonaAndEtiqueta($tenantId, $personaId, $etiquetaId);
        $this->repository->removeFromPersona($tenantId, $personaId, $etiquetaId);
        $this->repository->audit($tenantId, $userId, 'crm.etiqueta.removed', 'crm_persona_etiquetas', null, ['persona_id' => $personaId, 'etiqueta_id' => $etiquetaId], null);
    }

    private function guardPersonaAndEtiqueta(int $tenantId, int $personaId, int $etiquetaId): void
    {
        if (!$this->repository->personaExists($tenantId, $personaId)) {
            throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        }

        if ($this->repository->find($tenantId, $etiquetaId) === null) {
            throw new RuntimeException('CRM_TAG_NOT_FOUND');
        }
    }

    private function normalize(array $input, bool $requireNombre): array
    {
        $data = [];

        if ($requireNombre || array_key_exists('nombre', $input)) {
            $data['nombre'] = trim((string) $input['nombre']);
        }

        foreach (['descripcion', 'color'] as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $value = $input[$field];
            $data[$field] = $value === null || trim((string) $value) === '' ? null : trim((string) $value);
        }

        return $data;
    }
}
