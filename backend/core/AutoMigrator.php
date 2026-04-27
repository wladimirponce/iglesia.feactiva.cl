<?php

declare(strict_types=1);

class AutoMigrator
{
    public static function checkAndRun(): void
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // 1. Verificar si existe una tabla core (saas_tenants)
            $stmt = $pdo->query("SHOW TABLES LIKE 'saas_tenants'");
            if ($stmt->fetch() !== false) {
                // Las tablas ya existen, no hacer nada.
                return;
            }

            // 2. Si no existen, ejecutar migraciones en orden
            $baseDir = dirname(__DIR__, 2);
            $migrationsDir = $baseDir . '/database/migrations/';
            $seedsDir = $baseDir . '/database/seeds/';

            // Procesar Migraciones
            $files = glob($migrationsDir . '*.sql');
            sort($files);
            foreach ($files as $file) {
                $sql = file_get_contents($file);
                $pdo->exec($sql);
            }

            // Procesar Semillas (opcional pero recomendado para el primer despliegue)
            $files = glob($seedsDir . '*.sql');
            sort($files);
            foreach ($files as $file) {
                $sql = file_get_contents($file);
                $pdo->exec($sql);
            }

        } catch (Exception $e) {
            // En producción, podrías loguear esto en lugar de mostrarlo
            error_log("AutoMigrator Error: " . $e->getMessage());
        }
    }
}
