<?php
/**
 * header.php - Layout Header Común
 * Ubicación: public/views/layout/header.php
 */

// Definir variables si no existen (evita warnings)
$current_page = $current_page ?? 'home';
$page_title = $page_title ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= h($page_title) ?></title>
    
    <!-- Meta tags SEO -->
    <meta name="description" content="Campeonato de Fútbol GOMS 2026">
    
    <!-- Favicon (Usamos ruta relativa absoluta) -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    
    <!-- CSS Principal (RUTA ABSOLUTA DESDE LA RAÍZ) -->
    <link rel="stylesheet" href="/assets/css/main.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <script>
        const BASE_URL = "https://campeonatogoms2026.up.railway.app";
    </script>

    <!-- Google tag (gtag.js) - Campeonato GOMS 2026 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-J7JJY6Z4QS"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    
    // Configuración base
    gtag('config', 'G-J7JJY6Z4QS', {
        'page_title': document.title,
        'send_page_view': true
    });
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@700&family=Rajdhani:wght@700&display=swap" rel="stylesheet">
</head>
<body class="page-<?= h($current_page) ?>">
    <!-- Contenedor principal donde se inyecta el contenido de las vistas -->
    <main id="main-content">