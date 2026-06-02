<?php
/**
 * JugadorController.php - Controlador de Registro de Jugadores
 * Ubicación: src/Controllers/JugadorController.php
 * Maneja: Registro vía QR/Link, Listado, Eliminación
 */

namespace App\Controllers;

use PDO;
use BrevoMailer;

class JugadorController {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * GET /jugadores/registrar/{id_equipo} - Formulario de registro para un equipo específico
     */
    public function register(array $params = []) {
        $id_equipo = $params['id'] ?? null;
        
        if (!$id_equipo) {
            redirect_with_message(BASE_URL, 'Error: No se especificó el equipo', 'error');
        }
        
        // Verificar que el equipo existe
        $equipo = db_fetch_one(
            $this->pdo,
            "SELECT * FROM equipos WHERE id_equipo = :id",
            ['id' => (int)$id_equipo]
        );
        
        if (!$equipo) {
            redirect_with_message(BASE_URL, 'Error: Equipo no encontrado', 'error');
        }
        
        // Renderizar formulario de registro
        include __DIR__ . '/../../public/views/registro_jugador.php';
    }
    
    /**
     * POST /jugadores/registrar - Procesar registro de jugador
     */
    public function store() {
        try {
            // Validar método POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_error('Método no permitido', 405);
            }
            
            // Obtener datos del formulario
            $id_equipo = $_POST['id_equipo'] ?? null;
            $nombre = trim($_POST['nombre'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $area = trim($_POST['area'] ?? '');
            
            // Validaciones básicas
            if (!$id_equipo || !$nombre || !$correo) {
                redirect_with_message(
                    BASE_URL . "/jugadores/registrar/$id_equipo",
                    'Todos los campos son obligatorios',
                    'error'
                );
            }
            
            if (!isValidEmail($correo)) {
                redirect_with_message(
                    BASE_URL . "/jugadores/registrar/$id_equipo",
                    'Correo electrónico inválido',
                    'error'
                );
            }
            
            // Verificar que el equipo existe
            $equipo = db_fetch_one(
                $this->pdo,
                "SELECT * FROM equipos WHERE id_equipo = :id",
                ['id' => (int)$id_equipo]
            );
            
            if (!$equipo) {
                redirect_with_message(BASE_URL, 'Equipo no encontrado', 'error');
            }
            
            // Verificar si el correo ya está registrado en este equipo
            $existe = db_fetch_one(
                $this->pdo,
                "SELECT COUNT(*) as total FROM jugadores WHERE id_equipo = :equipo AND correo = :correo",
                ['equipo' => (int)$id_equipo, 'correo' => $correo]
            );
            
            if ($existe['total'] > 0) {
                redirect_with_message(
                    BASE_URL . "/jugadores/registrar/$id_equipo",
                    'Este correo ya está registrado en el equipo',
                    'warning'
                );
            }
            
            // Insertar jugador
            $id_jugador = db_insert($this->pdo, 'jugadores', [
                'id_equipo' => (int)$id_equipo,
                'nombre' => $nombre,
                'correo' => $correo,
                'area' => $area ?: null
            ]);
            
            // Enviar email de confirmación
            $this->enviarConfirmacionRegistro($correo, $nombre, $equipo['nombre']);
            
            // Log
            app_log("Jugador registrado: $nombre ($correo) en equipo {$equipo['nombre']} (ID: $id_equipo)");
            
            // Redirigir con éxito
            redirect_with_message(
                BASE_URL . "/jugadores/registrar/$id_equipo",
                "✅ ¡Registro exitoso! Bienvenido al equipo {$equipo['nombre']}. Te hemos enviado un correo de confirmación.",
                'success'
            );
            
        } catch (\Exception $e) {
            error_log("Error en store jugador: " . $e->getMessage());
            redirect_with_message(
                BASE_URL,
                'Error interno al registrar jugador. Intente nuevamente.',
                'error'
            );
        }
    }
    
    /**
     * GET /jugadores/listar - Listar todos los jugadores (admin)
     */
    public function index(array $params = []) {
        // Verificar autenticación admin (opcional, según requerimientos)
        // require_admin_auth();
        
        $equipo_id = $_GET['equipo'] ?? null;
        
        if ($equipo_id) {
            $sql = "
                SELECT j.*, e.nombre as equipo_nombre, e.grupo
                FROM jugadores j
                JOIN equipos e ON j.id_equipo = e.id_equipo
                WHERE j.id_equipo = :equipo_id
                ORDER BY j.nombre ASC
            ";
            $jugadores = db_fetch_all($this->pdo, $sql, ['equipo_id' => (int)$equipo_id]);
        } else {
            $sql = "
                SELECT j.*, e.nombre as equipo_nombre, e.grupo
                FROM jugadores j
                JOIN equipos e ON j.id_equipo = e.id_equipo
                ORDER BY e.grupo ASC, e.nombre ASC, j.nombre ASC
            ";
            $jugadores = db_fetch_all($this->pdo, $sql);
        }
        
        // Renderizar vista de listado
        include __DIR__ . '/../../public/views/jugadores_listado.php';
    }
    
    /**
     * DELETE /jugadores/eliminar/{id} - Eliminar jugador (admin)
     */
    public function delete(array $params = []) {
        try {
            // Verificar autenticación admin
            if (!is_admin_authenticated()) {
                json_error('No autorizado. Se requiere contraseña de administrador.', 403);
            }
            
            $id_jugador = $params['id'] ?? null;
            
            if (!$id_jugador) {
                json_error('ID de jugador requerido', 400);
            }
            
            // Verificar que el jugador existe
            $jugador = db_fetch_one(
                $this->pdo,
                "SELECT * FROM jugadores WHERE id_jugador = :id",
                ['id' => (int)$id_jugador]
            );
            
            if (!$jugador) {
                json_error('Jugador no encontrado', 404);
            }
            
            // Eliminar jugador
            db_delete(
                $this->pdo,
                'jugadores',
                'id_jugador = :id',
                ['id' => (int)$id_jugador]
            );
            
            app_log("Jugador eliminado: ID #$id_jugador - {$jugador['nombre']}");
            
            json_success([], 'Jugador eliminado exitosamente');
            
        } catch (\Exception $e) {
            error_log("Error en delete jugador: " . $e->getMessage());
            json_error('Error al eliminar jugador', 500);
        }
    }
    
    /**
     * Enviar email de confirmación de registro
     */
    private function enviarConfirmacionRegistro(string $email, string $nombre, string $equipo_nombre): bool {
        try {
            $mailer = new BrevoMailer();
            
            $subject = "✅ Confirmación de Registro - Campeonato GOMS 2026";
            
            $htmlBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #00ff87 0%, #3a86ff 100%); padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='color: white; margin: 0;'>⚽ CAMPEONATO GOMS 2026</h1>
                </div>
                
                <div style='padding: 30px; background: #f9f9f9;'>
                    <h2 style='color: #333;'>¡Bienvenido al equipo!</h2>
                    
                    <p style='font-size: 16px; color: #555;'>
                        Hola <strong>$nombre</strong>,
                    </p>
                    
                    <p style='font-size: 16px; color: #555;'>
                        Tu registro en el equipo <strong style='color: #00ff87;'>$equipo_nombre</strong> ha sido confirmado exitosamente.
                    </p>
                    
                    <div style='background: white; padding: 20px; border-left: 4px solid #00ff87; margin: 20px 0;'>
                        <h3 style='margin-top: 0; color: #333;'>Detalles de tu registro:</h3>
                        <ul style='color: #555;'>
                            <li><strong>Nombre:</strong> $nombre</li>
                            <li><strong>Equipo:</strong> $equipo_nombre</li>
                            <li><strong>Correo:</strong> $email</li>
                            <li><strong>Fecha de registro:</strong> " . date('d/m/Y H:i') . "</li>
                        </ul>
                    </div>
                    
                    <p style='font-size: 16px; color: #555;'>
                        Mantente atento a las actualizaciones del fixture y resultados. ¡Mucho éxito en el campeonato!
                    </p>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='" . BASE_URL . "' style='display: inline-block; background: #00ff87; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                            Ver Fixture y Resultados
                        </a>
                    </div>
                </div>
                
                <div style='background: #333; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px;'>
                    <p style='margin: 0; font-size: 14px;'>© 2026 Campeonato GOMS | Desarrollado por GLT Sport</p>
                </div>
            </div>
            ";
            
            return $mailer->send($email, $subject, $htmlBody);
            
        } catch (\Exception $e) {
            error_log("Error enviando email de confirmación: " . $e->getMessage());
            return false;
        }
    }
}