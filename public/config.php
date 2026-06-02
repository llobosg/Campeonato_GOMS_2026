
config_php = '''<?php
/**
 * config.php - Configuración para Railway + XAMPP Local
 * Ubicación: config/config.php
 */

if (PHP_SAPI !== 'cli' && !defined('APP_ENTRY_POINT')) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Helper robusto para variables de entorno
function env($key, $default = null) {
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $val === false ? $default : $val;
}

// Constantes de la aplicación
define('ADMIN_PASSWORD', 'psg2026');
define('APP_NAME', 'Campeonato GOMS 2026');
define('APP_VERSION', '1.0.0');
define('POINTS_WIN', 2);
define('POINTS_DRAW', 1);
define('POINTS_LOSS', 0);

$isProduction = false;
$host = null; 
$port = '3306'; 
$dbname = null; 
$username = null; 
$password = null;

// 🎯 PRIORIDAD ABSOLUTA: DATABASE_URL (formato estándar Railway)
$databaseUrl = env('DATABASE_URL');
if ($databaseUrl && stripos($databaseUrl, 'mysql://') === 0) {
    $parsed = parse_url($databaseUrl);
    
    $host = $parsed['host'] ?? null;
    $port = $parsed['port'] ?? '3306';
    $dbname = ltrim($parsed['path'] ?? '', '/');
    $username = $parsed['user'] ?? null;
    $password = $parsed['pass'] ?? null;
    
    if ($host && $dbname && $username !== null) {
        $isProduction = true;
        error_log("🚀 Railway: DATABASE_URL parseada -> $host:$port/$dbname");
    }
}

// Fallback: Configuración local XAMPP
if (!$isProduction) {
    $host = '127.0.0.1';
    $port = '3306';
    $dbname = 'campeonato_goms';
    $username = 'root';
    $password = '';
    error_log("💻 Local: $host:$port/$dbname");
}

//  CONEXIÓN CON REINTENTOS
$pdo = null;
$lastError = null;

for ($attempt = 1; $attempt <= 3; $attempt++) {
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone='+00:00'"
        ]);
        
        error_log("✅ PDO conectado (intento $attempt)");
        break;
        
    } catch (PDOException $e) {
        $lastError = $e->getMessage();
        error_log("❌ Intento $attempt: $lastError");
        
        if ($attempt < 3) {
            sleep(2);
            if ($attempt === 2 && stripos($host ?? '', 'proxy') !== false) {
                $host = 'mysql.railway.internal';
                $port = '3306';
                error_log("🔄 Reintentando con host interno: $host:$port");
            }
        }
    }
}

if (!$pdo) {
    error_log("FATAL: No se pudo conectar. Debug: host=$host port=$port db=$dbname user=$username");
    
    if ($isProduction) {
        http_response_code(500);
        exit("⚠️ Error de conexión a base de datos. Revise configuración de Railway.");
    }
    throw new PDOException($lastError ?? 'Unknown connection error');
}

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\\\';
    $base_dir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Include helpers
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/constants.php';
'''

print("✅ config/config.php generado")
print("="*70)
print(config_php)

 # Result execute error ```