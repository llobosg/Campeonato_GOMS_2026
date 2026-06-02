<?php
/**
 * functions.php - Helpers Globales Campeonato GOMS 2026
 * Ubicación: includes/functions.php
 * Funciones reutilizables para toda la aplicación
 */

// ============================================
// SEGURIDAD Y SANITIZACIÓN
// ============================================

/**
 * Sanitizar input de usuario (prevenir XSS)
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generar token CSRF simple
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================
// BASE DE DATOS HELPERS
// ============================================

/**
 * Ejecutar query preparada y retornar resultados
 */
function db_query($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log(" DB Error: " . $e->getMessage() . " | SQL: $sql");
        throw $e;
    }
}

/**
 * Obtener una fila
 */
function db_fetch_one($pdo, $sql, $params = []) {
    $stmt = db_query($pdo, $sql, $params);
    return $stmt->fetch();
}

/**
 * Obtener todas las filas
 */
function db_fetch_all($pdo, $sql, $params = []) {
    $stmt = db_query($pdo, $sql, $params);
    return $stmt->fetchAll();
}

/**
 * Insertar y retornar ID
 */
function db_insert($pdo, $table, $data) {
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    db_query($pdo, $sql, $data);
    
    return $pdo->lastInsertId();
}

/**
 * Actualizar registros
 */
function db_update($pdo, $table, $data, $where, $where_params = []) {
    $set_parts = [];
    foreach ($data as $key => $value) {
        $set_parts[] = "$key = :$key";
    }
    $set_clause = implode(', ', $set_parts);
    
    $sql = "UPDATE $table SET $set_clause WHERE $where";
    $params = array_merge($data, $where_params);
    
    $stmt = db_query($pdo, $sql, $params);
    return $stmt->rowCount();
}

/**
 * Eliminar registros
 */
function db_delete($pdo, $table, $where, $params = []) {
    $sql = "DELETE FROM $table WHERE $where";
    $stmt = db_query($pdo, $sql, $params);
    return $stmt->rowCount();
}

// ============================================
// RESPUESTAS JSON (API)
// ============================================

/**
 * Respuesta JSON exitosa
 */
function json_success($data = [], $message = 'Operación exitosa') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Respuesta JSON error
 */
function json_error($message = 'Error', $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// REDIRECCIONES
// ============================================

/**
 * Redirección segura
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        http_response_code(301);
    } else {
        http_response_code(302);
    }
    header("Location: $url");
    exit;
}

/**
 * Redirección con mensaje toast
 */
function redirect_with_message($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
    redirect($url);
}

/**
 * Obtener y limpiar mensaje flash
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// ============================================
// FORMATO Y UTILIDADES
// ============================================

/**
 * Formatear fecha para mostrar
 */
function format_date($date_string, $format = 'd/m/Y') {
    if (!$date_string) return '-';
    $timestamp = strtotime($date_string);
    return date($format, $timestamp);
}

/**
 * Formatear hora
 */
function format_time($time_string) {
    if (!$time_string) return '-';
    return substr($time_string, 0, 5); // HH:MM
}

/**
 * Calcular puntos según resultado
 */
function calcular_puntos($goles_favor, $goles_contra) {
    if ($goles_favor > $goles_contra) return 2; // Victoria
    if ($goles_favor == $goles_contra) return 1; // Empate
    return 0; // Derrota
}

/**
 * Generar URL amigable
 */
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text;
}

/**
 * Limitar longitud de texto
 */
function truncate($text, $length = 50, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

// ============================================
// LOGGING
// ============================================

/**
 * Log personalizado
 */
function app_log($message, $level = 'INFO') {
    $log_file = __DIR__ . '/../logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Crear directorio logs si no existe
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// ============================================
// VALIDACIONES
// ============================================

/**
 * Validar que un string no esté vacío
 */
function is_not_empty($value) {
    return !empty(trim($value ?? ''));
}

/**
 * Validar contraseña admin
 */
function verify_admin_password($password) {
    return $password === ADMIN_PASSWORD;
}

/**
 * Verificar si usuario está autenticado como admin
 */
function is_admin_authenticated() {
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}

/**
 * Requerir autenticación admin
 */
function require_admin_auth() {
    if (!is_admin_authenticated()) {
        json_error('No autorizado. Se requiere contraseña de administrador.', 403);
    }
}

// ============================================
// QR CODES
// ============================================

/**
 * Generar URL de registro para equipo
 */
function generate_team_registration_url($team_id) {
    return BASE_URL . "/jugadores/registrar/$team_id";
}

/**
 * Verificar si archivo QR existe
 */
function qr_exists($team_id) {
    $qr_path = QR_DIR . "/team_{$team_id}.png";
    return file_exists($qr_path);
}

/**
 * Obtener ruta del QR
 */
function get_qr_path($team_id) {
    return QR_DIR . "/team_{$team_id}.png";
}

/**
 * Obtener URL pública del QR
 */
function get_qr_url($team_id) {
    return BASE_URL . "/uploads/qrs/team_{$team_id}.png";
}

// ============================================
// ESTADÍSTICAS RÁPIDAS
// ============================================

/**
 * Obtener posiciones de un grupo
 */
function get_posiciones_grupo($pdo, $grupo) {
    $sql = "SELECT * FROM v_posiciones WHERE grupo = :grupo ORDER BY puntos DESC, goles_favor DESC";
    return db_fetch_all($pdo, $sql, ['grupo' => $grupo]);
}

/**
 * Obtener goleadores de un grupo
 */
function get_goleadores_grupo($pdo, $grupo, $limit = 3) {
    $sql = "SELECT * FROM v_goleadores WHERE grupo = :grupo ORDER BY goles DESC LIMIT :limit";
    return db_fetch_all($pdo, $sql, ['grupo' => $grupo, 'limit' => (int)$limit]);
}

/**
 * Obtener fixture de una fecha
 */
function get_fixture_fecha($pdo, $nro_fecha) {
    $sql = "
        SELECT f.*, 
               e1.nombre as nombre_equipo_a, 
               e2.nombre as nombre_equipo_b,
               e1.grupo as grupo_a,
               e2.grupo as grupo_b
        FROM fixture f
        JOIN equipos e1 ON f.equipo_a = e1.id_equipo
        JOIN equipos e2 ON f.equipo_b = e2.id_equipo
        WHERE f.nro_fecha = :nro_fecha
        ORDER BY f.hora ASC
    ";
    return db_fetch_all($pdo, $sql, ['nro_fecha' => (int)$nro_fecha]);
}

/**
 * Obtener goles de un partido
 */
function get_goles_partido($pdo, $id_fixture) {
    $sql = "
        SELECT g.*, j.nombre as jugador_nombre, e.nombre as equipo_nombre
        FROM goles g
        JOIN jugadores j ON g.id_jugador = j.id_jugador
        JOIN equipos e ON j.id_equipo = e.id_equipo
        WHERE g.id_fixture = :id_fixture
        ORDER BY g.created_at ASC
    ";
    return db_fetch_all($pdo, $sql, ['id_fixture' => (int)$id_fixture]);
}

/**
 * Contar goles por jugador en un partido
 */
function contar_goles_jugador_partido($pdo, $id_fixture, $id_jugador) {
    $sql = "SELECT COUNT(*) as total FROM goles WHERE id_fixture = :id_fixture AND id_jugador = :id_jugador";
    $result = db_fetch_one($pdo, $sql, [
        'id_fixture' => (int)$id_fixture,
        'id_jugador' => (int)$id_jugador
    ]);
    return (int)($result['total'] ?? 0);
}

// ============================================
// TOAST NOTIFICATIONS
// ============================================

/**
 * Generar HTML para toast notification
 */
function render_toast($message, $type = 'success') {
    $icons = [
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    
    $icon = $icons[$type] ?? 'ℹ️';
    
    return "
    <div class='toast toast-$type' role='alert'>
        <span class='toast-icon'>$icon</span>
        <span class='toast-message'>" . h($message) . "</span>
        <button class='toast-close' onclick='this.parentElement.remove()'>&times;</button>
    </div>
    ";
}

error_log("✅ functions.php cargado correctamente");