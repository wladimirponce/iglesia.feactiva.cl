-- FeActiva Iglesia SaaS
-- Migration: 002b_add_tutor_to_parentesco
-- Scope: Add tutor value to crm_persona_familia.parentesco enum for development databases.

ALTER TABLE crm_persona_familia
    MODIFY parentesco ENUM(
        'jefe_hogar',
        'conyuge',
        'hijo',
        'hija',
        'padre',
        'madre',
        'tutor',
        'hermano',
        'hermana',
        'abuelo',
        'abuela',
        'otro'
    ) NOT NULL DEFAULT 'otro';
