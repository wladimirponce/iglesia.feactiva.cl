<?php

declare(strict_types=1);

class AutoMigrator
{
    public static function checkAndRun(): void
    {
        try {
            $pdo = Database::connection();

            // Bootstrap the migration tracker on first use.
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS schema_migrations (
                    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL,
                    run_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_schema_migrations_migration (migration)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $ran = $pdo->query("SELECT migration FROM schema_migrations")
                       ->fetchAll(PDO::FETCH_COLUMN);
            $ranSet = array_flip((array) $ran);

            $baseDir       = dirname(__DIR__, 2);
            $migrationsDir = $baseDir . '/database/migrations/';
            $seedsDir      = $baseDir . '/database/seeds/';

            $isFreshInstall = empty($ran);

            $files = glob($migrationsDir . '*.sql');
            if (is_array($files)) {
                sort($files);
                foreach ($files as $file) {
                    $name = basename($file);
                    if (isset($ranSet[$name])) {
                        continue;
                    }
                    $sql = file_get_contents($file);
                    if ($sql === false || trim($sql) === '') {
                        continue;
                    }
                    $pdo->exec($sql);
                    $stmt = $pdo->prepare(
                        "INSERT IGNORE INTO schema_migrations (migration) VALUES (?)"
                    );
                    $stmt->execute([$name]);
                }
            }

            // Seeds only on a completely fresh install (no prior migrations).
            if ($isFreshInstall) {
                $files = glob($seedsDir . '*.sql');
                if (is_array($files)) {
                    sort($files);
                    foreach ($files as $file) {
                        $sql = file_get_contents($file);
                        if ($sql !== false && trim($sql) !== '') {
                            $pdo->exec($sql);
                        }
                    }
                }
            }

        } catch (Exception $e) {
            error_log('AutoMigrator Error: ' . $e->getMessage());
        }
    }
}
