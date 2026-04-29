<?php 

require __DIR__ . '/src/Controllers/StudentiController.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Esplodi URL
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// Controllo formato endpoint
if ($uri[1] !== 'api') {
    http_response_code(404);
    echo json_encode(["message" => "Endpoint non trovato"]);
    exit();
} 

// Router
try {
    
    switch ($uri[2]) {
        case 'studenti':
            $classe = $_GET['classe'];
            $controller = new StudentiController();
            if ($requestMethod == 'GET') {
                $controller->getAll($classe);
            } elseif ($requestMethod == 'POST') {
                $controller->create();
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(["message" => "Risorsa non trovata"]);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["message" => "Errore interno", "error" => $e->getMessage()]);
}

?>