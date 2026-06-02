<?php
/**
 * QRGenerator.php - Generador de Códigos QR
 * Ubicación: includes/QRGenerator.php
 * Genera QR codes PNG para registro de jugadores por equipo
 */

class QRGenerator {
    
    /**
     * Generar QR code para un equipo y guardarlo en disco
     * 
     * @param int $team_id ID del equipo
     * @param string $team_name Nombre del equipo (para nombre de archivo)
     * @return bool True si se generó exitosamente
     */
    public static function generateForTeam(int $team_id, string $team_name): bool {
        try {
            // Crear directorio si no existe
            if (!is_dir(QR_DIR)) {
                mkdir(QR_DIR, 0755, true);
            }
            
            // Generar URL de registro
            $registration_url = generate_team_registration_url($team_id);
            
            // Nombre del archivo
            $filename = "team_{$team_id}.png";
            $filepath = QR_DIR . '/' . $filename;
            
            // Si ya existe, no regenerar (a menos que se fuerce)
            if (file_exists($filepath)) {
                error_log("QR ya existe para equipo $team_id");
                return true;
            }
            
            // Generar QR usando API externa (qrcode-api.com) o librería local
            // Opción 1: Usar API externa (más simple, sin dependencias)
            $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($registration_url);
            
            // Descargar imagen QR
            $qr_image = self::downloadImage($qr_api_url);
            
            if ($qr_image === false) {
                throw new Exception("No se pudo descargar el QR desde la API");
            }
            
            // Guardar en disco
            file_put_contents($filepath, $qr_image);
            
            // Verificar que se guardó correctamente
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                throw new Exception("Error al guardar el archivo QR");
            }
            
            // Actualizar registro en base de datos
            global $pdo;
            if (isset($pdo)) {
                db_update(
                    $pdo,
                    'equipos',
                    [
                        'qr_code' => $filepath,
                        'link_registro' => $registration_url
                    ],
                    'id_equipo = :id',
                    ['id' => $team_id]
                );
            }
            
            app_log("QR generado para equipo #$team_id ($team_name): $filepath");
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Error generando QR para equipo $team_id: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generar QR code inline (sin guardar en disco) - para vista previa
     * 
     * @param string $data Datos a codificar en el QR
     * @return string|false Base64 encoded image o false si falla
     */
    public static function generateInline(string $data) {
        try {
            $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($data);
            
            $qr_image = self::downloadImage($qr_api_url);
            
            if ($qr_image === false) {
                return false;
            }
            
            // Convertir a base64
            $base64 = base64_encode($qr_image);
            $mime = 'image/png';
            
            return "data:$mime;base64,$base64";
            
        } catch (\Exception $e) {
            error_log("Error generando QR inline: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Regenerar todos los QRs de equipos existentes
     * 
     * @param PDO $pdo Conexión a base de datos
     * @return array Resultado por equipo
     */
    public static function regenerateAll(PDO $pdo): array {
        $results = [];
        
        try {
            // Obtener todos los equipos
            $equipos = db_fetch_all($pdo, "SELECT id_equipo, nombre FROM equipos ORDER BY id_equipo ASC");
            
            foreach ($equipos as $equipo) {
                $success = self::generateForTeam($equipo['id_equipo'], $equipo['nombre']);
                
                $results[] = [
                    'id_equipo' => $equipo['id_equipo'],
                    'nombre' => $equipo['nombre'],
                    'success' => $success,
                    'qr_path' => $success ? get_qr_path($equipo['id_equipo']) : null
                ];
            }
            
            app_log("Regeneración masiva de QRs completada. Total: " . count($results));
            
        } catch (\Exception $e) {
            error_log("Error en regeneración masiva de QRs: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Verificar si un QR existe para un equipo
     * 
     * @param int $team_id
     * @return bool
     */
    public static function exists(int $team_id): bool {
        return qr_exists($team_id);
    }
    
    /**
     * Eliminar QR de un equipo
     * 
     * @param int $team_id
     * @return bool
     */
    public static function delete(int $team_id): bool {
        try {
            $filepath = get_qr_path($team_id);
            
            if (file_exists($filepath)) {
                unlink($filepath);
                
                // Limpiar registro en BD
                global $pdo;
                if (isset($pdo)) {
                    db_update(
                        $pdo,
                        'equipos',
                        [
                            'qr_code' => null,
                            'link_registro' => null
                        ],
                        'id_equipo = :id',
                        ['id' => $team_id]
                    );
                }
                
                app_log("QR eliminado para equipo #$team_id");
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            error_log("Error eliminando QR para equipo $team_id: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Descargar imagen desde URL
     * 
     * @param string $url
     * @return string|false Contenido de la imagen o false
     */
    private static function downloadImage(string $url) {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'CampeonatoGOMS2026-QRGenerator/1.0'
        ]);
        
        $image_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($http_code !== 200 || $image_data === false) {
            error_log("Error descargando QR: HTTP $http_code | $error");
            return false;
        }
        
        // Verificar que es una imagen PNG válida
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_buffer($finfo, $image_data);
        finfo_close($finfo);
        
        if ($mime_type !== 'image/png') {
            error_log("Tipo MIME inválido para QR: $mime_type");
            return false;
        }
        
        return $image_data;
    }
    
    /**
     * Obtener estadísticas de QRs generados
     * 
     * @param PDO $pdo
     * @return array
     */
    public static function getStats(PDO $pdo): array {
        try {
            $total_equipos = db_fetch_one($pdo, "SELECT COUNT(*) as total FROM equipos")['total'];
            
            $con_qr = db_fetch_one($pdo, "SELECT COUNT(*) as total FROM equipos WHERE qr_code IS NOT NULL")['total'];
            
            $sin_qr = $total_equipos - $con_qr;
            
            return [
                'total_equipos' => (int)$total_equipos,
                'con_qr' => (int)$con_qr,
                'sin_qr' => (int)$sin_qr,
                'porcentaje_generados' => $total_equipos > 0 ? round(($con_qr / $total_equipos) * 100, 2) : 0
            ];
            
        } catch (\Exception $e) {
            error_log("Error obteniendo stats de QRs: " . $e->getMessage());
            return [
                'total_equipos' => 0,
                'con_qr' => 0,
                'sin_qr' => 0,
                'porcentaje_generados' => 0
            ];
        }
    }
}

// Helper functions adicionales para QR

/**
 * Obtener URL pública de un QR
 */
function get_qr_public_url(int $team_id): string {
    $qr_path = get_qr_path($team_id);
    
    if (file_exists($qr_path)) {
        return BASE_URL . "/uploads/qrs/team_{$team_id}.png";
    }
    
    return '';
}

/**
 * Renderizar HTML de QR para mostrar en página
 */
function render_qr_html(int $team_id, string $team_name, int $size = 200): string {
    $qr_url = get_qr_public_url($team_id);
    
    if (empty($qr_url)) {
        return "<div class='qr-placeholder'>
                    <p>QR no disponible para <strong>" . h($team_name) . "</strong></p>
                    <button onclick='generarQR($team_id)'>Generar QR</button>
                </div>";
    }
    
    return "
    <div class='qr-display'>
        <img src='$qr_url' alt='QR Code para $team_name' width='$size' height='$size' class='qr-image'>
        <p class='qr-url'>URL: " . generate_team_registration_url($team_id) . "</p>
        <button onclick='descargarQR($team_id)' class='btn btn-small'>Descargar QR</button>
    </div>
    ";
}

app_log("✅ QRGenerator.php cargado correctamente");