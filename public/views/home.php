<?php
/**
 * home.php - Vista Principal Completa
 */
global $pdo;
if (!isset($pdo)) die("Error crítico: No hay conexión a BD.");
$flash = get_flash_message();
?>

<!-- ============================================ -->
<!-- HEADER CON LOGO, TÍTULO Y BALÓN -->
<!-- ============================================ -->
<div class="championship-header">
    <div class="header-content">
        
        <!-- Copa a la izquierda -->
        <div class="copa-animada-container">
            <img src="/assets/images/copa-mundo.png" alt="Copa Fútbol Mundial GOMS 2026" class="copa-animada-img">
        </div>

        <!-- Grupo Central: Título + Balón -->
        <div class="header-text-group">
            <div class="header-title">
                <h1>CAMPEONATO MUNDIAL FÚTBOL GOMS 2026</h1>
            </div>
            
            <!-- Balón FIFA 2026 Giratorio -->
            <div class="balon-container">
                <img src="/assets/images/balonfifa2026.png" alt="Balón FIFA 2026" class="balon-animado-img">
            </div>
        </div>

        <div class="header-badge">
            <span class="badge-text">FECHA <?= $fecha_actual['nro_fecha'] ?? '-' ?></span>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- CONTENIDO PRINCIPAL -->
<!-- ============================================ -->
<div class="main-container">
    
    <!-- SECCIÓN IZQUIERDA (FIXTURE Y EQUIPOS) -->
    <div class="content-left">
        <?php if ($flash): ?><?= render_toast($flash['message'], $flash['type']) ?><?php endif; ?>
        
        <!-- FIXTURE -->
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
        
        <!-- EQUIPOS PARTICIPANTES -->
        <section class="equipos-section">
            <h3 class="section-title"><span class="icon">👥</span> Equipos Participantes</h3>
            <div class="equipos-grid">
                
                <!-- GRUPO A -->
                <div class="equipo-column grupo-a">
                    <h4 class="grupo-header">GRUPO A</h4>
                    
                    <?php
                    // Obtenemos los IDs de los top 3 goleadores del Grupo A
                    $topScorersA = [];
                    if (!empty($goleadoresA)) {
                        foreach (array_slice($goleadoresA, 0, 3) as $gol) {
                            // Necesitamos buscar el ID del jugador por su nombre para compararlo
                            // Nota: Lo ideal sería que $goleadoresA ya traiga id_jugador, 
                            // pero si no, comparamos por nombre exacto.
                            $topScorersA[] = $gol['jugador']; 
                        }
                    }

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
                                    <?php foreach ($jugadores as $jugador): 
                                        // Verificamos si este jugador es un goleador destacado
                                        $esGoleador = in_array($jugador['nombre'], $topScorersA);
                                    ?>
                                        <div class="jugador-item">
                                            <span class="jugador-nombre"><?= h($jugador['nombre']) ?></span>
                                            
                                            <!-- BALÓN GIF SI ES GOLEADOR -->
                                            <?php if ($esGoleador): ?>
                                                <img src="/assets/images/balonfifa2026.png" class="mini-balon-giratorio" alt="Goleador">
                                            <?php endif; ?>
                                            
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
                    // Obtenemos los IDs de los top 3 goleadores del Grupo B
                    $topScorersB = [];
                    if (!empty($goleadoresB)) {
                        foreach (array_slice($goleadoresB, 0, 3) as $gol) {
                            $topScorersB[] = $gol['jugador'];
                        }
                    }

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
                                    <?php foreach ($jugadores as $jugador): 
                                        $esGoleador = in_array($jugador['nombre'], $topScorersB);
                                    ?>
                                        <div class="jugador-item">
                                            <span class="jugador-nombre"><?= h($jugador['nombre']) ?></span>
                                            
                                            <!-- BALÓN GIF SI ES GOLEADOR -->
                                            <?php if ($esGoleador): ?>
                                                <img src="/assets/images/balonfifa2026.png" class="mini-balon-giratorio" alt="Goleador">
                                            <?php endif; ?>
                                            
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
    
    <!-- SECCIÓN DERECHA (ESTADÍSTICAS) -->
    <div class="content-right">
        
        <!-- POSICIONES -->
        <section class="posiciones-section">
            <h3 class="section-title"><span class="icon">🏆</span> Posiciones</h3>
            
            <!-- GRUPO A -->
            <div class="tabla-grupo">
                <h4 class="grupo-mini-header grupo-a">GRUPO A</h4>
                <table class="tabla-posiciones">
                    <thead>
                        <tr><th>#</th><th>Equipo</th><th>PJ</th><th>G</th><th>E</th><th>P</th><th>GF</th><th>GC</th><th>DG</th><th>Pts</th></tr>
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
                                <td style="color: <?= $equipo['dg'] > 0 ? '#00ff87' : ($equipo['dg'] < 0 ? '#ff006e' : '#ffffff') ?>; font-weight: bold;">
                                    <?= $equipo['dg'] > 0 ? '+' . $equipo['dg'] : $equipo['dg'] ?>
                                </td>
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
                        <tr><th>#</th><th>Equipo</th><th>PJ</th><th>G</th><th>E</th><th>P</th><th>GF</th><th>GC</th><th>DG</th><th>Pts</th></tr>
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
                                <td style="color: <?= $equipo['dg'] > 0 ? '#00ff87' : ($equipo['dg'] < 0 ? '#ff006e' : '#ffffff') ?>; font-weight: bold;">
                                    <?= $equipo['dg'] > 0 ? '+' . $equipo['dg'] : $equipo['dg'] ?>
                                </td>
                                <td class="puntos"><?= $equipo['puntos'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        
        <!-- GOLEADORES ⚽-->
        <section class="goleadores-section">
            <h3 class="section-title"><span class="balon-container">
                <img src="/assets/images/balonfifa2026.png" alt="Balón FIFA 2026" class="balon-animado-img">
            </span> Goleadores</h3>
            
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
                            
                            <!-- CAMBIO AQUÍ: Usamos la imagen animada en lugar del emoji -->
                            <span class="goleador-goles">
                                <?= $goleador['goles'] ?> 
                                <img src="/assets/images/balonfifa2026.png" class="mini-balon-giratorio" alt="Gol">
                            </span>
                            
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
                            
                            <!-- CAMBIO AQUÍ: Usamos la imagen animada en lugar del emoji -->
                            <span class="goleador-goles">
                                <?= $goleador['goles'] ?> 
                                <img src="/assets/images/balonfifa2026.png" class="mini-balon-giratorio" alt="Gol">
                            </span>

                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<!-- ============================================ -->
<!-- MODALES (RESULTADO Y VIVO) -->
<!-- ============================================ -->

<!-- MODAL RESULTADO -->
<div id="resultadoModal" class="modal-overlay" style="display: none; z-index: 99999;">
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
<div id="modalVivo" class="modal-overlay modal-vivo-overlay" style="display: none; z-index: 99999;">
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
            <!-- Botón Compartir WhatsApp -->
            <div class="vivo-share-section" style="margin-top:20px; text-align:center;">
                <button class="btn-share-wsp" onclick="compartirMarcadorWSP()">📲 Compartir por WhatsApp</button>
            </div>
        </div>
    </div>
</div>