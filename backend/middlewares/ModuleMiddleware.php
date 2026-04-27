<?php

declare(strict_types=1);

final class ModuleMiddleware
{
    public function __construct(
        private readonly string $moduleCode
    ) {
    }

    public function handle(callable $next): void
    {
        $tenantId = AuthContext::tenantId();

        if ($tenantId === null) {
            Response::error('TENANT_ACCESS_DENIED', 'No tiene acceso a esta iglesia.', [], 403);
            return;
        }

        try {
            $repository = new PermissionRepository();
            $isActive = $repository->tenantHasActiveModule($tenantId, $this->moduleCode);
        } catch (Throwable) {
            Response::error('MODULE_VALIDATION_ERROR', 'No fue posible validar el modulo.', [], 500);
            return;
        }

        if (!$isActive) {
            Response::error('MODULE_DISABLED', 'Este modulo no esta activo para esta iglesia.', [], 403);
            return;
        }

        $next();
    }
}
