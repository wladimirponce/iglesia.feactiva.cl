<?php

declare(strict_types=1);

final class FinanzasMovimientosRepository
{
    public function list(int $tenantId, int $limit, int $offset): array
    {
        $sql = "
            SELECT
                m.id,
                m.tipo,
                m.subtipo,
                m.descripcion,
                m.monto,
                m.moneda,
                m.fecha_movimiento,
                m.fecha_contable,
                m.medio_pago,
                m.estado,
                c.nombre AS cuenta_nombre,
                cat.nombre AS categoria_nombre,
                cc.nombre AS centro_costo_nombre,
                CONCAT(p.nombres, ' ', p.apellidos) AS persona_nombre
            FROM fin_movimientos m
            INNER JOIN fin_cuentas c
                ON c.id = m.cuenta_id
                AND c.tenant_id = m.tenant_id
            INNER JOIN fin_categorias cat
                ON cat.id = m.categoria_id
                AND cat.tenant_id = m.tenant_id
            LEFT JOIN fin_centros_costo cc
                ON cc.id = m.centro_costo_id
                AND cc.tenant_id = m.tenant_id
            LEFT JOIN crm_personas p
                ON p.id = m.persona_id
                AND p.tenant_id = m.tenant_id
            WHERE m.tenant_id = :tenant_id
            ORDER BY m.fecha_movimiento DESC, m.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->bindValue('tenant_id', $tenantId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function count(int $tenantId): int
    {
        $sql = "
            SELECT COUNT(id)
            FROM fin_movimientos
            WHERE tenant_id = :tenant_id
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);

        return (int) $statement->fetchColumn();
    }

    public function findById(int $tenantId, int $movimientoId): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                cuenta_id,
                categoria_id,
                centro_costo_id,
                campana_id,
                persona_id,
                tipo,
                subtipo,
                descripcion,
                monto,
                moneda,
                fecha_movimiento,
                fecha_contable,
                medio_pago,
                referencia_pago,
                estado,
                observacion,
                motivo_anulacion,
                anulado_at,
                anulado_by,
                created_at,
                updated_at,
                created_by,
                updated_by
            FROM fin_movimientos
            WHERE tenant_id = :tenant_id
              AND id = :movimiento_id
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'movimiento_id' => $movimientoId,
        ]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function tenantResourceExists(int $tenantId, string $table, int $id): bool
    {
        $allowed = [
            'fin_cuentas' => 'deleted_at IS NULL',
            'fin_categorias' => 'deleted_at IS NULL',
            'fin_centros_costo' => 'deleted_at IS NULL',
            'fin_campanas' => 'deleted_at IS NULL',
            'crm_personas' => 'deleted_at IS NULL',
        ];

        if (!isset($allowed[$table])) {
            throw new InvalidArgumentException('Invalid table.');
        }

        $sql = "
            SELECT 1
            FROM {$table}
            WHERE tenant_id = :tenant_id
              AND id = :id
              AND {$allowed[$table]}
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'id' => $id]);

        return $statement->fetchColumn() !== false;
    }

    public function categoriaMatchesType(int $tenantId, int $categoriaId, string $tipo): bool
    {
        $sql = "
            SELECT 1
            FROM fin_categorias
            WHERE tenant_id = :tenant_id
              AND id = :categoria_id
              AND tipo = :tipo
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'categoria_id' => $categoriaId, 'tipo' => $tipo]);

        return $statement->fetchColumn() !== false;
    }

    public function create(int $tenantId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO fin_movimientos (
                tenant_id,
                cuenta_id,
                categoria_id,
                centro_costo_id,
                campana_id,
                persona_id,
                tipo,
                subtipo,
                descripcion,
                monto,
                moneda,
                fecha_movimiento,
                fecha_contable,
                medio_pago,
                referencia_pago,
                observacion,
                created_by
            ) VALUES (
                :tenant_id,
                :cuenta_id,
                :categoria_id,
                :centro_costo_id,
                :campana_id,
                :persona_id,
                :tipo,
                :subtipo,
                :descripcion,
                :monto,
                :moneda,
                :fecha_movimiento,
                :fecha_contable,
                :medio_pago,
                :referencia_pago,
                :observacion,
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'cuenta_id' => $data['cuenta_id'],
            'categoria_id' => $data['categoria_id'],
            'centro_costo_id' => $data['centro_costo_id'],
            'campana_id' => $data['campana_id'],
            'persona_id' => $data['persona_id'],
            'tipo' => $data['tipo'],
            'subtipo' => $data['subtipo'],
            'descripcion' => $data['descripcion'],
            'monto' => $data['monto'],
            'moneda' => $data['moneda'],
            'fecha_movimiento' => $data['fecha_movimiento'],
            'fecha_contable' => $data['fecha_contable'],
            'medio_pago' => $data['medio_pago'],
            'referencia_pago' => $data['referencia_pago'],
            'observacion' => $data['observacion'],
            'created_by' => $userId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function cancel(int $tenantId, int $movimientoId, int $userId, string $motivo): void
    {
        $sql = "
            UPDATE fin_movimientos
            SET estado = 'anulado',
                motivo_anulacion = :motivo_anulacion,
                anulado_at = UTC_TIMESTAMP(),
                anulado_by = :anulado_by
            WHERE tenant_id = :tenant_id
              AND id = :movimiento_id
              AND estado <> 'anulado'
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'movimiento_id' => $movimientoId,
            'motivo_anulacion' => $motivo,
            'anulado_by' => $userId,
        ]);
    }

    public function audit(int $tenantId, int $userId, string $action, int $movimientoId, ?array $oldValues, ?array $newValues): void
    {
        $sql = "
            INSERT INTO audit_logs (
                tenant_id,
                user_id,
                module_code,
                action,
                table_name,
                record_id,
                old_values,
                new_values,
                ip_address,
                user_agent
            ) VALUES (
                :tenant_id,
                :user_id,
                'finanzas',
                :action,
                'fin_movimientos',
                :record_id,
                :old_values,
                :new_values,
                :ip_address,
                :user_agent
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'action' => $action,
            'record_id' => $movimientoId,
            'old_values' => $this->auditJson($oldValues),
            'new_values' => $this->auditJson($newValues),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    private function auditJson(?array $values): ?string
    {
        if ($values === null) {
            return null;
        }

        $safe = array_intersect_key($values, array_flip([
            'id',
            'cuenta_id',
            'categoria_id',
            'centro_costo_id',
            'persona_id',
            'campana_id',
            'tipo',
            'subtipo',
            'monto',
            'moneda',
            'fecha_movimiento',
            'fecha_contable',
            'medio_pago',
            'estado',
            'anulado_at',
            'anulado_by',
        ]));

        return json_encode($safe, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
