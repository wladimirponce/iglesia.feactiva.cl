<?php

declare(strict_types=1);

return [
    [
        'name' => 'IDENTIDAD / SALUDO',
        'message_text' => 'hola',
        'expected' => 'success=true, found=true, response_text no vacio',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'USUARIO NO REGISTRADO',
        'message_text' => 'hola',
        'phone_env' => 'TEST_UNKNOWN_PHONE',
        'skip_if_missing_phone' => true,
        'expected' => 'success=true, found=false, respuesta de usuario no encontrado',
        'assertions' => ['http_200', 'success_true', 'found_false', 'response_text_not_empty'],
    ],
    [
        'name' => 'CRM - Buscar persona Juan',
        'message_text' => 'buscar persona juan',
        'expected' => 'ejecuta crm_search_person o indica que no encontro',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'CRM - Crear persona Pedro Prueba',
        'message_text' => 'crea una persona Pedro Prueba, telefono +56911112222, email pedro.prueba@test.cl',
        'expected' => 'ejecuta crm_create_person, crea persona o conflicto controlado',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'FAMILIA - Crear familia Prueba QA',
        'message_text' => 'crea familia Prueba QA',
        'expected' => 'ejecuta crm_create_family o pide datos faltantes controladamente',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'FAMILIA - Agregar Pedro a familia',
        'message_text' => 'agrega Pedro Prueba a familia Prueba QA como hijo',
        'expected' => 'ejecuta crm_assign_person_to_family o pide aclaracion si hay ambiguedad',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'FINANZAS - Registrar ofrenda',
        'message_text' => 'registra ofrenda de 5000 en caja principal',
        'expected' => 'ejecuta finanzas_create_income, response_text indica ingreso registrado',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'FINANZAS - Registrar diezmo',
        'message_text' => 'registra diezmo de 10000 en caja principal',
        'expected' => 'ejecuta finanzas_create_income, response_text indica ingreso registrado',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'FINANZAS - Registrar egreso',
        'message_text' => 'registra egreso de 3000 por materiales en caja principal',
        'expected' => 'ejecuta finanzas_create_expense o pide categoria si falta',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'FINANZAS - Saldo hoy',
        'message_text' => 'cuanto dinero hay al dia de hoy',
        'expected' => 'ejecuta finanzas_get_balance_by_date o resumen financiero',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'CONTABILIDAD - Balance abril',
        'message_text' => 'quiero ver el balance contable de abril',
        'expected' => 'ejecuta contabilidad_get_balance o responde falta rango/fecha controlado',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'DISCIPULADO - Asignar ruta fundamentos',
        'message_text' => 'asigna Juan a discipulado fundamentos',
        'expected' => 'ejecuta discipulado_assign_route o pide aclaracion',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'PASTORAL - Crear solicitud oracion',
        'message_text' => 'crea solicitud de oracion para persona 1: su madre esta enferma',
        'expected' => 'ejecuta pastoral_create_prayer_request',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'AGENDA - Recordar llamada',
        'message_text' => 'recuerdame llamar a Juan manana a las 10',
        'expected' => 'ejecuta agenda_create_item, crea agenda_item tipo call',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'AGENDA - Ver agenda manana',
        'message_text' => 'que tengo agendado para manana',
        'expected' => 'ejecuta agenda_get_day_schedule',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'AGENDA - WhatsApp programado',
        'message_text' => 'envia whatsapp al 56958359091 manana a las 9 diciendo recuerda traer documentos',
        'expected' => 'crea agenda_item whatsapp_send y agenda_notification scheduled, no envia todavia',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'AGENDA - Completar llamada',
        'message_text' => 'marca como completada la llamada con Juan',
        'expected' => 'ejecuta agenda_complete_item o pide aclaracion si hay varias coincidencias',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'DATOS FALTANTES - Ofrenda sin datos',
        'message_text' => 'registra ofrenda',
        'expected' => 'NO ejecuta tool, pide monto y cuenta',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'DATOS FALTANTES - Llamar sin fecha',
        'message_text' => 'recuerdame llamar a Juan',
        'expected' => 'NO ejecuta si falta fecha/hora, pide fecha/hora',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'NO SOPORTADO - Nave espacial',
        'message_text' => 'quiero que construyas una nave espacial',
        'expected' => 'no ejecuta tool, responde con sugerencias validas',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_text_not_empty'],
    ],
    [
        'name' => 'SEGURIDAD - Sin integration key',
        'message_text' => 'hola',
        'auth' => 'none',
        'expected' => 'HTTP 401 JSON INTEGRATION_UNAUTHORIZED',
        'assertions' => ['http_401', 'success_false', 'integration_unauthorized'],
    ],
    [
        'name' => 'SEGURIDAD - Key invalida',
        'message_text' => 'hola',
        'auth' => 'invalid',
        'expected' => 'HTTP 401 JSON INTEGRATION_UNAUTHORIZED',
        'assertions' => ['http_401', 'success_false', 'integration_unauthorized'],
    ],
    [
        'name' => 'AUDIO STUB - Audio entrante',
        'message_text' => '',
        'payload' => [
            'message_type' => 'audio',
            'media_url' => 'https://example.com/audio-test.ogg',
        ],
        'expected' => 'response_mode=audio si el stub esta activo, no rompe flujo',
        'assertions' => ['http_200', 'success_true', 'found_true', 'response_mode_audio'],
    ],
];
