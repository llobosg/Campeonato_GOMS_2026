<?php
/**
 * limpiar_resultados.php - Script para resetear TODO el campeonato
 * EJECUTAR UNA SOLA VEZ desde el navegador
 */

define('APP_ENTRY_POINT', true);
require_once __DIR__ . '/../config.php';

echo "<h1>🧹 Limpiando Campeonato Completo...</h1>";
echo "<ul>";

try {
    // 1. Borrar todos los goles individuales
    $pdo->exec("DELETE FROM goles");
    echo "<li style='color:green;'>✅ Tabla 'goles' vaciada.</li>";

    // 2. Resetear la tabla FIXTURE (Quitar marcadores y dejar como pendiente)
    // Ajusta los nombres de columnas según tu BD (ej: goles_a, goles_b, resultado, estado)
    $pdo->exec("UPDATE fixture SET 
                goles_equipo_a = NULL, 
                goles_equipo_b = NULL, 
                estado = 'Pendiente',
                fecha_jugada = NULL");
    echo "<li style='color:green;'>✅ Tabla 'fixture' reseteada (marcadores borrados).</li>";

    // 3. Si tienes una tabla específica de 'resultados' o 'partidos_jugados', bórrala también
    // Descomenta si existe esta tabla en tu BD:
    // $pdo->exec("TRUNCATE TABLE resultados");
    // echo "<li style='color:green;'>✅ Tabla 'resultados' truncada.</li>";

    // 4. Verificar limpieza
    $count_fixture = $pdo->query("SELECT COUNT(*) FROM fixture WHERE estado != 'Pendiente'")->fetchColumn();
    $count_goles = $pdo->query("SELECT COUNT(*) FROM goles")->fetchColumn();
    
    echo "<li>ℹ️ Partidos con resultado activo: $count_fixture</li>";
    echo "<li>ℹ️ Goles registrados: $count_goles</li>";
    
    if ($count_fixture == 0 && $count_goles == 0) {
        echo "<li style='color:blue; font-weight:bold;'>🎉 ¡CAMPEONATO RESETEADO CON ÉXITO!</li>";
    } else {
        echo "<li style='color:orange;'>️ Quedan algunos datos residuales. Revisa las tablas manualmente.</li>";
    }

} catch (\Exception $e) {
    echo "<li style='color:red;'>❌ Error: " . $e->getMessage() . "</li>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</ul>";
echo "<p><strong>IMPORTANTE:</strong> Borra este archivo del servidor después de usarlo por seguridad.</p>";
?>