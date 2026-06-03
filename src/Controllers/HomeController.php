<?php
namespace App\Controllers;
use PDO;

class HomeController {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function index(array $params = []) {
        // Si viene $pdo en params, úsalo, sino usa el de la clase
        $pdo = $params['pdo'] ?? $this->pdo;
        
        // Obtener datos
        $fechas = $this->getAllFechas($pdo);
        
        // ✅ OBTENER POSICIONES DIRECTAMENTE DE LA VISTA CON ORDENAMIENTO CORRECTO
        $posicionesA = db_fetch_all($pdo, 
            "SELECT * FROM v_posiciones WHERE grupo = 'A' ORDER BY puntos DESC, dg DESC, goles_favor DESC"
        );
        
        $posicionesB = db_fetch_all($pdo, 
            "SELECT * FROM v_posiciones WHERE grupo = 'B' ORDER BY puntos DESC, dg DESC, goles_favor DESC"
        );
        
        // Goleadores (mantenemos la función auxiliar si funciona bien)
        $goleadoresA = get_goleadores_grupo($pdo, 'A', 3);
        $goleadoresB = get_goleadores_grupo($pdo, 'B', 3);
        
        $fecha_actual = $this->getFechaActual($pdo);
        
        // Pasar variables a la vista
        include __DIR__ . '/../../public/views/home.php';
    }
    
    private function getAllFechas(PDO $pdo): array {
        $sql = "SELECT DISTINCT nro_fecha, fecha, MIN(hora) as hora_inicio, MAX(hora) as hora_fin FROM fixture GROUP BY nro_fecha, fecha ORDER BY nro_fecha ASC";
        return db_fetch_all($pdo, $sql);
    }
    
    private function getFechaActual(PDO $pdo): ?array {
        $hoy = date('Y-m-d');
        $sql = "SELECT nro_fecha, fecha, MIN(hora) as hora_inicio FROM fixture WHERE fecha = :hoy GROUP BY nro_fecha, fecha";
        $resultado = db_fetch_one($pdo, $sql, ['hoy' => $hoy]);
        
        if ($resultado) return $resultado;
        
        $sql = "SELECT nro_fecha, fecha, MIN(hora) as hora_inicio FROM fixture WHERE fecha > :hoy ORDER BY fecha ASC LIMIT 1";
        return db_fetch_one($pdo, $sql, ['hoy' => $hoy]);
    }
}