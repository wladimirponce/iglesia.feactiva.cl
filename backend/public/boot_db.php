<?php
/**
 * Bootstrapper de Base de Datos - FeActiva Iglesia SaaS
 * Este script automatiza la creación de tablas e inserción de semillas en cPanel.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// 1. Cargar configuración desde .env manual (ya que no tenemos autoloader complejo aún)
$envFile = __DIR__ . '/../../.env';
if (!file_exists($envFile)) {
    die("Error: No se encontró el archivo .env en la raíz del proyecto.");
}

$env = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

$db_host = $env['DB_HOST'] ?? 'localhost';
$db_name = $env['DB_NAME'] ?? '';
$db_user = $env['DB_USER'] ?? '';
$db_pass = $env['DB_PASS'] ?? '';

echo "<h2>🚀 Iniciando Booteo de Base de Datos FeActiva</h2>";
echo "<p>Conectando a: <b>$db_name</b> en <b>$db_host</b>...</p>";

try {
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Crear la base de datos si no existe (opcional en cPanel ya suele estar creada)
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `$db_name`;");

    echo "<p style='color:green;'>✅ Conexión exitosa.</p>";

    // 2. Procesar Migraciones
    $migrationsDir = __DIR__ . '/../../database/migrations/';
    $migrationFiles = glob($migrationsDir . '*.sql');
    sort($migrationFiles);

    echo "<h3>🛠️ Ejecutando Migraciones...</h3>";
    foreach ($migrationFiles as $file) {
        $name = basename($file);
        echo "Procesando: $name... ";
        $sql = file_get_contents($file);
        
        // Ejecutar el SQL (usando exec para múltiples sentencias)
        $pdo->exec($sql);
        echo "<b style='color:blue;'>Hecho.</b><br>";
    }

    // 3. Procesar Semillas (Seeds)
    $seedsDir = __DIR__ . '/../../database/seeds/';
    $seedFiles = glob($seedsDir . '*.sql');
    sort($seedFiles);

    echo "<h3>🌱 Insertando Semillas (Seeds)...</h3>";
    foreach ($seedFiles as $file) {
        $name = basename($file);
        echo "Procesando: $name... ";
        $sql = file_get_contents($file);
        
        $pdo->exec($sql);
        echo "<b style='color:blue;'>Hecho.</b><br>";
    }

    echo "<h2>🎉 ¡Todo listo! El sistema FeActiva SaaS ya tiene su base de datos configurada.</h2>";
    echo "<p><b>RECOMENDACIÓN:</b> Por seguridad, elimina este archivo (<code>backend/public/boot_db.php</code>) de tu servidor ahora mismo.</p>";
    echo "<a href='/'>Ir al Inicio</a>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ ERROR: " . $e->getMessage() . "</p>";
}
