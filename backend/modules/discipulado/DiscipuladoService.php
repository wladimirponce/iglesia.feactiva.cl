<?php

declare(strict_types=1);

final class DiscipuladoService
{
    public function __construct(private readonly DiscipuladoRepository $repository)
    {
    }

    public function rutas(int $tenantId): array { return $this->repository->rutas($tenantId); }

    public function createRuta(int $tenantId, int $userId, array $input): int
    {
        $data = $this->rutaData($input);
        $id = $this->repository->createRuta($tenantId, $userId, $data);
        $this->repository->audit($tenantId, $userId, 'disc.ruta.created', 'disc_rutas', $id, $data + ['id' => $id]);
        return $id;
    }

    public function updateRuta(int $tenantId, int $userId, int $rutaId, array $input): void
    {
        if ($this->repository->rutaById($tenantId, $rutaId) === null) throw new RuntimeException('DISC_RUTA_NOT_FOUND');
        $this->repository->updateRuta($tenantId, $rutaId, $userId, $this->rutaData($input, true));
        $this->repository->audit($tenantId, $userId, 'disc.ruta.updated', 'disc_rutas', $rutaId, $input);
    }

    public function createEtapa(int $tenantId, int $rutaId, array $input): int
    {
        if ($this->repository->rutaById($tenantId, $rutaId) === null) throw new RuntimeException('DISC_RUTA_NOT_FOUND');
        return $this->repository->createEtapa($tenantId, $rutaId, $this->etapaData($input));
    }

    public function updateEtapa(int $tenantId, int $etapaId, array $input): void
    {
        if ($this->repository->etapaById($tenantId, $etapaId) === null) throw new RuntimeException('DISC_ETAPA_NOT_FOUND');
        $this->repository->updateEtapa($tenantId, $etapaId, $this->etapaData($input, true));
    }

    public function assignRuta(int $tenantId, int $userId, int $personaId, int $rutaId, array $input): int
    {
        if (!$this->repository->personaExists($tenantId, $personaId)) throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        if ($this->repository->rutaById($tenantId, $rutaId) === null) throw new RuntimeException('DISC_RUTA_NOT_FOUND');
        $mentorId = isset($input['mentor_persona_id']) && $input['mentor_persona_id'] !== '' ? (int) $input['mentor_persona_id'] : null;
        if ($mentorId !== null && !$this->repository->personaExists($tenantId, $mentorId)) throw new RuntimeException('DISC_MENTOR_NOT_FOUND');

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $personaRutaId = $this->repository->createPersonaRuta($tenantId, $personaId, $rutaId, $userId, [
                'mentor_persona_id' => $mentorId,
                'estado' => $input['estado'] ?? 'pendiente',
                'fecha_inicio' => $input['fecha_inicio'] ?? null,
                'observacion' => $this->nullable($input['observacion'] ?? null),
            ]);
            foreach ($this->repository->etapasByRuta($tenantId, $rutaId) as $etapa) {
                $this->repository->createPersonaEtapa($tenantId, $personaRutaId, (int) $etapa['id']);
            }
            $this->repository->audit($tenantId, $userId, 'disc.persona_ruta.assigned', 'disc_persona_rutas', $personaRutaId, ['persona_id' => $personaId, 'ruta_id' => $rutaId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            if ($exception instanceof PDOException && $exception->getCode() === '23000') throw new RuntimeException('DISC_PERSONA_RUTA_DUPLICATE');
            throw $exception;
        }
        return $personaRutaId;
    }

    public function avance(int $tenantId, int $personaId): array
    {
        if (!$this->repository->personaExists($tenantId, $personaId)) throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        return $this->repository->avancePersona($tenantId, $personaId);
    }

    public function completeEtapa(int $tenantId, int $userId, int $personaEtapaId, array $input): void
    {
        $etapa = $this->repository->personaEtapaById($tenantId, $personaEtapaId);
        if ($etapa === null) throw new RuntimeException('DISC_PERSONA_ETAPA_NOT_FOUND');
        $this->repository->completePersonaEtapa($tenantId, $personaEtapaId, $userId, $this->nullable($input['nota_resultado'] ?? null), $this->nullable($input['observacion'] ?? null));
        $this->repository->updatePersonaRutaProgress($tenantId, (int) $etapa['persona_ruta_id']);
        $this->repository->audit($tenantId, $userId, 'disc.etapa.completed', 'disc_persona_etapas', $personaEtapaId, $etapa);
    }

    public function mentorias(int $tenantId, int $personaId): array
    {
        if (!$this->repository->personaExists($tenantId, $personaId)) throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        return $this->repository->mentorias($tenantId, $personaId);
    }

    public function createMentoria(int $tenantId, int $userId, int $personaId, array $input): int
    {
        if (!$this->repository->personaExists($tenantId, $personaId)) throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        $mentorId = (int) $input['mentor_persona_id'];
        if (!$this->repository->personaExists($tenantId, $mentorId)) throw new RuntimeException('DISC_MENTOR_NOT_FOUND');
        $id = $this->repository->createMentoria($tenantId, $personaId, $userId, [
            'mentor_persona_id' => $mentorId,
            'persona_ruta_id' => isset($input['persona_ruta_id']) && $input['persona_ruta_id'] !== '' ? (int) $input['persona_ruta_id'] : null,
            'fecha_mentoria' => (string) $input['fecha_mentoria'],
            'modalidad' => $input['modalidad'] ?? 'presencial',
            'tema' => $this->nullable($input['tema'] ?? null),
            'resumen' => $this->nullable($input['resumen'] ?? null),
            'acuerdos' => $this->nullable($input['acuerdos'] ?? null),
            'proxima_fecha' => $this->nullable($input['proxima_fecha'] ?? null),
        ]);
        $this->repository->audit($tenantId, $userId, 'disc.mentoria.created', 'disc_mentorias', $id, ['persona_id' => $personaId, 'mentor_persona_id' => $mentorId]);
        return $id;
    }

    public function registros(int $tenantId, int $personaId): array
    {
        if (!$this->repository->personaExists($tenantId, $personaId)) throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        return $this->repository->registros($tenantId, $personaId);
    }

    public function createRegistro(int $tenantId, int $userId, int $personaId, array $input): int
    {
        if (!$this->repository->personaExists($tenantId, $personaId)) throw new RuntimeException('CRM_PERSON_NOT_FOUND');
        $id = $this->repository->createRegistro($tenantId, $personaId, $userId, [
            'tipo' => (string) $input['tipo'],
            'fecha_evento' => (string) $input['fecha_evento'],
            'lugar' => $this->nullable($input['lugar'] ?? null),
            'ministro_responsable' => $this->nullable($input['ministro_responsable'] ?? null),
            'observacion' => $this->nullable($input['observacion'] ?? null),
            'documento_url' => $this->nullable($input['documento_url'] ?? null),
        ]);
        $this->repository->audit($tenantId, $userId, 'disc.registro_espiritual.created', 'disc_registros_espirituales', $id, ['persona_id' => $personaId]);
        return $id;
    }

    private function rutaData(array $input, bool $partial = false): array
    {
        $data = [];
        foreach (['nombre', 'descripcion', 'publico_objetivo'] as $field) if (array_key_exists($field, $input)) $data[$field] = $this->nullable($input[$field]);
        foreach (['duracion_estimada_dias', 'es_activa'] as $field) if (array_key_exists($field, $input)) $data[$field] = $input[$field] === null || $input[$field] === '' ? null : (int) $input[$field];
        return $partial ? $data : $data + ['descripcion' => null, 'publico_objetivo' => null, 'duracion_estimada_dias' => null, 'es_activa' => 1];
    }

    private function etapaData(array $input, bool $partial = false): array
    {
        $data = [];
        foreach (['nombre', 'descripcion'] as $field) if (array_key_exists($field, $input)) $data[$field] = $this->nullable($input[$field]);
        foreach (['orden', 'duracion_estimada_dias', 'es_obligatoria', 'es_activa'] as $field) if (array_key_exists($field, $input)) $data[$field] = $input[$field] === null || $input[$field] === '' ? null : (int) $input[$field];
        return $partial ? $data : $data + ['descripcion' => null, 'orden' => 0, 'duracion_estimada_dias' => null, 'es_obligatoria' => 1, 'es_activa' => 1];
    }

    private function nullable(mixed $value): ?string
    {
        return $value === null || trim((string) $value) === '' ? null : trim((string) $value);
    }
}
