<?php

declare(strict_types=1);

final class FinanzasMovimientosService
{
    public function __construct(
        private readonly FinanzasMovimientosRepository $repository
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

    public function findById(int $tenantId, int $movimientoId): ?array
    {
        return $this->repository->findById($tenantId, $movimientoId);
    }

    public function create(int $tenantId, int $userId, array $input): int
    {
        $data = $this->normalizeCreateInput($input);
        $this->validateTenantResources($tenantId, $data);

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $movimientoId = $this->repository->create($tenantId, $userId, $data);
            $this->repository->audit($tenantId, $userId, 'fin.movimiento.created', $movimientoId, null, $data + ['id' => $movimientoId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        return $movimientoId;
    }

    public function cancel(int $tenantId, int $userId, int $movimientoId, string $motivo): void
    {
        $old = $this->repository->findById($tenantId, $movimientoId);

        if ($old === null) {
            throw new RuntimeException('FIN_MOVEMENT_NOT_FOUND');
        }

        if ($old['estado'] === 'anulado') {
            throw new RuntimeException('FIN_MOVEMENT_ALREADY_CANCELLED');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $this->repository->cancel($tenantId, $movimientoId, $userId, $motivo);
            $new = $this->repository->findById($tenantId, $movimientoId);
            $this->repository->audit($tenantId, $userId, 'fin.movimiento.cancelled', $movimientoId, $old, $new);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private function validateTenantResources(int $tenantId, array $data): void
    {
        if (!$this->repository->tenantResourceExists($tenantId, 'fin_cuentas', (int) $data['cuenta_id'])) {
            throw new RuntimeException('FIN_ACCOUNT_NOT_FOUND');
        }

        if (!$this->repository->tenantResourceExists($tenantId, 'fin_categorias', (int) $data['categoria_id'])) {
            throw new RuntimeException('FIN_CATEGORY_NOT_FOUND');
        }

        if (!$this->repository->categoriaMatchesType($tenantId, (int) $data['categoria_id'], $data['tipo'])) {
            throw new RuntimeException('FIN_CATEGORY_TYPE_MISMATCH');
        }

        if ($data['centro_costo_id'] !== null && !$this->repository->tenantResourceExists($tenantId, 'fin_centros_costo', (int) $data['centro_costo_id'])) {
            throw new RuntimeException('FIN_COST_CENTER_NOT_FOUND');
        }

        if ($data['campana_id'] !== null && !$this->repository->tenantResourceExists($tenantId, 'fin_campanas', (int) $data['campana_id'])) {
            throw new RuntimeException('FIN_CAMPAIGN_NOT_FOUND');
        }

        if ($data['persona_id'] !== null && !$this->repository->tenantResourceExists($tenantId, 'crm_personas', (int) $data['persona_id'])) {
            throw new RuntimeException('FIN_PERSON_NOT_FOUND');
        }
    }

    private function normalizeCreateInput(array $input): array
    {
        return [
            'cuenta_id' => (int) $input['cuenta_id'],
            'categoria_id' => (int) $input['categoria_id'],
            'centro_costo_id' => isset($input['centro_costo_id']) && $input['centro_costo_id'] !== '' ? (int) $input['centro_costo_id'] : null,
            'campana_id' => isset($input['campana_id']) && $input['campana_id'] !== '' ? (int) $input['campana_id'] : null,
            'persona_id' => isset($input['persona_id']) && $input['persona_id'] !== '' ? (int) $input['persona_id'] : null,
            'tipo' => (string) $input['tipo'],
            'subtipo' => $this->nullableString($input['subtipo'] ?? null),
            'descripcion' => trim((string) $input['descripcion']),
            'monto' => (float) $input['monto'],
            'moneda' => isset($input['moneda']) && $input['moneda'] !== '' ? (string) $input['moneda'] : 'CLP',
            'fecha_movimiento' => (string) $input['fecha_movimiento'],
            'fecha_contable' => (string) $input['fecha_contable'],
            'medio_pago' => isset($input['medio_pago']) && $input['medio_pago'] !== '' ? (string) $input['medio_pago'] : 'efectivo',
            'referencia_pago' => $this->nullableString($input['referencia_pago'] ?? null),
            'observacion' => $this->nullableString($input['observacion'] ?? null),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null || trim((string) $value) === '' ? null : trim((string) $value);
    }
}
