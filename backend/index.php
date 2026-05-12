<?php

require __DIR__ . '/src/Controllers/AuthController.php';
require __DIR__ . '/src/Controllers/StudentiController.php';
require __DIR__ . '/src/Controllers/VotiController.php';
require __DIR__ . '/src/Controllers/VerificheController.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = array_values(array_filter(explode('/', $path), fn($s) => $s !== ''));

if (count($segments) === 0 || $segments[0] !== 'api') {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint non trovato']);
    exit;
}

$resource = $segments[1] ?? '';

function methodNotAllowed(): void {
    http_response_code(405);
    echo json_encode(['message' => 'Metodo non consentito']);
}

try {
    switch ($resource) {

        // ===== AUTH =====
        case 'login':
            if ($method !== 'POST') return methodNotAllowed();
            (new AuthController())->login();
            break;

        case 'me':
            if ($method !== 'GET') return methodNotAllowed();
            (new AuthController())->me();
            break;

        // ===== STUDENTI =====
        case 'studenti':
            $sub = $segments[2] ?? null;

            if ($method === 'GET' && $sub === null) {
                $classe = $_GET['classe'] ?? '';
                if ($classe === '') {
                    http_response_code(400);
                    echo json_encode(['message' => 'Parametro classe obbligatorio']);
                    break;
                }
                (new StudentiController())->getByClasse($classe);
                break;
            }

            if ($method === 'GET' && ctype_digit($sub)) {
                (new StudentiController())->getById((int) $sub);
                break;
            }

            methodNotAllowed();
            break;

        case 'classi':
            // GET /api/classi -> classi e materie del professore loggato
            if ($method !== 'GET') return methodNotAllowed();
            (new StudentiController())->classiProfessore();
            break;

        // ===== VOTI =====
        case 'voti':
            $sub = $segments[2] ?? null;

            if ($method === 'GET' && $sub === 'me') {
                (new VotiController())->miei();
                break;
            }
            if ($method === 'GET' && $sub === null) {
                (new VotiController())->lista();
                break;
            }
            if ($method === 'POST' && $sub === null) {
                (new VotiController())->crea();
                break;
            }
            if ($method === 'PUT' && $sub !== null) {
                (new VotiController())->aggiorna($sub);
                break;
            }
            if ($method === 'DELETE' && $sub !== null) {
                (new VotiController())->elimina($sub);
                break;
            }
            methodNotAllowed();
            break;

        // ===== VERIFICHE =====
        case 'verifiche':
            $sub  = $segments[2] ?? null;
            $sub2 = $segments[3] ?? null;

            if ($method === 'GET' && $sub === null) {
                (new VerificheController())->lista();
                break;
            }
            if ($method === 'POST' && $sub === null) {
                (new VerificheController())->crea();
                break;
            }
            if (ctype_digit((string) $sub)) {
                $vid = (int) $sub;
                if ($sub2 === null && $method === 'GET') {
                    (new VerificheController())->dettaglio($vid);
                    break;
                }
                if ($sub2 === 'risposte') {
                    if ($method === 'POST') {
                        (new VerificheController())->consegna($vid);
                        break;
                    }
                    if ($method === 'GET') {
                        (new VerificheController())->risposte($vid);
                        break;
                    }
                }
            }
            methodNotAllowed();
            break;

        default:
            http_response_code(404);
            echo json_encode(['message' => 'Risorsa non trovata']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[ClasseMorta] ' . get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo json_encode([
        'message' => 'Errore interno',
        'error'   => $e->getMessage(),
        'type'    => get_class($e),
    ]);
}
