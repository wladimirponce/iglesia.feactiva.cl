<?php

declare(strict_types=1);

final class ContabilidadService
{
    public function __construct(
        private readonly ContabilidadRepository $repository
    ) {
    }

    public function configuracion(int $tenantId): ?array
    {
        return $this->repository->configuracion($tenantId);
    }

    public function updateConfiguracion(int $tenantId, int $userId, array $input): void
    {
        $old = $this->repository->configuracion($tenantId);
        if ($old === null) {
            throw new RuntimeException('ACCT_CONFIG_NOT_FOUND');
        }

        $data = $this->normalizeConfiguracion($input);
        $this->repository->updateConfiguracion($tenantId, $userId, $data);
        $new = $this->repository->configuracion($tenantId);
        $this->repository->audit($tenantId, $userId, 'acct.config.updated', 'acct_configuracion', (int) $old['id'], $old, $new);
    }

    public function cuentas(int $tenantId): array
    {
        return $this->repository->cuentas($tenantId);
    }

    public function createCuenta(int $tenantId, int $userId, array $input): int
    {
        $data = $this->normalizeCuenta($input);
        $this->validateCuentaParent($tenantId, $data);

        try {
            $id = $this->repository->createCuenta($tenantId, $userId, $data);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new RuntimeException('ACCT_ACCOUNT_DUPLICATE');
            }
            throw $exception;
        }

        $new = $this->repository->cuentaById($tenantId, $id);
        $this->repository->audit($tenantId, $userId, 'acct.cuenta.created', 'acct_cuentas', $id, null, $new);

        return $id;
    }

    public function updateCuenta(int $tenantId, int $userId, int $cuentaId, array $input): void
    {
        $old = $this->repository->cuentaById($tenantId, $cuentaId);
        if ($old === null) {
            throw new RuntimeException('ACCT_ACCOUNT_NOT_FOUND');
        }

        $data = $this->normalizeCuenta($input, true);
        $this->validateCuentaParent($tenantId, $data, $cuentaId);

        try {
            $this->repository->updateCuenta($tenantId, $cuentaId, $userId, $data);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new RuntimeException('ACCT_ACCOUNT_DUPLICATE');
            }
            throw $exception;
        }

        $new = $this->repository->cuentaById($tenantId, $cuentaId);
        $this->repository->audit($tenantId, $userId, 'acct.cuenta.updated', 'acct_cuentas', $cuentaId, $old, $new);
    }

    public function periodos(int $tenantId): array
    {
        return $this->repository->periodos($tenantId);
    }

    public function createPeriodo(int $tenantId, int $userId, array $input): int
    {
        try {
            $id = $this->repository->createPeriodo($tenantId, $userId, [
                'nombre' => trim((string) $input['nombre']),
                'fecha_inicio' => (string) $input['fecha_inicio'],
                'fecha_fin' => (string) $input['fecha_fin'],
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new RuntimeException('ACCT_PERIOD_DUPLICATE');
            }
            throw $exception;
        }

        $new = $this->repository->periodoById($tenantId, $id);
        $this->repository->audit($tenantId, $userId, 'acct.periodo.created', 'acct_periodos', $id, null, $new);

        return $id;
    }

    public function closePeriodo(int $tenantId, int $userId, int $periodoId): void
    {
        $old = $this->repository->periodoById($tenantId, $periodoId);
        if ($old === null) {
            throw new RuntimeException('ACCT_PERIOD_NOT_FOUND');
        }
        if ($old['estado'] !== 'abierto') {
            throw new RuntimeException('ACCT_PERIOD_NOT_OPEN');
        }

        $this->repository->closePeriodo($tenantId, $periodoId, $userId);
        $new = $this->repository->periodoById($tenantId, $periodoId);
        $this->repository->audit($tenantId, $userId, 'acct.periodo.closed', 'acct_periodos', $periodoId, $old, $new);
    }

    public function asientos(int $tenantId): array
    {
        return $this->repository->asientos($tenantId);
    }

    public function asiento(int $tenantId, int $asientoId): ?array
    {
        $asiento = $this->repository->asientoById($tenantId, $asientoId);
        if ($asiento === null) {
            return null;
        }

        $asiento['lineas'] = $this->repository->asientoDetalles($tenantId, $asientoId);

        return $asiento;
    }

    public function createAsiento(int $tenantId, int $userId, array $input): int
    {
        $data = $this->normalizeAsiento($input);
        $this->validateOpenPeriod($tenantId, $data);
        $this->validateAsientoResources($tenantId, $data);

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $asientoId = $this->repository->createAsiento($tenantId, $userId, $data);

            foreach ($data['lineas'] as $linea) {
                $this->repository->createAsientoDetalle($tenantId, $asientoId, $linea);
            }

            $new = $this->asiento($tenantId, $asientoId);
            $this->repository->audit($tenantId, $userId, 'acct.asiento.created', 'acct_asientos', $asientoId, null, $new);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();

            if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                throw new RuntimeException('ACCT_JOURNAL_DUPLICATE');
            }

            throw $exception;
        }

        return $asientoId;
    }

    public function approveAsiento(int $tenantId, int $userId, int $asientoId): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $old = $this->requireEditableAsiento($tenantId, $asientoId, true);
            $this->validateExistingAsientoBalanced($tenantId, $asientoId);
            $this->ensureAsientoPeriodOpen($tenantId, $old);
            $this->repository->approveAsiento($tenantId, $asientoId, $userId);
            $new = $this->asiento($tenantId, $asientoId);
            $this->repository->audit($tenantId, $userId, 'acct.asiento.approved', 'acct_asientos', $asientoId, $old, $new);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function cancelAsiento(int $tenantId, int $userId, int $asientoId, ?string $motivo): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $old = $this->repository->asientoById($tenantId, $asientoId);
            if ($old === null) {
                throw new RuntimeException('ACCT_JOURNAL_NOT_FOUND');
            }
            if ($old['estado'] === 'anulado') {
                throw new RuntimeException('ACCT_JOURNAL_ALREADY_CANCELLED');
            }
            $this->ensureAsientoPeriodOpen($tenantId, $old);
            $this->repository->cancelAsiento($tenantId, $asientoId, $userId, $motivo);
            $new = $this->asiento($tenantId, $asientoId);
            $this->repository->audit($tenantId, $userId, 'acct.asiento.cancelled', 'acct_asientos', $asientoId, $old, $new);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function reverseAsiento(int $tenantId, int $userId, int $asientoId): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $old = $this->asiento($tenantId, $asientoId);
            if ($old === null) {
                throw new RuntimeException('ACCT_JOURNAL_NOT_FOUND');
            }
            if ($old['estado'] !== 'aprobado') {
                throw new RuntimeException('ACCT_JOURNAL_NOT_APPROVED');
            }
            $this->ensureAsientoPeriodOpen($tenantId, $old);

            $data = [
                'periodo_id' => $old['periodo_id'] === null ? null : (int) $old['periodo_id'],
                'numero' => $old['numero'] . '-REV-' . gmdate('YmdHis'),
                'fecha_asiento' => gmdate('Y-m-d'),
                'descripcion' => 'Reversa de asiento ' . $old['numero'],
                'origen' => 'reversa',
                'fin_movimiento_id' => null,
                'asiento_reversado_id' => $asientoId,
                'total_debe' => (float) $old['total_haber'],
                'total_haber' => (float) $old['total_debe'],
                'moneda' => $old['moneda'],
                'lineas' => [],
            ];

            foreach ($old['lineas'] as $linea) {
                $data['lineas'][] = [
                    'cuenta_id' => (int) $linea['cuenta_id'],
                    'centro_costo_id' => $linea['centro_costo_id'] === null ? null : (int) $linea['centro_costo_id'],
                    'descripcion' => 'Reversa: ' . ($linea['descripcion'] ?? $old['descripcion']),
                    'debe' => (float) $linea['haber'],
                    'haber' => (float) $linea['debe'],
                    'referencia' => $old['numero'],
                ];
            }

            $reversalId = $this->repository->createAsiento($tenantId, $userId, $data);
            foreach ($data['lineas'] as $linea) {
                $this->repository->createAsientoDetalle($tenantId, $reversalId, $linea);
            }

            $new = $this->asiento($tenantId, $reversalId);
            $this->repository->audit($tenantId, $userId, 'acct.asiento.reversed', 'acct_asientos', $reversalId, $old, $new);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        return $reversalId;
    }

    public function libroDiario(int $tenantId, int $userId, array $filters): array
    {
        [$fechaInicio, $fechaFin] = $this->reportDateRange($filters);
        $data = $this->repository->libroDiario($tenantId, $fechaInicio, $fechaFin);
        $this->auditReport($tenantId, $userId, 'libro-diario', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]);

        return [
            'data' => $data,
            'meta' => ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'estado' => 'aprobado'],
        ];
    }

    public function libroMayor(int $tenantId, int $userId, array $filters): array
    {
        [$fechaInicio, $fechaFin] = $this->reportDateRange($filters);
        $cuentaId = isset($filters['cuenta_id']) ? (int) $filters['cuenta_id'] : 0;

        if ($cuentaId < 1 || !$this->repository->cuentaExists($tenantId, $cuentaId)) {
            throw new RuntimeException('ACCT_ACCOUNT_NOT_FOUND');
        }

        $data = $this->repository->libroMayor($tenantId, $cuentaId, $fechaInicio, $fechaFin);
        $this->auditReport($tenantId, $userId, 'libro-mayor', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'cuenta_id' => $cuentaId]);

        return [
            'data' => $data,
            'meta' => ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'cuenta_id' => $cuentaId, 'estado' => 'aprobado'],
        ];
    }

    public function balanceComprobacion(int $tenantId, int $userId, array $filters): array
    {
        [$fechaInicio, $fechaFin] = $this->reportDateRange($filters);
        $data = $this->repository->balanceComprobacion($tenantId, $fechaInicio, $fechaFin);
        $this->auditReport($tenantId, $userId, 'balance-comprobacion', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]);

        return [
            'data' => $data,
            'meta' => ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'estado' => 'aprobado'],
        ];
    }

    public function estadoResultados(int $tenantId, int $userId, array $filters): array
    {
        [$fechaInicio, $fechaFin] = $this->reportDateRange($filters);
        $data = $this->repository->estadoResultados($tenantId, $fechaInicio, $fechaFin);
        $this->auditReport($tenantId, $userId, 'estado-resultados', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]);

        return [
            'data' => $data,
            'meta' => ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'estado' => 'aprobado'],
        ];
    }

    public function mapeosFinanzas(int $tenantId): array
    {
        return $this->repository->mapeosFinanzas($tenantId);
    }

    public function createMapeoFinanzas(int $tenantId, int $userId, array $input): int
    {
        $data = $this->normalizeMapeoFinanzas($input);
        $this->validateMapeoResources($tenantId, $data);

        try {
            $id = $this->repository->createMapeoFinanzas($tenantId, $userId, $data);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new RuntimeException('ACCT_MAPEO_DUPLICATE');
            }
            throw $exception;
        }

        $new = $this->repository->mapeoById($tenantId, $id);
        $this->repository->audit($tenantId, $userId, 'acct.mapeo.created', 'acct_mapeo_finanzas', $id, null, $new);

        return $id;
    }

    public function updateMapeoFinanzas(int $tenantId, int $userId, int $mapeoId, array $input): void
    {
        $old = $this->repository->mapeoById($tenantId, $mapeoId);
        if ($old === null) {
            throw new RuntimeException('ACCT_MAPEO_NOT_FOUND');
        }

        $data = $this->normalizeMapeoFinanzas($input, true);
        $merged = array_merge($old, $data);
        $this->validateMapeoResources($tenantId, $merged);

        try {
            $this->repository->updateMapeoFinanzas($tenantId, $mapeoId, $userId, $data);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new RuntimeException('ACCT_MAPEO_DUPLICATE');
            }
            throw $exception;
        }

        $new = $this->repository->mapeoById($tenantId, $mapeoId);
        $this->repository->audit($tenantId, $userId, 'acct.mapeo.updated', 'acct_mapeo_finanzas', $mapeoId, $old, $new);
    }

    public function generarDesdeFinanzas(int $tenantId, int $userId, int $movimientoId): int
    {
        $movimiento = $this->repository->finMovimientoById($tenantId, $movimientoId);
        if ($movimiento === null) {
            throw new RuntimeException('FIN_MOVEMENT_NOT_FOUND');
        }
        if ($movimiento['estado'] === 'anulado') {
            throw new RuntimeException('FIN_MOVEMENT_CANCELLED');
        }
        if ($this->repository->asientoExistsForMovimiento($tenantId, $movimientoId)) {
            throw new RuntimeException('ACCT_JOURNAL_ALREADY_EXISTS_FOR_FINANCE');
        }

        $mapeo = $this->repository->mapeoByCategoriaTipo($tenantId, (int) $movimiento['categoria_id'], (string) $movimiento['tipo']);
        if ($mapeo === null) {
            throw new RuntimeException('ACCT_MAPEO_NOT_FOUND');
        }

        $data = [
            'periodo_id' => null,
            'numero' => 'FIN-' . $movimiento['id'] . '-' . gmdate('YmdHis'),
            'fecha_asiento' => (string) ($movimiento['fecha_contable'] ?: $movimiento['fecha_movimiento']),
            'descripcion' => 'Asiento desde Finanzas: ' . $movimiento['descripcion'],
            'origen' => 'finanzas',
            'fin_movimiento_id' => (int) $movimiento['id'],
            'asiento_reversado_id' => null,
            'total_debe' => (float) $movimiento['monto'],
            'total_haber' => (float) $movimiento['monto'],
            'moneda' => (string) $movimiento['moneda'],
            'lineas' => [
                [
                    'cuenta_id' => (int) $mapeo['cuenta_debe_id'],
                    'centro_costo_id' => $movimiento['centro_costo_id'] === null ? null : (int) $movimiento['centro_costo_id'],
                    'descripcion' => $movimiento['descripcion'],
                    'debe' => (float) $movimiento['monto'],
                    'haber' => 0.0,
                    'referencia' => $movimiento['referencia_pago'],
                ],
                [
                    'cuenta_id' => (int) $mapeo['cuenta_haber_id'],
                    'centro_costo_id' => $movimiento['centro_costo_id'] === null ? null : (int) $movimiento['centro_costo_id'],
                    'descripcion' => $movimiento['descripcion'],
                    'debe' => 0.0,
                    'haber' => (float) $movimiento['monto'],
                    'referencia' => $movimiento['referencia_pago'],
                ],
            ],
        ];

        $this->validateOpenPeriod($tenantId, $data);
        $this->validateAsientoResources($tenantId, $data);

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $asientoId = $this->repository->createAsiento($tenantId, $userId, $data);
            foreach ($data['lineas'] as $linea) {
                $this->repository->createAsientoDetalle($tenantId, $asientoId, $linea);
            }
            $new = $this->asiento($tenantId, $asientoId);
            $this->repository->audit($tenantId, $userId, 'acct.asiento.created_from_finance', 'acct_asientos', $asientoId, null, [
                'asiento' => $new,
                'fin_movimiento' => $movimiento,
                'mapeo' => $mapeo,
            ]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        return $asientoId;
    }

    private function normalizeConfiguracion(array $input): array
    {
        $data = [];
        foreach (['pais_codigo', 'moneda_base', 'norma_contable'] as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = trim((string) $input[$field]) === '' ? null : trim((string) $input[$field]);
            }
        }
        foreach (['periodo_inicio_mes', 'usa_centros_costo', 'requiere_aprobacion_asientos', 'numeracion_automatica'] as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = (int) $input[$field];
            }
        }

        return $data;
    }

    private function normalizeMapeoFinanzas(array $input, bool $partial = false): array
    {
        $data = [];
        foreach (['categoria_id', 'cuenta_debe_id', 'cuenta_haber_id', 'es_activo'] as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $input[$field] === null || $input[$field] === '' ? null : (int) $input[$field];
            }
        }
        foreach (['tipo_movimiento', 'descripcion'] as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = trim((string) $input[$field]) === '' && $field === 'descripcion' ? null : trim((string) $input[$field]);
            }
        }

        if (!$partial) {
            $data += ['descripcion' => null, 'es_activo' => 1];
        }

        return $data;
    }

    private function validateMapeoResources(int $tenantId, array $data): void
    {
        if (!$this->repository->finCategoriaExists($tenantId, (int) $data['categoria_id'], (string) $data['tipo_movimiento'])) {
            throw new RuntimeException('FIN_CATEGORY_NOT_FOUND');
        }
        if (!$this->repository->cuentaExists($tenantId, (int) $data['cuenta_debe_id'], true)) {
            throw new RuntimeException('ACCT_DEBIT_ACCOUNT_NOT_FOUND');
        }
        if (!$this->repository->cuentaExists($tenantId, (int) $data['cuenta_haber_id'], true)) {
            throw new RuntimeException('ACCT_CREDIT_ACCOUNT_NOT_FOUND');
        }
        if ((int) $data['cuenta_debe_id'] === (int) $data['cuenta_haber_id']) {
            throw new RuntimeException('ACCT_MAPEO_SAME_ACCOUNT');
        }
    }

    private function reportDateRange(array $filters): array
    {
        $fechaInicio = isset($filters['fecha_inicio']) && $filters['fecha_inicio'] !== ''
            ? (string) $filters['fecha_inicio']
            : gmdate('Y-01-01');
        $fechaFin = isset($filters['fecha_fin']) && $filters['fecha_fin'] !== ''
            ? (string) $filters['fecha_fin']
            : gmdate('Y-12-31');

        if (!$this->isDate($fechaInicio) || !$this->isDate($fechaFin) || $fechaInicio > $fechaFin) {
            throw new RuntimeException('ACCT_REPORT_INVALID_DATE_RANGE');
        }

        return [$fechaInicio, $fechaFin];
    }

    private function auditReport(int $tenantId, int $userId, string $reportName, array $filters): void
    {
        $this->repository->audit($tenantId, $userId, 'acct.reporte.generated', 'acct_reportes', 0, null, [
            'report' => $reportName,
            'filters' => $filters,
        ]);
    }

    private function isDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }

    private function normalizeCuenta(array $input, bool $partial = false): array
    {
        $data = [];
        $stringFields = ['codigo', 'nombre', 'descripcion', 'tipo', 'naturaleza'];
        foreach ($stringFields as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = trim((string) $input[$field]) === '' && $field === 'descripcion' ? null : trim((string) $input[$field]);
            }
        }
        foreach (['cuenta_padre_id', 'nivel', 'es_movimiento', 'es_activa'] as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $input[$field] === null || $input[$field] === '' ? null : (int) $input[$field];
            }
        }

        if (!$partial) {
            $data += [
                'descripcion' => null,
                'cuenta_padre_id' => null,
                'nivel' => 1,
                'es_movimiento' => 1,
                'es_activa' => 1,
            ];
        }

        return $data;
    }

    private function normalizeAsiento(array $input): array
    {
        $lineas = [];
        $totalDebe = 0.0;
        $totalHaber = 0.0;

        foreach ($input['lineas'] as $linea) {
            $normalized = [
                'cuenta_id' => (int) $linea['cuenta_id'],
                'centro_costo_id' => isset($linea['centro_costo_id']) && $linea['centro_costo_id'] !== '' ? (int) $linea['centro_costo_id'] : null,
                'descripcion' => isset($linea['descripcion']) && trim((string) $linea['descripcion']) !== '' ? trim((string) $linea['descripcion']) : null,
                'debe' => isset($linea['debe']) ? (float) $linea['debe'] : 0.0,
                'haber' => isset($linea['haber']) ? (float) $linea['haber'] : 0.0,
                'referencia' => isset($linea['referencia']) && trim((string) $linea['referencia']) !== '' ? trim((string) $linea['referencia']) : null,
            ];
            $totalDebe += $normalized['debe'];
            $totalHaber += $normalized['haber'];
            $lineas[] = $normalized;
        }

        return [
            'periodo_id' => isset($input['periodo_id']) && $input['periodo_id'] !== '' ? (int) $input['periodo_id'] : null,
            'numero' => trim((string) $input['numero']),
            'fecha_asiento' => (string) $input['fecha_asiento'],
            'descripcion' => trim((string) $input['descripcion']),
            'origen' => isset($input['origen']) && $input['origen'] !== '' ? (string) $input['origen'] : 'manual',
            'fin_movimiento_id' => isset($input['fin_movimiento_id']) && $input['fin_movimiento_id'] !== '' ? (int) $input['fin_movimiento_id'] : null,
            'asiento_reversado_id' => isset($input['asiento_reversado_id']) && $input['asiento_reversado_id'] !== '' ? (int) $input['asiento_reversado_id'] : null,
            'total_debe' => round($totalDebe, 2),
            'total_haber' => round($totalHaber, 2),
            'moneda' => isset($input['moneda']) && $input['moneda'] !== '' ? (string) $input['moneda'] : 'CLP',
            'lineas' => $lineas,
        ];
    }

    private function validateCuentaParent(int $tenantId, array $data, ?int $selfId = null): void
    {
        if (!array_key_exists('cuenta_padre_id', $data) || $data['cuenta_padre_id'] === null) {
            return;
        }
        if ($selfId !== null && (int) $data['cuenta_padre_id'] === $selfId) {
            throw new RuntimeException('ACCT_ACCOUNT_INVALID_PARENT');
        }
        if (!$this->repository->cuentaExists($tenantId, (int) $data['cuenta_padre_id'])) {
            throw new RuntimeException('ACCT_ACCOUNT_PARENT_NOT_FOUND');
        }
    }

    private function validateOpenPeriod(int $tenantId, array &$data): void
    {
        if ($data['periodo_id'] !== null) {
            $periodo = $this->repository->periodoById($tenantId, (int) $data['periodo_id']);
        } else {
            $periodo = $this->repository->periodoForDate($tenantId, $data['fecha_asiento']);
            $data['periodo_id'] = $periodo === null ? null : (int) $periodo['id'];
        }

        if ($periodo !== null && $periodo['estado'] !== 'abierto') {
            throw new RuntimeException('ACCT_PERIOD_CLOSED');
        }
    }

    private function validateAsientoResources(int $tenantId, array $data): void
    {
        foreach ($data['lineas'] as $linea) {
            if (!$this->repository->cuentaExists($tenantId, (int) $linea['cuenta_id'], true)) {
                throw new RuntimeException('ACCT_ACCOUNT_NOT_FOUND');
            }
            if ($linea['centro_costo_id'] !== null && !$this->repository->centroCostoExists($tenantId, (int) $linea['centro_costo_id'])) {
                throw new RuntimeException('ACCT_COST_CENTER_NOT_FOUND');
            }
        }
    }

    private function requireEditableAsiento(int $tenantId, int $asientoId, bool $requireDraft = false): array
    {
        $asiento = $this->asiento($tenantId, $asientoId);
        if ($asiento === null) {
            throw new RuntimeException('ACCT_JOURNAL_NOT_FOUND');
        }
        if ($requireDraft && $asiento['estado'] !== 'borrador') {
            throw new RuntimeException('ACCT_JOURNAL_NOT_DRAFT');
        }

        return $asiento;
    }

    private function validateExistingAsientoBalanced(int $tenantId, int $asientoId): void
    {
        $lineas = $this->repository->asientoDetalles($tenantId, $asientoId);
        if (count($lineas) < 2) {
            throw new RuntimeException('ACCT_JOURNAL_MIN_LINES');
        }

        $debe = 0.0;
        $haber = 0.0;
        foreach ($lineas as $linea) {
            $debe += (float) $linea['debe'];
            $haber += (float) $linea['haber'];
        }

        if (round($debe, 2) <= 0 || round($debe, 2) !== round($haber, 2)) {
            throw new RuntimeException('ACCT_JOURNAL_UNBALANCED');
        }
    }

    private function ensureAsientoPeriodOpen(int $tenantId, array $asiento): void
    {
        if ($asiento['periodo_id'] === null) {
            return;
        }

        $periodo = $this->repository->periodoById($tenantId, (int) $asiento['periodo_id']);
        if ($periodo !== null && $periodo['estado'] !== 'abierto') {
            throw new RuntimeException('ACCT_PERIOD_CLOSED');
        }
    }
}
