<?php
/**
 * config.php - Configuración Central Campeonato GOMS 2026
 * Ubicación: RAÍZ del proyecto (/Applications/XAMPP/xamppfiles/htdocs/campeonato goms 2026/)
 * Compatible: Local XAMPP + Railway Production
 */

// Prevenir acceso directo desde web
if (PHP_SAPI !== 'cli' && !defined('APP_ENTRY_POINT')) {
    http_response_code(403);
    exit('Acceso denegado');
}

// ============================================
// HELPER: Obtener variables de entorno
// ============================================
function env($key, $default = null) {
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $val === false ? $default : $val;
}

// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================
$isProduction = false;
$host = null;
$port = '3306';
$dbname = null;
$username = null;
$password = null;

// 🎯 PRIORIDAD: DATABASE_URL (Railway standard)
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
        error_log("🚀 Railway: Conectando a $host:$port/$dbname");
    }
}

//  FALLBACK: Configuración local XAMPP
if (!$isProduction) {
    $host = '127.0.0.1';
    $port = '3306';
    $dbname = 'campeonato_goms';
    $username = 'root';
    $password = '';
    error_log(" Local XAMPP: $host:$port/$dbname");
}

// ============================================
// CONEXIÓN PDO CON REINTENTOS (Race condition Railway)
// ============================================
$pdo = null;
$lastError = null;
$maxAttempts = 3;

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone='+00:00'"
        ]);
        
        error_log("✅ PDO conectado exitosamente (intento $attempt/$maxAttempts)");
        break;
        
    } catch (PDOException $e) {
        $lastError = $e->getMessage();
        error_log(" Intento $attempt fallido: $lastError");
        
        if ($attempt < $maxAttempts) {
            sleep(2); // Esperar 2 segundos entre intentos
            
            // Segundo intento: probar host interno si es Railway proxy
            if ($attempt === 2 && stripos($host ?? '', 'proxy') !== false) {
                $host = 'mysql.railway.internal';
                $port = '3306';
                error_log(" Reintentando con host interno Railway: $host:$port");
            }
        }
    }
}

// ============================================
// VALIDACIÓN FINAL DE CONEXIÓN
// ============================================
if (!$pdo) {
    error_log(" FATAL: No se pudo conectar a BD después de $maxAttempts intentos");
    error_log("Debug: host=$host | port=$port | db=$dbname | user=$username");
    
    if ($isProduction) {
        http_response_code(500);
        exit("⚠️ Error de conexión a base de datos. Contacte al administrador.");
    }
    
    throw new PDOException($lastError ?? 'Unknown connection error');
}

// ============================================
// CONSTANTES GLOBALES
// ============================================
define('APP_NAME', 'Campeonato GOMS 2026');
define('APP_VERSION', '1.0.0');
define('APP_ENV', $isProduction ? 'production' : 'development');
define('BASE_URL', $isProduction 
    ? 'https://campeonato-goms-2026.up.railway.app' 
    : 'http://localhost/campeonato%20goms%202026/public');
define('UPLOADS_DIR', __DIR__ . '/public/uploads');
define('QR_DIR', UPLOADS_DIR . '/qrs');
define('LOGOS_DIR', UPLOADS_DIR . '/logos');

// ============================================
// CONFIGURACIÓN DE SEGURIDAD
// ============================================
define('ADMIN_PASSWORD', 'psg2026'); // Contraseña para ingresar resultados
define('SESSION_LIFETIME', 86400); // 24 horas sin timeout

// ============================================
// AUTOLOAD CLASES (PSR-4 simple)
// ============================================
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// ============================================
// INCLUIR HELPERS GLOBALES
// ============================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/BrevoMailer.php';
require_once __DIR__ . '/includes/QRGenerator.php';

// ============================================
// INICIAR SESIÓN SEGURA
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => SESSION_LIFETIME,
        'cookie_secure' => $isProduction,
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'sid_length' => 48,
        'sid_bits_per_character' => 6
    ]);
}

error_log("✅ config.php cargado correctamente | ENV: " . APP_ENV);