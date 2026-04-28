<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../core/Database.php';

$statement = Database::connection()->prepare("
    SELECT
        id,
        tenant_id,
        agenda_item_id,
        channel,
        recipient_type,
        scheduled_at,
        attempts
    FROM agenda_notifications
    WHERE status = 'scheduled'
      AND scheduled_at <= UTC_TIMESTAMP()
      AND deleted_at IS NULL
    ORDER BY scheduled_at ASC, id ASC
    LIMIT 50
");
$statement->execute();
$notifications = $statement->fetchAll();

foreach ($notifications as $notification) {
    error_log(sprintf(
        '[agenda_notifications] ready id=%d tenant=%d item=%d channel=%s scheduled_at=%s',
        (int) $notification['id'],
        (int) $notification['tenant_id'],
        (int) $notification['agenda_item_id'],
        (string) $notification['channel'],
        (string) $notification['scheduled_at']
    ));

    // TODO: Fase envio real.
    // No se envia WhatsApp todavia. Este cron solo detecta notificaciones listas
    // para que la fase siguiente conecte el proveedor y actualice status/sent_at.
}

echo json_encode([
    'success' => true,
    'ready_count' => count($notifications),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
