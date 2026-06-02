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
                    <img src="<?= BASE_URL ?>/assets/images/logo-footer.png" alt="Mundial Futbol GOMS 2026" class="footer-logo">
                    <h3>MUNDIAL FUTBOL GOMS 2026</h3>
                    <p>El campeonato de fútbol más emocionante del año</p>
                </div>
                
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Navegación</h4>
                        <ul>
                            <li><a href="<?= BASE_URL ?>">Inicio</a></li>
                            <li><a href="<?= BASE_URL ?>/fixture">Fixture</a></li>
                            <li><a href="<?= BASE_URL ?>/posiciones">Tabla de Posiciones</a></li>
                            <li><a href="<?= BASE_URL ?>/goleadores">Goleadores</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4>Acciones Rápidas</h4>
                        <ul>
                            <li><a href="<?= BASE_URL ?>/vivo"> Marcador en Vivo</a></li>
                            <li><a href="#" onclick="openResultadoModal(1)">Ingresar Resultado</a></li>
                            <li><a href="<?= BASE_URL ?>/equipos">Ver Equipos</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4>Información</h4>
                        <ul>
                            <li><a href="#">Reglamento</a></li>
                            <li><a href="#">Contacto</a></li>
                            <li><a href="#">Acerca de</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Divider -->
            <div class="footer-divider"></div>
            
            <!-- Sección Inferior del Footer -->
            <div class="footer-bottom">
                <div class="footer-copyright">
                    <p>&copy; <?= date('Y') ?> Campeonato GOMS 2026. Todos los derechos reservados.</p>
                    <p class="footer-developer">Desarrollado por <strong>GLT Sport</strong> ⚽</p>
                </div>
                
                <div class="footer-social">
                    <a href="#" class="social-link" aria-label="Facebook">
                        <span class="social-icon">📘</span>
                    </a>
                    <a href="#" class="social-link" aria-label="Instagram">
                        <span class="social-icon">📸</span>
                    </a>
                    <a href="#" class="social-link" aria-label="Twitter">
                        <span class="social-icon">🐦</span>
                    </a>
                    <a href="#" class="social-link" aria-label="WhatsApp">
                        <span class="social-icon">💬</span>
                    </a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- ============================================ -->
    <!-- SCRIPTS JAVASCRIPT -->
    <!-- ============================================ -->
    
    <!-- JavaScript Principal -->
    <script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
    
    <!-- Scripts específicos por página (opcional) -->
    <?php if (file_exists(__DIR__ . "/../assets/js/{$current_page}.js")): ?>
        <script src="<?= BASE_URL ?>/assets/js/<?= h($current_page) ?>.js" defer></script>
    <?php endif; ?>
    
    <!-- Service Worker para PWA (opcional) -->
    <?php if (APP_ENV === 'production'): ?>
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js')
                        .then(registration => {
                            console.log('SW registrado:', registration.scope);
                        })
                        .catch(error => {
                            console.log('SW registro fallido:', error);
                        });
                });
            }
        </script>
    <?php endif; ?>
    
    <!-- Debug Info (solo en desarrollo) -->
    <?php if (APP_ENV !== 'production'): ?>
        <div class="debug-info">
            <small>ENV: <?= APP_ENV ?> | PHP: <?= phpversion() ?> | Page: <?= h($current_page ?? 'unknown') ?></small>
        </div>
    <?php endif; ?>
    
</body>
</html>