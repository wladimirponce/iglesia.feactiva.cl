<?php

declare(strict_types=1);

final class DiscipuladoRepository
{
    public function personaExists(int $tenantId, int $personaId): bool
    {
        $sql = "SELECT 1 FROM crm_personas WHERE tenant_id = :tenant_id AND id = :persona_id AND deleted_at IS NULL LIMIT 1";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'persona_id' => $personaId]);
        return $statement->fetchColumn() !== false;
    }

    public function rutas(int $tenantId): array
    {
        $sql = "
            SELECT id, nombre, descripcion, publico_objetivo, duracion_estimada_dias, es_activa, created_at, updated_at
            FROM disc_rutas
            WHERE tenant_id = :tenant_id AND deleted_at IS NULL
            ORDER BY nombre
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);
        return $statement->fetchAll();
    }

    public function rutaById(int $tenantId, int $rutaId): ?array
    {
        $sql = "
            SELECT id, tenant_id, nombre, descripcion, publico_objetivo, duracion_estimada_dias, es_activa, created_at, updated_at
            FROM disc_rutas
            WHERE tenant_id = :tenant_id AND id = :ruta_id AND deleted_at IS NULL
            LIMIT 1
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'ruta_id' => $rutaId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function createRuta(int $tenantId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO disc_rutas (tenant_id, nombre, descripcion, publico_objetivo, duracion_estimada_dias, es_activa, created_by)
            VALUES (:tenant_id, :nombre, :descripcion, :publico_objetivo, :duracion_estimada_dias, :es_activa, :created_by)
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute($data + ['tenant_id' => $tenantId, 'created_by' => $userId]);
        return (int) Database::connection()->lastInsertId();
    }

    public function updateRuta(int $tenantId, int $rutaId, int $userId, array $data): void
    {
        $this->updateByAllowed('disc_rutas', 'ruta_id', $rutaId, $tenantId, $userId, $data, ['nombre', 'descripcion', 'publico_objetivo', 'duracion_estimada_dias', 'es_activa']);
    }

    public function etapasByRuta(int $tenantId, int $rutaId): array
    {
        $sql = "
            SELECT id, ruta_id, nombre, descripcion, orden, duracion_estimada_dias, es_obligatoria, es_activa, created_at, updated_at
            FROM disc_etapas
            WHERE tenant_id = :tenant_id AND ruta_id = :ruta_id
            ORDER BY orden, id
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'ruta_id' => $rutaId]);
        return $statement->fetchAll();
    }

    public function etapaById(int $tenantId, int $etapaId): ?array
    {
        $sql = "
            SELECT id, tenant_id, ruta_id, nombre, descripcion, orden, duracion_estimada_dias, es_obligatoria, es_activa
            FROM disc_etapas
            WHERE tenant_id = :tenant_id AND id = :etapa_id
            LIMIT 1
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'etapa_id' => $etapaId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function createEtapa(int $tenantId, int $rutaId, array $data): int
    {
        $sql = "
            INSERT INTO disc_etapas (tenant_id, ruta_id, nombre, descripcion, orden, duracion_estimada_dias, es_obligatoria, es_activa)
            VALUES (:tenant_id, :ruta_id, :nombre, :descripcion, :orden, :duracion_estimada_dias, :es_obligatoria, :es_activa)
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute($data + ['tenant_id' => $tenantId, 'ruta_id' => $rutaId]);
        return (int) Database::connection()->lastInsertId();
    }

    public function updateEtapa(int $tenantId, int $etapaId, array $data): void
    {
        $this->updateByAllowed('disc_etapas', 'etapa_id', $etapaId, $tenantId, null, $data, ['nombre', 'descripcion', 'orden', 'duracion_estimada_dias', 'es_obligatoria', 'es_activa']);
    }

    public function createPersonaRuta(int $tenantId, int $personaId, int $rutaId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO disc_persona_rutas (tenant_id, persona_id, ruta_id, mentor_persona_id, estado, fecha_inicio, observacion, created_by)
            VALUES (:tenant_id, :persona_id, :ruta_id, :mentor_persona_id, :estado, :fecha_inicio, :observacion, :created_by)
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute($data + ['tenant_id' => $tenantId, 'persona_id' => $personaId, 'ruta_id' => $rutaId, 'created_by' => $userId]);
        return (int) Database::connection()->lastInsertId();
    }

    public function createPersonaEtapa(int $tenantId, int $personaRutaId, int $etapaId): void
    {
        $sql = "INSERT IGNORE INTO disc_persona_etapas (tenant_id, persona_ruta_id, etapa_id) VALUES (:tenant_id, :persona_ruta_id, :etapa_id)";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'persona_ruta_id' => $personaRutaId, 'etapa_id' => $etapaId]);
    }

    public function personaEtapaById(int $tenantId, int $personaEtapaId): ?array
    {
        $sql = "
            SELECT pe.id, pe.persona_ruta_id, pe.etapa_id, pe.estado, pr.persona_id, pr.ruta_id
            FROM disc_persona_etapas pe
            INNER JOIN disc_persona_rutas pr ON pr.id = pe.persona_ruta_id AND pr.tenant_id = pe.tenant_id
            WHERE pe.tenant_id = :tenant_id AND pe.id = :persona_etapa_id
            LIMIT 1
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'persona_etapa_id' => $personaEtapaId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function completePersonaEtapa(int $tenantId, int $personaEtapaId, int $userId, ?string $nota, ?string $observacion): void
    {
        $sql = "
            UPDATE disc_persona_etapas
            SET estado = 'completada', fecha_fin = UTC_DATE(), nota_resultado = :nota_resultado, observacion = :observacion, updated_by = :updated_by
            WHERE tenant_id = :tenant_id AND id = :persona_etapa_id
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'persona_etapa_id' => $personaEtapaId, 'nota_resultado' => $nota, 'observacion' => $observacion, 'updated_by' => $userId]);
    }

    public function updatePersonaRutaProgress(int $tenantId, int $personaRutaId): void
    {
        $sql = "
            UPDATE disc_persona_rutas pr
            SET porcentaje_avance = (
                SELECT COALESCE(ROUND(SUM(CASE WHEN pe.estado = 'completada' THEN 1 ELSE 0 END) * 100 / NULLIF(COUNT(pe.id), 0), 2), 0)
                FROM disc_persona_etapas pe
                WHERE pe.tenant_id = pr.tenant_id AND pe.persona_ruta_id = pr.id
            ),
            estado = CASE
                WHEN (
                    SELECT COUNT(pe2.id) FROM disc_persona_etapas pe2
                    WHERE pe2.tenant_id = pr.tenant_id AND pe2.persona_ruta_id = pr.id AND pe2.estado <> 'completada'
                ) = 0 THEN 'completada'
                ELSE 'en_progreso'
            END
            WHERE pr.tenant_id = :tenant_id AND pr.id = :persona_ruta_id
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'persona_ruta_id' => $personaRutaId]);
    }

    public function avancePersona(int $tenantId, int $personaId): array
    {
        $sql = "
            SELECT
                pr.id AS persona_ruta_id,
                pr.estado AS ruta_estado,
                pr.porcentaje_avance,
                r.id AS ruta_id,
                r.nombre AS ruta_nombre,
                e.id AS etapa_id,
                e.nombre AS etapa_nombre,
                pe.id AS persona_etapa_id,
                pe.estado AS etapa_estado,
                pe.fecha_inicio,
                pe.fecha_fin
            FROM disc_persona_rutas pr
            INNER JOIN disc_rutas r ON r.id = pr.ruta_id AND r.tenant_id = pr.tenant_id
            LEFT JOIN disc_persona_etapas pe ON pe.persona_ruta_id = pr.id AND pe.tenant_id = pr.tenant_id
            LEFT JOIN disc_etapas e ON e.id = pe.etapa_id AND e.tenant_id = pr.tenant_id
            WHERE pr.tenant_id = :tenant_id AND pr.persona_id = :persona_id
            ORDER BY r.nombre, e.orden, e.id
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'persona_id' => $personaId]);
        return $statement->fetchAll();
    }

    public function mentorias(int $tenantId, int $personaId): array
    {
        $sql = "
            SELECT id, persona_id, mentor_persona_id, persona_ruta_id, fecha_mentoria, modalidad, tema, resumen, acuerdos, proxima_fecha, created_at
            FROM disc_mentorias
            WHERE tenant_id = :tenant_id AND persona_id = :persona_id
            ORDER BY fecha_mentoria DESC
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'persona_id' => $personaId]);
        return $statement->fetchAll();
    }

    public function createMentoria(int $tenantId, int $personaId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO disc_mentorias (tenant_id, persona_id, mentor_persona_id, persona_ruta_id, fecha_mentoria, modalidad, tema, resumen, acuerdos, proxima_fecha, created_by)
            VALUES (:tenant_id, :persona_id, :mentor_persona_id, :persona_ruta_id, :fecha_mentoria, :modalidad, :tema, :resumen, :acuerdos, :proxima_fecha, :created_by)
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute($data + ['tenant_id' => $tenantId, 'persona_id' => $personaId, 'created_by' => $userId]);
        return (int) Database::connection()->lastInsertId();
    }

    public function registros(int $tenantId, int $personaId): array
    {
        $sql = "
            SELECT id, persona_id, tipo, fecha_evento, lugar, ministro_responsable, observacion, documento_url, created_at
            FROM disc_registros_espirituales
            WHERE tenant_id = :tenant_id AND persona_id = :persona_id
            ORDER BY fecha_evento DESC, id DESC
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'persona_id' => $personaId]);
        return $statement->fetchAll();
    }

    public function createRegistro(int $tenantId, int $personaId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO disc_registros_espirituales (tenant_id, persona_id, tipo, fecha_evento, lugar, ministro_responsable, observacion, documento_url, created_by)
            VALUES (:tenant_id, :persona_id, :tipo, :fecha_evento, :lugar, :ministro_responsable, :observacion, :documento_url, :created_by)
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute($data + ['tenant_id' => $tenantId, 'persona_id' => $personaId, 'created_by' => $userId]);
        return (int) Database::connection()->lastInsertId();
    }

    public function audit(int $tenantId, int $userId, string $action, string $tableName, int $recordId, ?array $newValues): void
    {
        $sql = "
            INSERT INTO audit_logs (tenant_id, user_id, module_code, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
            VALUES (:tenant_id, :user_id, 'discipulado', :action, :table_name, :record_id, NULL, :new_values, :ip_address, :user_agent)
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'new_values' => $newValues === null ? null : json_encode($newValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    private function updateByAllowed(string $table, string $idName, int $id, int $tenantId, ?int $userId, array $data, array $allowed): void
    {
        $assignments = [];
        $params = ['tenant_id' => $tenantId, $idName => $id];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $assignments[] = $field . ' = :' . $field;
                $params[$field] = $data[$field];
            }
        }
        if ($userId !== null) {
            $assignments[] = 'updated_by = :updated_by';
            $params['updated_by'] = $userId;
        }
        if ($assignments === []) {
            throw new RuntimeException('DISC_EMPTY_UPDATE');
        }
        $idColumn = $idName === 'ruta_id' ? 'id' : 'id';
        $sql = "UPDATE {$table} SET " . implode(', ', $assignments) . " WHERE tenant_id = :tenant_id AND {$idColumn} = :{$idName}";
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
    }
}
