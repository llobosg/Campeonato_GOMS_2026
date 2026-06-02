<?php
/**
 * EquipoController.php - Controlador de Gestión de Equipos
 * Ubicación: src/Controllers/EquipoController.php
 * Maneja: Crear, Editar, Eliminar, Listar equipos y generar QRs
 */

namespace App\Controllers;

use PDO;
use QRGenerator;

// Importar funciones globales definidas en config/functions
global $pdo, $isProduction, $BASE_URL; 

class EquipoController {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * GET /equipos/listar - Listar todos los equipos (Admin)
     */
    public function index(array $params = []) {
        // Verificar autenticación admin
        if (!is_admin_authenticated()) {
            // Si no está autenticado, mostrar login o redirigir
            // Por ahora, permitimos ver la lista pero no editar
        }
        
        $grupo = $_GET['grupo'] ?? null;
        
        if ($grupo && in_array($grupo, ['A', 'B'])) {
            $sql = "
                SELECT e.*, 
                       COUNT(j.id_jugador) as total_jugadores,
                       CASE WHEN e.qr_code IS NOT NULL THEN 1 ELSE 0 END as tiene_qr
                FROM equipos e
                LEFT JOIN jugadores j ON e.id_equipo = j.id_equipo
                WHERE e.grupo = :grupo
                GROUP BY e.id_equipo
                ORDER BY e.nombre ASC
            ";
            $equipos = db_fetch_all($this->pdo, $sql, ['grupo' => $grupo]);
        } else {
            $sql = "
                SELECT e.*, 
                       COUNT(j.id_jugador) as total_jugadores,
                       CASE WHEN e.qr_code IS NOT NULL THEN 1 ELSE 0 END as tiene_qr
                FROM equipos e
                LEFT JOIN jugadores j ON e.id_equipo = j.id_equipo
                GROUP BY e.id_equipo
                ORDER BY e.grupo ASC, e.nombre ASC
            ";
            $equipos = db_fetch_all($this->pdo, $sql);
        }
        
        // Obtener estadísticas de QRs
        $qr_stats = QRGenerator::getStats($this->pdo);
        
        // Renderizar vista
        include __DIR__ . '/../../public/views/equipos_listado.php';
    }
    
    /**
     * GET /equipos/crear - Formulario para crear nuevo equipo
     */
    public function create(array $params = []) {
        // Verificar autenticación admin
        require_admin_auth();
        
        include __DIR__ . '/../../public/views/equipo_form.php';
    }
    
    /**
     * POST /equipos/crear - Procesar creación de equipo
     */
    public function store() {
        try {
            // Verificar autenticación admin
            require_admin_auth();
            
            // Validar método POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_error('Método no permitido', 405);
            }
            
            // Obtener datos
            $nombre = trim($_POST['nombre'] ?? '');
            $grupo = $_POST['grupo'] ?? '';
            
            // Validaciones
            if (empty($nombre)) {
                redirect_with_message(BASE_URL . '/equipos/crear', 'El nombre del equipo es obligatorio', 'error');
            }
            
            if (!in_array($grupo, ['A', 'B'])) {
                redirect_with_message(BASE_URL . '/equipos/crear', 'Grupo inválido. Debe ser A o B', 'error');
            }
            
            // Verificar si ya existe un equipo con ese nombre
            $existe = db_fetch_one(
                $this->pdo,
                "SELECT COUNT(*) as total FROM equipos WHERE nombre = :nombre",
                ['nombre' => $nombre]
            );
            
            if ($existe['total'] > 0) {
                redirect_with_message(BASE_URL . '/equipos/crear', 'Ya existe un equipo con ese nombre', 'warning');
            }
            
            // Insertar equipo
            $id_equipo = db_insert($this->pdo, 'equipos', [
                'nombre' => $nombre,
                'grupo' => $grupo
            ]);
            
            // Generar QR automáticamente
            QRGenerator::generateForTeam($id_equipo, $nombre);
            
            app_log("Equipo creado: #$id_equipo - $nombre (Grupo $grupo)");
            
            redirect_with_message(
                BASE_URL . '/equipos/listar',
                "✅ Equipo '$nombre' creado exitosamente en Grupo $grupo. QR generado automáticamente.",
                'success'
            );
            
        } catch (\Exception $e) {
            error_log("Error en store equipo: " . $e->getMessage());
            redirect_with_message(BASE_URL, 'Error interno al crear equipo', 'error');
        }
    }
    
    /**
     * GET /equipos/editar/{id} - Formulario para editar equipo
     */
    public function edit(array $params = []) {
        // Verificar autenticación admin
        require_admin_auth();
        
        $id_equipo = $params['id'] ?? null;
        
        if (!$id_equipo) {
            redirect_with_message(BASE_URL . '/equipos/listar', 'ID de equipo no especificado', 'error');
        }
        
        $equipo = db_fetch_one(
            $this->pdo,
            "SELECT * FROM equipos WHERE id_equipo = :id",
            ['id' => (int)$id_equipo]
        );
        
        if (!$equipo) {
            redirect_with_message(BASE_URL . '/equipos/listar', 'Equipo no encontrado', 'error');
        }
        
        include __DIR__ . '/../../public/views/equipo_form.php';
    }
    
    /**
     * POST /equipos/editar/{id} - Procesar edición de equipo
     */
    public function update(array $params = []) {
        try {
            // Verificar autenticación admin
            require_admin_auth();
            
            // Validar método POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_error('Método no permitido', 405);
            }
            
            $id_equipo = $params['id'] ?? null;
            
            if (!$id_equipo) {
                redirect_with_message(BASE_URL . '/equipos/listar', 'ID de equipo no especificado', 'error');
            }
            
            // Obtener datos
            $nombre = trim($_POST['nombre'] ?? '');
            $grupo = $_POST['grupo'] ?? '';
            
            // Validaciones
            if (empty($nombre)) {
                redirect_with_message(BASE_URL . "/equipos/editar/$id_equipo", 'El nombre del equipo es obligatorio', 'error');
            }
            
            if (!in_array($grupo, ['A', 'B'])) {
                redirect_with_message(BASE_URL . "/equipos/editar/$id_equipo", 'Grupo inválido', 'error');
            }
            
            // Verificar si el nombre ya existe en otro equipo
            $existe = db_fetch_one(
                $this->pdo,
                "SELECT COUNT(*) as total FROM equipos WHERE nombre = :nombre AND id_equipo != :id",
                ['nombre' => $nombre, 'id' => (int)$id_equipo]
            );
            
            if ($existe['total'] > 0) {
                redirect_with_message(BASE_URL . "/equipos/editar/$id_equipo", 'Ya existe otro equipo con ese nombre', 'warning');
            }
            
            // Actualizar equipo
            db_update(
                $this->pdo,
                'equipos',
                [
                    'nombre' => $nombre,
                    'grupo' => $grupo
                ],
                'id_equipo = :id',
                ['id' => (int)$id_equipo]
            );
            
            // Si cambió el grupo, regenerar QR
            $equipo_anterior = db_fetch_one(
                $this->pdo,
                "SELECT grupo FROM equipos WHERE id_equipo = :id",
                ['id' => (int)$id_equipo]
            );
            
            if ($equipo_anterior['grupo'] !== $grupo) {
                QRGenerator::delete((int)$id_equipo);
                QRGenerator::generateForTeam((int)$id_equipo, $nombre);
            }
            
            app_log("Equipo actualizado: #$id_equipo - $nombre (Grupo $grupo)");
            
            redirect_with_message(
                BASE_URL . '/equipos/listar',
                "✅ Equipo '$nombre' actualizado exitosamente",
                'success'
            );
            
        } catch (\Exception $e) {
            error_log("Error en update equipo: " . $e->getMessage());
            redirect_with_message(BASE_URL, 'Error interno al actualizar equipo', 'error');
        }
    }
    
    /**
     * DELETE /equipos/eliminar/{id} - Eliminar equipo (Admin)
     */
    public function delete(array $params = []) {
        try {
            // Verificar autenticación admin
            require_admin_auth();
            
            $id_equipo = $params['id'] ?? null;
            
            if (!$id_equipo) {
                json_error('ID de equipo requerido', 400);
            }
            
            // Verificar que el equipo existe
            $equipo = db_fetch_one(
                $this->pdo,
                "SELECT * FROM equipos WHERE id_equipo = :id",
                ['id' => (int)$id_equipo]
            );
            
            if (!$equipo) {
                json_error('Equipo no encontrado', 404);
            }
            
            // Verificar si tiene jugadores (no permitir eliminar si tiene jugadores registrados)
            $jugadores_count = db_fetch_one(
                $this->pdo,
                "SELECT COUNT(*) as total FROM jugadores WHERE id_equipo = :id",
                ['id' => (int)$id_equipo]
            );
            
            if ($jugadores_count['total'] > 0) {
                json_error("No se puede eliminar el equipo porque tiene {$jugadores_count['total']} jugador(es) registrado(s). Elimine los jugadores primero.", 400);
            }
            
            // Eliminar QR si existe
            QRGenerator::delete((int)$id_equipo);
            
            // Eliminar equipo
            db_delete(
                $this->pdo,
                'equipos',
                'id_equipo = :id',
                ['id' => (int)$id_equipo]
            );
            
            app_log("Equipo eliminado: ID #$id_equipo - {$equipo['nombre']}");
            
            json_success([], 'Equipo eliminado exitosamente');
            
        } catch (\Exception $e) {
            error_log("Error en delete equipo: " . $e->getMessage());
            json_error('Error al eliminar equipo: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * POST /equipos/generar-qr/{id} - Regenerar QR de un equipo específico
     */
    public function generarQR(array $params = []) {
        try {
            // Verificar autenticación admin
            require_admin_auth();
            
            $id_equipo = $params['id'] ?? null;
            
            if (!$id_equipo) {
                json_error('ID de equipo requerido', 400);
            }
            
            $equipo = db_fetch_one(
                $this->pdo,
                "SELECT * FROM equipos WHERE id_equipo = :id",
                ['id' => (int)$id_equipo]
            );
            
            if (!$equipo) {
                json_error('Equipo no encontrado', 404);
            }
            
            // Eliminar QR anterior si existe
            QRGenerator::delete((int)$id_equipo);
            
            // Generar nuevo QR
            $success = QRGenerator::generateForTeam((int)$id_equipo, $equipo['nombre']);
            
            if ($success) {
                app_log("QR regenerado para equipo #$id_equipo - {$equipo['nombre']}");
                json_success([
                    'qr_url' => get_qr_url((int)$id_equipo)
                ], 'QR regenerado exitosamente');
            } else {
                json_error('Error al generar QR', 500);
            }
            
        } catch (\Exception $e) {
            error_log("Error generando QR: " . $e->getMessage());
            json_error('Error al generar QR: ' . $e->getMessage(), 500);
        }
    }
    
        /**
     * GET /equipos/ver/{id} - Ver detalles de un equipo
     */
    public function show(array $params = []) {
        // Usar la conexión PDO de la clase, NO global
        $id_equipo = $params['id'] ?? null;
        
        if (!$id_equipo) {
            redirect_with_message(BASE_URL, 'ID de equipo no especificado', 'error');
        }
        
        try {
            // 1. Obtener datos del equipo
            $equipo = db_fetch_one(
                $this->pdo, // <--- USAR $this->pdo
                "SELECT * FROM equipos WHERE id_equipo = :id",
                ['id' => (int)$id_equipo]
            );
            
            if (!$equipo) {
                redirect_with_message(BASE_URL, 'Equipo no encontrado', 'error');
            }
            
            // 2. Obtener jugadores del equipo
            $jugadores = db_fetch_all(
                $this->pdo, // <--- USAR $this->pdo
                "SELECT * FROM jugadores WHERE id_equipo = :id ORDER BY nombre ASC",
                ['id' => (int)$id_equipo]
            );
            
                        // 3. Obtener partidos del equipo
            $sql_partidos = "
                SELECT f.*, 
                       e1.nombre as nombre_equipo_a, 
                       e2.nombre as nombre_equipo_b
                FROM fixture f
                JOIN equipos e1 ON f.equipo_a = e1.id_equipo
                JOIN equipos e2 ON f.equipo_b = e2.id_equipo
                WHERE f.equipo_a = :id1 OR f.equipo_b = :id2
                ORDER BY f.fecha ASC, f.hora ASC
            ";
            
            $partidos = db_fetch_all(
                $this->pdo,
                $sql_partidos,
                [
                    'id1' => (int)$id_equipo,
                    'id2' => (int)$id_equipo
                ]
            );
            
            // 4. Cargar la vista
            // Pasamos las variables a la vista explícitamente si es necesario, 
            // pero al incluirse aquí, estarán disponibles.
            include __DIR__ . '/../../public/views/equipo_detalle.php';
            
        } catch (\Exception $e) {
            // Loguear el error real para verlo en Railway
            error_log("❌ ERROR CRÍTICO en EquipoController::show(): " . $e->getMessage());
            error_log("Stack Trace: " . $e->getTraceAsString());
            
            http_response_code(500);
            // Mostrar el error real temporalmente para debug
            echo "<h1>Error Interno</h1><p>Detalles: " . $e->getMessage() . "</p>";
        }
    }
}