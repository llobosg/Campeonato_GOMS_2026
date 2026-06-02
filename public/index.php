<?php
/**
 * index.php - Router Principal Campeonato GOMS 2026
 * Ubicación: public/index.php
 * Punto de entrada único para Railway y XAMPP
 */

define('APP_ENTRY_POINT', true);
require_once __DIR__ . '/../config.php';

// ============================================
// ROUTER SIMPLE (Sin frameworks pesados)
// ============================================
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = $isProduction ? '' : '/campeonato%20goms%202026/public';
$request_uri = str_replace($base_path, '', $request_uri);
$request_uri = trim($request_uri, '/');

// Dividir ruta en segmentos
$route_segments = explode('/', $request_uri);
$action = $route_segments[0] ?? 'home';
$sub_action = $route_segments[1] ?? null;
$id = $route_segments[2] ?? null;

// ============================================
// MAPA DE RUTAS
// ============================================
$routes = [
    // Páginas principales
    'home' => ['controller' => 'HomeController', 'method' => 'index'],
    'fixture' => ['controller' => 'FixtureController', 'method' => 'index'],
    'posiciones' => ['controller' => 'EstadisticasController', 'method' => 'posiciones'],
    'goleadores' => ['controller' => 'EstadisticasController', 'method' => 'goleadores'],
    
    // Equipos
    'equipos' => [
        'listar' => ['controller' => 'EquipoController', 'method' => 'index'],
        'crear' => ['controller' => 'EquipoController', 'method' => 'create'],
        'editar' => ['controller' => 'EquipoController', 'method' => 'edit'],
        'eliminar' => ['controller' => 'EquipoController', 'method' => 'delete'],
        'ver' => ['controller' => 'EquipoController', 'method' => 'show'],
    ],
    
    // Jugadores
    'jugadores' => [
        'registrar' => ['controller' => 'JugadorController', 'method' => 'register'],
        'listar' => ['controller' => 'JugadorController', 'method' => 'index'],
        'eliminar' => ['controller' => 'JugadorController', 'method' => 'delete'],
    ],
    
    // Resultados y Fixture
    'resultado' => [
        'ingresar' => ['controller' => 'ResultadoController', 'method' => 'store'],
        'verificar' => ['controller' => 'ResultadoController', 'method' => 'verify'],
        'editar' => ['controller' => 'ResultadoController', 'method' => 'update'],
    ],
    
    // Marcador en vivo
    'vivo' => ['controller' => 'MarcadorController', 'method' => 'show'],
    
    // API endpoints (JSON responses)
    'api' => [
        'equipos' => ['controller' => 'ApiController', 'method' => 'getEquipos'],
        'jugadores' => ['controller' => 'ApiController', 'method' => 'getJugadores'],
        'fixture' => ['controller' => 'ApiController', 'method' => 'getFixture'],
        'posiciones' => ['controller' => 'ApiController', 'method' => 'getPosiciones'],
        'goleadores' => ['controller' => 'ApiController', 'method' => 'getGoleadores'],
        'marcador' => ['controller' => 'ApiController', 'method' => 'getMarcadorVivo'],
    ],
];

// ============================================
// RESOLVER RUTA Y EJECUTAR CONTROLADOR
// ============================================
try {
    // Hacer $pdo global para que las vistas puedan usarlo si es necesario (solución rápida)
    global $pdo; 
    $controller_class = null;
    $method = null;
    $params = [];
    
    // Determinar controlador y método según ruta
    if ($action === 'api') {
        // API routes: /api/endpoint
        $endpoint = $sub_action ?? 'error';
        if (isset($routes['api'][$endpoint])) {
            $controller_class = 'App\\Controllers\\' . $routes['api'][$endpoint]['controller'];
            $method = $routes['api'][$endpoint]['method'];
            $params = ['id' => $id];
        }
    } elseif ($action === 'equipos' || $action === 'jugadores' || $action === 'resultado') {
        // Sub-routes: /equipos/crear, /jugadores/registrar, etc.
        $sub = $sub_action ?? 'listar';
        if (isset($routes[$action][$sub])) {
            $controller_class = 'App\\Controllers\\' . $routes[$action][$sub]['controller'];
            $method = $routes[$action][$sub]['method'];
            $params = ['id' => $id, 'sub_action' => $sub];
        }
    } elseif (isset($routes[$action])) {
        // Simple routes: /home, /fixture, /vivo
        $controller_class = 'App\\Controllers\\' . $routes[$action]['controller'];
        $method = $routes[$action]['method'];
        $params = ['id' => $id];
    }
    
    // Ejecutar controlador si existe
    if ($controller_class && class_exists($controller_class)) {
        // Pasamos $pdo al constructor del controlador
        $controller = new $controller_class($pdo);
        
        if (method_exists($controller, $method)) {
            ob_start();
            // Pasamos $pdo también como parámetro extra si el método lo necesita (opcional)
            $controller->$method(array_merge($params, ['pdo' => $pdo]));
            $output = ob_get_clean();
            
            if ($action === 'api') {
                header('Content-Type: application/json; charset=utf-8');
                echo $output;
            } else {
                render_layout($output, $action);
            }
        } else {
             throw new Exception("Método '$method' no encontrado");
        }
    } else {
        // Fallback a Home
        require_once __DIR__ . '/../src/Controllers/HomeController.php';
        $controller = new App\Controllers\HomeController($pdo);
        ob_start();
        $controller->index(['pdo' => $pdo]);
        $output = ob_get_clean();
        render_layout($output, 'home');
    }
    
} catch (Exception $e) {
    error_log("❌ Error en router: " . $e->getMessage());
    
    if ($action === 'api') {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => APP_ENV === 'production' 
                ? 'Error interno del servidor' 
                : $e->getMessage()
        ]);
    } else {
        http_response_code(500);
        render_error_page($e->getMessage());
    }
}

// ============================================
// FUNCIONES AUXILIARES DEL ROUTER
// ============================================

/**
 * Renderizar layout completo con header/footer
 */
function render_layout($content, $page_title = 'home') {
    $titles = [
        'home' => 'Inicio - Campeonato GOMS 2026',
        'fixture' => 'Fixture - Campeonato GOMS 2026',
        'posiciones' => 'Tabla de Posiciones',
        'goleadores' => 'Goleadores',
        'vivo' => 'Marcador en Vivo ⚽',
    ];
    
    $title = $titles[$page_title] ?? APP_NAME;
    
    include __DIR__ . '/views/layout/header.php';
    echo $content;
    include __DIR__ . '/views/layout/footer.php';
}

/**
 * Página de error genérica
 */
function render_error_page($message) {
    include __DIR__ . '/views/layout/header.php';
    ?>
    <div class="error-container">
        <h1>⚠️ Error</h1>
        <p><?= htmlspecialchars($message) ?></p>
        <a href="<?= BASE_URL ?>" class="btn btn-primary">Volver al Inicio</a>
    </div>
    <?php
    include __DIR__ . '/views/layout/footer.php';
}