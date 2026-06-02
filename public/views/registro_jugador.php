<?php
/**
 * registro_jugador.php - Formulario de Registro de Jugador
 * Ubicación: public/views/registro_jugador.php
 * Diseño: FIFA World Cup 2026 Style con colores fluorescentes
 */

// Obtener mensaje flash si existe
$flash = get_flash_message();
?>

<!-- ============================================ -->
<!-- HEADER ESPECÍFICO PARA REGISTRO -->
<!-- ============================================ -->
<div class="registration-header">
    <div class="header-content">
        <a href="<?= BASE_URL ?>" class="back-button" aria-label="Volver al inicio">
            <span class="back-icon">←</span>
            <span>Volver</span>
        </a>
        
        <div class="logo-container">
            <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Mundial Futbol GOMS 2026" class="championship-logo">
        </div>
        
        <div class="header-title">
            <h1>REGISTRO DE JUGADOR</h1>
            <h2>⚽ Campeonato GOMS 2026</h2>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- CONTENIDO PRINCIPAL -->
<!-- ============================================ -->
<main class="registration-container">
    
    <!-- FLASH MESSAGE -->
    <?php if ($flash): ?>
        <?= render_toast($flash['message'], $flash['type']) ?>
    <?php endif; ?>
    
    <div class="registration-card">
        
        <!-- Información del Equipo -->
        <div class="team-info-banner">
            <div class="team-badge grupo-<?= strtolower($equipo['grupo']) ?>">
                <span class="badge-letter"><?= $equipo['grupo'] ?></span>
            </div>
            <div class="team-details">
                <h3><?= h($equipo['nombre']) ?></h3>
                <p>Grupo <?= $equipo['grupo'] ?> - Campeonato GOMS 2026</p>
            </div>
            <div class="team-icon">
                 ⚽
            </div>
        </div>
        
        <!-- Formulario de Registro -->
        <form action="<?= BASE_URL ?>/jugadores/registrar" method="POST" class="registration-form" id="playerForm">
            
            <!-- Campo oculto: ID del equipo -->
            <input type="hidden" name="id_equipo" value="<?= $equipo['id_equipo'] ?>">
            
            <!-- Nombre Completo -->
            <div class="form-group">
                <label for="nombre" class="form-label">
                    <span class="label-icon"></span>
                    Nombre Completo
                    <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    id="nombre" 
                    name="nombre" 
                    class="form-input" 
                    placeholder="Ej: Juan Pérez González"
                    required
                    maxlength="100"
                    autocomplete="name"
                >
                <small class="form-hint">Ingresa tu nombre completo como aparece en tu documento de identidad</small>
            </div>
            
            <!-- Correo Electrónico -->
            <div class="form-group">
                <label for="correo" class="form-label">
                    <span class="label-icon"></span>
                    Correo Electrónico
                    <span class="required">*</span>
                </label>
                <input 
                    type="email" 
                    id="correo" 
                    name="correo" 
                    class="form-input" 
                    placeholder="ejemplo@correo.com"
                    required
                    maxlength="100"
                    autocomplete="email"
                >
                <small class="form-hint">Te enviaremos una confirmación a este correo</small>
            </div>
            
            <!-- Área/Posición (Opcional) -->
            <div class="form-group">
                <label for="area" class="form-label">
                    <span class="label-icon"></span>
                    Área / Posición
                    <span class="optional">(Opcional)</span>
                </label>
                <select id="area" name="area" class="form-select">
                    <option value="">Selecciona tu posición...</option>
                    <option value="Arquero">Arquero</option>
                    <option value="Defensa">Defensa</option>
                    <option value="Mediocampista">Mediocampista</option>
                    <option value="Delantero">Delantero</option>
                    <option value="Polifuncional">Polifuncional</option>
                    <option value="Otro">Otro</option>
                </select>
                <small class="form-hint">Esta información es opcional y no afecta tu inscripción</small>
            </div>
            
            <!-- Términos y Condiciones -->
            <div class="form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="terminos" required class="checkbox-input">
                    <span class="checkbox-custom"></span>
                    <span class="checkbox-text">
                        Acepto los <a href="#" class="link-terms">términos y condiciones</a> del campeonato
                        <span class="required">*</span>
                    </span>
                </label>
            </div>
            
            <!-- Botón de Envío -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-register" id="submitBtn">
                    <span class="btn-icon"></span>
                    Registrarme en el Equipo
                </button>
            </div>
            
            <!-- Loading State -->
            <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                <div class="spinner"></div>
                <p>Procesando tu registro...</p>
            </div>
        </form>
        
        <!-- Información Adicional -->
        <div class="additional-info">
            <div class="info-box">
                <h4>ℹ️ Información Importante</h4>
                <ul>
                    <li>Tu registro quedará asociado permanentemente al equipo <strong><?= h($equipo['nombre']) ?></strong></li>
                    <li>Recibirás un correo de confirmación inmediatamente después del registro</li>
                    <li>Podrás ver el fixture y resultados en tiempo real desde la página principal</li>
                    <li>Los resultados de los partidos se actualizan automáticamente</li>
                </ul>
            </div>
            
            <div class="qr-info">
                <h4>📱 Comparte este QR</h4>
                <p>Si eres representante del equipo, comparte este código QR con tus jugadores para facilitar su registro:</p>
                <?php if (qr_exists($equipo['id_equipo'])): ?>
                    <img src="<?= get_qr_url($equipo['id_equipo']) ?>" alt="QR Code para registro" class="qr-image">
                <?php else: ?>
                    <p class="no-qr">El QR aún no ha sido generado. Contacta al administrador.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- ============================================ -->
<!-- JAVASCRIPT ESPECÍFICO PARA REGISTRO -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('playerForm');
    const submitBtn = document.getElementById('submitBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    // Validación en tiempo real del email
    const emailInput = document.getElementById('correo');
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        if (email && !isValidEmail(email)) {
            this.classList.add('input-error');
            showToast('❌ Formato de correo inválido', 'error');
        } else {
            this.classList.remove('input-error');
        }
    });
    
    // Validación del nombre
    const nameInput = document.getElementById('nombre');
    nameInput.addEventListener('blur', function() {
        const name = this.value.trim();
        if (name && name.length < 3) {
            this.classList.add('input-error');
            showToast('❌ El nombre debe tener al menos 3 caracteres', 'error');
        } else {
            this.classList.remove('input-error');
        }
    });
    
    // Submit del formulario
    form.addEventListener('submit', function(e) {
        // Validar campos requeridos
        const nombre = nameInput.value.trim();
        const correo = emailInput.value.trim();
        const terminos = form.querySelector('input[name="terminos"]').checked;
        
        if (!nombre || nombre.length < 3) {
            e.preventDefault();
            showToast('❌ Ingresa tu nombre completo', 'error');
            nameInput.focus();
            return;
        }
        
        if (!correo || !isValidEmail(correo)) {
            e.preventDefault();
            showToast('❌ Ingresa un correo electrónico válido', 'error');
            emailInput.focus();
            return;
        }
        
        if (!terminos) {
            e.preventDefault();
            showToast('❌ Debes aceptar los términos y condiciones', 'error');
            return;
        }
        
        // Mostrar loading
        loadingOverlay.style.display = 'flex';
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-small"></span> Procesando...';
    });
    
    // Helper: Validar email
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
});
</script>

<!-- ============================================ -->
<!-- CSS ADICIONAL ESPECÍFICO PARA REGISTRO -->
<!-- ============================================ -->
<style>
/* Header específico para registro */
.registration-header {
    background: var(--gradient-header);
    padding: var(--spacing-md) var(--spacing-lg);
    box-shadow: var(--shadow-lg);
}

.registration-header .header-content {
    max-width: 800px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-md);
}

.back-button {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    color: var(--color-light);
    text-decoration: none;
    font-weight: 600;
    padding: var(--spacing-sm) var(--spacing-md);
    background: rgba(255,255,255,0.2);
    border-radius: var(--border-radius);
    transition: all 0.3s ease;
}

.back-button:hover {
    background: rgba(255,255,255,0.3);
    transform: translateX(-5px);
}

.back-icon {
    font-size: var(--font-size-xl);
}

.registration-header .header-title {
    text-align: right;
}

.registration-header .header-title h1 {
    font-size: var(--font-size-xl);
    margin-bottom: var(--spacing-xs);
}

.registration-header .header-title h2 {
    font-size: var(--font-size-base);
    color: var(--color-gold);
}

/* Contenedor principal */
.registration-container {
    max-width: 800px;
    margin: var(--spacing-xl) auto;
    padding: 0 var(--spacing-md);
}

/* Card de registro */
.registration-card {
    background: var(--gradient-card);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-xl);
    box-shadow: var(--shadow-lg);
    border: 2px solid var(--color-primary);
}

/* Banner de información del equipo */
.team-info-banner {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    background: var(--color-gray);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-xl);
    border-left: 6px solid var(--color-primary);
}

.team-badge {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--font-size-xxl);
    font-weight: var(--font-bold);
    color: var(--color-dark);
    flex-shrink: 0;
}

.team-badge.grupo-a {
    background: var(--grupo-a);
}

.team-badge.grupo-b {
    background: var(--grupo-b);
}

.team-details {
    flex: 1;
}

.team-details h3 {
    font-size: var(--font-size-xl);
    color: var(--color-primary);
    margin-bottom: var(--spacing-xs);
}

.team-details p {
    font-size: var(--font-size-sm);
    color: var(--color-gray-light);
}

.team-icon {
    font-size: 48px;
    opacity: 0.3;
}

/* Formulario */
.registration-form {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.form-label {
    font-weight: 600;
    color: var(--color-light);
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.label-icon {
    font-size: var(--font-size-lg);
}

.required {
    color: var(--color-secondary);
    font-weight: var(--font-bold);
}

.optional {
    color: var(--color-gray-light);
    font-size: var(--font-size-sm);
    font-weight: normal;
}

.form-input,
.form-select {
    padding: var(--spacing-md);
    background: var(--color-gray);
    border: 2px solid var(--color-gray-light);
    border-radius: var(--border-radius);
    color: var(--color-light);
    font-size: var(--font-size-base);
    transition: all 0.3s ease;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: var(--shadow-glow);
}

.form-input.input-error {
    border-color: var(--color-secondary);
    box-shadow: 0 0 10px rgba(255, 0, 110, 0.3);
}

.form-hint {
    font-size: var(--font-size-sm);
    color: var(--color-gray-light);
    font-style: italic;
}

/* Checkbox personalizado */
.checkbox-group {
    margin-top: var(--spacing-sm);
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-sm);
    cursor: pointer;
    user-select: none;
}

.checkbox-input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    background: var(--color-gray);
    border: 2px solid var(--color-gray-light);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.checkbox-input:checked + .checkbox-custom {
    background: var(--color-primary);
    border-color: var(--color-primary);
}

.checkbox-input:checked + .checkbox-custom::after {
    content: '✓';
    color: var(--color-dark);
    font-weight: bold;
    font-size: 14px;
}

.checkbox-text {
    font-size: var(--font-size-sm);
    color: var(--color-light);
    line-height: 1.4;
}

.link-terms {
    color: var(--color-primary);
    text-decoration: underline;
}

.link-terms:hover {
    color: var(--color-accent);
}

/* Botón de registro */
.btn-register {
    width: 100%;
    padding: var(--spacing-lg);
    font-size: var(--font-size-lg);
    background: var(--gradient-button);
    color: var(--color-dark);
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    font-weight: var(--font-bold);
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-sm);
}

.btn-register:hover:not(:disabled) {
    transform: scale(1.02);
    box-shadow: var(--shadow-glow);
}

.btn-register:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-icon {
    font-size: var(--font-size-xl);
}

/* Loading overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    gap: var(--spacing-md);
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(0, 255, 135, 0.3);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.spinner-small {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(0, 0, 0, 0.3);
    border-top-color: var(--color-dark);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    display: inline-block;
}

.loading-overlay p {
    color: var(--color-light);
    font-size: var(--font-size-lg);
    font-weight: 600;
}

/* Información adicional */
.additional-info {
    margin-top: var(--spacing-xl);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-lg);
}

.info-box,
.qr-info {
    background: var(--color-gray);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
    border-left: 4px solid var(--color-accent);
}

.info-box h4,
.qr-info h4 {
    font-size: var(--font-size-lg);
    color: var(--color-accent);
    margin-bottom: var(--spacing-md);
}

.info-box ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-box li {
    padding: var(--spacing-xs) 0;
    font-size: var(--font-size-sm);
    color: var(--color-light);
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-xs);
}

.info-box li::before {
    content: '•';
    color: var(--color-primary);
    font-weight: bold;
}

.qr-image {
    width: 150px;
    height: 150px;
    border-radius: var(--border-radius);
    border: 2px solid var(--color-primary);
    margin-top: var(--spacing-sm);
}

.no-qr {
    font-size: var(--font-size-sm);
    color: var(--color-gray-light);
    font-style: italic;
}

/* Responsive */
@media (max-width: 768px) {
    .registration-header .header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .registration-header .header-title {
        text-align: center;
    }
    
    .back-button {
        align-self: flex-start;
    }
    
    .team-info-banner {
        flex-direction: column;
        text-align: center;
    }
    
    .additional-info {
        grid-template-columns: 1fr;
    }
    
    .registration-card {
        padding: var(--spacing-lg);
    }
}

@media (max-width: 480px) {
    .registration-header .header-title h1 {
        font-size: var(--font-size-lg);
    }
    
    .form-input,
    .form-select {
        padding: var(--spacing-sm);
    }
    
    .btn-register {
        padding: var(--spacing-md);
        font-size: var(--font-size-base);
    }
}
</style>