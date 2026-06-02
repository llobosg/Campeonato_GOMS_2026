<?php
/**
 * limpiar_resultados.php - Script para resetear resultados de partidos
 * EJECUTAR UNA SOLA VEZ desde el navegador: https://campeonatogoms2026.up.railway.app/limpiar_resultados.php
 */

define('APP_ENTRY_POINT', true);
require_once __DIR__ . '/../config.php';

echo "<h1>🧹 Limpiando Resultados de Prueba...</h1>";
echo "<ul>";

try {
    // 1. Borrar todos los goles registrados
    $sql_delete_goles = "DELETE FROM goles";
    $stmt = $pdo->prepare($sql_delete_goles);
    $stmt->execute();
    echo "<li style='color:green;'>✅ Tabla 'goles' vaciada correctamente.</li>";

    // 2. Opcional: Resetear marcadores en fixture (poner goles_a y goles_b a NULL o 0)
    // Si tienes columnas de marcador directo en la tabla fixture, descomenta esto:
    /*
    $sql_reset_fixture = "UPDATE fixture SET goles_equipo_a = NULL, goles_equipo_b = NULL, estado = 'Pendiente'";
    $stmt = $pdo->prepare($sql_reset_fixture);
    $stmt->execute();
    echo "<li style='color:green;'>✅ Marcadores en 'fixture' reseteados.</li>";
    */

    // 3. Verificar que las vistas estén limpias (consultando una muestra)
    $check_posiciones = $pdo->query("SELECT COUNT(*) FROM v_posiciones")->fetchColumn();
    echo "<li>ℹ️ Registros actuales en v_posiciones: $check_posiciones (Debería ser 0 o solo equipos sin partidos)</li>";

    echo "</ul>";
    echo "<p><strong>¡Limpieza completada!</strong> Puedes borrar este archivo ahora.</p>";

} catch (\Exception $e) {
    echo "<li style='color:red;'>❌ Error: " . $e->getMessage() . "</li>";
}
?>