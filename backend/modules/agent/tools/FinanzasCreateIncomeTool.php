<?php

declare(strict_types=1);

final class FinanzasCreateIncomeTool implements AgentToolInterface
{
    public function name(): string { return 'finanzas_create_income'; }

    public function moduleCode(): string { return 'finanzas'; }

    public function requiredPermission(): string { return 'fin.movimientos.crear'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        return $this->createMovement($tenantId, $userId, $input, 'ingreso');
    }

    public function createMovement(int $tenantId, int $userId, array $input, string $tipo): array
    {
        $cuentaId = (int) ($input['cuenta_id'] ?? 0);
        $categoriaId = (int) ($input['categoria_id'] ?? 0);
        $monto = (float) ($input['monto'] ?? 0);
        $fecha = trim((string) ($input['fecha_movimiento'] ?? date('Y-m-d')));
        $medioPago = trim((string) ($input['medio_pago'] ?? 'efectivo'));
        $descripcion = trim((string) ($input['descripcion'] ?? 'Ingreso registrado por agente'));
        $subtipo = trim((string) ($input['subtipo'] ?? 'otro'));
        $centroCostoId = isset($input['centro_costo_id']) ? (int) $input['centro_costo_id'] : null;
        $personaId = isset($input['persona_id']) ? (int) $input['persona_id'] : null;

        if ($cuentaId < 1 || $categoriaId < 1 || $monto <= 0) {
            throw new RuntimeException('AGENT_TOOL_MISSING_FINANCE_DATA');
        }

        if (!$this->validDate($fecha)) {
            throw new RuntimeException('AGENT_TOOL_INVALID_DATE');
        }

        if (!in_array($medioPago, ['efectivo', 'transferencia', 'tarjeta_debito', 'tarjeta_credito', 'cheque', 'paypal', 'stripe', 'flow', 'mercadopago', 'otro'], true)) {
            $medioPago = 'efectivo';
        }

        $this->ensureCuenta($tenantId, $cuentaId);
        $this->ensureCategoria($tenantId, $categoriaId, $tipo);

        $sql = "
            INSERT INTO fin_movimientos (
                tenant_id,
                cuenta_id,
                categoria_id,
                centro_costo_id,
                persona_id,
                tipo,
                subtipo,
                descripcion,
                monto,
                moneda,
                fecha_movimiento,
                fecha_contable,
                medio_pago,
                estado,
                created_by
            ) VALUES (
                :tenant_id,
                :cuenta_id,
                :categoria_id,
                :centro_costo_id,
                :persona_id,
                :tipo,
                :subtipo,
                :descripcion,
                :monto,
                'CLP',
                :fecha_movimiento,
                :fecha_contable,
                :medio_pago,
                'registrado',
                :created_by
            )
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'cuenta_id' => $cuentaId,
            'categoria_id' => $categoriaId,
            'centro_costo_id' => $centroCostoId !== null && $centroCostoId > 0 ? $centroCostoId : null,
            'persona_id' => $personaId !== null && $personaId > 0 ? $personaId : null,
            'tipo' => $tipo,
            'subtipo' => $subtipo,
            'descripcion' => $descripcion,
            'monto' => $monto,
            'fecha_movimiento' => $fecha,
            'fecha_contable' => $fecha,
            'medio_pago' => $medioPago,
            'created_by' => $userId,
        ]);

        return [
            'id' => (int) Database::connection()->lastInsertId(),
            'tipo' => $tipo,
            'subtipo' => $subtipo,
            'monto' => $monto,
            'fecha_movimiento' => $fecha,
            'cuenta_id' => $cuentaId,
            'categoria_id' => $categoriaId,
        ];
    }

    private function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
    }

    private function ensureCuenta(int $tenantId, int $cuentaId): void
    {
        $statement = Database::connection()->prepare("SELECT id FROM fin_cuentas WHERE tenant_id = :tenant_id AND id = :id AND deleted_at IS NULL AND es_activa = 1 LIMIT 1");
        $statement->execute(['tenant_id' => $tenantId, 'id' => $cuentaId]);
        if ($statement->fetchColumn() === false) {
            throw new RuntimeException('AGENT_TOOL_ACCOUNT_NOT_FOUND');
        }
    }

    private function ensureCategoria(int $tenantId, int $categoriaId, string $tipo): void
    {
        $statement = Database::connection()->prepare("SELECT id FROM fin_categorias WHERE tenant_id = :tenant_id AND id = :id AND tipo = :tipo AND deleted_at IS NULL AND es_activa = 1 LIMIT 1");
        $statement->execute(['tenant_id' => $tenantId, 'id' => $categoriaId, 'tipo' => $tipo]);
        if ($statement->fetchColumn() === false) {
            throw new RuntimeException('AGENT_TOOL_CATEGORY_NOT_FOUND');
        }
    }
}
