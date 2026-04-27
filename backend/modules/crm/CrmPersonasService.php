<?php

declare(strict_types=1);

final class CrmPersonasService
{
    public function __construct(
        private readonly CrmPersonasRepository $repository
    ) {
    }

    public function list(int $tenantId, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        $offset = ($page - 1) * $limit;

        $total = $this->repository->count($tenantId);

        return [
            'data' => $this->repository->list($tenantId, $limit, $offset),
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => (int) ceil($total / $limit),
            ],
        ];
    }

    public function findById(int $tenantId, int $personaId): ?array
    {
        return $this->repository->findById($tenantId, $personaId);
    }

    public function create(int $tenantId, int $userId, array $input): int
    {
        $data = $this->normalizeCreateInput($input);

        if ($this->repository->documentExists($tenantId, $data['tipo_documento'], $data['numero_documento'])) {
            throw new RuntimeException('CRM_DUPLICATE_DOCUMENT');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $personaId = $this->repository->create($tenantId, $userId, $data);
            $this->repository->createMembershipHistory($tenantId, $personaId, null, $data['estado_persona'], $userId, 'Creacion de persona');
            $this->repository->auditCreated($tenantId, $userId, $personaId, $data + ['id' => $personaId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        return $personaId;
    }

    public function update(int $tenantId, int $userId, int $personaId, array $input): void
    {
        $oldPersona = $this->repository->findById($tenantId, $personaId);

        if ($oldPersona === null) {
            throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        }

        $data = $this->normalizeUpdateInput($input);

        if ($data === []) {
            throw new RuntimeException('CRM_EMPTY_UPDATE');
        }

        $tipoDocumento = array_key_exists('tipo_documento', $data) ? $data['tipo_documento'] : $oldPersona['tipo_documento'];
        $numeroDocumento = array_key_exists('numero_documento', $data) ? $data['numero_documento'] : $oldPersona['numero_documento'];

        if ($this->repository->documentExistsExcluding($tenantId, $personaId, $tipoDocumento, $numeroDocumento)) {
            throw new RuntimeException('CRM_DUPLICATE_DOCUMENT');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $this->repository->update($tenantId, $personaId, $userId, $data);

            if (array_key_exists('estado_persona', $data) && $data['estado_persona'] !== $oldPersona['estado_persona']) {
                $this->repository->createMembershipHistory(
                    $tenantId,
                    $personaId,
                    $oldPersona['estado_persona'],
                    $data['estado_persona'],
                    $userId,
                    'Cambio de estado de persona'
                );
            }

            $newPersona = $this->repository->findById($tenantId, $personaId);
            $this->repository->auditUpdated($tenantId, $userId, $personaId, $oldPersona, $newPersona ?? $data);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function delete(int $tenantId, int $userId, int $personaId): void
    {
        $oldPersona = $this->repository->findById($tenantId, $personaId);

        if ($oldPersona === null) {
            throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $this->repository->softDelete($tenantId, $personaId, $userId);
            $this->repository->auditDeleted($tenantId, $userId, $personaId, $oldPersona);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private function normalizeCreateInput(array $input): array
    {
        $fields = [
            'nombre_preferido',
            'tipo_documento',
            'numero_documento',
            'email',
            'telefono',
            'whatsapp',
            'fecha_nacimiento',
            'genero',
            'estado_civil',
            'direccion',
            'ciudad',
            'region',
            'pais',
            'fecha_primer_contacto',
            'fecha_ingreso',
            'fecha_membresia',
            'origen_contacto',
            'observaciones_generales',
            'foto_url',
        ];

        $data = [
            'nombres' => trim((string) $input['nombres']),
            'apellidos' => trim((string) $input['apellidos']),
            'estado_persona' => isset($input['estado_persona']) && $input['estado_persona'] !== ''
                ? (string) $input['estado_persona']
                : 'visita',
        ];

        foreach ($fields as $field) {
            $value = $input[$field] ?? null;
            $data[$field] = $value === null || trim((string) $value) === '' ? null : trim((string) $value);
        }

        return $data;
    }

    private function normalizeUpdateInput(array $input): array
    {
        $allowedFields = [
            'nombres',
            'apellidos',
            'nombre_preferido',
            'tipo_documento',
            'numero_documento',
            'email',
            'telefono',
            'whatsapp',
            'fecha_nacimiento',
            'genero',
            'estado_civil',
            'direccion',
            'ciudad',
            'region',
            'pais',
            'estado_persona',
            'fecha_primer_contacto',
            'fecha_ingreso',
            'fecha_membresia',
            'origen_contacto',
            'observaciones_generales',
            'foto_url',
        ];

        $data = [];

        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $value = $input[$field];
            $data[$field] = $value === null || trim((string) $value) === '' ? null : trim((string) $value);
        }

        return $data;
    }
}
