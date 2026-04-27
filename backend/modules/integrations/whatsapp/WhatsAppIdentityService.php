<?php

declare(strict_types=1);

final class WhatsAppIdentityService
{
    public function __construct(
        private readonly WhatsAppIdentityRepository $repository
    ) {
    }

    public function identify(string $phone, string $countryCode, ?string $ipAddress, ?string $userAgent): array
    {
        $normalizedPhone = PhoneNormalizer::toE164($phone, $countryCode);

        if ($normalizedPhone === null) {
            $this->repository->auditIdentifyAttempt(null, null, 'invalid_phone', [
                'phone' => $this->maskedPhone($phone),
                'country_code' => $countryCode,
            ], $ipAddress, $userAgent);

            throw new RuntimeException('WHATSAPP_INVALID_PHONE');
        }

        $user = $this->repository->findActiveUserByPhone($normalizedPhone);

        if ($user === null) {
            $this->repository->auditIdentifyAttempt(null, null, 'not_found', [
                'phone' => $this->maskedPhone($normalizedPhone),
            ], $ipAddress, $userAgent);

            return [
                'found' => false,
                'identified' => false,
                'phone' => $normalizedPhone,
                'user' => null,
                'tenant_id' => null,
                'tenants' => [],
            ];
        }

        $userId = (int) $user['id'];
        $tenants = $this->repository->activeTenantsForUser($userId);

        if (count($tenants) === 1) {
            $tenant = $tenants[0];
            $tenantId = (int) $tenant['id'];

            $this->repository->auditIdentifyAttempt($tenantId, $userId, 'found', [
                'phone' => $this->maskedPhone($normalizedPhone),
                'tenant_count' => 1,
            ], $ipAddress, $userAgent);

            return [
                'found' => true,
                'identified' => true,
                'phone' => $normalizedPhone,
                'user' => $this->publicUser($user),
                'tenant_id' => $tenantId,
                'tenants' => [
                    $this->publicTenant($tenant),
                ],
            ];
        }

        $eventDescription = count($tenants) > 1 ? 'multiple_tenants' : 'not_found';

        $this->repository->auditIdentifyAttempt(null, $userId, $eventDescription, [
            'phone' => $this->maskedPhone($normalizedPhone),
            'tenant_count' => count($tenants),
            'tenant_ids' => array_map(static fn (array $tenant): int => (int) $tenant['id'], $tenants),
        ], $ipAddress, $userAgent);

        return [
            'found' => true,
            'identified' => true,
            'phone' => $normalizedPhone,
            'user' => $this->publicUser($user),
            'tenant_id' => null,
            'tenants' => array_map(fn (array $tenant): array => $this->publicTenant($tenant), $tenants),
        ];
    }

    /** @param array{id: int|string, name: string, email: string, phone: string} $user */
    private function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
        ];
    }

    /** @param array{id: int|string, name: string, status: string} $tenant */
    private function publicTenant(array $tenant): array
    {
        return [
            'id' => (int) $tenant['id'],
            'name' => (string) $tenant['name'],
            'status' => (string) $tenant['status'],
        ];
    }

    private function maskedPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (!is_string($digits) || strlen($digits) < 4) {
            return '****';
        }

        return str_repeat('*', max(4, strlen($digits) - 4)) . substr($digits, -4);
    }
}
