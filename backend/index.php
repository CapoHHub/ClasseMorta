<?php 


header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");



require __DIR__ . '/src/helpers/db.php';

$conn = getPDO();




$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

if ($uri[1] !== 'api') {
    http_response_code(404);
    echo json_encode(["message" => "Endpoint non trovato"]);
    exit();
} else {
    http_response_code(200);
}

// Router
switch ($uri[2]) {
    case 'users':
        $controller = new UserController();
        if ($requestMethod == 'GET') {
            $controller->getAll();
        } elseif ($requestMethod == 'POST') {
            $controller->create();
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(["message" => "Risorsa non trovata"]);
        break;
}

?>