<?php

declare(strict_types=1);

final class ContabilidadRepository
{
    public function configuracion(int $tenantId): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                pais_codigo,
                moneda_base,
                norma_contable,
                periodo_inicio_mes,
                usa_centros_costo,
                requiere_aprobacion_asientos,
                numeracion_automatica,
                created_at,
                updated_at
            FROM acct_configuracion
            WHERE tenant_id = :tenant_id
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function updateConfiguracion(int $tenantId, int $userId, array $data): void
    {
        $fields = [
            'pais_codigo',
            'moneda_base',
            'norma_contable',
            'periodo_inicio_mes',
            'usa_centros_costo',
            'requiere_aprobacion_asientos',
            'numeracion_automatica',
        ];
        $assignments = [];
        $params = ['tenant_id' => $tenantId, 'updated_by' => $userId];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $assignments[] = $field . ' = :' . $field;
                $params[$field] = $data[$field];
            }
        }

        if ($assignments === []) {
            throw new RuntimeException('ACCT_EMPTY_UPDATE');
        }

        $assignments[] = 'updated_by = :updated_by';

        $sql = "
            UPDATE acct_configuracion
            SET " . implode(', ', $assignments) . "
            WHERE tenant_id = :tenant_id
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
    }

    public function cuentas(int $tenantId): array
    {
        $sql = "
            SELECT
                id,
                codigo,
                nombre,
                descripcion,
                tipo,
                naturaleza,
                cuenta_padre_id,
                nivel,
                es_movimiento,
                es_sistema,
                es_activa,
                created_at,
                updated_at
            FROM acct_cuentas
            WHERE tenant_id = :tenant_id
              AND deleted_at IS NULL
            ORDER BY codigo
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);

        return $statement->fetchAll();
    }

    public function cuentaById(int $tenantId, int $cuentaId): ?array
    {
        $sql = "
            SELECT
                id,
                codigo,
                nombre,
                descripcion,
                tipo,
                naturaleza,
                cuenta_padre_id,
                nivel,
                es_movimiento,
                es_sistema,
                es_activa,
                created_at,
                updated_at
            FROM acct_cuentas
            WHERE tenant_id = :tenant_id
              AND id = :cuenta_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'cuenta_id' => $cuentaId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function cuentaExists(int $tenantId, int $cuentaId, bool $movementOnly = false): bool
    {
        $sql = "
            SELECT 1
            FROM acct_cuentas
            WHERE tenant_id = :tenant_id
              AND id = :cuenta_id
              AND deleted_at IS NULL
              " . ($movementOnly ? "AND es_movimiento = 1 AND es_activa = 1" : "") . "
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'cuenta_id' => $cuentaId]);

        return $statement->fetchColumn() !== false;
    }

    public function centroCostoExists(int $tenantId, int $centroCostoId): bool
    {
        $sql = "
            SELECT 1
            FROM fin_centros_costo
            WHERE tenant_id = :tenant_id
              AND id = :centro_costo_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'centro_costo_id' => $centroCostoId]);

        return $statement->fetchColumn() !== false;
    }

    public function createCuenta(int $tenantId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO acct_cuentas (
                tenant_id,
                codigo,
                nombre,
                descripcion,
                tipo,
                naturaleza,
                cuenta_padre_id,
                nivel,
                es_movimiento,
                es_sistema,
                es_activa,
                created_by
            ) VALUES (
                :tenant_id,
                :codigo,
                :nombre,
                :descripcion,
                :tipo,
                :naturaleza,
                :cuenta_padre_id,
                :nivel,
                :es_movimiento,
                0,
                :es_activa,
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'codigo' => $data['codigo'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
            'tipo' => $data['tipo'],
            'naturaleza' => $data['naturaleza'],
            'cuenta_padre_id' => $data['cuenta_padre_id'],
            'nivel' => $data['nivel'],
            'es_movimiento' => $data['es_movimiento'],
            'es_activa' => $data['es_activa'],
            'created_by' => $userId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function updateCuenta(int $tenantId, int $cuentaId, int $userId, array $data): void
    {
        $allowed = ['codigo', 'nombre', 'descripcion', 'tipo', 'naturaleza', 'cuenta_padre_id', 'nivel', 'es_movimiento', 'es_activa'];
        $assignments = [];
        $params = ['tenant_id' => $tenantId, 'cuenta_id' => $cuentaId, 'updated_by' => $userId];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $assignments[] = $field . ' = :' . $field;
                $params[$field] = $data[$field];
            }
        }

        if ($assignments === []) {
            throw new RuntimeException('ACCT_EMPTY_UPDATE');
        }

        $assignments[] = 'updated_by = :updated_by';

        $sql = "
            UPDATE acct_cuentas
            SET " . implode(', ', $assignments) . "
            WHERE tenant_id = :tenant_id
              AND id = :cuenta_id
              AND deleted_at IS NULL
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
    }

    public function periodos(int $tenantId): array
    {
        $sql = "
            SELECT
                id,
                nombre,
                fecha_inicio,
                fecha_fin,
                estado,
                cerrado_at,
                cerrado_by,
                created_at,
                updated_at
            FROM acct_periodos
            WHERE tenant_id = :tenant_id
            ORDER BY fecha_inicio DESC
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);

        return $statement->fetchAll();
    }

    public function periodoById(int $tenantId, int $periodoId): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                nombre,
                fecha_inicio,
                fecha_fin,
                estado,
                cerrado_at,
                cerrado_by
            FROM acct_periodos
            WHERE tenant_id = :tenant_id
              AND id = :periodo_id
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'periodo_id' => $periodoId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function periodoForDate(int $tenantId, string $date): ?array
    {
        $sql = "
            SELECT
                id,
                estado
            FROM acct_periodos
            WHERE tenant_id = :tenant_id
              AND :fecha BETWEEN fecha_inicio AND fecha_fin
            ORDER BY fecha_inicio DESC
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'fecha' => $date]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function createPeriodo(int $tenantId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO acct_periodos (
                tenant_id,
                nombre,
                fecha_inicio,
                fecha_fin,
                estado,
                created_by
            ) VALUES (
                :tenant_id,
                :nombre,
                :fecha_inicio,
                :fecha_fin,
                'abierto',
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'nombre' => $data['nombre'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'created_by' => $userId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function closePeriodo(int $tenantId, int $periodoId, int $userId): void
    {
        $sql = "
            UPDATE acct_periodos
            SET estado = 'cerrado',
                cerrado_at = UTC_TIMESTAMP(),
                cerrado_by = :cerrado_by,
                updated_by = :updated_by
            WHERE tenant_id = :tenant_id
              AND id = :periodo_id
              AND estado = 'abierto'
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'periodo_id' => $periodoId,
            'cerrado_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function asientos(int $tenantId): array
    {
        $sql = "
            SELECT
                id,
                periodo_id,
                numero,
                fecha_asiento,
                descripcion,
                origen,
                fin_movimiento_id,
                asiento_reversado_id,
                estado,
                total_debe,
                total_haber,
                moneda,
                aprobado_at,
                anulado_at,
                created_at,
                updated_at
            FROM acct_asientos
            WHERE tenant_id = :tenant_id
            ORDER BY fecha_asiento DESC, id DESC
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);

        return $statement->fetchAll();
    }

    public function asientoById(int $tenantId, int $asientoId): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                periodo_id,
                numero,
                fecha_asiento,
                descripcion,
                origen,
                fin_movimiento_id,
                asiento_reversado_id,
                estado,
                total_debe,
                total_haber,
                moneda,
                aprobado_at,
                aprobado_by,
                anulado_at,
                anulado_by,
                motivo_anulacion,
                created_at,
                updated_at,
                created_by,
                updated_by
            FROM acct_asientos
            WHERE tenant_id = :tenant_id
              AND id = :asiento_id
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'asiento_id' => $asientoId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function asientoDetalles(int $tenantId, int $asientoId): array
    {
        $sql = "
            SELECT
                d.id,
                d.asiento_id,
                d.cuenta_id,
                c.codigo AS cuenta_codigo,
                c.nombre AS cuenta_nombre,
                d.centro_costo_id,
                d.descripcion,
                d.debe,
                d.haber,
                d.referencia,
                d.created_at
            FROM acct_asiento_detalles d
            INNER JOIN acct_cuentas c
                ON c.id = d.cuenta_id
                AND c.tenant_id = d.tenant_id
            WHERE d.tenant_id = :tenant_id
              AND d.asiento_id = :asiento_id
            ORDER BY d.id
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'asiento_id' => $asientoId]);

        return $statement->fetchAll();
    }

    public function createAsiento(int $tenantId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO acct_asientos (
                tenant_id,
                periodo_id,
                numero,
                fecha_asiento,
                descripcion,
                origen,
                fin_movimiento_id,
                asiento_reversado_id,
                estado,
                total_debe,
                total_haber,
                moneda,
                created_by
            ) VALUES (
                :tenant_id,
                :periodo_id,
                :numero,
                :fecha_asiento,
                :descripcion,
                :origen,
                :fin_movimiento_id,
                :asiento_reversado_id,
                'borrador',
                :total_debe,
                :total_haber,
                :moneda,
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'periodo_id' => $data['periodo_id'],
            'numero' => $data['numero'],
            'fecha_asiento' => $data['fecha_asiento'],
            'descripcion' => $data['descripcion'],
            'origen' => $data['origen'],
            'fin_movimiento_id' => $data['fin_movimiento_id'],
            'asiento_reversado_id' => $data['asiento_reversado_id'],
            'total_debe' => $data['total_debe'],
            'total_haber' => $data['total_haber'],
            'moneda' => $data['moneda'],
            'created_by' => $userId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function createAsientoDetalle(int $tenantId, int $asientoId, array $linea): void
    {
        $sql = "
            INSERT INTO acct_asiento_detalles (
                tenant_id,
                asiento_id,
                cuenta_id,
                centro_costo_id,
                descripcion,
                debe,
                haber,
                referencia
            ) VALUES (
                :tenant_id,
                :asiento_id,
                :cuenta_id,
                :centro_costo_id,
                :descripcion,
                :debe,
                :haber,
                :referencia
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'asiento_id' => $asientoId,
            'cuenta_id' => $linea['cuenta_id'],
            'centro_costo_id' => $linea['centro_costo_id'],
            'descripcion' => $linea['descripcion'],
            'debe' => $linea['debe'],
            'haber' => $linea['haber'],
            'referencia' => $linea['referencia'],
        ]);
    }

    public function approveAsiento(int $tenantId, int $asientoId, int $userId): void
    {
        $sql = "
            UPDATE acct_asientos
            SET estado = 'aprobado',
                aprobado_at = UTC_TIMESTAMP(),
                aprobado_by = :aprobado_by,
                updated_by = :updated_by
            WHERE tenant_id = :tenant_id
              AND id = :asiento_id
              AND estado = 'borrador'
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'asiento_id' => $asientoId,
            'aprobado_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function cancelAsiento(int $tenantId, int $asientoId, int $userId, ?string $motivo): void
    {
        $sql = "
            UPDATE acct_asientos
            SET estado = 'anulado',
                anulado_at = UTC_TIMESTAMP(),
                anulado_by = :anulado_by,
                motivo_anulacion = :motivo_anulacion,
                updated_by = :updated_by
            WHERE tenant_id = :tenant_id
              AND id = :asiento_id
              AND estado <> 'anulado'
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'asiento_id' => $asientoId,
            'anulado_by' => $userId,
            'motivo_anulacion' => $motivo,
            'updated_by' => $userId,
        ]);
    }

    public function audit(int $tenantId, int $userId, string $action, string $tableName, int $recordId, ?array $oldValues, ?array $newValues): void
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
                'contabilidad',
                :action,
                :table_name,
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
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'new_values' => $newValues === null ? null : json_encode($newValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    public function mapeosFinanzas(int $tenantId): array
    {
        $sql = "
            SELECT
                m.id,
                m.categoria_id,
                fc.nombre AS categoria_nombre,
                m.tipo_movimiento,
                m.cuenta_debe_id,
                cd.codigo AS cuenta_debe_codigo,
                cd.nombre AS cuenta_debe_nombre,
                m.cuenta_haber_id,
                ch.codigo AS cuenta_haber_codigo,
                ch.nombre AS cuenta_haber_nombre,
                m.descripcion,
                m.es_activo,
                m.created_at,
                m.updated_at
            FROM acct_mapeo_finanzas m
            INNER JOIN fin_categorias fc
                ON fc.id = m.categoria_id
                AND fc.tenant_id = m.tenant_id
            INNER JOIN acct_cuentas cd
                ON cd.id = m.cuenta_debe_id
                AND cd.tenant_id = m.tenant_id
            INNER JOIN acct_cuentas ch
                ON ch.id = m.cuenta_haber_id
                AND ch.tenant_id = m.tenant_id
            WHERE m.tenant_id = :tenant_id
            ORDER BY m.tipo_movimiento, fc.nombre
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId]);

        return $statement->fetchAll();
    }

    public function mapeoById(int $tenantId, int $mapeoId): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                categoria_id,
                tipo_movimiento,
                cuenta_debe_id,
                cuenta_haber_id,
                descripcion,
                es_activo,
                created_at,
                updated_at,
                created_by,
                updated_by
            FROM acct_mapeo_finanzas
            WHERE tenant_id = :tenant_id
              AND id = :mapeo_id
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'mapeo_id' => $mapeoId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function mapeoByCategoriaTipo(int $tenantId, int $categoriaId, string $tipoMovimiento): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                categoria_id,
                tipo_movimiento,
                cuenta_debe_id,
                cuenta_haber_id,
                descripcion,
                es_activo
            FROM acct_mapeo_finanzas
            WHERE tenant_id = :tenant_id
              AND categoria_id = :categoria_id
              AND tipo_movimiento = :tipo_movimiento
              AND es_activo = 1
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'categoria_id' => $categoriaId,
            'tipo_movimiento' => $tipoMovimiento,
        ]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function finCategoriaExists(int $tenantId, int $categoriaId, string $tipoMovimiento): bool
    {
        $sql = "
            SELECT 1
            FROM fin_categorias
            WHERE tenant_id = :tenant_id
              AND id = :categoria_id
              AND tipo = :tipo_movimiento
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'categoria_id' => $categoriaId,
            'tipo_movimiento' => $tipoMovimiento,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function createMapeoFinanzas(int $tenantId, int $userId, array $data): int
    {
        $sql = "
            INSERT INTO acct_mapeo_finanzas (
                tenant_id,
                categoria_id,
                tipo_movimiento,
                cuenta_debe_id,
                cuenta_haber_id,
                descripcion,
                es_activo,
                created_by
            ) VALUES (
                :tenant_id,
                :categoria_id,
                :tipo_movimiento,
                :cuenta_debe_id,
                :cuenta_haber_id,
                :descripcion,
                :es_activo,
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'categoria_id' => $data['categoria_id'],
            'tipo_movimiento' => $data['tipo_movimiento'],
            'cuenta_debe_id' => $data['cuenta_debe_id'],
            'cuenta_haber_id' => $data['cuenta_haber_id'],
            'descripcion' => $data['descripcion'],
            'es_activo' => $data['es_activo'],
            'created_by' => $userId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function updateMapeoFinanzas(int $tenantId, int $mapeoId, int $userId, array $data): void
    {
        $allowed = ['categoria_id', 'tipo_movimiento', 'cuenta_debe_id', 'cuenta_haber_id', 'descripcion', 'es_activo'];
        $assignments = [];
        $params = ['tenant_id' => $tenantId, 'mapeo_id' => $mapeoId, 'updated_by' => $userId];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $assignments[] = $field . ' = :' . $field;
                $params[$field] = $data[$field];
            }
        }

        if ($assignments === []) {
            throw new RuntimeException('ACCT_EMPTY_UPDATE');
        }

        $assignments[] = 'updated_by = :updated_by';

        $sql = "
            UPDATE acct_mapeo_finanzas
            SET " . implode(', ', $assignments) . "
            WHERE tenant_id = :tenant_id
              AND id = :mapeo_id
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
    }

    public function finMovimientoById(int $tenantId, int $movimientoId): ?array
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
                created_at
            FROM fin_movimientos
            WHERE tenant_id = :tenant_id
              AND id = :movimiento_id
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'movimiento_id' => $movimientoId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function asientoExistsForMovimiento(int $tenantId, int $movimientoId): bool
    {
        $sql = "
            SELECT 1
            FROM acct_asientos
            WHERE tenant_id = :tenant_id
              AND fin_movimiento_id = :movimiento_id
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'movimiento_id' => $movimientoId]);

        return $statement->fetchColumn() !== false;
    }

    public function libroDiario(int $tenantId, string $fechaInicio, string $fechaFin): array
    {
        $sql = "
            SELECT
                a.numero,
                a.fecha_asiento,
                a.descripcion AS asiento_descripcion,
                a.origen,
                a.estado,
                c.codigo AS cuenta_codigo,
                c.nombre AS cuenta_nombre,
                d.descripcion AS detalle_descripcion,
                d.debe,
                d.haber
            FROM acct_asientos a
            INNER JOIN acct_asiento_detalles d
                ON d.asiento_id = a.id
                AND d.tenant_id = a.tenant_id
            INNER JOIN acct_cuentas c
                ON c.id = d.cuenta_id
                AND c.tenant_id = a.tenant_id
            WHERE a.tenant_id = :tenant_id
              AND a.fecha_asiento BETWEEN :fecha_inicio AND :fecha_fin
              AND a.estado = 'aprobado'
            ORDER BY a.fecha_asiento, a.numero, d.id
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);

        return $statement->fetchAll();
    }

    public function libroMayor(int $tenantId, int $cuentaId, string $fechaInicio, string $fechaFin): array
    {
        $sql = "
            SELECT
                c.codigo,
                c.nombre,
                a.fecha_asiento,
                a.numero,
                a.descripcion,
                d.debe,
                d.haber
            FROM acct_asiento_detalles d
            INNER JOIN acct_asientos a
                ON a.id = d.asiento_id
                AND a.tenant_id = d.tenant_id
            INNER JOIN acct_cuentas c
                ON c.id = d.cuenta_id
                AND c.tenant_id = d.tenant_id
            WHERE d.tenant_id = :tenant_id
              AND d.cuenta_id = :cuenta_id
              AND a.estado = 'aprobado'
              AND a.fecha_asiento BETWEEN :fecha_inicio AND :fecha_fin
            ORDER BY a.fecha_asiento, a.numero
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'cuenta_id' => $cuentaId,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);

        return $statement->fetchAll();
    }

    public function balanceComprobacion(int $tenantId, string $fechaInicio, string $fechaFin): array
    {
        $sql = "
            SELECT
                c.codigo,
                c.nombre,
                c.tipo,
                SUM(d.debe) AS total_debe,
                SUM(d.haber) AS total_haber,
                SUM(d.debe - d.haber) AS saldo_deudor,
                SUM(d.haber - d.debe) AS saldo_acreedor
            FROM acct_asiento_detalles d
            INNER JOIN acct_asientos a
                ON a.id = d.asiento_id
                AND a.tenant_id = d.tenant_id
            INNER JOIN acct_cuentas c
                ON c.id = d.cuenta_id
                AND c.tenant_id = d.tenant_id
            WHERE d.tenant_id = :tenant_id
              AND a.estado = 'aprobado'
              AND a.fecha_asiento BETWEEN :fecha_inicio AND :fecha_fin
            GROUP BY c.id, c.codigo, c.nombre, c.tipo
            ORDER BY c.codigo
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);

        return $statement->fetchAll();
    }

    public function estadoResultados(int $tenantId, string $fechaInicio, string $fechaFin): array
    {
        $sql = "
            SELECT
                c.tipo,
                c.codigo,
                c.nombre,
                SUM(d.haber - d.debe) AS saldo
            FROM acct_asiento_detalles d
            INNER JOIN acct_asientos a
                ON a.id = d.asiento_id
                AND a.tenant_id = d.tenant_id
            INNER JOIN acct_cuentas c
                ON c.id = d.cuenta_id
                AND c.tenant_id = d.tenant_id
            WHERE d.tenant_id = :tenant_id
              AND a.estado = 'aprobado'
              AND c.tipo IN ('ingreso', 'gasto')
              AND a.fecha_asiento BETWEEN :fecha_inicio AND :fecha_fin
            GROUP BY c.id, c.tipo, c.codigo, c.nombre
            ORDER BY c.codigo
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);

        return $statement->fetchAll();
    }
}
