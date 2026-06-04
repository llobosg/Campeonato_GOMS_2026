<?php
/**
 * home.php - Vista Principal
 */
global $pdo;
if (!isset($pdo)) die("Error crítico: No hay conexión a BD.");
$flash = get_flash_message();
?>

<!-- HEADER -->
<div class="championship-header">
    <div class="header-content">
        <div class="copa-animada-container">
            <img src="/assets/images/copa-mundo.png" alt="Copa" class="copa-animada-img">
        </div>
        <div class="header-title">
            <h1>CAMPEONATO MUNDIAL FÚTBOL GOMS 2026 ⚽</h1>
        </div>
        <div class="header-badge">
            <span class="badge-text">FECHA <?= $fecha_actual['nro_fecha'] ?? '-' ?></span>
        </div>
    </div>
</div>

<!-- CONTENIDO PRINCIPAL -->
<div class="main-container">
    <div class="content-left">
        <?php if ($flash): ?><?= render_toast($flash['message'], $flash['type']) ?><?php endif; ?>
        
        <section class="fixture-section">
            <h3 class="section-title"><span class="icon"></span> Fixture del Campeonato</h3>
            
            <div class="fechas-tabs">
                <?php foreach ($fechas as $index => $fecha): ?>
                    <button class="fecha-tab-img <?= $index === 0 ? 'active' : '' ?>" 
                            data-fecha="<?= $fecha['nro_fecha'] ?>"
                            onclick="seleccionarFecha(<?= $fecha['nro_fecha'] ?>)">
                        <img src="/assets/images/fecha<?= $fecha['nro_fecha'] ?>.png" alt="Fecha <?= $fecha['nro_fecha'] ?>" class="fecha-img">
                    </button>
                <?php endforeach; ?>
            </div>
            
            <?php foreach ($fechas as $index => $fecha): ?>
                <div class="fecha-content <?= $index === 0 ? 'active' : '' ?>" id="fecha-<?= $fecha['nro_fecha'] ?>">
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
                            <div class="match-card" 
                                data-id="<?= $partido['id_fixture'] ?>"
                                data-fecha="<?= date('Y-m-d', strtotime($partido['fecha'])) ?>" 
                                data-hora="<?= date('H:i:s', strtotime($partido['hora'])) ?>"
                                data-estado="<?= h($partido['estado']) ?>">
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
                            <div class="match-card" 
                                data-id="<?= $partido['id_fixture'] ?>"
                                data-fecha="<?= date('Y-m-d', strtotime($partido['fecha'])) ?>" 
                                data-hora="<?= date('H:i:s', strtotime($partido['hora'])) ?>"
                                data-estado="<?= h($partido['estado']) ?>">
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
        
        <!-- EQUIPOS (Simplificado para brevedad, mantén tu código original de equipos aquí) -->
        <section class="equipos-section">
             <!-- ... Tu código original de equipos ... -->
        </section>
    </div>
    
    <div class="content-right">
        <!-- POSICIONES Y GOLEADORES (Mantén tu código original aquí) -->
         <!-- ... Tu código original de posiciones y goleadores ... -->
    </div>
</div>

<!-- MODAL RESULTADO -->
<div id="resultadoModal" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-resultado">
        <div class="modal-header">
            <h3>Ingresar Resultado</h3>
            <button class="modal-close" onclick="closeResultadoModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="paso-password">
                <p class="modal-instruction">Contraseña de administrador:</p>
                <input type="password" id="adminPassword" class="input-password" placeholder="...">
                <button class="btn btn-primary" onclick="verificarPassword()">Verificar</button>
                <p id="passwordError" class="error-message" style="display: none;">❌ Incorrecta</p>
            </div>
            
            <div id="paso-goles" style="display: none;">
                <div class="partido-info">
                    <h4 id="modalPartidoTitulo">-</h4>
                    <div class="marcador-preview">
                        <span id="previewEquipoA">-</span> <span class="marcador-numero" id="previewGolesA">0</span>
                        <span>-</span>
                        <span class="marcador-numero" id="previewGolesB">0</span> <span id="previewEquipoB">-</span>
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
                <button class="btn-finalizar" onclick="finalizarPartido()" style="width:100%; margin-top:20px; background:#00ff87; color:black; font-weight:bold; padding:10px; border:none; border-radius:5px; cursor:pointer;">
                    ✅ FINALIZAR PARTIDO
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VIVO -->
<div id="modalVivo" class="modal-overlay modal-vivo-overlay" style="display: none;">
    <div class="modal-content modal-vivo-content">
        <button class="btn-close-vivo" onclick="closeModalVivo()"><span class="close-icon">×</span></button>
        <div class="vivo-header">
            <h2 class="vivo-title">EN VIVO AHORA</h2>
            <div class="vivo-indicator"><span class="dot"></span> LIVE</div>
        </div>
        <div id="vivo-match-container" class="vivo-match-card">
            <div class="vivo-teams-display">
                <div class="vivo-team team-a">
                    <h3 id="vivo-team-a-name">EQUIPO A</h3>
                    <div class="vivo-score" id="vivo-score-a">0</div>
                </div>
                <div class="vivo-vs">VS</div>
                <div class="vivo-team team-b">
                    <h3 id="vivo-team-b-name">EQUIPO B</h3>
                    <div class="vivo-score" id="vivo-score-b">0</div>
                </div>
            </div>
            <div class="vivo-info">
                <p id="vivo-match-time">--:--</p>
                <p id="vivo-match-date">Fecha --</p>
            </div>
            <div class="vivo-scorers-list">
                <h4>Goleadores:</h4>
                <ul id="vivo-scorers-ul"><li>Cargando...</li></ul>
            </div>
        </div>
    </div>
</div>