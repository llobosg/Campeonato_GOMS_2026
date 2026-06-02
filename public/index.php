<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('APP_ENTRY_POINT', true);
require_once __DIR__ . '/../config.php';

// ============================================
// FUNCIÓN HELPER PARA RENDERIZAR LAYOUT
// ============================================
function render_layout($content, $page_title = 'home') {
    include __DIR__ . '/views/layout/header.php';
    echo $content;
    include __DIR__ . '/views/layout/footer.php';
}

// ============================================
// LÓGICA DE ENRUTAMIENTO
// ============================================
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Ajustar base path según entorno
$base_path = '';
if (!$isProduction) {
    // En local XAMPP, la ruta suele incluir la carpeta del proyecto
    $base_path = '/campeonato%20goms%202026/public'; 
}

$request_uri = str_replace($base_path, '', $request_uri);
$request_uri = trim($request_uri, '/');

// Dividir ruta en segmentos
$route_segments = explode('/', $request_uri);
$action = $route_segments[0] ?? 'home';
$sub_action = $route_segments[1] ?? null;
$id = $route_segments[2] ?? null;

try {
    global $pdo;
    
    $controller_class = null;
    $method = null;
    $params = ['id' => $id, 'pdo' => $pdo];

    // --- RUTAS API ---
    if ($action === 'api') {
        $endpoint = $sub_action ?? '';
        $sub_endpoint = $route_segments[2] ?? '';
        
        if ($endpoint === 'resultado') {
            $controller_class = 'App\\Controllers\\ApiController';
            if ($sub_endpoint === 'verificar') $method = 'verifyPassword';
            elseif ($sub_endpoint === 'ingresar') $method = 'storeResultado';
        } 
        elseif ($endpoint === 'fixture' && is_numeric($id)) {
            $controller_class = 'App\\Controllers\\ApiController';
            $method = 'getFixture';
        }
        elseif ($endpoint === 'jugadores') {
            $controller_class = 'App\\Controllers\\ApiController';
            $method = 'getJugadores';
        }
        elseif ($endpoint === 'marcador') {
             $controller_class = 'App\\Controllers\\ApiController';
             $method = 'getMarcadorVivo';
        }
        else {
            // Default API
            $controller_class = 'App\\Controllers\\ApiController';
            $method = 'getEquipos';
        }
    } 
    
    // --- RUTAS EQUIPOS ---
    elseif ($action === 'equipos') {
        $controller_class = 'App\\Controllers\\EquipoController';
        
        if ($sub_action === 'listar' || empty($sub_action)) {
            $method = 'index';
        } 
        elseif ($sub_action === 'crear') {
            $method = ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'store' : 'create';
        } 
        elseif ($sub_action === 'editar') {
            $method = ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'update' : 'edit';
        } 
        elseif ($sub_action === 'eliminar') {
            $method = 'delete';
        } 
        elseif ($sub_action === 'ver') {
            $method = 'show';
        } 
        elseif ($sub_action === 'generar-qr') {
            $method = 'generarQR';
        } 
        else {
            $method = 'index';
        }
    } 
    
    // --- RUTAS JUGADORES ---
    elseif ($action === 'jugadores') {
        $controller_class = 'App\\Controllers\\JugadorController';
        
        if ($sub_action === 'registrar') {
            $method = ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'store' : 'register';
        } 
        elseif ($sub_action === 'eliminar') {
            $method = 'delete';
        } 
        else {
            $method = 'index';
        }
    } 
    
    // --- RUTAS PRINCIPALES ---
    else {
        $controller_class = 'App\\Controllers\\HomeController';
        $method = 'index';
    }

    // --- EJECUCIÓN ---
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
                render_layout($output, $action);
            }
        } else {
            throw new Exception("Método '$method' no encontrado en $controller_class");
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
    error_log("❌ Router Error: " . $e->getMessage());
    http_response_code(500);
    echo "<h1>Error Interno</h1><p>Detalles: " . ($isProduction ? "Contacte soporte" : $e->getMessage()) . "</p>";
}