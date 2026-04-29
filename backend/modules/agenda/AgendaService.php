<?php

declare(strict_types=1);

final class AgendaService
{
    public function __construct(
        private readonly AgendaRepository $repository,
        private readonly AgendaAuditLogger $auditLogger
    ) {
    }

    public function list(int $tenantId, array $filters = []): array
    {
        return $this->repository->list($tenantId, $filters);
    }

    public function show(int $tenantId, int $id): array
    {
        $item = $this->repository->find($tenantId, $id);
        if ($item === null) {
            throw new RuntimeException('AGENDA_ITEM_NOT_FOUND');
        }
        return $item;
    }

    public function create(int $tenantId, int $userId, array $input): int
    {
        $this->assertReferences($tenantId, $input);
        $id = $this->repository->create($tenantId, $userId, $input);
        $item = $this->show($tenantId, $id);
        $this->auditLogger->log($tenantId, $userId, $id, 'agenda.item.created', [], $input + ['id' => $id]);

        if (($item['tipo'] ?? '') === 'meeting') {
            $this->syncMeetingToCalendar($tenantId, $userId, $item);
        }

        return $id;
    }

    public function update(int $tenantId, int $userId, int $id, array $input): void
    {
        $old = $this->show($tenantId, $id);
        $this->assertReferences($tenantId, $input);
        $this->repository->update($tenantId, $id, $input);
        $this->auditLogger->log($tenantId, $userId, $id, 'agenda.item.updated', $old, $input);
    }

    public function complete(int $tenantId, int $userId, int $id): void
    {
        $old = $this->show($tenantId, $id);
        $this->repository->setStatus($tenantId, $id, 'completed');
        $this->auditLogger->log($tenantId, $userId, $id, 'agenda.item.completed', $old, ['estado' => 'completed']);
    }

    public function cancel(int $tenantId, int $userId, int $id): void
    {
        $old = $this->show($tenantId, $id);
        $this->repository->setStatus($tenantId, $id, 'cancelled');
        if (class_exists('GoogleCalendarService')) {
            $calendar = new GoogleCalendarService(new GoogleCalendarRepository(), new GoogleCalendarProviderStub(), $this->auditLogger);
            $calendar->cancelEventFromAgendaItem($tenantId, $userId, $id);
        }
        $this->auditLogger->log($tenantId, $userId, $id, 'agenda.item.cancelled', $old, ['estado' => 'cancelled']);
    }

    public function createNotification(int $tenantId, int $userId, int $agendaItemId, array $input): int
    {
        $agendaItem = $this->show($tenantId, $agendaItemId);
        $recipientType = (string) ($input['recipient_type'] ?? '');
        if ($recipientType === 'phone' && !$this->validE164((string) ($input['recipient_phone'] ?? ''))) {
            throw new RuntimeException('AGENDA_INVALID_E164_PHONE');
        }
        if ($recipientType === 'email' && !filter_var((string) ($input['recipient_email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('AGENDA_INVALID_EMAIL');
        }
        $id = $this->repository->createNotification($tenantId, $agendaItemId, $input);
        $channel = (string) ($input['channel'] ?? 'whatsapp');
        $this->auditLogger->log($tenantId, $userId, $agendaItemId, 'agenda.notification.scheduled', [], $input + ['id' => $id]);
        if ($channel === 'whatsapp') {
            $this->auditLogger->log($tenantId, $userId, $agendaItemId, 'notification.whatsapp.scheduled', [], $input + ['id' => $id]);
        }
        if ($channel === 'email') {
            $this->auditLogger->log($tenantId, $userId, $agendaItemId, 'notification.email.scheduled', [], $input + ['id' => $id]);
        }

        $this->sendDueConfirmedNotification($tenantId, $userId, $agendaItem, $id, $input);
        return $id;
    }

    private function assertReferences(int $tenantId, array $input): void
    {
        if (isset($input['persona_id']) && (int) $input['persona_id'] > 0 && !$this->repository->personaExists($tenantId, (int) $input['persona_id'])) {
            throw new RuntimeException('AGENDA_PERSONA_NOT_FOUND');
        }
        if (isset($input['familia_id']) && (int) $input['familia_id'] > 0 && !$this->repository->familiaExists($tenantId, (int) $input['familia_id'])) {
            throw new RuntimeException('AGENDA_FAMILIA_NOT_FOUND');
        }
    }

    private function validE164(string $phone): bool
    {
        return preg_match('/^\+[1-9][0-9]{7,14}$/', trim($phone)) === 1;
    }

    private function syncMeetingToCalendar(int $tenantId, int $userId, array $item): void
    {
        if (!class_exists('GoogleCalendarService') || !class_exists('GoogleCalendarProviderStub')) {
            return;
        }

        $calendar = new GoogleCalendarService(new GoogleCalendarRepository(), new GoogleCalendarProviderStub(), $this->auditLogger);
        $calendar->createEventFromAgendaItem($tenantId, $userId, $item);
    }

    private function sendDueConfirmedNotification(int $tenantId, int $userId, array $agendaItem, int $notificationId, array $input): void
    {
        if (empty($agendaItem['confirmed_at'])) {
            return;
        }

        $scheduledAt = strtotime((string) ($input['scheduled_at'] ?? ''));
        if ($scheduledAt === false || $scheduledAt > time()) {
            return;
        }

        $channel = (string) ($input['channel'] ?? 'whatsapp');
        try {
            if ($channel === 'whatsapp' && class_exists('WhatsAppSenderStub')) {
                $phone = (string) ($input['recipient_phone'] ?? '');
                if ($phone === '') {
                    return;
                }
                $response = (new WhatsAppSenderStub())->sendText($phone, (string) $input['message_text'], [
                    'agenda_item_id' => (int) $agendaItem['id'],
                    'notification_id' => $notificationId,
                ]);
                $this->repository->markNotificationDelivery(
                    $tenantId,
                    $notificationId,
                    'sent',
                    (string) ($response['external_provider'] ?? 'whatsapp_stub'),
                    (string) ($response['external_message_id'] ?? ''),
                    $response
                );
                $this->auditLogger->log($tenantId, $userId, (int) $agendaItem['id'], 'notification.sent', [], [
                    'notification_id' => $notificationId,
                    'channel' => 'whatsapp',
                    'simulated' => true,
                ]);
                return;
            }

            if ($channel === 'email' && class_exists('EmailSenderStub')) {
                $email = (string) ($input['recipient_email'] ?? '');
                if ($email === '') {
                    return;
                }
                $response = (new EmailSenderStub())->send($email, (string) ($agendaItem['titulo'] ?? 'Agenda FeActiva'), (string) $input['message_text'], [
                    'agenda_item_id' => (int) $agendaItem['id'],
                    'notification_id' => $notificationId,
                ]);
                $this->repository->markNotificationDelivery(
                    $tenantId,
                    $notificationId,
                    'sent',
                    (string) ($response['external_provider'] ?? 'email_stub'),
                    (string) ($response['external_message_id'] ?? ''),
                    $response
                );
                $this->auditLogger->log($tenantId, $userId, (int) $agendaItem['id'], 'notification.sent', [], [
                    'notification_id' => $notificationId,
                    'channel' => 'email',
                    'simulated' => true,
                ]);
            }
        } catch (Throwable $throwable) {
            $this->repository->markNotificationDelivery($tenantId, $notificationId, 'failed', null, null, [
                'error' => $throwable->getMessage(),
            ]);
            $this->auditLogger->log($tenantId, $userId, (int) $agendaItem['id'], 'notification.failed', [], [
                'notification_id' => $notificationId,
                'channel' => $channel,
                'error' => $throwable->getMessage(),
            ]);
        }
    }
}
