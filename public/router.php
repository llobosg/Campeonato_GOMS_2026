<?php
/**
 * router.php - Entry point for PHP Built-in Server on Railway
 * Redirige todas las peticiones a index.php excepto archivos estáticos existentes
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Si es un archivo real (CSS, JS, Imágenes), servirlo directamente
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; // El servidor sirve el archivo estático
}

// De lo contrario, incluir index.php (nuestro router MVC)
require_once __DIR__ . '/index.php';
?>