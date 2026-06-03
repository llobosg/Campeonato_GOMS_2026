<?php
// public/api/visitas.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Credenciales de Google Analytics (Debes crearlas en Google Cloud Console)
$property_id = 'properties/TU_PROPERTY_ID'; // Ej: properties/123456789
$key_file = __DIR__ . '/../../ga-service-account.json'; 

if (!file_exists($key_file)) {
    echo json_encode(['count' => 0]);
    exit;
}

putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $key_file);

require_once __DIR__ . '/../../vendor/autoload.php'; // Asegúrate de tener google/analytics-data

try {
    $client = new \Google\Analytics\Data\V1beta\BetaAnalyticsDataClient();
    
    $response = $client->runReport([
        'property' => $property_id,
        'dateRanges' => [
            new \Google\Analytics\Data\V1beta\DateRange([
                'start_date' => 'today',
                'end_date' => 'today',
            ]),
        ],
        'metrics' => [
            new \Google\Analytics\Data\V1beta\Metric(['name' => 'activeUsers']),
        ],
    ]);

    $count = 0;
    foreach ($response->getRows() as $row) {
        $count = (int) $row->getMetricValues()[0]->getValue();
    }

    echo json_encode(['count' => $count]);

} catch (\Exception $e) {
    // Fallback a 0 si hay error de credenciales
    echo json_encode(['count' => 0]); 
}
?>