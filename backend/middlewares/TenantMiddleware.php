<?php

declare(strict_types=1);

final class TenantMiddleware
{
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
            $hasAccess = $repository->userBelongsToActiveTenant($userId, $tenantId);
        } catch (Throwable) {
            Response::error('TENANT_VALIDATION_ERROR', 'No fue posible validar la iglesia.', [], 500);
            return;
        }

        if (!$hasAccess) {
            Response::error('TENANT_ACCESS_DENIED', 'No tiene acceso a esta iglesia.', [], 403);
            return;
        }

        $next();
    }
}
