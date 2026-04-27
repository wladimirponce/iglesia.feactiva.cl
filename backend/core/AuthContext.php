<?php

declare(strict_types=1);

final class AuthContext
{
    private static ?int $userId = null;
    private static ?string $email = null;
    private static ?int $tenantId = null;

    public static function setUser(int $userId, string $email): void
    {
        self::$userId = $userId;
        self::$email = $email;
    }

    public static function setTenantId(int $tenantId): void
    {
        self::$tenantId = $tenantId;
    }

    public static function userId(): ?int
    {
        return self::$userId;
    }

    public static function email(): ?string
    {
        return self::$email;
    }

    public static function tenantId(): ?int
    {
        return self::$tenantId;
    }

    public static function reset(): void
    {
        self::$userId = null;
        self::$email = null;
        self::$tenantId = null;
    }
}
