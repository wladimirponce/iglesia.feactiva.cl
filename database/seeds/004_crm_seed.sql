-- FeActiva Iglesia SaaS
-- Seed: 004_crm_seed
-- Scope: Development CRM membership statuses for tenant_id = 1 only.
-- WARNING: Do not run in production.

INSERT INTO crm_estados_membresia
(tenant_id, code, nombre, descripcion, color, orden, es_activo)
VALUES
(1, 'visita', 'Visita', 'Persona que asistio o tuvo primer contacto.', '#94a3b8', 1, 1),
(1, 'nuevo_asistente', 'Nuevo asistente', 'Persona en proceso inicial de integracion.', '#38bdf8', 2, 1),
(1, 'miembro', 'Miembro', 'Persona reconocida como miembro de la iglesia.', '#22c55e', 3, 1),
(1, 'lider', 'Lider', 'Persona con responsabilidad de liderazgo.', '#a855f7', 4, 1),
(1, 'servidor', 'Servidor', 'Persona que participa activamente en algun servicio.', '#f59e0b', 5, 1),
(1, 'inactivo', 'Inactivo', 'Persona sin participacion reciente.', '#ef4444', 6, 1),
(1, 'trasladado', 'Trasladado', 'Persona trasladada a otra iglesia.', '#64748b', 7, 1),
(1, 'fallecido', 'Fallecido', 'Persona fallecida.', '#111827', 8, 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    color = VALUES(color),
    orden = VALUES(orden),
    es_activo = VALUES(es_activo);
