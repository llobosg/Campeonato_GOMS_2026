<?php
/**
 * equipos_listado.php - Listado de Equipos (Admin)
 * Ubicación: public/views/equipos_listado.php
 * Diseño: FIFA World Cup 2026 Style con gestión de QRs
 */

// Obtener mensaje flash si existe
$flash = get_flash_message();
?>

<!-- ============================================ -->
<!-- HEADER DE ADMINISTRACIÓN -->
<!-- ============================================ -->
<div class="admin-header">
    <div class="header-content">
        <a href="<?= BASE_URL ?>" class="back-button" aria-label="Volver al inicio">
            <span class="back-icon">←</span>
            <span>Volver al Campeonato</span>
        </a>
        
        <div class="header-title">
            <h1>GESTIÓN DE EQUIPOS</h1>
            <h2>⚙️ Panel de Administración</h2>
        </div>
        
        <div class="header-actions">
            <a href="<?= BASE_URL ?>/equipos/crear" class="btn btn-primary btn-create">
                <span class="btn-icon">+</span> Nuevo Equipo
            </a>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- CONTENIDO PRINCIPAL -->
<!-- ============================================ -->
<main class="admin-container">
    
    <!-- FLASH MESSAGE -->
    <?php if ($flash): ?>
        <?= render_toast($flash['message'], $flash['type']) ?>
    <?php endif; ?>
    
    <!-- ESTADÍSTICAS RÁPIDAS -->
    <section class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon"></div>
            <div class="stat-info">
                <h3><?= count($equipos) ?></h3>
                <p>Total Equipos</p>
            </div>
        </div>
        
        <div class="stat-card grupo-a-stat">
            <div class="stat-icon">🅰️</div>
            <div class="stat-info">
                <h3><?= count(array_filter($equipos, fn($e) => $e['grupo'] === 'A')) ?></h3>
                <p>Grupo A</p>
            </div>
        </div>
        
        <div class="stat-card grupo-b-stat">
            <div class="stat-icon">🅱️</div>
            <div class="stat-info">
                <h3><?= count(array_filter($equipos, fn($e) => $e['grupo'] === 'B')) ?></h3>
                <p>Grupo B</p>
            </div>
        </div>
        
        <div class="stat-card qr-stat">
            <div class="stat-icon"></div>
            <div class="stat-info">
                <h3><?= $qr_stats['con_qr'] ?> / <?= $qr_stats['total_equipos'] ?></h3>
                <p>QRs Generados</p>
            </div>
        </div>
    </section>
    
    <!-- FILTROS Y TABLA -->
    <section class="teams-section">
        <div class="section-header">
            <h3 class="section-title">
                <span class="icon">👥</span> Lista de Equipos Registrados
            </h3>
            
            <div class="filter-controls">
                <select id="grupoFilter" class="filter-select" onchange="filtrarEquipos()">
                    <option value="">Todos los Grupos</option>
                    <option value="A">Grupo A</option>
                    <option value="B">Grupo B</option>
                </select>
                
                <button onclick="regenerarTodosQRs()" class="btn btn-secondary btn-small">
                    🔄 Regenerar Todos los QRs
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="admin-table" id="equiposTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Equipo</th>
                        <th>Grupo</th>
                        <th>Jugadores</th>
                        <th>QR Code</th>
                        <th>Link Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($equipos)): ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                No hay equipos registrados. 
                                <a href="<?= BASE_URL ?>/equipos/crear" class="link-action">Crear primer equipo</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($equipos as $equipo): ?>
                            <tr data-grupo="<?= $equipo['grupo'] ?>" data-id="<?= $equipo['id_equipo'] ?>">
                                <td class="text-center">#<?= $equipo['id_equipo'] ?></td>
                                
                                <td>
                                    <div class="team-cell">
                                        <strong><?= h($equipo['nombre']) ?></strong>
                                    </div>
                                </td>
                                
                                <td>
                                    <span class="badge badge-<?= strtolower($equipo['grupo']) ?>">
                                        Grupo <?= $equipo['grupo'] ?>
                                    </span>
                                </td>
                                
                                <td class="text-center">
                                    <span class="player-count"><?= $equipo['total_jugadores'] ?></span>
                                </td>
                                
                                <td class="text-center">
                                    <?php if ($equipo['tiene_qr']): ?>
                                        <button class="btn-icon btn-qr" onclick="verQR(<?= $equipo['id_equipo'] ?>, '<?= h($equipo['nombre']) ?>')" title="Ver QR">
                                            📱
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-icon btn-generate" onclick="generarQR(<?= $equipo['id_equipo'] ?>)" title="Generar QR">
                                            ⚡
                                        </button>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if ($equipo['link_registro']): ?>
                                        <a href="<?= h($equipo['link_registro']) ?>" target="_blank" class="link-copy" title="Copiar link">
                                             Ver Link
                                        </a>
                                    <?php else: ?>
                                        <span class="no-link">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="actions-cell">
                                    <div class="action-buttons">
                                        <!-- Ver Equipo -->
                                        <a href="<?= BASE_URL ?>?page=equipos&action=ver&id=<?= $equipo['id_equipo'] ?>" class="btn-action btn-view" title="Ver detalles">
                                            👁️
                                        </a>
                                        
                                        <!-- Editar Equipo -->
                                        <a href="<?= BASE_URL ?>?page=equipos&action=editar&id=<?= $equipo['id_equipo'] ?>" class="btn-action btn-edit" title="Editar">
                                            ✏️
                                        </a>
                                        
                                        <button onclick="eliminarEquipo(<?= $equipo['id_equipo'] ?>, '<?= h($equipo['nombre']) ?>')" 
                                                class="btn-action btn-delete" 
                                                title="Eliminar"
                                                <?= $equipo['total_jugadores'] > 0 ? 'disabled' : '' ?>>
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- ============================================ -->
<!-- MODAL VER QR -->
<!-- ============================================ -->
<div id="qrModal" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-qr">
        <div class="modal-header">
            <h3 id="qrModalTitle">QR Code</h3>
            <button class="modal-close" onclick="closeQRModal()">&times;</button>
        </div>
        
        <div class="modal-body text-center">
            <img id="qrImage" src="" alt="QR Code" class="qr-modal-image">
            
            <div class="qr-actions">
                <a id="qrDownloadLink" href="#" download class="btn btn-primary">
                    ️ Descargar QR
                </a>
                
                <button onclick="copiarLinkRegistro()" class="btn btn-secondary">
                    📋 Copiar Link
                </button>
            </div>
            
            <p id="qrUrlText" class="qr-url-text"></p>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT ESPECÍFICO -->
<!-- ============================================ -->
<script>
let currentTeamId = null;
let currentTeamName = '';
let currentTeamLink = '';

// Filtrar equipos por grupo
function filtrarEquipos() {
    const filtro = document.getElementById('grupoFilter').value;
    const filas = document.querySelectorAll('#equiposTable tbody tr');
    
    filas.forEach(fila => {
        const grupo = fila.dataset.grupo;
        
        if (!filtro || grupo === filtro) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    });
}

// Ver QR en modal
function verQR(teamId, teamName) {
    currentTeamId = teamId;
    currentTeamName = teamName;
    
    const qrUrl = `${BASE_URL}/uploads/qrs/team_${teamId}.png`;
    const linkRegistro = `${BASE_URL}/jugadores/registrar/${teamId}`;
    
    document.getElementById('qrModalTitle').textContent = `QR - ${teamName}`;
    document.getElementById('qrImage').src = qrUrl;
    document.getElementById('qrDownloadLink').href = qrUrl;
    document.getElementById('qrUrlText').textContent = linkRegistro;
    currentTeamLink = linkRegistro;
    
    document.getElementById('qrModal').style.display = 'flex';
}

// Cerrar modal QR
function closeQRModal() {
    document.getElementById('qrModal').style.display = 'none';
}

// Copiar link al portapapeles
function copiarLinkRegistro() {
    navigator.clipboard.writeText(currentTeamLink).then(() => {
        showToast('✅ Link copiado al portapapeles', 'success');
    }).catch(err => {
        showToast('❌ Error al copiar link', 'error');
    });
}

// Generar QR individual
async function generarQR(teamId) {
    if (!confirm('¿Deseas regenerar el QR para este equipo?')) return;
    
    try {
        showToast('⏳ Generando QR...', 'info');
        
        const response = await fetch(`${BASE_URL}/equipos/generar-qr/${teamId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ QR generado exitosamente', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('❌ Error: ' + (data.error || 'No se pudo generar'), 'error');
        }
    } catch (error) {
        console.error('Error generando QR:', error);
        showToast('❌ Error de conexión', 'error');
    }
}

// Regenerar todos los QRs
async function regenerarTodosQRs() {
    if (!confirm('️ ¿Estás seguro de regenerar TODOS los QRs? Esto puede tardar unos segundos.')) return;
    
    try {
        showToast('⏳ Regenerando todos los QRs...', 'info');
        
        // Nota: Implementar endpoint bulk en ApiController si es necesario
        // Por ahora, recargamos la página para que el usuario vea los cambios
        // En una versión futura, se puede hacer un loop AJAX
        
        showToast('️ Función en desarrollo. Regenera manualmente cada QR.', 'warning');
        
    } catch (error) {
        showToast('❌ Error', 'error');
    }
}

// Eliminar equipo
async function eliminarEquipo(teamId, teamName) {
    if (!confirm(`️ ¿Estás seguro de eliminar el equipo "${teamName}"?\n\nEsta acción no se puede deshacer.`)) return;
    
    try {
        showToast('⏳ Eliminando equipo...', 'info');
        
        const response = await fetch(`${BASE_URL}/equipos/eliminar/${teamId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Equipo eliminado exitosamente', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('❌ Error: ' + (data.error || 'No se pudo eliminar'), 'error');
        }
    } catch (error) {
        console.error('Error eliminando equipo:', error);
        showToast('❌ Error de conexión', 'error');
    }
}

// Cerrar modal al hacer click fuera
document.getElementById('qrModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeQRModal();
    }
});
</script>

<!-- ============================================ -->
<!-- CSS ADICIONAL ESPECÍFICO -->
<!-- ============================================ -->
<style>
/* Header Admin */
.admin-header {
    background: var(--gradient-header);
    padding: var(--spacing-md) var(--spacing-lg);
    box-shadow: var(--shadow-lg);
}

.admin-header .header-content {
    max-width: 1400px;
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

.btn-create {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

/* Stats Cards */
.stats-cards {
    max-width: 1400px;
    margin: var(--spacing-lg) auto;
    padding: 0 var(--spacing-md);
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
}

.stat-card {
    background: var(--gradient-card);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius-lg);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    box-shadow: var(--shadow-md);
    border: 1px solid rgba(0, 255, 135, 0.2);
}

.stat-icon {
    font-size: 48px;
}

.stat-info h3 {
    font-size: var(--font-size-xxl);
    color: var(--color-primary);
    margin-bottom: var(--spacing-xs);
}

.stat-info p {
    font-size: var(--font-size-sm);
    color: var(--color-gray-light);
}

.grupo-a-stat .stat-info h3 { color: var(--grupo-a); }
.grupo-b-stat .stat-info h3 { color: var(--grupo-b); }

/* Teams Section */
.admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--spacing-md) var(--spacing-xl);
}

.teams-section {
    background: var(--gradient-card);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid rgba(0, 255, 135, 0.2);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-lg);
    flex-wrap: wrap;
    gap: var(--spacing-md);
}

.filter-controls {
    display: flex;
    gap: var(--spacing-sm);
}

.filter-select {
    padding: var(--spacing-sm) var(--spacing-md);
    background: var(--color-gray);
    border: 2px solid var(--color-gray-light);
    border-radius: var(--border-radius);
    color: var(--color-light);
}

/* Tabla Admin */
.table-responsive {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--font-size-sm);
}

.admin-table thead {
    background: var(--color-gray);
}

.admin-table th {
    padding: var(--spacing-md);
    text-align: left;
    font-weight: 600;
    color: var(--color-primary);
    border-bottom: 2px solid var(--color-primary);
    white-space: nowrap;
}

.admin-table td {
    padding: var(--spacing-md);
    border-bottom: 1px solid rgba(255,255,255,0.05);
    vertical-align: middle;
}

.admin-table tr:hover {
    background: rgba(0, 255, 135, 0.05);
}

.text-center { text-align: center; }

.badge {
    display: inline-block;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-sm);
    font-weight: 600;
}

.badge-a { background: rgba(0, 255, 135, 0.2); color: var(--grupo-a); }
.badge-b { background: rgba(255, 0, 110, 0.2); color: var(--grupo-b); }

.player-count {
    font-size: var(--font-size-lg);
    font-weight: var(--font-bold);
    color: var(--color-light);
}

.btn-icon {
    background: none;
    border: none;
    font-size: var(--font-size-lg);
    cursor: pointer;
    padding: var(--spacing-xs);
    transition: transform 0.2s ease;
}

.btn-icon:hover { transform: scale(1.2); }

.link-copy {
    color: var(--color-accent);
    text-decoration: none;
    font-size: var(--font-size-sm);
}

.link-copy:hover { text-decoration: underline; }

.no-link { color: var(--color-gray-light); font-style: italic; }

.action-buttons {
    display: flex;
    gap: var(--spacing-xs);
}

.btn-action {
    background: var(--color-gray);
    border: none;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-sm);
    cursor: pointer;
    font-size: var(--font-size-base);
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-action:hover:not(:disabled) {
    transform: scale(1.1);
    box-shadow: var(--shadow-sm);
}

.btn-view { color: var(--color-accent); }
.btn-edit { color: var(--color-gold); }
.btn-delete { color: var(--color-secondary); }
.btn-delete:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.no-data {
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--color-gray-light);
}

.link-action {
    color: var(--color-primary);
    text-decoration: underline;
}

/* Modal QR */
.modal-qr {
    max-width: 500px;
}

.qr-modal-image {
    width: 250px;
    height: 250px;
    border-radius: var(--border-radius);
    border: 2px solid var(--color-primary);
    margin-bottom: var(--spacing-lg);
}

.qr-actions {
    display: flex;
    gap: var(--spacing-sm);
    justify-content: center;
    margin-bottom: var(--spacing-md);
}

.qr-url-text {
    font-size: var(--font-size-sm);
    color: var(--color-gray-light);
    word-break: break-all;
    background: var(--color-gray);
    padding: var(--spacing-sm);
    border-radius: var(--border-radius-sm);
}

/* Responsive */
@media (max-width: 768px) {
    .admin-header .header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .header-title { order: -1; margin-bottom: var(--spacing-md); }
    
    .stats-cards {
        grid-template-columns: 1fr 1fr;
    }
    
    .section-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .admin-table {
        font-size: var(--font-size-xs);
    }
    
    .admin-table th,
    .admin-table td {
        padding: var(--spacing-sm);
    }
}
</style>