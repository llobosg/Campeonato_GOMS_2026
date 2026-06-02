<?php
/**
 * enviar_correos_qr.php - Script para enviar QRs a encargados
 * Ejecutar UNA VEZ desde el navegador: https://campeonatogoms2026.up.railway.app/enviar_correos_qr.php
 */

define('APP_ENTRY_POINT', true);
require_once __DIR__ . '/../config.php';

// Lista de Encargados
$encargados = [
    'Los Galacticos'    => ['nombre' => 'Dennis Garrido', 'email' => 'luis.lobos.g@gmail.com'],
    'Mas Menos 1 Metro FC' => ['nombre' => 'Fabian Poblete', 'email' => 'luis.lobos.g@gmail.com'],
    'Calidad Prime' => ['nombre' => 'Luis Hernández', 'email' => 'luishernandezt.ing@gmail.com'],
    'Los Desquinchadores' => ['nombre' => 'Carlos Rodríguez', 'email' => 'rodriguezriveroscarlos@gmail.com'],
];

echo "<h1>Enviando Correos con QRs...</h1>";
echo "<ul>";

foreach ($encargados as $nombre_equipo => $datos) {
    // 1. Buscar el equipo en la BD
    $equipo = db_fetch_one($pdo, "SELECT * FROM equipos WHERE nombre = :nombre", ['nombre' => $nombre_equipo]);
    
    if (!$equipo) {
        echo "<li style='color:red;'>❌ Equipo '$nombre_equipo' no encontrado en la BD.</li>";
        continue;
    }

    // 2. Generar QR si no existe
    if (empty($equipo['qr_code'])) {
        QRGenerator::generateForTeam($equipo['id_equipo'], $equipo['nombre']);
        echo "<li>⚡ QR generado para $nombre_equipo.</li>";
    }

    // 3. Preparar el correo
    $link_registro = BASE_URL . "/jugadores/registrar/" . $equipo['id_equipo'];
    $qr_image_url = BASE_URL . "/uploads/qrs/team_" . $equipo['id_equipo'] . ".png";
    
    $asunto = "🏆 Tu Código QR Oficial - Campeonato GOMS 2026";
    
    $cuerpo = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
        <!-- Header -->
        <div style='background: linear-gradient(135deg, #00ff87 0%, #3a86ff 100%); padding: 20px; text-align: center;'>
            <h1 style='color: white; margin: 0; font-size: 24px;'>CAMPEONATO GOMS 2026</h1>
            <p style='color: white; margin: 5px 0 0; font-size: 16px;'>Oficial</p>
        </div>
        
        <!-- Body -->
        <div style='padding: 30px; background: #f9f9f9; text-align: center;'>
            <h2 style='color: #333;'>Hola, {$datos['nombre']}</h2>
            <p style='font-size: 16px; color: #555;'>
                Como encargado del equipo <strong style='color: #00ff87;'>$nombre_equipo</strong>, 
                te enviamos tu código oficial de registro para que lo compartas con tus jugadores.
            </p>
            
            <!-- QR Image -->
            <div style='margin: 20px 0; padding: 10px; background: #ffffff; display: inline-block; border-radius: 8px; border: 2px solid #000000;'>
                <img src='$qr_image_url' alt='QR Code' style='width: 200px; height: 200px; display: block;'>
            </div>
            
            <p style='font-size: 14px; color: #777;'>Escanea este código o usa el link de abajo:</p>
            
            <!-- Button -->
            <a href='$link_registro' style='display: inline-block; background: #00ff87; color: #000; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 10px;'>
                Link de Registro Directo
            </a>
        </div>
        
        <!-- Footer -->
        <div style='background: #333; color: white; padding: 15px; text-align: center; font-size: 12px;'>
            <p style='margin: 0;'>© 2026 Campeonato GOMS | Desarrollado por GLT Sport</p>
        </div>
    </div>
    ";

    // 4. Enviar Correo
    $mailer = new BrevoMailer();
    $enviado = $mailer->send($datos['email'], $asunto, $cuerpo);
    
    if ($enviado) {
        echo "<li style='color:green;'>✅ Correo enviado a {$datos['email']} ($nombre_equipo)</li>";
    } else {
        echo "<li style='color:orange;'>⚠️ Falló el envío a {$datos['email']} (Revisa API Key de Brevo)</li>";
    }
}

echo "</ul>";
echo "<p><strong>Proceso terminado.</strong></p>";
?>