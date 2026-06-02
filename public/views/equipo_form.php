<?php
/**
 * equipo_form.php - Formulario Crear/Editar Equipo
 * Ubicación: public/views/equipo_form.php
 * Diseño: FIFA World Cup 2026 Style
 */

// Determinar si es edición o creación
$is_edit = isset($equipo) && !empty($equipo);
$page_title = $is_edit ? "Editar Equipo: {$equipo['nombre']}" : "Nuevo Equipo";

// Obtener mensaje flash si existe
$flash = get_flash_message();
?>

<!-- ============================================ -->
<!-- HEADER DE FORMULARIO -->
<!-- ============================================ -->
<div class="form-header">
    <div class="header-content">
        <a href="<?= BASE_URL ?>/equipos/listar" class="back-button" aria-label="Volver al listado">
            <span class="back-icon">←</span>
            <span>Volver a Equipos</span>
        </a>
        
        <div class="header-title">
            <h1><?= $page_title ?></h1>
            <h2>⚙️ Gestión de Equipos</h2>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- CONTENIDO PRINCIPAL -->
<!-- ============================================ -->
<main class="form-container">
    
    <!-- FLASH MESSAGE -->
    <?php if ($flash): ?>
        <?= render_toast($flash['message'], $flash['type']) ?>
    <?php endif; ?>
    
    <div class="form-card">
        
        <!-- Icono Decorativo -->
        <div class="form-icon-banner">
            <span class="icon-large"><?= $is_edit ? '✏️' : '➕' ?></span>
            <p><?= $is_edit ? 'Modifica los datos del equipo' : 'Registra un nuevo equipo en el campeonato' ?></p>
        </div>
        
        <!-- Formulario -->
        <form action="<?= $is_edit ? BASE_URL . '/equipos/editar/' . $equipo['id_equipo'] : BASE_URL . '/equipos/crear' ?>" 
              method="POST" 
              class="team-form" 
              id="teamForm">
            
            <!-- Nombre del Equipo -->
            <div class="form-group">
                <label for="nombre" class="form-label">
                    <span class="label-icon"></span>
                    Nombre del Equipo
                    <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    id="nombre" 
                    name="nombre" 
                    class="form-input" 
                    placeholder="Ej: Los Galácticos FC"
                    value="<?= $is_edit ? h($equipo['nombre']) : '' ?>"
                    required
                    maxlength="100"
                    autocomplete="off"
                >
                <small class="form-hint">El nombre debe ser único en el campeonato</small>
                <div id="nombreFeedback" class="feedback-message"></div>
            </div>
            
            <!-- Grupo -->
            <div class="form-group">
                <label for="grupo" class="form-label">
                    <span class="label-icon"></span>
                    Grupo
                    <span class="required">*</span>
                </label>
                <select id="grupo" name="grupo" class="form-select" required>
                    <option value="">Selecciona un grupo...</option>
                    <option value="A" <?= ($is_edit && $equipo['grupo'] === 'A') ? 'selected' : '' ?>>Grupo A</option>
                    <option value="B" <?= ($is_edit && $equipo['grupo'] === 'B') ? 'selected' : '' ?>>Grupo B</option>
                </select>
                <small class="form-hint">El grupo determina la serie en la que jugará el equipo</small>
            </div>
            
            <!-- Información Adicional (Solo Edición) -->
            <?php if ($is_edit): ?>
                <div class="info-box edit-info">
                    <h4>ℹ️ Información del Equipo</h4>
                    <ul>
                        <li><strong>ID:</strong> #<?= $equipo['id_equipo'] ?></li>
                        <li><strong>Jugadores Registrados:</strong> <?= $equipo['total_jugadores'] ?? 0 ?></li>
                        <li><strong>QR Code:</strong> <?= ($equipo['qr_code'] ? '✅ Generado' : '❌ Pendiente') ?></li>
                        <li><strong>Creado:</strong> <?= format_date($equipo['created_at']) ?></li>
                    </ul>
                    
                    <?php if ($equipo['qr_code']): ?>
                        <div class="qr-preview-small">
                            <img src="<?= get_qr_url($equipo['id_equipo']) ?>" alt="QR Preview" width="80">
                            <p><small>QR Actual</small></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Botones de Acción -->
            <div class="form-actions">
                <a href="<?= BASE_URL ?>/equipos/listar" class="btn btn-secondary">
                    Cancelar
                </a>
                
                <button type="submit" class="btn btn-primary btn-submit" id="submitBtn">
                    <span class="btn-icon"><?= $is_edit ? '💾' : '✅' ?></span>
                    <?= $is_edit ? 'Guardar Cambios' : 'Crear Equipo' ?>
                </button>
            </div>
            
            <!-- Loading State -->
            <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                <div class="spinner"></div>
                <p>Procesando...</p>
            </div>
        </form>
    </div>
</main>

<!-- ============================================ -->
<!-- JAVASCRIPT ESPECÍFICO -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('teamForm');
    const submitBtn = document.getElementById('submitBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const nombreInput = document.getElementById('nombre');
    const nombreFeedback = document.getElementById('nombreFeedback');
    
    // Validación en tiempo real del nombre (opcional: chequear disponibilidad vía AJAX)
    nombreInput.addEventListener('blur', function() {
        const nombre = this.value.trim();
        
        if (nombre.length < 3) {
            showFeedback('error', 'El nombre debe tener al menos 3 caracteres');
        } else {
            clearFeedback();
            // Aquí se podría agregar una llamada AJAX para verificar unicidad
            // checkNombreDisponible(nombre);
        }
    });
    
    // Submit del formulario
    form.addEventListener('submit', function(e) {
        const nombre = nombreInput.value.trim();
        const grupo = document.getElementById('grupo').value;
        
        if (!nombre || nombre.length < 3) {
            e.preventDefault();
            showToast('❌ Ingresa un nombre válido para el equipo', 'error');
            nombreInput.focus();
            return;
        }
        
        if (!grupo) {
            e.preventDefault();
            showToast('❌ Selecciona un grupo (A o B)', 'error');
            return;
        }
        
        // Mostrar loading
        loadingOverlay.style.display = 'flex';
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-small"></span> Guardando...';
    });
    
    // Helpers de feedback
    function showFeedback(type, message) {
        nombreFeedback.textContent = message;
        nombreFeedback.className = `feedback-message feedback-${type}`;
        nombreFeedback.style.display = 'block';
    }
    
    function clearFeedback() {
        nombreFeedback.style.display = 'none';
        nombreFeedback.textContent = '';
    }
});
</script>

<!-- ============================================ -->
<!-- CSS ADICIONAL ESPECÍFICO -->
<!-- ============================================ -->
<style>
/* Header Form */
.form-header {
    background: var(--gradient-header);
    padding: var(--spacing-md) var(--spacing-lg);
    box-shadow: var(--shadow-lg);
}

.form-header .header-content {
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

.header-title {
    text-align: center;
    flex: 1;
}

.header-title h1 {
    font-size: var(--font-size-xl);
    margin-bottom: var(--spacing-xs);
}

.header-title h2 {
    font-size: var(--font-size-base);
    color: var(--color-gold);
}

/* Contenedor Form */
.form-container {
    max-width: 800px;
    margin: var(--spacing-xl) auto;
    padding: 0 var(--spacing-md);
}

.form-card {
    background: var(--gradient-card);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-xl);
    box-shadow: var(--shadow-lg);
    border: 2px solid var(--color-primary);
}

/* Banner Icono */
.form-icon-banner {
    text-align: center;
    margin-bottom: var(--spacing-xl);
    padding-bottom: var(--spacing-lg);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.icon-large {
    font-size: 64px;
    display: block;
    margin-bottom: var(--spacing-sm);
}

.form-icon-banner p {
    color: var(--color-gray-light);
    font-size: var(--font-size-base);
}

/* Formulario */
.team-form {
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

.form-hint {
    font-size: var(--font-size-sm);
    color: var(--color-gray-light);
    font-style: italic;
}

.feedback-message {
    font-size: var(--font-size-sm);
    margin-top: var(--spacing-xs);
    display: none;
}

.feedback-error {
    color: var(--color-secondary);
}

.feedback-success {
    color: var(--color-primary);
}

/* Info Box (Edición) */
.edit-info {
    background: var(--color-gray);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
    border-left: 4px solid var(--color-accent);
    margin-top: var(--spacing-md);
}

.edit-info h4 {
    color: var(--color-accent);
    margin-bottom: var(--spacing-md);
}

.edit-info ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.edit-info li {
    padding: var(--spacing-xs) 0;
    font-size: var(--font-size-sm);
    color: var(--color-light);
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.edit-info li:last-child {
    border-bottom: none;
}

.qr-preview-small {
    margin-top: var(--spacing-md);
    text-align: center;
}

.qr-preview-small img {
    border-radius: var(--border-radius-sm);
    border: 1px solid var(--color-primary);
}

.qr-preview-small p {
    margin-top: var(--spacing-xs);
    font-size: var(--font-size-sm);
    color: var(--color-gray-light);
}

/* Botones */
.form-actions {
    display: flex;
    gap: var(--spacing-md);
    justify-content: flex-end;
    margin-top: var(--spacing-lg);
}

.btn-submit {
    min-width: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-sm);
}

/* Loading Overlay */
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

/* Responsive */
@media (max-width: 768px) {
    .form-header .header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .back-button {
        align-self: flex-start;
    }
    
    .form-actions {
        flex-direction: column-reverse;
    }
    
    .btn-submit, .btn-secondary {
        width: 100%;
    }
}
</style>