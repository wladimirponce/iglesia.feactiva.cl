<?php

declare(strict_types=1);

final class PastoralRepository
{
    public function personaExists(int $tenantId, int $personaId): bool
    {
        $sql = "SELECT 1 FROM crm_personas WHERE tenant_id = :tenant_id AND id = :persona_id AND deleted_at IS NULL LIMIT 1";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'persona_id' => $personaId]);
        return $statement->fetchColumn() !== false;
    }

    public function userExists(int $userId): bool
    {
        $sql = "SELECT 1 FROM auth_users WHERE id = :user_id AND deleted_at IS NULL LIMIT 1";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchColumn() !== false;
    }

    public function casos(int $tenantId, bool $includeConfidential): array
    {
        $confidentialSql = $includeConfidential ? '' : 'AND c.es_confidencial = 0';
        $sql = "
            SELECT c.id, c.persona_id, p.nombres, p.apellidos, c.responsable_user_id, c.tipo, c.titulo,
                   c.descripcion_general, c.prioridad, c.estado, c.fecha_apertura, c.fecha_cierre,
                   c.es_confidencial, c.created_at, c.updated_at
            FROM past_casos c
            INNER JOIN crm_personas p ON p.id = c.persona_id AND p.tenant_id = c.tenant_id
            WHERE c.tenant_id = :tenant_id AND c.deleted_at IS NULL {$confidentialSql}
            ORDER BY c.fecha_apertura DESC, c.id DESC
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);
        return $statement->fetchAll();
    }

    public function casoById(int $tenantId, int $casoId): ?array
    {
        $sql = "
            SELECT c.id, c.tenant_id, c.persona_id, p.nombres, p.apellidos, c.responsable_user_id,
                   c.tipo, c.titulo, c.descripcion_general, c.prioridad, c.estado,
                   c.fecha_apertura, c.fecha_cierre, c.es_confidencial, c.created_at, c.updated_at
            FROM past_casos c
            INNER JOIN crm_personas p ON p.id = c.persona_id AND p.tenant_id = c.tenant_id
            WHERE c.tenant_id = :tenant_id AND c.id = :caso_id AND c.deleted_at IS NULL
            LIMIT 1
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'caso_id' => $casoId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function createCaso(int $tenantId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO past_casos (
                tenant_id, persona_id, responsable_user_id, tipo, titulo, descripcion_general,
                prioridad, estado, fecha_apertura, fecha_cierre, es_confidencial, created_by
            )
            VALUES (
                :tenant_id, :persona_id, :responsable_user_id, :tipo, :titulo, :descripcion_general,
                :prioridad, :estado, :fecha_apertura, :fecha_cierre, :es_confidencial, :created_by
            )
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute($data + ['tenant_id' => $tenantId, 'created_by' => $userId]);
        return (int) Database::connection()->lastInsertId();
    }

    public function updateCaso(int $tenantId, int $casoId, int $userId, array $data): void
    {
        $this->updateByAllowed('past_casos', $tenantId, $casoId, $data, [
            'persona_id',
            'responsable_user_id',
            'tipo',
            'titulo',
            'descripcion_general',
            'prioridad',
            'estado',
            'fecha_apertura',
            'fecha_cierre',
            'es_confidencial',
        ], $userId);
    }

    public function closeCaso(int $tenantId, int $casoId, int $userId): void
    {
        $sql = "
            UPDATE past_casos
            SET estado = 'cerrado', fecha_cierre = UTC_DATE(), updated_by = :updated_by
            WHERE tenant_id = :tenant_id AND id = :caso_id AND deleted_at IS NULL
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'caso_id' => $casoId, 'updated_by' => $userId]);
    }

    public function sesionesByCaso(int $tenantId, int $casoId): array
    {
        $sql = "
            SELECT id, caso_id, persona_id, fecha_sesion, modalidad, resumen, acuerdos,
                   proxima_accion, proxima_fecha, es_confidencial, created_at, updated_at
            FROM past_sesiones
            WHERE tenant_id = :tenant_id AND caso_id = :caso_id
            ORDER BY fecha_sesion DESC, id DESC
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'caso_id' => $casoId]);
        return $statement->fetchAll();
    }

    public function createSesion(int $tenantId, int $casoId, int $personaId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO past_sesiones (
                tenant_id, caso_id, persona_id, fecha_sesion, modalidad, resumen,
                acuerdos, proxima_accion, proxima_fecha, es_confidencial, created_by
            )
            VALUES (
                :tenant_id, :caso_id, :persona_id, :fecha_sesion, :modalidad, :resumen,
                :acuerdos, :proxima_accion, :proxima_fecha, :es_confidencial, :created_by
            )
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute($data + ['tenant_id' => $tenantId, 'caso_id' => $casoId, 'persona_id' => $personaId, 'created_by' => $userId]);
        return (int) Database::connection()->lastInsertId();
    }

    public function oraciones(int $tenantId): array
    {
        $sql = "
            SELECT o.id, o.persona_id, p.nombres, p.apellidos, o.nombre_solicitante, o.contacto_solicitante,
                   o.titulo, o.detalle, o.categoria, o.privacidad, o.estado, o.fecha_solicitud,
                   o.fecha_cierre, o.created_at, o.updated_at
            FROM past_solicitudes_oracion o
            LEFT JOIN crm_personas p ON p.id = o.persona_id AND p.tenant_id = o.tenant_id
            WHERE o.tenant_id = :tenant_id
            ORDER BY o.fecha_solicitud DESC, o.id DESC
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);
        return $statement->fetchAll();
    }

    public function oracionById(int $tenantId, int $oracionId): ?array
    {
        $sql = "
            SELECT id, tenant_id, persona_id, nombre_solicitante, contacto_solicitante, titulo,
                   detalle, categoria, privacidad, estado, fecha_solicitud, fecha_cierre,
                   created_at, updated_at
            FROM past_solicitudes_oracion
            WHERE tenant_id = :tenant_id AND id = :oracion_id
            LIMIT 1
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'oracion_id' => $oracionId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function createOracion(int $tenantId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO past_solicitudes_oracion (
                tenant_id, persona_id, nombre_solicitante, contacto_solicitante, titulo,
                detalle, categoria, privacidad, estado, created_by
            )
            VALUES (
                :tenant_id, :persona_id, :nombre_solicitante, :contacto_solicitante, :titulo,
                :detalle, :categoria, :privacidad, :estado, :created_by
            )
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute($data + ['tenant_id' => $tenantId, 'created_by' => $userId]);
        return (int) Database::connection()->lastInsertId();
    }

    public function updateOracion(int $tenantId, int $oracionId, array $data): void
    {
        $this->updateByAllowed('past_solicitudes_oracion', $tenantId, $oracionId, $data, [
            'persona_id',
            'nombre_solicitante',
            'contacto_solicitante',
            'titulo',
            'detalle',
            'categoria',
            'privacidad',
            'estado',
            'fecha_cierre',
        ]);
    }

    public function closeOracion(int $tenantId, int $oracionId): void
    {
        $sql = "
            UPDATE past_solicitudes_oracion
            SET estado = 'cerrada', fecha_cierre = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id AND id = :oracion_id
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'oracion_id' => $oracionId]);
    }

    public function createDerivacion(int $tenantId, int $casoId, int $personaId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO past_derivaciones (
                tenant_id, caso_id, persona_id, derivado_a_user_id, derivado_a_nombre,
                tipo_derivacion, motivo, estado, created_by
            )
            VALUES (
                :tenant_id, :caso_id, :persona_id, :derivado_a_user_id, :derivado_a_nombre,
                :tipo_derivacion, :motivo, :estado, :created_by
            )
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute($data + ['tenant_id' => $tenantId, 'caso_id' => $casoId, 'persona_id' => $personaId, 'created_by' => $userId]);
        return (int) Database::connection()->lastInsertId();
    }

    public function markCasoDerivado(int $tenantId, int $casoId, int $userId): void
    {
        $sql = "
            UPDATE past_casos
            SET estado = 'derivado', updated_by = :updated_by
            WHERE tenant_id = :tenant_id AND id = :caso_id AND deleted_at IS NULL
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'caso_id' => $casoId, 'updated_by' => $userId]);
    }

    public function audit(int $tenantId, int $userId, string $action, string $tableName, int $recordId, ?array $newValues = null): void
    {
        $sql = "
            INSERT INTO audit_logs (tenant_id, user_id, module_code, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
            VALUES (:tenant_id, :user_id, 'pastoral', :action, :table_name, :record_id, NULL, :new_values, :ip_address, :user_agent)
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

    public function auditSensitiveAccess(int $tenantId, int $userId, int $casoId, ?string $motivo = null): void
    {
        if ($this->tableExists('audit_sensitive_access')) {
            $sql = "
                INSERT INTO audit_sensitive_access (tenant_id, user_id, modulo_codigo, recurso_tipo, recurso_id, accion, motivo, ip_address, user_agent)
                VALUES (:tenant_id, :user_id, 'pastoral', 'past_casos', :recurso_id, 'view', :motivo, :ip_address, :user_agent)
            ";
            $statement = Database::connection()->prepare($sql);
            $statement->execute([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'recurso_id' => $casoId,
                'motivo' => $motivo,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
            return;
        }

        $this->audit($tenantId, $userId, 'past.caso.viewed', 'past_casos', $casoId, ['motivo' => $motivo]);
    }

    private function tableExists(string $tableName): bool
    {
        $sql = "
            SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
            LIMIT 1
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['table_name' => $tableName]);
        return $statement->fetchColumn() !== false;
    }

    private function updateByAllowed(string $table, int $tenantId, int $id, array $data, array $allowed, ?int $userId = null): void
    {
        $assignments = [];
        $params = ['tenant_id' => $tenantId, 'id' => $id];
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
            throw new RuntimeException('PAST_EMPTY_UPDATE');
        }
        $sql = "UPDATE {$table} SET " . implode(', ', $assignments) . " WHERE tenant_id = :tenant_id AND id = :id";
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
    }
}
