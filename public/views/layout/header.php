<?php
/**
 * header.php - Layout Header Común
 * Ubicación: public/views/layout/header.php
 */

// Definir variables si no existen (evita warnings)
$current_page = $current_page ?? 'home';
$page_title = $page_title ?? APP_NAME;

// Determinar ruta base relativa para assets si es necesario
// Pero usaremos BASE_URL definida en config.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= h($page_title) ?></title>
    
    <!-- Meta tags SEO -->
    <meta name="description" content="Campeonato de Fútbol GOMS 2026">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    
    <!-- Google Fonts (Opcional pero recomendado para el estilo moderno) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
</head>
<body class="page-<?= h($current_page) ?>">

    <!-- Navbar Simple (Opcional, puedes quitarlo si ya tienes header en home.php) -->
    <nav class="top-nav">
        <div class="nav-container">
            <a href="<?= BASE_URL ?>" class="nav-brand"> GOMS 2026</a>
            <div class="nav-links">
                <a href="<?= BASE_URL ?>">Inicio</a>
                <a href="<?= BASE_URL ?>/fixture">Fixture</a>
                <?php if (is_admin_authenticated()): ?>
                    <a href="<?= BASE_URL ?>/equipos/listar" class="admin-link">Admin</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Contenedor principal donde se inyecta el contenido de las vistas -->
    <main id="main-content">