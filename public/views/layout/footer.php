<?php
/**
 * footer.php - Layout Footer Común
 */

// Definir current_page si no existe (evita el warning)
$current_page = $current_page ?? 'home'; 
?>

    </main>
    
    <!-- ============================================ -->
    <!-- FOOTER PRINCIPAL -->
    <!-- ============================================ -->
    <footer class="site-footer" role="contentinfo">
        <div class="footer-container">
            
            <!-- Sección Superior del Footer -->
            <div class="footer-top">
                <div class="footer-brand">
                    <!-- Copa a la izquierda -->
                    <div class="copa-animada-container">
                        <img src="/assets/images/copa-mundo.png" alt="Copa Fútbol Mundial GOMS 2026" class="copa-animada-img">
                    </div>
                    <h3>CAMPEONATO MUNDIAL FUTBOL GOMS 2026</h3>
                    <p>El campeonato de fútbol más emocionante del año</p>
                </div>
                
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Navegación</h4>
                        <ul>
                            <!-- Usamos / para enlaces internos -->
                            <li><a href="/">Inicio</a></li>
                            <li><a href="/fixture">Fixture</a></li>
                            <li><a href="/posiciones">Tabla de Posiciones</a></li>
                            <li><a href="/goleadores">Goleadores</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4>Acciones Rápidas</h4>
                        <ul>
                            <li><a href="/vivo"> Marcador en Vivo</a></li>
                            <li><a href="#" onclick="openResultadoModal(1)">Ingresar Resultado</a></li>
                            <li><a href="/equipos/listar">Ver Equipos</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4>Información</h4>
                        <ul>
                            <li><a href="https://canchasport.com">Contacto</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Divider -->
            <div class="footer-divider"></div>
            
            <!-- Sección Inferior del Footer -->
            <div class="footer-bottom">
                <div class="footer-copyright" style="text-align: center; width: 100%;">
                    <p>&copy; <?= date('Y') ?> Campeonato Fútbol GOMS 2026. Todos los derechos reservados.</p>
                    <p class="footer-developer" style="text-align: center; width: 100%;">Powered by <strong>CanchaSport</strong> </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- ============================================ -->
    <!-- SCRIPTS JAVASCRIPT -->
    <!-- ============================================ -->
    
    <!-- JavaScript Principal (RUTA ABSOLUTA DESDE LA RAÍZ) -->
    <script src="/assets/js/app.js" defer></script>
    
    <!-- Scripts específicos por página (opcional) -->
    <?php if (file_exists(__DIR__ . "/../assets/js/{$current_page}.js")): ?>
        <script src="/assets/js/<?= h($current_page) ?>.js" defer></script>
    <?php endif; ?>
    
    <!-- Service Worker para PWA (opcional) -->
    <?php /* if (defined('APP_ENV') && APP_ENV === 'production'): ?>
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then(registration => { console.log('SW registrado:', registration.scope); })
                        .catch(error => { console.log('SW registro fallido:', error); });
                });
            }
        </script>
    <?php endif; ?>
    /* --- IGNORE --- */ ?>
    
    <!-- Debug Info (solo en desarrollo) -->
    <?php if (defined('APP_ENV') && APP_ENV !== 'production'): ?>
        <div class="debug-info">
            <small>ENV: <?= APP_ENV ?> | PHP: <?= phpversion() ?> | Page: <?= h($current_page ?? 'unknown') ?></small>
        </div>
    <?php endif; ?>
    
</body>
</html>