<?php
/**
 * ApiController.php - Controlador de API JSON
 * Ubicación: src/Controllers/ApiController.php
 * Maneja endpoints para AJAX calls desde el frontend
 */

namespace App\Controllers;

use PDO;

class ApiController {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * GET /api/equipos - Listar todos los equipos
     */
    public function getEquipos(array $params = []) {
        try {
            $grupo = $params['grupo'] ?? null;
            
            if ($grupo) {
                $sql = "SELECT * FROM equipos WHERE grupo = :grupo ORDER BY nombre ASC";
                $equipos = db_fetch_all($this->pdo, $sql, ['grupo' => $grupo]);
            } else {
                $sql = "SELECT * FROM equipos ORDER BY grupo ASC, nombre ASC";
                $equipos = db_fetch_all($this->pdo, $sql);
            }
            
            json_success($equipos, 'Equipos obtenidos exitosamente');
            
        } catch (\Exception $e) {
            error_log("Error en getEquipos: " . $e->getMessage());
            json_error('Error al obtener equipos', 500);
        }
    }
    
    /**
     * GET /api/jugadores?equipo={id} - Listar jugadores de un equipo
     */
    public function getJugadores(array $params = []) {
        try {
            $equipo_id = $_GET['equipo'] ?? null;
            
            if (!$equipo_id) {
                json_error('Se requiere parámetro equipo', 400);
            }
            
            $sql = "
                SELECT j.id_jugador, j.nombre, j.correo, j.area, 
                       e.nombre as equipo_nombre, e.grupo
                FROM jugadores j
                JOIN equipos e ON j.id_equipo = e.id_equipo
                WHERE j.id_equipo = :equipo_id
                ORDER BY j.nombre ASC
            ";
            
            $jugadores = db_fetch_all($this->pdo, $sql, ['equipo_id' => (int)$equipo_id]);
            
            json_success($jugadores, 'Jugadores obtenidos exitosamente');
            
        } catch (\Exception $e) {
            error_log("Error en getJugadores: " . $e->getMessage());
            json_error('Error al obtener jugadores', 500);
        }
    }
    
    /**
     * GET /api/fixture - Listar fixture completo o por fecha
     */
    public function getFixture(array $params = []) {
        try {
            $nro_fecha = $_GET['fecha'] ?? null;
            $id_fixture = $params['id'] ?? null;
            
            // Si se solicita un fixture específico por ID
            if ($id_fixture) {
                $sql = "
                    SELECT f.*, 
                           e1.nombre as nombre_equipo_a, 
                           e2.nombre as nombre_equipo_b,
                           e1.grupo as grupo_a,
                           e2.grupo as grupo_b
                    FROM fixture f
                    JOIN equipos e1 ON f.equipo_a = e1.id_equipo
                    JOIN equipos e2 ON f.equipo_b = e2.id_equipo
                    WHERE f.id_fixture = :id_fixture
                ";
                
                $fixture = db_fetch_one($this->pdo, $sql, ['id_fixture' => (int)$id_fixture]);
                
                if (!$fixture) {
                    json_error('Partido no encontrado', 404);
                }
                
                // Obtener goles del partido
                $goles = get_goles_partido($this->pdo, (int)$id_fixture);
                $fixture['goles_detalle'] = $goles;
                
                json_success($fixture, 'Fixture obtenido exitosamente');
            }
            // Si se solicita por número de fecha
            elseif ($nro_fecha) {
                $partidos = get_fixture_fecha($this->pdo, (int)$nro_fecha);
                json_success($partidos, "Fixture Fecha $nro_fecha obtenido");
            }
            // Si se solicita todo el fixture
            else {
                $sql = "
                    SELECT f.*, 
                           e1.nombre as nombre_equipo_a, 
                           e2.nombre as nombre_equipo_b,
                           e1.grupo as grupo_a,
                           e2.grupo as grupo_b
                    FROM fixture f
                    JOIN equipos e1 ON f.equipo_a = e1.id_equipo
                    JOIN equipos e2 ON f.equipo_b = e2.id_equipo
                    ORDER BY f.nro_fecha ASC, f.hora ASC
                ";
                
                $fixture = db_fetch_all($this->pdo, $sql);
                json_success($fixture, 'Fixture completo obtenido');
            }
            
        } catch (\Exception $e) {
            error_log("Error en getFixture: " . $e->getMessage());
            json_error('Error al obtener fixture', 500);
        }
    }
    
    /**
     * GET /api/posiciones - Tabla de posiciones
     */
    public function getPosiciones(array $params = []) {
        try {
            $grupo = $_GET['grupo'] ?? null;
            
            if ($grupo && in_array($grupo, ['A', 'B'])) {
                $posiciones = get_posiciones_grupo($this->pdo, $grupo);
                json_success($posiciones, "Posiciones Grupo $grupo obtenidas");
            } else {
                // Devolver ambos grupos
                $posicionesA = get_posiciones_grupo($this->pdo, 'A');
                $posicionesB = get_posiciones_grupo($this->pdo, 'B');
                
                json_success([
                    'grupo_a' => $posicionesA,
                    'grupo_b' => $posicionesB
                ], 'Posiciones obtenidas exitosamente');
            }
            
        } catch (\Exception $e) {
            error_log("Error en getPosiciones: " . $e->getMessage());
            json_error('Error al obtener posiciones', 500);
        }
    }
    
    /**
     * GET /api/goleadores - Lista de goleadores
     */
    public function getGoleadores(array $params = []) {
        try {
            $grupo = $_GET['grupo'] ?? null;
            $limit = $_GET['limit'] ?? 10;
            
            if ($grupo && in_array($grupo, ['A', 'B'])) {
                $goleadores = get_goleadores_grupo($this->pdo, $grupo, (int)$limit);
                json_success($goleadores, "Goleadores Grupo $grupo obtenidos");
            } else {
                // Devolver ambos grupos
                $goleadoresA = get_goleadores_grupo($this->pdo, 'A', (int)$limit);
                $goleadoresB = get_goleadores_grupo($this->pdo, 'B', (int)$limit);
                
                json_success([
                    'grupo_a' => $goleadoresA,
                    'grupo_b' => $goleadoresB
                ], 'Goleadores obtenidos exitosamente');
            }
            
        } catch (\Exception $e) {
            error_log("Error en getGoleadores: " . $e->getMessage());
            json_error('Error al obtener goleadores', 500);
        }
    }
    
    /**
     * GET /api/marcador/vivo - Marcador en vivo de la fecha actual
     */
    public function getMarcadorVivo(array $params = []) {
        try {
            $hoy = date('Y-m-d');
            
            // Obtener partidos de hoy
            $sql = "
                SELECT f.*, 
                       e1.nombre as nombre_equipo_a, 
                       e2.nombre as nombre_equipo_b,
                       e1.grupo as grupo_a,
                       e2.grupo as grupo_b
                FROM fixture f
                JOIN equipos e1 ON f.equipo_a = e1.id_equipo
                JOIN equipos e2 ON f.equipo_b = e2.id_equipo
                WHERE f.fecha = :hoy
                ORDER BY f.hora ASC
            ";
            
            $partidos = db_fetch_all($this->pdo, $sql, ['hoy' => $hoy]);
            
            // Enriquecer con detalles de goles
            foreach ($partidos as &$partido) {
                $partido['goles_detalle'] = get_goles_partido($this->pdo, $partido['id_fixture']);
            }
            
            json_success($partidos, 'Marcador en vivo obtenido');
            
        } catch (\Exception $e) {
            error_log("Error en getMarcadorVivo: " . $e->getMessage());
            json_error('Error al obtener marcador en vivo', 500);
        }
    }
    
    /**
     * POST /api/resultado/verificar - Verificar contraseña admin
     */
    public function verifyPassword(array $params = []) {
        try {
            // Leer body JSON
            $input = json_decode(file_get_contents('php://input'), true);
            $password = $input['password'] ?? '';
            
            if (empty($password)) {
                json_error('Contraseña requerida', 400);
            }
            
            // Verificar contraseña
            if (verify_admin_password($password)) {
                // Marcar sesión como autenticada
                $_SESSION['admin_authenticated'] = true;
                json_success([], 'Contraseña correcta');
            } else {
                json_error('Contraseña incorrecta', 403);
            }
            
        } catch (\Exception $e) {
            error_log("Error en verifyPassword: " . $e->getMessage());
            json_error('Error de verificación', 500);
        }
    }
    
    /**
     * POST /api/resultado/ingresar - Ingresar resultado de partido
     */
    public function storeResultado(array $params = []) {
        try {
            // Verificar autenticación admin
            if (!is_admin_authenticated()) {
                json_error('No autorizado. Se requiere autenticación.', 403);
            }
            
            // Leer body JSON
            $input = json_decode(file_get_contents('php://input'), true);
            $id_fixture = $input['id_fixture'] ?? null;
            $goles = $input['goles'] ?? [];
            
            if (!$id_fixture) {
                json_error('ID de fixture requerido', 400);
            }
            
            if (!is_array($goles)) {
                json_error('Formato de goles inválido', 400);
            }
            
            // Verificar que el fixture existe
            $fixture = db_fetch_one(
                $this->pdo,
                "SELECT * FROM fixture WHERE id_fixture = :id",
                ['id' => (int)$id_fixture]
            );
            
            if (!$fixture) {
                json_error('Partido no encontrado', 404);
            }
            
            // Si ya está finalizado, permitir re-edición pero limpiar goles anteriores
            if ($fixture['estado'] === 'finalizado') {
                db_delete(
                    $this->pdo,
                    'goles',
                    'id_fixture = :id_fixture',
                    ['id_fixture' => (int)$id_fixture]
                );
            }
            
            // Insertar nuevos goles
            $goles_insertados = 0;
            foreach ($goles as $gol) {
                if (!isset($gol['id_jugador'])) continue;
                
                db_insert($this->pdo, 'goles', [
                    'id_fixture' => (int)$id_fixture,
                    'id_jugador' => (int)$gol['id_jugador'],
                    'minuto' => $gol['minuto'] ?? null
                ]);
                
                $goles_insertados++;
            }
            
            // Calcular marcadores finales
            $goles_a = 0;
            $goles_b = 0;
            
            // Obtener IDs de equipos del fixture
            $equipo_a_id = $fixture['equipo_a'];
            $equipo_b_id = $fixture['equipo_b'];
            
            // Contar goles por equipo
            foreach ($goles as $gol) {
                $jugador = db_fetch_one(
                    $this->pdo,
                    "SELECT id_equipo FROM jugadores WHERE id_jugador = :id",
                    ['id' => (int)$gol['id_jugador']]
                );
                
                if ($jugador) {
                    if ($jugador['id_equipo'] == $equipo_a_id) {
                        $goles_a++;
                    } elseif ($jugador['id_equipo'] == $equipo_b_id) {
                        $goles_b++;
                    }
                }
            }
            
            // Actualizar fixture con resultados
            db_update(
                $this->pdo,
                'fixture',
                [
                    'goles_a' => $goles_a,
                    'goles_b' => $goles_b,
                    'estado' => 'finalizado'
                ],
                'id_fixture = :id',
                ['id' => (int)$id_fixture]
            );
            
            // Log del resultado
            app_log("Resultado registrado: Fixture #$id_fixture | $goles_a - $goles_b | Goles insertados: $goles_insertados");
            
            json_success([
                'id_fixture' => $id_fixture,
                'goles_a' => $goles_a,
                'goles_b' => $goles_b,
                'total_goles' => $goles_insertados
            ], 'Resultado registrado exitosamente');
            
        } catch (\Exception $e) {
            error_log("Error en storeResultado: " . $e->getMessage());
            json_error('Error al registrar resultado: ' . $e->getMessage(), 500);
        }
    }
}