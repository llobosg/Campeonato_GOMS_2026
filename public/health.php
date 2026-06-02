<?php
/**
 * health.php - Healthcheck Endpoint para Railway
 * Ubicación: public/health.php
 * Verifica: Conexión DB, PHP versión, estado general del sistema
 */

define('APP_ENTRY_POINT', true);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$health_status = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get(),
    'checks' => []
];

// ============================================
// CHECK 1: Conexión a Base de Datos
// ============================================
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        // Ejecutar query simple para verificar conexión
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        if ($result && $result['test'] == 1) {
            $health_status['checks']['database'] = [
                'status' => 'pass',
                'message' => 'Conexión a MySQL exitosa',
                'host' => $isProduction ? 'Railway MySQL' : 'Local XAMPP',
                'database' => $dbname ?? 'unknown'
            ];
        } else {
            throw new Exception('Query de prueba falló');
        }
    } else {
        throw new Exception('Objeto PDO no disponible');
    }
} catch (\Exception $e) {
    $health_status['status'] = 'degraded';
    $health_status['checks']['database'] = [
        'status' => 'fail',
        'message' => 'Error de conexión: ' . $e->getMessage()
    ];
}

// ============================================
// CHECK 2: Versión de PHP
// ============================================
$php_version = phpversion();
$php_min_version = '8.2.0';

if (version_compare($php_version, $php_min_version, '>=')) {
    $health_status['checks']['php_version'] = [
        'status' => 'pass',
        'message' => "PHP $php_version (mínimo requerido: $php_min_version)",
        'version' => $php_version
    ];
} else {
    $health_status['status'] = 'degraded';
    $health_status['checks']['php_version'] = [
        'status' => 'fail',
        'message' => "PHP $php_version es inferior al mínimo requerido ($php_min_version)",
        'version' => $php_version
    ];
}

// ============================================
// CHECK 3: Extensiones PHP requeridas
// ============================================
$required_extensions = ['pdo', 'pdo_mysql', 'curl', 'json'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (empty($missing_extensions)) {
    $health_status['checks']['extensions'] = [
        'status' => 'pass',
        'message' => 'Todas las extensiones requeridas están cargadas',
        'loaded' => $required_extensions
    ];
} else {
    $health_status['status'] = 'degraded';
    $health_status['checks']['extensions'] = [
        'status' => 'fail',
        'message' => 'Extensiones faltantes: ' . implode(', ', $missing_extensions),
        'missing' => $missing_extensions
    ];
}

// ============================================
// CHECK 4: Permisos de escritura en uploads
// ============================================
$uploads_writable = is_writable(UPLOADS_DIR);
$qrs_writable = is_writable(QR_DIR);
$logos_writable = is_writable(LOGOS_DIR);

if ($uploads_writable && $qrs_writable && $logos_writable) {
    $health_status['checks']['file_permissions'] = [
        'status' => 'pass',
        'message' => 'Directorios de uploads tienen permisos de escritura',
        'uploads_dir' => UPLOADS_DIR,
        'qrs_dir' => QR_DIR,
        'logos_dir' => LOGOS_DIR
    ];
} else {
    $health_status['status'] = 'degraded';
    $issues = [];
    if (!$uploads_writable) $issues[] = 'uploads/';
    if (!$qrs_writable) $issues[] = 'uploads/qrs/';
    if (!$logos_writable) $issues[] = 'uploads/logos/';
    
    $health_status['checks']['file_permissions'] = [
        'status' => 'fail',
        'message' => 'Permisos insuficientes en: ' . implode(', ', $issues),
        'issues' => $issues
    ];
}

// ============================================
// CHECK 5: Variables de entorno críticas
// ============================================
$env_checks = [];

if ($isProduction) {
    // En producción, verificar DATABASE_URL
    $database_url = env('DATABASE_URL');
    if ($database_url && stripos($database_url, 'mysql://') === 0) {
        $env_checks['DATABASE_URL'] = 'present';
    } else {
        $env_checks['DATABASE_URL'] = 'missing_or_invalid';
        $health_status['status'] = 'degraded';
    }
    
    // Verificar BREVO_API_KEY (opcional, puede estar vacío en dev)
    $brevo_key = env('BREVO_API_KEY');
    if ($brevo_key && strlen($brevo_key) > 10) {
        $env_checks['BREVO_API_KEY'] = 'present';
    } else {
        $env_checks['BREVO_API_KEY'] = 'missing_or_short';
        // No marcar como degraded si es solo email
    }
} else {
    $env_checks['ENVIRONMENT'] = 'development';
}

$health_status['checks']['environment'] = [
    'status' => 'pass',
    'message' => 'Variables de entorno verificadas',
    'details' => $env_checks,
    'app_env' => APP_ENV
];

// ============================================
// CHECK 6: Estado de tablas críticas
// ============================================
try {
    $tables_check = [];
    
    // Verificar tabla equipos
    $count_equipos = db_fetch_one($pdo, "SELECT COUNT(*) as total FROM equipos")['total'];
    $tables_check['equipos'] = [
        'exists' => true,
        'count' => (int)$count_equipos,
        'status' => $count_equipos > 0 ? 'populated' : 'empty'
    ];
    
    // Verificar tabla fixture
    $count_fixture = db_fetch_one($pdo, "SELECT COUNT(*) as total FROM fixture")['total'];
    $tables_check['fixture'] = [
        'exists' => true,
        'count' => (int)$count_fixture,
        'status' => $count_fixture > 0 ? 'populated' : 'empty'
    ];
    
    // Verificar vista v_posiciones
    try {
        $count_posiciones = db_fetch_one($pdo, "SELECT COUNT(*) as total FROM v_posiciones")['total'];
        $tables_check['v_posiciones'] = [
            'exists' => true,
            'count' => (int)$count_posiciones
        ];
    } catch (\Exception $e) {
        $tables_check['v_posiciones'] = [
            'exists' => false,
            'error' => $e->getMessage()
        ];
        $health_status['status'] = 'degraded';
    }
    
    $health_status['checks']['database_tables'] = [
        'status' => 'pass',
        'message' => 'Tablas críticas verificadas',
        'tables' => $tables_check
    ];
    
} catch (\Exception $e) {
    $health_status['status'] = 'degraded';
    $health_status['checks']['database_tables'] = [
        'status' => 'fail',
        'message' => 'Error verificando tablas: ' . $e->getMessage()
    ];
}

// ============================================
// RESPONDER CON ESTADO FINAL
// ============================================

// Determinar código HTTP según estado
if ($health_status['status'] === 'healthy') {
    http_response_code(200);
} elseif ($health_status['status'] === 'degraded') {
    http_response_code(200); // Railway acepta 200 incluso si hay warnings
} else {
    http_response_code(503);
}

// Agregar metadatos finales
$health_status['application'] = [
    'name' => APP_NAME,
    'version' => APP_VERSION,
    'environment' => APP_ENV,
    'base_url' => BASE_URL
];

// Output JSON
echo json_encode($health_status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Log para debugging
if ($health_status['status'] !== 'healthy') {
    error_log("️ Healthcheck degradado: " . json_encode($health_status));
}