<?php
/**
 * public/router.php
 * Maneja el enrutamiento para el servidor integrado de PHP en Railway
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Si la URI es '/', servir index.php directamente
if ($uri === '/' || $uri === '/index.php') {
    require_once __DIR__ . '/index.php';
    return true;
}

// Verificar si es un archivo físico real (CSS, JS, Imágenes, etc.)
$filePath = __DIR__ . $uri;

if (file_exists($filePath) && !is_dir($filePath)) {
    // Devolver false indica al servidor integrado de PHP que sirva el archivo estático
    return false;
}

// Si no es un archivo estático, es una ruta de la aplicación -> llamar a index.php
require_once __DIR__ . '/index.php';
return true;
?>