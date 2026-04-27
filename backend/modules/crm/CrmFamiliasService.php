<?php

declare(strict_types=1);

final class CrmFamiliasService
{
    public function __construct(
        private readonly CrmFamiliasRepository $repository
    ) {
    }

    public function list(int $tenantId): array
    {
        return $this->repository->list($tenantId);
    }

    public function show(int $tenantId, int $familiaId): array
    {
        $familia = $this->repository->find($tenantId, $familiaId);

        if ($familia === null) {
            throw new RuntimeException('CRM_FAMILY_NOT_FOUND');
        }

        $familia['miembros'] = $this->repository->listMiembros($tenantId, $familiaId);

        return $familia;
    }

    public function create(int $tenantId, int $userId, array $input): int
    {
        $data = $this->normalize($input, true);
        $id = $this->repository->create($tenantId, $userId, $data);
        $this->repository->audit($tenantId, $userId, 'crm.familia.created', 'crm_familias', $id, null, $data + ['id' => $id]);
        return $id;
    }

    public function update(int $tenantId, int $userId, int $familiaId, array $input): void
    {
        $old = $this->repository->find($tenantId, $familiaId);

        if ($old === null) {
            throw new RuntimeException('CRM_FAMILY_NOT_FOUND');
        }

        $data = $this->normalize($input, false);

        if ($data === []) {
            throw new RuntimeException('CRM_EMPTY_UPDATE');
        }

        $this->repository->update($tenantId, $familiaId, $userId, $data);
        $new = $this->repository->find($tenantId, $familiaId);
        $this->repository->audit($tenantId, $userId, 'crm.familia.updated', 'crm_familias', $familiaId, $old, $new);
    }

    public function addPersona(int $tenantId, int $userId, int $familiaId, array $input): void
    {
        $familia = $this->repository->find($tenantId, $familiaId);
        $personaId = (int) $input['persona_id'];

        if ($familia === null) {
            throw new RuntimeException('CRM_FAMILY_NOT_FOUND');
        }

        if (!$this->repository->personaExists($tenantId, $personaId)) {
            throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        }

        if ($this->repository->relationExists($tenantId, $familiaId, $personaId)) {
            throw new RuntimeException('CRM_FAMILY_PERSON_DUPLICATE');
        }

        $data = [
            'parentesco' => $this->mapRelacion((string) ($input['tipo_relacion'] ?? 'otro')),
            'es_contacto_principal' => !empty($input['es_contacto_principal']) ? 1 : 0,
            'vive_en_hogar' => array_key_exists('vive_en_hogar', $input) ? (!empty($input['vive_en_hogar']) ? 1 : 0) : 1,
        ];

        $this->repository->addPersona($tenantId, $familiaId, $personaId, $data);
        $this->repository->audit($tenantId, $userId, 'crm.familia.person_added', 'crm_persona_familia', null, null, $data + [
            'familia_id' => $familiaId,
            'persona_id' => $personaId,
            'tipo_relacion' => $input['tipo_relacion'] ?? 'otro',
        ]);
    }

    public function removePersona(int $tenantId, int $userId, int $familiaId, int $personaId): void
    {
        if ($this->repository->find($tenantId, $familiaId) === null) {
            throw new RuntimeException('CRM_FAMILY_NOT_FOUND');
        }

        if (!$this->repository->personaExists($tenantId, $personaId)) {
            throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        }

        $this->repository->removePersona($tenantId, $familiaId, $personaId);
        $this->repository->audit($tenantId, $userId, 'crm.familia.person_removed', 'crm_persona_familia', null, [
            'familia_id' => $familiaId,
            'persona_id' => $personaId,
        ], null);
    }

    private function normalize(array $input, bool $requireNombre): array
    {
        $data = [];

        if ($requireNombre || array_key_exists('nombre_familia', $input)) {
            $data['nombre_familia'] = trim((string) $input['nombre_familia']);
        }

        foreach (['direccion', 'ciudad', 'region', 'pais', 'telefono_principal', 'email_principal', 'observaciones'] as $field) {
            if (!$requireNombre && !array_key_exists($field, $input)) {
                continue;
            }

            $value = $input[$field] ?? null;
            $data[$field] = $value === null || trim((string) $value) === '' ? null : trim((string) $value);
        }

        return $data;
    }

    private function mapRelacion(string $tipoRelacion): string
    {
        return match ($tipoRelacion) {
            'padre' => 'padre',
            'madre' => 'madre',
            'hijo' => 'hijo',
            'hija' => 'hija',
            'tutor' => 'tutor',
            default => 'otro',
        };
    }
}
