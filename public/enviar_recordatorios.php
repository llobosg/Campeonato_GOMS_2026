<?php
/**
 * enviar_recordatorios.php - Envía recordatorios diarios a jugadores
 * Ejecutar manualmente o configurar cron job para las 08:00 AM
 */

define('APP_ENTRY_POINT', true);
require_once __DIR__ . '/../config.php';

// 1. Determinar fecha objetivo
// Para pruebas hoy, usamos 'today'. Para producción, usa date('Y-m-d')
$fecha_objetivo = date('Y-m-d'); 

echo "<h1>📧 Enviando Recordatorios para: $fecha_objetivo</h1>";
echo "<hr>";

try {
    // 2. Buscar partidos programados para esa fecha
    $partidos_hoy = db_fetch_all($pdo, 
        "SELECT f.*, e1.nombre as nombre_equipo_a, e2.nombre as nombre_equipo_b, e1.grupo as grupo_a, e2.grupo as grupo_b
         FROM fixture f
         JOIN equipos e1 ON f.equipo_a = e1.id_equipo
         JOIN equipos e2 ON f.equipo_b = e2.id_equipo
         WHERE f.fecha = :fecha AND f.estado != 'finalizado'
         ORDER BY f.hora ASC", 
        ['fecha' => $fecha_objetivo]
    );

    if (empty($partidos_hoy)) {
        echo "<p>No hay partidos programados para hoy.</p>";
        exit;
    }

    foreach ($partidos_hoy as $partido) {
        echo "<h3>⚽ Partido: {$partido['nombre_equipo_a']} vs {$partido['nombre_equipo_b']} ({$partido['hora']})</h3>";
        
        // Procesar Equipo A
        enviarCorreoAEquipo($partido['equipo_a'], $partido['grupo_a'], $partido['nombre_equipo_a'], $partido['nombre_equipo_b'], $partido['hora']);
        
        // Procesar Equipo B
        enviarCorreoAEquipo($partido['equipo_b'], $partido['grupo_b'], $partido['nombre_equipo_b'], $partido['nombre_equipo_a'], $partido['hora']);
    }

    echo "<hr><p><strong>✅ Proceso completado.</strong></p>";

} catch (\Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
    error_log("Error en recordatorios: " . $e->getMessage());
}

// ============================================
// FUNCIÓN PARA GENERAR Y ENVIAR CORREO
// ============================================
function enviarCorreoAEquipo($id_equipo, $grupo, $nombre_mi_equipo, $nombre_rival, $hora_partido) {
    global $pdo;

    // 1. Obtener jugadores del equipo (USANDO COLUMNA 'correo')
    $jugadores = db_fetch_all($pdo, 
        "SELECT j.nombre, j.correo FROM jugadores j WHERE j.id_equipo = :id", 
        ['id' => $id_equipo]
    );

    if (empty($jugadores)) {
        echo "<li style='color:gray;'>️ Sin jugadores registrados en $nombre_mi_equipo</li>";
        return;
    }

    // 2. Obtener Tabla de Posiciones de su Grupo
    $posiciones = db_fetch_all($pdo,
        "SELECT equipo, puntos, ganados, empatados, perdidos, goles_favor, goles_contra 
         FROM v_posiciones 
         WHERE grupo = :grupo 
         ORDER BY puntos DESC, (goles_favor - goles_contra) DESC 
         LIMIT 5", 
        ['grupo' => $grupo]
    );

    // 3. Obtener Top Goleadores General
    $goleadores = db_fetch_all($pdo,
        "SELECT j.nombre as jugador, e.nombre as equipo, COUNT(g.id_gol) as total_goles
         FROM goles g
         JOIN jugadores j ON g.id_jugador = j.id_jugador
         JOIN equipos e ON j.id_equipo = e.id_equipo
         GROUP BY j.id_jugador, j.nombre, e.nombre
         ORDER BY total_goles DESC
         LIMIT 3",
        []
    );

    // 4. Generar HTML del Correo
    $html = generarHTMLCorreo($nombre_mi_equipo, $nombre_rival, $hora_partido, $grupo, $posiciones, $goleadores);

    // 5. Enviar a cada jugador
    $mailer = new BrevoMailer(); 
    
    foreach ($jugadores as $jugador) {
        if (empty($jugador['correo'])) continue;

        $asunto = "⚽ ¡Hoy se juega! $nombre_mi_equipo vs $nombre_rival a las $hora_partido";
        
        // Personalizar saludo
        $nombre_pila = explode(' ', $jugador['nombre'])[0];
        $cuerpo_personalizado = str_replace('[NOMBRE_JUGADOR]', $nombre_pila, $html);

        $enviado = $mailer->send($jugador['correo'], $asunto, $cuerpo_personalizado);
        
        if ($enviado) {
            echo "<li style='color:green;'>✅ Enviado a <b>{$jugador['nombre']}</b> ({$jugador['correo']})</li>";
        } else {
            echo "<li style='color:orange;'>⚠️ Falló envío a {$jugador['correo']}</li>";
        }
    }
}

// ============================================
// TEMPLATE HTML DEL CORREO (DISEÑO FIFA 2026)
// ============================================
function generarHTMLCorreo($mi_equipo, $rival, $hora, $grupo, $posiciones, $goleadores) {
    
    // Construir tabla de posiciones HTML
    $tabla_pos_html = "<table style='width:100%; border-collapse: collapse; font-size: 13px; margin-top:10px;'>
                        <tr style='background:#eee; text-align:left;'><th style='padding:8px;'>#</th><th>Equipo</th><th>Pts</th></tr>";
    
    $i = 1;
    foreach ($posiciones as $pos) {
        // Resaltar si es mi equipo
        $highlight = ($pos['equipo'] == $mi_equipo) ? "style='background:#e0ffe0; font-weight:bold; border-left: 4px solid #00ff87;'" : "style='border-bottom:1px solid #ddd;'";
        
        $tabla_pos_html .= "<tr $highlight>
                                <td style='padding:8px;'>$i</td>
                                <td style='padding:8px;'>{$pos['equipo']}</td>
                                <td style='padding:8px; text-align:center; font-weight:bold;'>{$pos['puntos']}</td>
                            </tr>";
        $i++;
    }
    $tabla_pos_html .= "</table>";

    // Construir lista goleadores HTML
    $goles_html = "<ul style='list-style:none; padding:0; margin-top:10px;'>";
    foreach ($goleadores as $gol) {
        $goles_html .= "<li style='margin-bottom:8px; font-size:13px; display:flex; justify-content:space-between; border-bottom:1px dashed #ccc; padding-bottom:4px;'> 
                            <span><b>{$gol['jugador']}</b> <small style='color:#666;'>({$gol['equipo']})</small></span> 
                            <span style='color:#00ff87; font-weight:bold;'>{$gol['total_goles']} ⚽</span>
                        </li>";
    }
    $goles_html .= "</ul>";

    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9f9f9; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>
        <!-- Header -->
        <div style='background: linear-gradient(135deg, #00ff87 0%, #3a86ff 100%); padding: 25px; text-align: center; color: white;'>
            <h1 style='margin:0; font-size: 26px; text-transform:uppercase; letter-spacing:1px;'>Campeonato GOMS 2026</h1>
            <p style='margin:5px 0 0; opacity: 0.9; font-size:14px;'>¡La emoción continúa!</p>
        </div>
        
        <!-- Body -->
        <div style='padding: 25px; background: white;'>
            <h2 style='color: #333; margin-top:0;'>Hola [NOMBRE_JUGADOR],</h2>
            <p style='font-size: 16px; color: #555; line-height:1.5;'>
                Hoy es un día especial. Tu equipo <strong style='color: #00ff87;'>$mi_equipo</strong> tiene cita con la victoria.
            </p>
            
            <!-- Caja del Partido -->
            <div style='background: #f0f0f0; padding: 20px; border-radius: 8px; margin: 25px 0; text-align: center; border-left: 5px solid #00ff87;'>
                <p style='margin:0; font-size: 20px; font-weight: bold; color: #333; text-transform:uppercase;'> $mi_equipo VS $rival</p>
                <p style='margin:10px 0 0; font-size: 24px; color: #00ff87; font-weight:900;'> HORA: $hora hrs</p>
                <p style='margin:5px 0 0; font-size:12px; color:#666;'>No llegues tarde, ¡cada minuto cuenta!</p>
            </div>
            
            <hr style='border: 0; border-top: 1px solid #eee; margin: 25px 0;'>
            
            <h3 style='color: #3a86ff; border-bottom: 2px solid #3a86ff; padding-bottom: 5px; margin-bottom:10px;'>🏆 Posiciones Grupo $grupo</h3>
            $tabla_pos_html
            
            <h3 style='color: #ff006e; border-bottom: 2px solid #ff006e; padding-bottom: 5px; margin-top: 25px; margin-bottom:10px;'>⚽ Top Goleadores</h3>
            $goles_html
        </div>
        
        <!-- Footer -->
        <div style='background: #1a1a1a; color: white; padding: 15px; text-align: center; font-size: 12px;'>
            <p style='margin:0;'>© 2026 Campeonato GOMS | Preparado para la gloria.</p>
            <p style='margin:5px 0 0; opacity:0.6; font-size:10px;'>Este es un correo automático, por favor no responder.</p>
        </div>
    </div>
    ";
}
?>