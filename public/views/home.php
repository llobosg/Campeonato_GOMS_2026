<?php
/**
 * home.php - Vista Principal
 */

// Asegurar que $pdo esté disponible (viene del controller o global)
global $pdo;
if (!isset($pdo)) {
    die("Error crítico: No hay conexión a BD.");
}

$flash = get_flash_message();
?>

<!-- ============================================ -->
<!-- HEADER CON LOGO -->
<!-- ============================================ -->
<div class="championship-header">
    <div class="header-content">
        <div class="copa-animada-container">
        <img src="/assets/images/copa-mundo.png" alt="Copa Fútbol Mundial GOMS 2026" class="copa-animada-img">
    </div>
        <div class="header-title">
            <h1>CAMPEONATO MUNDIAL FÚTBOL GOMS</h1>
            <h2>2026 ⚽</h2>
        </div>
        <div class="header-badge">
            <span class="badge-text">FECHA <?= $fecha_actual['nro_fecha'] ?? '-' ?></span>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- CONTENIDO PRINCIPAL (80/20) -->
<!-- ============================================ -->
<div class="main-container">
    
    <!-- SECCIÓN IZQUIERDA (80%) - FIXTURE Y EQUIPOS -->
    <div class="content-left">
        
        <!-- FLASH MESSAGE -->
        <?php if ($flash): ?>
            <?= render_toast($flash['message'], $flash['type']) ?>
        <?php endif; ?>
        
        <!-- FIXTURE POR FECHAS -->
        <section class="fixture-section">
            <h3 class="section-title">
                <span class="icon"></span> Fixture del Campeonato
            </h3>
            
            <div class="fechas-tabs">
                <?php foreach ($fechas as $index => $fecha): ?>
                    <button class="fecha-tab-img <?= $index === 0 ? 'active' : '' ?>" 
                            data-fecha="<?= $fecha['nro_fecha'] ?>"
                            onclick="seleccionarFecha(<?= $fecha['nro_fecha'] ?>)">
                        <!-- Usamos la imagen correspondiente al número de fecha -->
                        <img src="/assets/images/fecha<?= $fecha['nro_fecha'] ?>.png" 
                            alt="Fecha <?= $fecha['nro_fecha'] ?>" 
                            class="fecha-img">
                    </button>
                <?php endforeach; ?>
            </div>
            
            <?php foreach ($fechas as $index => $fecha): ?>
                <div class="fecha-content <?= $index === 0 ? 'active' : '' ?>" 
                     id="fecha-<?= $fecha['nro_fecha'] ?>">
                    
                    <div class="fecha-header">
                        <h4>Fecha <?= $fecha['nro_fecha'] ?> - <?= format_date($fecha['fecha']) ?></h4>
                        <span class="hora-range"><?= format_time($fecha['hora_inicio']) ?> - <?= format_time($fecha['hora_fin']) ?></span>
                    </div>
                    
                    <?php
                    $partidos = get_fixture_fecha($pdo, $fecha['nro_fecha']);
                    $partidosA = array_filter($partidos, fn($p) => $p['grupo_a'] === 'A');
                    $partidosB = array_filter($partidos, fn($p) => $p['grupo_b'] === 'B');
                    ?>
                    
                    <!-- GRUPO A -->
                    <div class="grupo-matches grupo-a">
                        <h5 class="grupo-title">GRUPO A</h5>
                        <?php foreach ($partidosA as $partido): ?>
                            <div class="match-card" data-id="<?= $partido['id_fixture'] ?>">
                                <div class="match-time"><?= format_time($partido['hora']) ?></div>
                                <div class="match-teams">
                                    <span class="team team-home"><?= h($partido['nombre_equipo_a']) ?></span>
                                    <span class="vs">VS</span>
                                    <span class="team team-away"><?= h($partido['nombre_equipo_b']) ?></span>
                                </div>
                                <div class="match-result">
                                    <?php if ($partido['estado'] === 'finalizado'): ?>
                                        <span class="score score-a"><?= $partido['goles_a'] ?></span>
                                        <span class="score-divider">-</span>
                                        <span class="score score-b"><?= $partido['goles_b'] ?></span>
                                    <?php else: ?>
                                        <span class="status-pendiente">Pendiente</span>
                                    <?php endif; ?>
                                </div>
                                <button class="btn-resultado" onclick="openResultadoModal(<?= $partido['id_fixture'] ?>)">
                                    📝 Resultado
                                </button>

                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- GRUPO B -->
                    <div class="grupo-matches grupo-b">
                        <h5 class="grupo-title">GRUPO B</h5>
                        <?php foreach ($partidosB as $partido): ?>
                            <div class="match-card" data-id="<?= $partido['id_fixture'] ?>">
                                <div class="match-time"><?= format_time($partido['hora']) ?></div>
                                <div class="match-teams">
                                    <span class="team team-home"><?= h($partido['nombre_equipo_a']) ?></span>
                                    <span class="vs">VS</span>
                                    <span class="team team-away"><?= h($partido['nombre_equipo_b']) ?></span>
                                </div>
                                <div class="match-result">
                                    <?php if ($partido['estado'] === 'finalizado'): ?>
                                        <span class="score score-a"><?= $partido['goles_a'] ?></span>
                                        <span class="score-divider">-</span>
                                        <span class="score score-b"><?= $partido['goles_b'] ?></span>
                                    <?php else: ?>
                                        <span class="status-pendiente">Pendiente</span>
                                    <?php endif; ?>
                                </div>
                                <button class="btn-resultado" onclick="openResultadoModal(<?= $partido['id_fixture'] ?>)">
                                    📝 Resultado
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
        
        <!-- EQUIPOS POR GRUPO -->
        <section class="equipos-section">
            <h3 class="section-title">
                <span class="icon">👥</span> Equipos Participantes
            </h3>
            
            <div class="equipos-grid">
                <!-- GRUPO A -->
                <div class="equipo-column grupo-a">
                    <h4 class="grupo-header">GRUPO A</h4>
                    <?php
                    $equiposA = db_fetch_all($pdo, "SELECT * FROM equipos WHERE grupo = 'A' ORDER BY nombre ASC");
                    foreach ($equiposA as $equipo):
                        $jugadores = db_fetch_all($pdo, "SELECT * FROM jugadores WHERE id_equipo = ? ORDER BY nombre ASC", [$equipo['id_equipo']]);
                    ?>
                        <div class="equipo-card">
                            <div class="equipo-name"><?= h($equipo['nombre']) ?></div>
                            <div class="jugadores-list">
                                <?php if (empty($jugadores)): ?>
                                    <small class="no-jugadores">Sin jugadores registrados</small>
                                <?php else: ?>
                                    <?php foreach ($jugadores as $jugador): ?>
                                        <div class="jugador-item">
                                            <span class="jugador-nombre"><?= h($jugador['nombre']) ?></span>
                                            <span class="jugador-area"><?= h($jugador['area'] ?? '-') ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- GRUPO B -->
                <div class="equipo-column grupo-b">
                    <h4 class="grupo-header">GRUPO B</h4>
                    <?php
                    $equiposB = db_fetch_all($pdo, "SELECT * FROM equipos WHERE grupo = 'B' ORDER BY nombre ASC");
                    foreach ($equiposB as $equipo):
                        $jugadores = db_fetch_all($pdo, "SELECT * FROM jugadores WHERE id_equipo = ? ORDER BY nombre ASC", [$equipo['id_equipo']]);
                    ?>
                        <div class="equipo-card">
                            <div class="equipo-name"><?= h($equipo['nombre']) ?></div>
                            <div class="jugadores-list">
                                <?php if (empty($jugadores)): ?>
                                    <small class="no-jugadores">Sin jugadores registrados</small>
                                <?php else: ?>
                                    <?php foreach ($jugadores as $jugador): ?>
                                        <div class="jugador-item">
                                            <span class="jugador-nombre"><?= h($jugador['nombre']) ?></span>
                                            <span class="jugador-area"><?= h($jugador['area'] ?? '-') ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>
    
    <!-- SECCIÓN DERECHA (20%) - POSICIONES Y GOLEADORES -->
    <div class="content-right">
        
        <!-- TABLA DE POSICIONES -->
        <section class="posiciones-section">
            <h3 class="section-title">
                <span class="icon">🏆</span> Posiciones
            </h3>
            
            <!-- GRUPO A -->
            <div class="tabla-grupo">
                <h4 class="grupo-mini-header grupo-a">GRUPO A</h4>
                <table class="tabla-posiciones">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Equipo</th>
                            <th>PJ</th>
                            <th>G</th>
                            <th>E</th>
                            <th>P</th>
                            <th>GF</th>
                            <th>GC</th>
                            <th>Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posicionesA as $idx => $equipo): ?>
                            <tr class="<?= $idx === 0 ? 'first-place' : '' ?>">
                                <td class="position"><?= $idx + 1 ?></td>
                                <td class="team-name"><?= h($equipo['equipo']) ?></td>
                                <td><?= $equipo['ganados'] + $equipo['empatados'] + $equipo['perdidos'] ?></td>
                                <td><?= $equipo['ganados'] ?></td>
                                <td><?= $equipo['empatados'] ?></td>
                                <td><?= $equipo['perdidos'] ?></td>
                                <td><?= $equipo['goles_favor'] ?></td>
                                <td><?= $equipo['goles_contra'] ?></td>
                                <td class="puntos"><?= $equipo['puntos'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- GRUPO B -->
            <div class="tabla-grupo">
                <h4 class="grupo-mini-header grupo-b">GRUPO B</h4>
                <table class="tabla-posiciones">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Equipo</th>
                            <th>PJ</th>
                            <th>G</th>
                            <th>E</th>
                            <th>P</th>
                            <th>GF</th>
                            <th>GC</th>
                            <th>Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posicionesB as $idx => $equipo): ?>
                            <tr class="<?= $idx === 0 ? 'first-place' : '' ?>">
                                <td class="position"><?= $idx + 1 ?></td>
                                <td class="team-name"><?= h($equipo['equipo']) ?></td>
                                <td><?= $equipo['ganados'] + $equipo['empatados'] + $equipo['perdidos'] ?></td>
                                <td><?= $equipo['ganados'] ?></td>
                                <td><?= $equipo['empatados'] ?></td>
                                <td><?= $equipo['perdidos'] ?></td>
                                <td><?= $equipo['goles_favor'] ?></td>
                                <td><?= $equipo['goles_contra'] ?></td>
                                <td class="puntos"><?= $equipo['puntos'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        
        <!-- GOLEADORES -->
        <section class="goleadores-section">
            <h3 class="section-title">
                <span class="icon">⚽</span> Goleadores
            </h3>
            
            <!-- GRUPO A -->
            <div class="goleadores-grupo">
                <h4 class="grupo-mini-header grupo-a">GRUPO A</h4>
                <?php if (empty($goleadoresA)): ?>
                    <p class="no-data">Aún no hay goles registrados</p>
                <?php else: ?>
                    <?php foreach ($goleadoresA as $idx => $goleador): ?>
                        <div class="goleador-item <?= $idx === 0 ? 'top-scorer' : '' ?>">
                            <span class="goleador-rank"><?= $idx + 1 ?>.</span>
                            <div class="goleador-info">
                                <span class="goleador-nombre"><?= h($goleador['jugador']) ?></span>
                                <span class="goleador-equipo"><?= h($goleador['equipo']) ?></span>
                            </div>
                            <span class="goleador-goles"><?= $goleador['goles'] ?> ⚽</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- GRUPO B -->
            <div class="goleadores-grupo">
                <h4 class="grupo-mini-header grupo-b">GRUPO B</h4>
                <?php if (empty($goleadoresB)): ?>
                    <p class="no-data">Aún no hay goles registrados</p>
                <?php else: ?>
                    <?php foreach ($goleadoresB as $idx => $goleador): ?>
                        <div class="goleador-item <?= $idx === 0 ? 'top-scorer' : '' ?>">
                            <span class="goleador-rank"><?= $idx + 1 ?>.</span>
                            <div class="goleador-info">
                                <span class="goleador-nombre"><?= h($goleador['jugador']) ?></span>
                                <span class="goleador-equipo"><?= h($goleador['equipo']) ?></span>
                            </div>
                            <span class="goleador-goles"><?= $goleador['goles'] ?> ⚽</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- BOTÓN MARCADOR EN VIVO -->
        <button class="btn-vivo" onclick="window.location.href='<?= BASE_URL ?>/vivo'">
             RESULTADOS EN VIVO
        </button>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL INGRESO DE RESULTADO -->
<!-- ============================================ -->
<div id="resultadoModal" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-resultado">
        <div class="modal-header">
            <h3>Ingresar Resultado</h3>
            <button class="modal-close" onclick="closeResultadoModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <!-- Paso 1: Contraseña Admin -->
            <div id="paso-password">
                <p class="modal-instruction">Ingrese contraseña de administrador para registrar resultado:</p>
                <input type="password" id="adminPassword" class="input-password" placeholder="Contraseña...">
                <button class="btn btn-primary" onclick="verificarPassword()">Verificar</button>
                <p id="passwordError" class="error-message" style="display: none;"> Contraseña incorrecta</p>
            </div>
            
            <!-- Paso 2: Ingreso de Goles -->
            <div id="paso-goles" style="display: none;">
                <div class="partido-info">
                    <h4 id="modalPartidoTitulo">-</h4>
                    <div class="marcador-preview">
                        <span id="previewEquipoA">-</span>
                        <span class="marcador-numero" id="previewGolesA">0</span>
                        <span>-</span>
                        <span class="marcador-numero" id="previewGolesB">0</span>
                        <span id="previewEquipoB">-</span>
                    </div>
                </div>
                
                <div class="goles-input-section">
                    <div class="equipo-goles equipo-a">
                        <h5 id="tituloEquipoA">Equipo A</h5>
                        <div id="listaJugadoresA" class="jugadores-goles-list"></div>
                    </div>
                    
                    <div class="equipo-goles equipo-b">
                        <h5 id="tituloEquipoB">Equipo B</h5>
                        <div id="listaJugadoresB" class="jugadores-goles-list"></div>
                    </div>
                </div>
                
                <button class="btn btn-success btn-finalizar" onclick="finalizarPartido()">
                    ✅ Finalizar Partido
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT INLINE (Se moverá a app.js después) -->
<!-- ============================================ -->
<script>
let currentFixtureId = null;
let currentPartidoData = null;

// Tabs de fechas
document.querySelectorAll('.fecha-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.fecha-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.fecha-content').forEach(c => c.classList.remove('active'));
        
        this.classList.add('active');
        const fechaNum = this.dataset.fecha;
        document.getElementById(`fecha-${fechaNum}`).classList.add('active');
    });
});

// Abrir modal de resultado
function openResultadoModal(fixtureId) {
    currentFixtureId = fixtureId;
    document.getElementById('resultadoModal').style.display = 'flex';
    document.getElementById('paso-password').style.display = 'block';
    document.getElementById('paso-goles').style.display = 'none';
    document.getElementById('adminPassword').value = '';
    document.getElementById('passwordError').style.display = 'none';
}

// Cerrar modal
function closeResultadoModal() {
    document.getElementById('resultadoModal').style.display = 'none';
    currentFixtureId = null;
}

// Verificar contraseña admin
async function verificarPassword() {
    const password = document.getElementById('adminPassword').value;
    
    const response = await fetch(`${BASE_URL}/api/resultado/verificar`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({password: password})
    });
    
    const data = await response.json();
    
    if (data.success) {
        document.getElementById('paso-password').style.display = 'none';
        document.getElementById('paso-goles').style.display = 'block';
        cargarJugadoresPartido(currentFixtureId);
    } else {
        document.getElementById('passwordError').style.display = 'block';
    }
}

// Cargar jugadores del partido
async function cargarJugadoresPartido(fixtureId) {
    const response = await fetch(`${BASE_URL}/api/fixture/${fixtureId}`);
    const data = await response.json();
    
    if (data.success) {
        currentPartidoData = data.data;
        
        // Actualizar títulos
        document.getElementById('modalPartidoTitulo').textContent = 
            `Fecha ${currentPartidoData.nro_fecha} - ${formatDate(currentPartidoData.fecha)} ${currentPartidoData.hora.substring(0,5)}`;
        document.getElementById('previewEquipoA').textContent = currentPartidoData.nombre_equipo_a;
        document.getElementById('previewEquipoB').textContent = currentPartidoData.nombre_equipo_b;
        document.getElementById('tituloEquipoA').textContent = currentPartidoData.nombre_equipo_a;
        document.getElementById('tituloEquipoB').textContent = currentPartidoData.nombre_equipo_b;
        
        // Cargar jugadores
        await cargarListaJugadores(currentPartidoData.equipo_a, 'A');
        await cargarListaJugadores(currentPartidoData.equipo_b, 'B');
    }
}

// Cargar lista de jugadores con controles +/-
async function cargarListaJugadores(equipoId, lado) {
    const response = await fetch(`${BASE_URL}/api/jugadores?equipo=${equipoId}`);
    const data = await response.json();
    
    const container = document.getElementById(`listaJugadores${lado}`);
    container.innerHTML = '';
    
    if (data.success && data.data.length > 0) {
        data.data.forEach(jugador => {
            const div = document.createElement('div');
            div.className = 'jugador-gol-item';
            div.innerHTML = `
                <span class="jugador-nombre">${jugador.nombre}</span>
                <div class="gol-controls">
                    <button class="btn-minus" onclick="restarGol(${jugador.id_jugador}, '${lado}')">−</button>
                    <span class="gol-count" id="gol-${jugador.id_jugador}">0</span>
                    <button class="btn-plus" onclick="sumarGol(${jugador.id_jugador}, '${lado}')">+</button>
                </div>
            `;
            container.appendChild(div);
        });
    } else {
        container.innerHTML = '<p class="no-jugadores">No hay jugadores registrados</p>';
    }
}

// Sumar gol
function sumarGol(jugadorId, lado) {
    const element = document.getElementById(`gol-${jugadorId}`);
    let count = parseInt(element.textContent) || 0;
    element.textContent = count + 1;
    actualizarMarcadorPreview();
    animateNumber(element);
}

// Restar gol
function restarGol(jugadorId, lado) {
    const element = document.getElementById(`gol-${jugadorId}`);
    let count = parseInt(element.textContent) || 0;
    if (count > 0) {
        element.textContent = count - 1;
        actualizarMarcadorPreview();
    }
}

// Actualizar marcador preview
function actualizarMarcadorPreview() {
    const golesA = Array.from(document.querySelectorAll('#listaJugadoresA .gol-count'))
        .reduce((sum, el) => sum + parseInt(el.textContent || 0), 0);
    const golesB = Array.from(document.querySelectorAll('#listaJugadoresB .gol-count'))
        .reduce((sum, el) => sum + parseInt(el.textContent || 0), 0);
    
    document.getElementById('previewGolesA').textContent = golesA;
    document.getElementById('previewGolesB').textContent = golesB;
}

// Animación de números
function animateNumber(element) {
    element.classList.add('number-pop');
    setTimeout(() => element.classList.remove('number-pop'), 300);
}

// Finalizar partido
async function finalizarPartido() {
    if (!confirm('¿Estás seguro de finalizar este partido? El resultado quedará registrado.')) {
        return;
    }
    
    // Recolectar goles
    const goles = [];
    
    document.querySelectorAll('#listaJugadoresA .jugador-gol-item').forEach(item => {
        const jugadorId = item.querySelector('.btn-plus').getAttribute('onclick').match(/\d+/)[0];
        const golesCount = parseInt(item.querySelector('.gol-count').textContent) || 0;
        
        for (let i = 0; i < golesCount; i++) {
            goles.push({
                id_jugador: parseInt(jugadorId),
                minuto: null
            });
        }
    });
    
    document.querySelectorAll('#listaJugadoresB .jugador-gol-item').forEach(item => {
        const jugadorId = item.querySelector('.btn-plus').getAttribute('onclick').match(/\d+/)[0];
        const golesCount = parseInt(item.querySelector('.gol-count').textContent) || 0;
        
        for (let i = 0; i < golesCount; i++) {
            goles.push({
                id_jugador: parseInt(jugadorId),
                minuto: null
            });
        }
    });
    
    // Enviar al backend
    const response = await fetch(`${BASE_URL}/api/resultado/ingresar`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            id_fixture: currentFixtureId,
            goles: goles
        })
    });
    
    const data = await response.json();
    
    if (data.success) {
        showToast('✅ Resultado registrado exitosamente', 'success');
        closeResultadoModal();
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast('❌ Error: ' + data.error, 'error');
    }
}

// Helper: Formatear fecha
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-CL', {day: '2-digit', month: '2-digit', year: 'numeric'});
}

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${type === 'success' ? '✅' : '❌'}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('toast-exit');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Cerrar modal al hacer click fuera
document.getElementById('resultadoModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeResultadoModal();
    }
});
</script>