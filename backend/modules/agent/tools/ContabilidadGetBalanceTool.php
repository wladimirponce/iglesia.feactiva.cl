<?php

declare(strict_types=1);

final class ContabilidadGetBalanceTool implements AgentToolInterface
{
    public function name(): string { return 'contabilidad_get_balance'; }
    public function moduleCode(): string { return 'contabilidad'; }
    public function requiredPermission(): string { return 'acct.reportes.ver'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        $inicio = trim((string) ($input['fecha_inicio'] ?? date('Y-m-01')));
        $fin = trim((string) ($input['fecha_fin'] ?? date('Y-m-d')));
        if (!$this->validDate($inicio) || !$this->validDate($fin)) {
            throw new RuntimeException('AGENT_TOOL_INVALID_DATE');
        }

        $sql = "
            SELECT c.id, c.codigo, c.nombre, c.tipo, c.naturaleza,
                   COALESCE(SUM(CASE WHEN a.id IS NOT NULL THEN d.debe ELSE 0 END), 0) AS debe,
                   COALESCE(SUM(CASE WHEN a.id IS NOT NULL THEN d.haber ELSE 0 END), 0) AS haber
            FROM acct_cuentas c
            LEFT JOIN acct_asiento_detalles d ON d.cuenta_id = c.id AND d.tenant_id = c.tenant_id
            LEFT JOIN acct_asientos a ON a.id = d.asiento_id AND a.tenant_id = d.tenant_id
                AND a.estado <> 'anulado' AND a.fecha_asiento BETWEEN :fecha_inicio AND :fecha_fin
            WHERE c.tenant_id = :tenant_id AND c.deleted_at IS NULL AND c.es_activa = 1
            GROUP BY c.id, c.codigo, c.nombre, c.tipo, c.naturaleza
            ORDER BY c.codigo ASC
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin]);
        $cuentas = array_map(static fn (array $r): array => [
            'id' => (int) $r['id'], 'codigo' => $r['codigo'], 'nombre' => $r['nombre'],
            'tipo' => $r['tipo'], 'naturaleza' => $r['naturaleza'],
            'debe' => (float) $r['debe'], 'haber' => (float) $r['haber'],
        ], $statement->fetchAll());

        return ['fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'cuentas' => $cuentas];
    }

    private function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
    }
}
