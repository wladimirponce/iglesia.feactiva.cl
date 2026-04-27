<?php

declare(strict_types=1);

final class PermissionMiddleware
{
    public function __construct(
        private readonly string $permissionCode
    ) {
    }

    public function handle(callable $next): void
    {
        $userId = AuthContext::userId();
        $tenantId = AuthContext::tenantId();

        if ($userId === null) {
            Response::error('UNAUTHENTICATED', 'Debe iniciar sesion.', [], 401);
            return;
        }

        if ($tenantId === null) {
            Response::error('TENANT_ACCESS_DENIED', 'No tiene acceso a esta iglesia.', [], 403);
            return;
        }

        try {
            $repository = new PermissionRepository();
            $hasPermission = $repository->userHasPermission($userId, $tenantId, $this->permissionCode);
        } catch (Throwable) {
            Response::error('PERMISSION_VALIDATION_ERROR', 'No fue posible validar el permiso.', [], 500);
            return;
        }

        if (!$hasPermission) {
            Response::error('FORBIDDEN', 'No tiene permisos para realizar esta accion.', [], 403);
            return;
        }

        $next();
    }
}
