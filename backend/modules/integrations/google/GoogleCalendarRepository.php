<?php

declare(strict_types=1);

final class GoogleCalendarRepository
{
    public function findActiveAccountForUser(int $tenantId, int $userId): ?array
    {
        $sql = "
            SELECT
                id,
                tenant_id,
                user_id,
                provider,
                calendar_id,
                email,
                status
            FROM calendar_accounts
            WHERE tenant_id = :tenant_id
              AND user_id = :user_id
              AND provider = 'google'
              AND status = 'active'
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT 1
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'user_id' => $userId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function createAccountPlaceholder(int $tenantId, int $userId, string $email): int
    {
        $sql = "
            INSERT INTO calendar_accounts (
                tenant_id,
                user_id,
                provider,
                calendar_id,
                email,
                status
            ) VALUES (
                :tenant_id,
                :user_id,
                'google',
                'primary',
                :email,
                'active'
            )
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'user_id' => $userId, 'email' => $email]);
        return (int) Database::connection()->lastInsertId();
    }

    public function createEvent(int $tenantId, int $agendaItemId, int $calendarAccountId): int
    {
        $sql = "
            INSERT INTO calendar_events (
                tenant_id,
                agenda_item_id,
                calendar_account_id,
                provider,
                sync_status
            ) VALUES (
                :tenant_id,
                :agenda_item_id,
                :calendar_account_id,
                'google',
                'pending'
            )
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'agenda_item_id' => $agendaItemId,
            'calendar_account_id' => $calendarAccountId,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function markEventSynced(int $tenantId, int $eventId, string $externalEventId): void
    {
        $sql = "
            UPDATE calendar_events
            SET sync_status = 'synced',
                external_event_id = :external_event_id,
                updated_at = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id
              AND id = :id
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'tenant_id' => $tenantId,
            'id' => $eventId,
            'external_event_id' => $externalEventId,
        ]);
    }

    public function markEventFailed(int $tenantId, int $eventId, string $lastError): void
    {
        $sql = "
            UPDATE calendar_events
            SET sync_status = 'failed',
                last_error = :last_error,
                updated_at = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id
              AND id = :id
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'id' => $eventId, 'last_error' => $lastError]);
    }

    public function markEventCancelled(int $tenantId, int $agendaItemId): void
    {
        $sql = "
            UPDATE calendar_events
            SET sync_status = 'cancelled',
                updated_at = UTC_TIMESTAMP()
            WHERE tenant_id = :tenant_id
              AND agenda_item_id = :agenda_item_id
              AND sync_status <> 'cancelled'
        ";
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['tenant_id' => $tenantId, 'agenda_item_id' => $agendaItemId]);
    }
}
