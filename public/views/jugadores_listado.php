<?php
/**
 * jugadores_listado.php - Listado Global de Jugadores (Admin)
 * Ubicación: public/views/jugadores_listado.php
 * Diseño: FIFA World Cup 2026 Style
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
            <h1>GESTIÓN DE JUGADORES</h1>
            <h2>👥 Panel de Administración</h2>
        </div>
        
        <div class="header-actions">
            <a href="<?= BASE_URL ?>/equipos/listar" class="btn btn-secondary btn-small">
                 Ver Equipos
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
                <h3><?= count($jugadores) ?></h3>
                <p>Total Jugadores</p>
            </div>
        </div>
        
        <div class="stat-card grupo-a-stat">
            <div class="stat-icon">🅰️</div>
            <div class="stat-info">
                <h3><?= count(array_filter($jugadores, fn($j) => $j['grupo'] === 'A')) ?></h3>
                <p>Grupo A</p>
            </div>
        </div>
        
        <div class="stat-card grupo-b-stat">
            <div class="stat-icon">🅱️</div>
            <div class="stat-info">
                <h3><?= count(array_filter($jugadores, fn($j) => $j['grupo'] === 'B')) ?></h3>
                <p>Grupo B</p>
            </div>
        </div>
    </section>
    
    <!-- FILTROS Y TABLA -->
    <section class="players-section">
        <div class="section-header">
            <h3 class="section-title">
                <span class="icon"></span> Lista de Jugadores Registrados
            </h3>
            
            <div class="filter-controls">
                <select id="equipoFilter" class="filter-select" onchange="filtrarJugadores()">
                    <option value="">Todos los Equipos</option>
                    <?php 
                    // Obtener lista única de equipos para el filtro
                    $equipos_unicos = [];
                    foreach ($jugadores as $j) {
                        if (!isset($equipos_unicos[$j['id_equipo']])) {
                            $equipos_unicos[$j['id_equipo']] = $j['equipo_nombre'];
                        }
                    }
                    ksort($equipos_unicos);
                    foreach ($equipos_unicos as $id => $nombre): 
                    ?>
                        <option value="<?= $id ?>"><?= h($nombre) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" id="searchInput" class="filter-input" placeholder="Buscar jugador..." onkeyup="filtrarJugadores()">
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="admin-table" id="jugadoresTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Jugador</th>
                        <th>Correo</th>
                        <th>Área/Posición</th>
                        <th>Equipo</th>
                        <th>Grupo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jugadores)): ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                No hay jugadores registrados aún.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jugadores as $jugador): ?>
                            <tr data-equipo="<?= $jugador['id_equipo'] ?>" data-nombre="<?= strtolower(h($jugador['nombre'])) ?>">
                                <td class="text-center">#<?= $jugador['id_jugador'] ?></td>
                                
                                <td>
                                    <div class="player-cell">
                                        <strong><?= h($jugador['nombre']) ?></strong>
                                    </div>
                                </td>
                                
                                <td>
                                    <a href="mailto:<?= h($jugador['correo']) ?>" class="link-email">
                                        <?= h($jugador['correo']) ?>
                                    </a>
                                </td>
                                
                                <td>
                                    <span class="badge-area">
                                        <?= h($jugador['area'] ?? '-') ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <span class="team-name"><?= h($jugador['equipo_nombre']) ?></span>
                                </td>
                                
                                <td>
                                    <span class="badge badge-<?= strtolower($jugador['grupo']) ?>">
                                        Grupo <?= $jugador['grupo'] ?>
                                    </span>
                                </td>
                                
                                <td class="actions-cell">
                                    <div class="action-buttons">
                                        <button onclick="eliminarJugador(<?= $jugador['id_jugador'] ?>, '<?= h($jugador['nombre']) ?>')" 
                                                class="btn-action btn-delete" 
                                                title="Eliminar Jugador">
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
<!-- JAVASCRIPT ESPECÍFICO -->
<!-- ============================================ -->
<script>
// Filtrar jugadores por equipo y búsqueda de texto
function filtrarJugadores() {
    const equipoId = document.getElementById('equipoFilter').value;
    const searchText = document.getElementById('searchInput').value.toLowerCase();
    const filas = document.querySelectorAll('#jugadoresTable tbody tr');
    
    filas.forEach(fila => {
        const filaEquipo = fila.dataset.equipo;
        const filaNombre = fila.dataset.nombre;
        
        const coincideEquipo = !equipoId || filaEquipo === equipoId;
        const coincideTexto = !searchText || filaNombre.includes(searchText);
        
        if (coincideEquipo && coincideTexto) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    });
}

// Eliminar jugador
async function eliminarJugador(jugadorId, nombre) {
    if (!confirm(`⚠️ ¿Estás seguro de eliminar a "${nombre}"?\n\nEsta acción no se puede deshacer.`)) return;
    
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
            showToast('✅ Jugador eliminado exitosamente', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('❌ Error: ' + (data.error || 'No se pudo eliminar'), 'error');
        }
    } catch (error) {
        console.error('Error eliminando jugador:', error);
        showToast(' Error de conexión', 'error');
    }
}
</script>

<!-- ============================================ -->
<!-- CSS ADICIONAL ESPECÍFICO -->
<!-- ============================================ -->
<style>
/* Reutilizamos estilos de admin-header y stats-cards de equipos_listado.php */
/* Aquí agregamos específicos de jugadores */

.filter-input {
    padding: var(--spacing-sm) var(--spacing-md);
    background: var(--color-gray);
    border: 2px solid var(--color-gray-light);
    border-radius: var(--border-radius);
    color: var(--color-light);
    min-width: 200px;
}

.filter-input:focus {
    outline: none;
    border-color: var(--color-primary);
}

.badge-area {
    display: inline-block;
    padding: 2px 8px;
    background: var(--color-dark);
    border-radius: 4px;
    font-size: var(--font-size-xs);
    color: var(--color-gray-light);
}

.link-email {
    color: var(--color-accent);
    text-decoration: none;
    font-size: var(--font-size-sm);
}

.link-email:hover {
    text-decoration: underline;
}

.team-name {
    font-weight: 600;
    color: var(--color-light);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .filter-controls {
        flex-direction: column;
        width: 100%;
    }
    
    .filter-select, .filter-input {
        width: 100%;
    }
}
</style>