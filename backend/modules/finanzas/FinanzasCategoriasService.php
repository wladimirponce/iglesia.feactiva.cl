<?php

declare(strict_types=1);

final class FinanzasCategoriasService
{
    public function __construct(
        private readonly FinanzasCategoriasRepository $repository
    ) {
    }

    public function list(int $tenantId, ?string $tipo): array
    {
        if (!in_array($tipo, ['ingreso', 'egreso', null], true)) {
            throw new RuntimeException('FIN_INVALID_CATEGORY_TYPE');
        }

        return $this->repository->list($tenantId, $tipo);
    }
}
