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
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = $isProduction ? '' : '/campeonato%20goms%202026/public'; // Ajusta si es local
$request_uri = str_replace($base_path, '', $request_uri);
$request_uri = trim($request_uri, '/');

// Dividir ruta en segmentos
$route_segments = explode('/', $request_uri);
$action = $route_segments[0] ?? 'home';
$sub_action = $route_segments[1] ?? null;
$id = $route_segments[2] ?? null;

// Mapa de rutas simplificado para manejar dinámicamente
try {
    global $pdo;
    
    $controller_class = null;
    $method = null;
    $params = ['id' => $id];

    // Lógica de enrutamiento
    if ($action === 'api') {
        // API Routes: /api/endpoint
        $endpoint = $sub_action ?? 'error';
        // Mapeo manual de endpoints API si es necesario, o usar un switch
        if ($endpoint === 'resultado' && isset($route_segments[2])) {
             $sub_endpoint = $route_segments[2]; // ej: verificar, ingresar
             if ($sub_endpoint === 'verificar') {
                 $controller_class = 'App\\Controllers\\ApiController';
                 $method = 'verifyPassword';
             } elseif ($sub_endpoint === 'ingresar') {
                 $controller_class = 'App\\Controllers\\ApiController';
                 $method = 'storeResultado';
             }
        } elseif ($endpoint === 'fixture' && is_numeric($id)) {
            $controller_class = 'App\\Controllers\\ApiController';
            $method = 'getFixture';
        } elseif ($endpoint === 'jugadores') {
            $controller_class = 'App\\Controllers\\ApiController';
            $method = 'getJugadores';
        } elseif ($endpoint === 'marcador') {
            $controller_class = 'App\\Controllers\\ApiController';
            $method = 'getMarcadorVivo';
        } else {
             // Fallback API
             $controller_class = 'App\\Controllers\\ApiController';
             $method = 'getEquipos'; 
        }

    } elseif ($action === 'equipos') {
        // Equipos Routes: /equipos/listar, /equipos/crear, /equipos/editar/{id}, /equipos/ver/{id}
        $controller_class = 'App\\Controllers\\EquipoController';
        
        if ($sub_action === 'listar' || empty($sub_action)) {
            $method = 'index';
        } elseif ($sub_action === 'crear') {
            $method = 'create'; // GET form
            if ($_SERVER['REQUEST_METHOD'] === 'POST') $method = 'store';
        } elseif ($sub_action === 'editar') {
            $method = 'edit'; // GET form
            if ($_SERVER['REQUEST_METHOD'] === 'POST') $method = 'update';
        } elseif ($sub_action === 'eliminar') {
            $method = 'delete';
        } elseif ($sub_action === 'ver') {
            $method = 'show';
        } elseif ($sub_action === 'generar-qr') {
            $method = 'generarQR';
        } else {
            // Si no hay subacción, listar por defecto
            $method = 'index';
        }

    } elseif ($action === 'jugadores') {
        // Jugadores Routes: /jugadores/registrar/{id}, /jugadores/listar
        $controller_class = 'App\\Controllers\\JugadorController';
        
        if ($sub_action === 'registrar') {
            $method = 'register'; // GET form
            if ($_SERVER['REQUEST_METHOD'] === 'POST') $method = 'store';
        } elseif ($sub_action === 'eliminar') {
            $method = 'delete';
        } else {
            $method = 'index'; // Listar
        }

    } elseif ($action === 'vivo') {
        $controller_class = 'App\\Controllers\\HomeController'; // O crear MarcadorController
        $method = 'index'; // Por ahora reutilizamos home o creamos vista específica
        
    } else {
        // Default Routes: home, fixture, posiciones, goleadores
        $controller_class = 'App\\Controllers\\HomeController';
        $method = 'index';
    }

    // Ejecutar controlador
    if ($controller_class && class_exists($controller_class)) {
        $controller = new $controller_class($pdo);
        
        if (method_exists($controller, $method)) {
            ob_start();
            $controller->$method($params);
            $output = ob_get_clean();
            
            if ($action === 'api') {
                header('Content-Type: application/json; charset=utf-8');
                echo $output;
            } else {
                render_layout($output, $action . '-' . ($sub_action ?? 'index'));
            }
        } else {
            throw new Exception("Método '$method' no encontrado en $controller_class");
        }
    } else {
        // Fallback a Home si la ruta no existe
        require_once __DIR__ . '/../src/Controllers/HomeController.php';
        $controller = new App\Controllers\HomeController($pdo);
        ob_start();
        $controller->index(['pdo' => $pdo]);
        $output = ob_get_clean();
        render_layout($output, 'home');
    }
    
} catch (Exception $e) {
    error_log("❌ Error en router: " . $e->getMessage());
    http_response_code(500);
    echo "<h1>Error Interno</h1><p>" . ($isProduction ? "Contacte soporte" : $e->getMessage()) . "</p>";
}

// Función helper para renderizar layout (asegúrate que esté definida antes o fuera del try)
function render_layout($content, $page_title = 'home') {
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