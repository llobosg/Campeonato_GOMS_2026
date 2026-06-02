<?php
/**
 * HomeController.php - Controlador Principal
 * Ubicación: src/Controllers/HomeController.php
 * Maneja la página de inicio con fixture, posiciones y goleadores
 */

namespace App\Controllers;

use PDO;

class HomeController {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Página principal - Muestra fixture, posiciones y goleadores
     */
    public function index(array $params = []) {
        // Obtener datos para la vista
        $fechas = $this->getAllFechas();
        $posicionesA = get_posiciones_grupo($this->pdo, 'A');
        $posicionesB = get_posiciones_grupo($this->pdo, 'B');
        $goleadoresA = get_goleadores_grupo($this->pdo, 'A', 3);
        $goleadoresB = get_goleadores_grupo($this->pdo, 'B', 3);
        
        // Fecha actual o próxima
        $fecha_actual = $this->getFechaActual();
        
        // Renderizar vista
        include __DIR__ . '/../../public/views/home.php';
    }
    
    /**
     * Obtener todas las fechas del fixture
     */
    private function getAllFechas(): array {
        $sql = "
            SELECT DISTINCT nro_fecha, fecha, 
                   MIN(hora) as hora_inicio,
                   MAX(hora) as hora_fin
            FROM fixture
            GROUP BY nro_fecha, fecha
            ORDER BY nro_fecha ASC
        ";
        return db_fetch_all($this->pdo, $sql);
    }
    
    /**
     * Determinar qué fecha se está jugando hoy o la próxima
     */
    private function getFechaActual(): ?array {
        $hoy = date('Y-m-d');
        
        // Buscar fecha de hoy
        $sql = "
            SELECT nro_fecha, fecha, MIN(hora) as hora_inicio
            FROM fixture
            WHERE fecha = :hoy
            GROUP BY nro_fecha, fecha
        ";
        $resultado = db_fetch_one($this->pdo, $sql, ['hoy' => $hoy]);
        
        if ($resultado) {
            return $resultado;
        }
        
        // Si no hay fecha hoy, buscar la próxima
        $sql = "
            SELECT nro_fecha, fecha, MIN(hora) as hora_inicio
            FROM fixture
            WHERE fecha > :hoy
            ORDER BY fecha ASC
            LIMIT 1
        ";
        return db_fetch_one($this->pdo, $sql, ['hoy' => $hoy]);
    }
}