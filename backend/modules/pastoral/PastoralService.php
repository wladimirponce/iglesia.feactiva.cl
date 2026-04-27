<?php

declare(strict_types=1);

final class PastoralService
{
    public function __construct(
        private readonly PastoralRepository $repository,
        private readonly PermissionRepository $permissionRepository
    ) {
    }

    public function casos(int $tenantId, int $userId): array
    {
        $canSeeConfidential = $this->canSeeConfidential($tenantId, $userId);
        $casos = $this->repository->casos($tenantId, $canSeeConfidential);
        foreach ($casos as $caso) {
            $this->repository->auditSensitiveAccess($tenantId, $userId, (int) $caso['id'], 'list');
        }
        return $casos;
    }

    public function caso(int $tenantId, int $userId, int $casoId): array
    {
        return $this->authorizedCaso($tenantId, $userId, $casoId, 'show');
    }

    public function createCaso(int $tenantId, int $userId, array $input): int
    {
        $personaId = (int) $input['persona_id'];
        if (!$this->repository->personaExists($tenantId, $personaId)) {
            throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        }
        $responsableId = $this->intOrNull($input['responsable_user_id'] ?? null);
        if ($responsableId !== null && !$this->repository->userExists($responsableId)) {
            throw new RuntimeException('PAST_RESPONSABLE_NOT_FOUND');
        }

        $data = [
            'persona_id' => $personaId,
            'responsable_user_id' => $responsableId,
            'tipo' => $input['tipo'] ?? 'acompanamiento',
            'titulo' => trim((string) $input['titulo']),
            'descripcion_general' => $this->nullable($input['descripcion_general'] ?? null),
            'prioridad' => $input['prioridad'] ?? 'media',
            'estado' => $input['estado'] ?? 'abierto',
            'fecha_apertura' => (string) $input['fecha_apertura'],
            'fecha_cierre' => $this->nullable($input['fecha_cierre'] ?? null),
            'es_confidencial' => $this->boolInt($input['es_confidencial'] ?? true),
        ];
        $id = $this->repository->createCaso($tenantId, $userId, $data);
        $this->repository->audit($tenantId, $userId, 'past.caso.created', 'past_casos', $id, [
            'id' => $id,
            'persona_id' => $personaId,
            'tipo' => $data['tipo'],
            'prioridad' => $data['prioridad'],
            'estado' => $data['estado'],
            'es_confidencial' => $data['es_confidencial'],
        ]);
        return $id;
    }

    public function updateCaso(int $tenantId, int $userId, int $casoId, array $input): void
    {
        $this->authorizedCaso($tenantId, $userId, $casoId, 'update');
        $data = $this->casoUpdateData($tenantId, $input);
        $this->repository->updateCaso($tenantId, $casoId, $userId, $data);
        $this->repository->audit($tenantId, $userId, 'past.caso.updated', 'past_casos', $casoId, $this->safeCaseAudit($data));
    }

    public function closeCaso(int $tenantId, int $userId, int $casoId): void
    {
        $this->authorizedCaso($tenantId, $userId, $casoId, 'close');
        $this->repository->closeCaso($tenantId, $casoId, $userId);
        $this->repository->audit($tenantId, $userId, 'past.caso.closed', 'past_casos', $casoId, ['estado' => 'cerrado']);
    }

    public function sesiones(int $tenantId, int $userId, int $casoId): array
    {
        $this->authorizedCaso($tenantId, $userId, $casoId, 'sessions');
        return $this->repository->sesionesByCaso($tenantId, $casoId);
    }

    public function createSesion(int $tenantId, int $userId, int $casoId, array $input): int
    {
        $caso = $this->authorizedCaso($tenantId, $userId, $casoId, 'create_session');
        $data = [
            'fecha_sesion' => (string) $input['fecha_sesion'],
            'modalidad' => $input['modalidad'] ?? 'presencial',
            'resumen' => $this->nullable($input['resumen'] ?? null),
            'acuerdos' => $this->nullable($input['acuerdos'] ?? null),
            'proxima_accion' => $this->nullable($input['proxima_accion'] ?? null),
            'proxima_fecha' => $this->nullable($input['proxima_fecha'] ?? null),
            'es_confidencial' => $this->boolInt($input['es_confidencial'] ?? true),
        ];
        $id = $this->repository->createSesion($tenantId, $casoId, (int) $caso['persona_id'], $userId, $data);
        $this->repository->audit($tenantId, $userId, 'past.sesion.created', 'past_sesiones', $id, [
            'id' => $id,
            'caso_id' => $casoId,
            'persona_id' => (int) $caso['persona_id'],
            'modalidad' => $data['modalidad'],
            'fecha_sesion' => $data['fecha_sesion'],
            'es_confidencial' => $data['es_confidencial'],
        ]);
        return $id;
    }

    public function oraciones(int $tenantId): array
    {
        return $this->repository->oraciones($tenantId);
    }

    public function createOracion(int $tenantId, int $userId, array $input): int
    {
        $data = $this->oracionData($tenantId, $input);
        $id = $this->repository->createOracion($tenantId, $userId, $data);
        $this->repository->audit($tenantId, $userId, 'past.oracion.created', 'past_solicitudes_oracion', $id, [
            'id' => $id,
            'persona_id' => $data['persona_id'],
            'categoria' => $data['categoria'],
            'privacidad' => $data['privacidad'],
            'estado' => $data['estado'],
        ]);
        return $id;
    }

    public function updateOracion(int $tenantId, int $userId, int $oracionId, array $input): void
    {
        if ($this->repository->oracionById($tenantId, $oracionId) === null) {
            throw new RuntimeException('PAST_PRAYER_REQUEST_NOT_FOUND');
        }
        $data = $this->oracionData($tenantId, $input, true);
        $this->repository->updateOracion($tenantId, $oracionId, $data);
        $this->repository->audit($tenantId, $userId, 'past.oracion.updated', 'past_solicitudes_oracion', $oracionId, $this->safePrayerAudit($data));
    }

    public function closeOracion(int $tenantId, int $userId, int $oracionId): void
    {
        if ($this->repository->oracionById($tenantId, $oracionId) === null) {
            throw new RuntimeException('PAST_PRAYER_REQUEST_NOT_FOUND');
        }
        $this->repository->closeOracion($tenantId, $oracionId);
        $this->repository->audit($tenantId, $userId, 'past.oracion.closed', 'past_solicitudes_oracion', $oracionId, ['estado' => 'cerrada']);
    }

    public function createDerivacion(int $tenantId, int $userId, int $casoId, array $input): int
    {
        $caso = $this->authorizedCaso($tenantId, $userId, $casoId, 'derive');
        $derivadoUserId = $this->intOrNull($input['derivado_a_user_id'] ?? null);
        if ($derivadoUserId !== null && !$this->repository->userExists($derivadoUserId)) {
            throw new RuntimeException('PAST_DERIVADO_USER_NOT_FOUND');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $data = [
                'derivado_a_user_id' => $derivadoUserId,
                'derivado_a_nombre' => $this->nullable($input['derivado_a_nombre'] ?? null),
                'tipo_derivacion' => $input['tipo_derivacion'] ?? 'pastor',
                'motivo' => trim((string) $input['motivo']),
                'estado' => $input['estado'] ?? 'pendiente',
            ];
            $id = $this->repository->createDerivacion($tenantId, $casoId, (int) $caso['persona_id'], $userId, $data);
            $this->repository->markCasoDerivado($tenantId, $casoId, $userId);
            $this->repository->audit($tenantId, $userId, 'past.derivacion.created', 'past_derivaciones', $id, [
                'id' => $id,
                'caso_id' => $casoId,
                'persona_id' => (int) $caso['persona_id'],
                'derivado_a_user_id' => $data['derivado_a_user_id'],
                'tipo_derivacion' => $data['tipo_derivacion'],
                'estado' => $data['estado'],
            ]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        return $id;
    }

    private function authorizedCaso(int $tenantId, int $userId, int $casoId, string $motivo): array
    {
        $caso = $this->repository->casoById($tenantId, $casoId);
        if ($caso === null) {
            throw new RuntimeException('PAST_CASE_NOT_FOUND');
        }
        if ((int) $caso['es_confidencial'] === 1 && !$this->canSeeConfidential($tenantId, $userId)) {
            throw new RuntimeException('PAST_CONFIDENTIAL_ACCESS_DENIED');
        }
        $this->repository->auditSensitiveAccess($tenantId, $userId, $casoId, $motivo);
        return $caso;
    }

    private function canSeeConfidential(int $tenantId, int $userId): bool
    {
        return $this->permissionRepository->userHasPermission($userId, $tenantId, 'past.casos.ver_confidencial');
    }

    private function casoUpdateData(int $tenantId, array $input): array
    {
        $data = [];
        if (array_key_exists('persona_id', $input)) {
            $personaId = (int) $input['persona_id'];
            if (!$this->repository->personaExists($tenantId, $personaId)) {
                throw new RuntimeException('CRM_PERSON_NOT_FOUND');
            }
            $data['persona_id'] = $personaId;
        }
        if (array_key_exists('responsable_user_id', $input)) {
            $responsableId = $this->intOrNull($input['responsable_user_id']);
            if ($responsableId !== null && !$this->repository->userExists($responsableId)) {
                throw new RuntimeException('PAST_RESPONSABLE_NOT_FOUND');
            }
            $data['responsable_user_id'] = $responsableId;
        }
        foreach (['tipo', 'titulo', 'descripcion_general', 'prioridad', 'estado', 'fecha_apertura', 'fecha_cierre'] as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = in_array($field, ['titulo', 'tipo', 'prioridad', 'estado', 'fecha_apertura'], true)
                    ? trim((string) $input[$field])
                    : $this->nullable($input[$field]);
            }
        }
        if (array_key_exists('es_confidencial', $input)) {
            $data['es_confidencial'] = $this->boolInt($input['es_confidencial']);
        }
        return $data;
    }

    private function oracionData(int $tenantId, array $input, bool $partial = false): array
    {
        $data = [];
        if (array_key_exists('persona_id', $input)) {
            $personaId = $this->intOrNull($input['persona_id']);
            if ($personaId !== null && !$this->repository->personaExists($tenantId, $personaId)) {
                throw new RuntimeException('CRM_PERSON_NOT_FOUND');
            }
            $data['persona_id'] = $personaId;
        }
        foreach (['nombre_solicitante', 'contacto_solicitante', 'titulo', 'detalle', 'categoria', 'privacidad', 'estado', 'fecha_cierre'] as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = in_array($field, ['titulo', 'privacidad', 'estado'], true)
                    ? trim((string) $input[$field])
                    : $this->nullable($input[$field]);
            }
        }
        return $partial ? $data : $data + [
            'persona_id' => null,
            'nombre_solicitante' => null,
            'contacto_solicitante' => null,
            'detalle' => null,
            'categoria' => null,
            'privacidad' => 'privada',
            'estado' => 'recibida',
        ];
    }

    private function nullable(mixed $value): ?string
    {
        return $value === null || trim((string) $value) === '' ? null : trim((string) $value);
    }

    private function safeCaseAudit(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'persona_id',
            'responsable_user_id',
            'tipo',
            'prioridad',
            'estado',
            'fecha_apertura',
            'fecha_cierre',
            'es_confidencial',
        ]));
    }

    private function safePrayerAudit(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'persona_id',
            'categoria',
            'privacidad',
            'estado',
            'fecha_cierre',
        ]));
    }

    private function intOrNull(mixed $value): ?int
    {
        return $value === null || trim((string) $value) === '' ? null : (int) $value;
    }

    private function boolInt(mixed $value): int
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ? 1 : 0;
    }
}
