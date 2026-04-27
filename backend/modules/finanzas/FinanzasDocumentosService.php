<?php

declare(strict_types=1);

final class FinanzasDocumentosService
{
    public function __construct(
        private readonly FinanzasDocumentosRepository $repository
    ) {
    }

    public function listByMovimiento(int $tenantId, int $movimientoId): array
    {
        if (!$this->repository->movimientoExists($tenantId, $movimientoId)) {
            throw new RuntimeException('FIN_MOVEMENT_NOT_FOUND');
        }

        return $this->repository->listByMovimiento($tenantId, $movimientoId);
    }

    public function create(int $tenantId, int $userId, int $movimientoId, array $input): int
    {
        if (!$this->repository->movimientoExists($tenantId, $movimientoId)) {
            throw new RuntimeException('FIN_MOVEMENT_NOT_FOUND');
        }

        $data = [
            'tipo_documento' => isset($input['tipo_documento']) && $input['tipo_documento'] !== '' ? (string) $input['tipo_documento'] : 'otro',
            'numero_documento' => $this->nullableString($input['numero_documento'] ?? null),
            'fecha_documento' => $this->nullableString($input['fecha_documento'] ?? null),
            'archivo_url' => $this->nullableString($input['archivo_url'] ?? null),
            'archivo_nombre' => $this->nullableString($input['archivo_nombre'] ?? null),
            'archivo_mime' => $this->nullableString($input['archivo_mime'] ?? null),
            'archivo_size' => isset($input['archivo_size']) && $input['archivo_size'] !== '' ? (int) $input['archivo_size'] : null,
            'descripcion' => $this->nullableString($input['descripcion'] ?? null),
        ];

        $documentoId = $this->repository->create($tenantId, $movimientoId, $userId, $data);
        $this->repository->audit($tenantId, $userId, 'fin.documento.uploaded', $documentoId, null, $data + ['id' => $documentoId, 'movimiento_id' => $movimientoId]);

        return $documentoId;
    }

    public function delete(int $tenantId, int $userId, int $documentoId): void
    {
        $old = $this->repository->find($tenantId, $documentoId);

        if ($old === null) {
            throw new RuntimeException('FIN_DOCUMENT_NOT_FOUND');
        }

        $this->repository->delete($tenantId, $documentoId);
        $this->repository->audit($tenantId, $userId, 'fin.documento.deleted', $documentoId, $old, null);
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null || trim((string) $value) === '' ? null : trim((string) $value);
    }
}
