<?php

declare(strict_types=1);

final class AgendaCreateWhatsappNotificationTool implements AgentToolInterface
{
    public function name(): string { return 'agenda_create_whatsapp_notification'; }
    public function moduleCode(): string { return 'agenda'; }
    public function requiredPermission(): string { return 'agenda.notifications.crear'; }

    public function execute(int $tenantId, int $userId, array $input): array
    {
        foreach (['fecha_inicio', 'message_text'] as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                throw new RuntimeException('AGENT_TOOL_MISSING_NOTIFICATION_DATA');
            }
        }
        $service = new AgendaService(new AgendaRepository(), new AgendaAuditLogger());
        $agendaId = $service->create($tenantId, $userId, [
            'tipo' => 'whatsapp_send',
            'titulo' => $input['titulo'] ?? 'WhatsApp programado',
            'descripcion' => $input['message_text'],
            'fecha_inicio' => $input['fecha_inicio'],
            'persona_id' => $input['persona_id'] ?? null,
            'familia_id' => $input['familia_id'] ?? null,
            'modulo_origen' => 'agent',
        ]);
        $notificationId = $service->createNotification($tenantId, $userId, $agendaId, [
            'recipient_type' => isset($input['persona_id']) ? 'persona' : 'phone',
            'recipient_persona_id' => $input['persona_id'] ?? null,
            'recipient_phone' => $input['phone'] ?? null,
            'message_text' => $input['message_text'],
            'scheduled_at' => $input['fecha_inicio'],
        ]);
        return ['id' => $notificationId, 'agenda_item_id' => $agendaId, 'scheduled_at' => $input['fecha_inicio'], 'message_text' => $input['message_text']];
    }
}
