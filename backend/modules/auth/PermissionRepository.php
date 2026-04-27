<?php

declare(strict_types=1);

final class PermissionRepository
{
    public function userBelongsToActiveTenant(int $userId, int $tenantId): bool
    {
        $sql = "
            SELECT 1
            FROM auth_user_tenants ut
            INNER JOIN saas_tenants t
                ON t.id = ut.tenant_id
            WHERE ut.user_id = :user_id
              AND ut.tenant_id = :tenant_id
              AND ut.status = 'active'
              AND t.status IN ('active', 'trial')
              AND t.deleted_at IS NULL
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function tenantHasActiveModule(int $tenantId, string $moduleCode): bool
    {
        $sql = "
            SELECT 1
            FROM saas_tenant_modules tm
            INNER JOIN saas_modules m
                ON m.id = tm.module_id
            WHERE tm.tenant_id = :tenant_id
              AND m.code = :module_code
              AND tm.is_enabled = 1
              AND m.is_active = 1
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'module_code' => $moduleCode,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function userHasPermission(int $userId, int $tenantId, string $permissionCode): bool
    {
        $sql = "
            SELECT 1
            FROM auth_user_roles ur
            INNER JOIN auth_roles r
                ON r.id = ur.role_id
            INNER JOIN auth_role_permissions rp
                ON rp.role_id = ur.role_id
            INNER JOIN auth_permissions p
                ON p.id = rp.permission_id
            WHERE ur.user_id = :user_id
              AND ur.tenant_id = :tenant_id
              AND p.code = :permission_code
              AND r.deleted_at IS NULL
              AND (r.tenant_id IS NULL OR r.tenant_id = :role_tenant_id)
            LIMIT 1
        ";

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'role_tenant_id' => $tenantId,
            'permission_code' => $permissionCode,
        ]);

        return $statement->fetchColumn() !== false;
    }
}
