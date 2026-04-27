<?php

declare(strict_types=1);

final class CrmContactosService
{
    public function __construct(
        private readonly CrmContactosRepository $repository
    ) {
    }

    public function listByPersona(int $tenantId, int $personaId): array
    {
        if (!$this->repository->personaExists($tenantId, $personaId)) {
            throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        }

        return $this->repository->listByPersona($tenantId, $personaId);
    }

    public function create(int $tenantId, int $personaId, int $userId, array $input): int
    {
        if (!$this->repository->personaExists($tenantId, $personaId)) {
            throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        }

        $data = [
            'tipo_contacto' => isset($input['tipo_contacto']) && $input['tipo_contacto'] !== '' ? (string) $input['tipo_contacto'] : 'otro',
            'fecha_contacto' => (string) $input['fecha_contacto'],
            'asunto' => $this->nullableString($input['asunto'] ?? null),
            'resumen' => $this->nullableString($input['resumen'] ?? null),
            'resultado' => $this->nullableString($input['resultado'] ?? null),
            'requiere_seguimiento' => !empty($input['requiere_seguimiento']) ? 1 : 0,
            'fecha_seguimiento' => $this->nullableString($input['fecha_seguimiento'] ?? null),
        ];

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $contactoId = $this->repository->create($tenantId, $personaId, $userId, $data);
            $this->repository->auditCreated($tenantId, $userId, $contactoId, $data + ['id' => $contactoId, 'persona_id' => $personaId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        return $contactoId;
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null || trim((string) $value) === '' ? null : trim((string) $value);
    }
}
