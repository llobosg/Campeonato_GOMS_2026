<?php
/**
 * equipo_detalle.php - Detalle de Equipo, Jugadores y Partidos
 * Ubicación: public/views/equipo_detalle.php
 * Diseño: FIFA World Cup 2026 Style
 */

// Obtener mensaje flash si existe
$flash = get_flash_message();
?>

<!-- ============================================ -->
<!-- HEADER DE DETALLE -->
<!-- ============================================ -->
<div class="detail-header">
    <div class="header-content">
        <a href="<?= BASE_URL ?>/equipos/listar" class="back-button" aria-label="Volver al listado">
            <span class="back-icon">←</span>
            <span>Volver a Equipos</span>
        </a>
        
        <div class="header-title">
            <h1><?= h($equipo['nombre']) ?></h1>
            <h2>Grupo <?= $equipo['grupo'] ?> ⚽</h2>
        </div>
        
        <div class="header-actions">
            <a href="<?= BASE_URL ?>/equipos/editar/<?= $equipo['id_equipo'] ?>" class="btn btn-secondary btn-small">
                 Editar
            </a>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- CONTENIDO PRINCIPAL -->
<!-- ============================================ -->
<main class="detail-container">
    
    <!-- FLASH MESSAGE -->
    <?php if ($flash): ?>
        <?= render_toast($flash['message'], $flash['type']) ?>
    <?php endif; ?>
    
    <!-- TARJETAS DE RESUMEN -->
    <section class="summary-cards">

        <!-- AQUÍ VA BLOQUE PARA QR DE REGISTRO Y LINK A COPIAR -->

        <div class="card card-stats">
            <h3> Estadísticas</h3>
            <div class="stat-item">
                <span class="stat-label">Jugadores:</span>
                <span class="stat-value"><?= count($jugadores) ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Partidos Jugados:</span>
                <span class="stat-value"><?= count(array_filter($partidos, fn($p) => $p['estado'] === 'finalizado')) ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Próximo Partido:</span>
                <?php 
                $proximos = array_filter($partidos, fn($p) => $p['estado'] !== 'finalizado');
                $proximo = !empty($proximos) ? reset($proximos) : null;
                ?>
                <span class="stat-value">
                    <?= $proximo ? format_date($proximo['fecha']) . ' ' . format_time($proximo['hora']) : 'No hay pendientes' ?>
                </span>
            </div>
        </div>
    </section>
    
    <!-- SECCIÓN JUGADORES -->
    <section class="detail-section">
        <div class="section-header">
            <h3 class="section-title">
                <span class="icon"></span> Plantilla de Jugadores (<?= count($jugadores) ?>)
            </h3>
            
            <?php if (is_admin_authenticated()): ?>
                <a href="<?= BASE_URL ?>/jugadores/registrar/<?= $equipo['id_equipo'] ?>" class="btn btn-primary btn-small">
                    + Registrar Jugador
                </a>
            <?php endif; ?>
        </div>
        
        <div class="players-grid">
            <?php if (empty($jugadores)): ?>
                <div class="empty-state">
                    <span class="empty-icon"></span>
                    <p>No hay jugadores registrados en este equipo.</p>
                    <a href="<?= BASE_URL ?>/jugadores/registrar/<?= $equipo['id_equipo'] ?>" class="btn btn-secondary">
                        Registrar Primer Jugador
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($jugadores as $jugador): ?>
                    <div class="player-card">
                        <div class="player-avatar">
                            <?= strtoupper(substr($jugador['nombre'], 0, 1)) ?>
                        </div>
                        <div class="player-info">
                            <h4><?= h($jugador['nombre']) ?></h4>
                            <p class="player-area"><?= h($jugador['area'] ?? 'Sin posición definida') ?></p>
                            <small class="player-email"><?= h($jugador['correo']) ?></small>
                        </div>
                        <?php if (is_admin_authenticated()): ?>
                            <button onclick="eliminarJugador(<?= $jugador['id_jugador'] ?>, '<?= h($jugador['nombre']) ?>')" class="btn-icon btn-delete-player" title="Eliminar Jugador">
                                🗑️
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- SECCIÓN PARTIDOS -->
    <section class="detail-section">
        <div class="section-header">
            <h3 class="section-title">
                <span class="icon">📅</span> Historial de Partidos
            </h3>
        </div>
        
        <div class="matches-list">
            <?php if (empty($partidos)): ?>
                <div class="empty-state">
                    <p>No hay partidos programados para este equipo.</p>
                </div>
            <?php else: ?>
                <?php foreach ($partidos as $partido): ?>
                    <div class="match-row <?= $partido['estado'] === 'finalizado' ? 'match-finished' : 'match-pending' ?>">
                        <div class="match-date">
                            <span class="date-day"><?= date('d', strtotime($partido['fecha'])) ?></span>
                            <span class="date-month"><?= date('M', strtotime($partido['fecha'])) ?></span>
                            <span class="match-time"><?= format_time($partido['hora']) ?></span>
                        </div>
                        
                        <div class="match-details">
                            <div class="teams-display">
                                <?php if ($partido['equipo_a'] == $equipo['id_equipo']): ?>
                                    <span class="team home-team highlight"><?= h($partido['nombre_equipo_a']) ?></span>
                                    <span class="vs">VS</span>
                                    <span class="team away-team"><?= h($partido['nombre_equipo_b']) ?></span>
                                <?php else: ?>
                                    <span class="team home-team"><?= h($partido['nombre_equipo_a']) ?></span>
                                    <span class="vs">VS</span>
                                    <span class="team away-team highlight"><?= h($partido['nombre_equipo_b']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($partido['estado'] === 'finalizado'): ?>
                                <div class="result-badge">
                                    <?php 
                                    $goles_favor = ($partido['equipo_a'] == $equipo['id_equipo']) ? $partido['goles_a'] : $partido['goles_b'];
                                    $goles_contra = ($partido['equipo_a'] == $equipo['id_equipo']) ? $partido['goles_b'] : $partido['goles_a'];
                                    
                                    $clase_resultado = $goles_favor > $goles_contra ? 'win' : ($goles_favor == $goles_contra ? 'draw' : 'loss');
                                    $texto_resultado = $goles_favor > $goles_contra ? 'VICTORIA' : ($goles_favor == $goles_contra ? 'EMPATE' : 'DERROTA');
                                    ?>
                                    <span class="score-final"><?= $goles_favor ?> - <?= $goles_contra ?></span>
                                    <span class="result-text result-<?= $clase_resultado ?>"><?= $texto_resultado ?></span>
                                </div>
                            <?php else: ?>
                                <div class="status-badge status-pending">
                                    Pendiente
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<!-- ============================================ -->
<!-- JAVASCRIPT ESPECÍFICO -->
<!-- ============================================ -->
<script>
// Copiar link al portapapeles
function copiarLink(url) {
    navigator.clipboard.writeText(url).then(() => {
        showToast('✅ Link copiado al portapapeles', 'success');
    }).catch(err => {
        showToast('❌ Error al copiar link', 'error');
    });
}

// Generar QR
async function generarQR(teamId) {
    if (!confirm('¿Deseas generar el QR para este equipo? Se requiere permisos de admin.')) return;
    
    try {
        showToast('⏳ Generando QR...', 'info');
        
        const response = await fetch(`${BASE_URL}/equipos/generar-qr/${teamId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin' // Importante para enviar cookies de sesión
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ QR generado exitosamente', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            // Si el error es de autorización, podríamos redirigir o mostrar mensaje
            showToast('❌ Error: ' + (data.error || 'No se pudo generar'), 'error');
        }
    } catch (error) {
        console.error('Error generando QR:', error);
        showToast('❌ Error de conexión', 'error');
    }
}

// Eliminar jugador (Admin)
async function eliminarJugador(jugadorId, nombre) {
    if (!confirm(`⚠️ ¿Estás seguro de eliminar a "${nombre}" del equipo?\n\nEsta acción no se puede deshacer.`)) return;
    
    try {
        showToast('⏳ Eliminando jugador...', 'info');
        
        const response = await fetch(`${BASE_URL}/jugadores/eliminar/${jugadorId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Jugador eliminado', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('❌ Error: ' + (data.error || 'No se pudo eliminar'), 'error');
        }
    } catch (error) {
        console.error('Error eliminando jugador:', error);
        showToast('❌ Error de conexión', 'error');
    }
}
</script>

<!-- ============================================ -->
<!-- CSS ADICIONAL ESPECÍFICO -->
<!-- ============================================ -->
<style>
/* Header Detalle */
.detail-header {
    background: var(--gradient-header);
    padding: var(--spacing-md) var(--spacing-lg);
    box-shadow: var(--shadow-lg);
}

.detail-header .header-content {
    max-width: 1200px;
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

/* Contenedor Detalle */
.detail-container {
    max-width: 1200px;
    margin: var(--spacing-xl) auto;
    padding: 0 var(--spacing-md);
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xl);
}

/* Summary Cards */
.summary-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-lg);
}

.card {
    background: var(--gradient-card);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid rgba(0, 255, 135, 0.2);
    text-align: center;
}

.card h3 {
    color: var(--color-primary);
    margin-bottom: var(--spacing-md);
    font-size: var(--font-size-lg);
}

.qr-detail-image {
    width: 150px;
    height: 150px;
    border-radius: var(--border-radius);
    border: 2px solid var(--color-primary);
    margin-bottom: var(--spacing-md);
}

.link-register {
    display: block;
    color: var(--color-accent);
    text-decoration: none;
    margin-bottom: var(--spacing-sm);
    word-break: break-all;
    font-size: var(--font-size-sm);
}

.btn-copy {
    width: 100%;
}

.no-qr-text {
    color: var(--color-gray-light);
    margin-bottom: var(--spacing-md);
}

.stat-item {
    display: flex;
    justify-content: space-between;
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    color: var(--color-gray-light);
}

.stat-value {
    font-weight: var(--font-bold);
    color: var(--color-light);
}

/* Detail Sections */
.detail-section {
    background: var(--gradient-card);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid rgba(0, 255, 135, 0.2);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-lg);
    border-bottom: 2px solid var(--color-primary);
    padding-bottom: var(--spacing-sm);
}

.section-title {
    font-size: var(--font-size-lg);
    color: var(--color-primary);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

/* Players Grid */
.players-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: var(--spacing-md);
}

.player-card {
    background: var(--color-gray);
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    transition: all 0.3s ease;
    position: relative;
}

.player-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-sm);
    border-color: var(--color-primary);
}

.player-avatar {
    width: 50px;
    height: 50px;
    background: var(--gradient-button);
    color: var(--color-dark);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--font-size-xl);
    font-weight: var(--font-bold);
    flex-shrink: 0;
}

.player-info {
    flex: 1;
    overflow: hidden;
}

.player-info h4 {
    font-size: var(--font-size-base);
    color: var(--color-light);
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.player-area {
    font-size: var(--font-size-sm);
    color: var(--color-primary);
    margin-bottom: 2px;
}

.player-email {
    font-size: var(--font-size-xs);
    color: var(--color-gray-light);
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.btn-delete-player {
    position: absolute;
    top: 5px;
    right: 5px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.player-card:hover .btn-delete-player {
    opacity: 1;
}

.empty-state {
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--color-gray-light);
}

.empty-icon {
    font-size: 48px;
    display: block;
    margin-bottom: var(--spacing-md);
    opacity: 0.5;
}

/* Matches List */
.matches-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.match-row {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    background: var(--color-gray);
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    border-left: 4px solid transparent;
}

.match-finished {
    border-left-color: var(--color-primary);
}

.match-pending {
    border-left-color: var(--color-gray-light);
    opacity: 0.7;
}

.match-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 60px;
    text-align: center;
    background: var(--color-dark);
    padding: var(--spacing-xs);
    border-radius: var(--border-radius-sm);
}

.date-day {
    font-size: var(--font-size-lg);
    font-weight: var(--font-bold);
    color: var(--color-light);
}

.date-month {
    font-size: var(--font-size-xs);
    color: var(--color-primary);
    text-transform: uppercase;
}

.match-time {
    font-size: var(--font-size-sm);
    color: var(--color-gold);
    margin-top: 2px;
}

.match-details {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.teams-display {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    font-weight: 600;
}

.team {
    color: var(--color-light);
}

.team.highlight {
    color: var(--color-primary);
    font-weight: var(--font-bold);
}

.vs {
    color: var(--color-gray-light);
    font-size: var(--font-size-sm);
}

.result-badge {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
}

.score-final {
    font-size: var(--font-size-lg);
    font-weight: var(--font-bold);
    color: var(--color-light);
    background: var(--color-dark);
    padding: 2px 8px;
    border-radius: 4px;
}

.result-text {
    font-size: var(--font-size-xs);
    font-weight: var(--font-bold);
    text-transform: uppercase;
}

.result-win { color: var(--color-primary); }
.result-draw { color: var(--color-gold); }
.result-loss { color: var(--color-secondary); }

.status-badge {
    font-size: var(--font-size-sm);
    padding: 4px 8px;
    border-radius: 4px;
    background: var(--color-dark);
    color: var(--color-gray-light);
}

/* Responsive */
@media (max-width: 768px) {
    .detail-header .header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .back-button {
        align-self: flex-start;
    }
    
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .players-grid {
        grid-template-columns: 1fr;
    }
    
    .match-row {
        flex-direction: column;
        align-items: stretch;
        gap: var(--spacing-sm);
    }
    
    .match-details {
        flex-direction: column;
        align-items: stretch;
        gap: var(--spacing-sm);
    }
    
    .teams-display {
        justify-content: center;
    }
    
    .result-badge {
        align-items: center;
    }
}
</style>