<?php

require_once __DIR__ . '/db.php';

function jwtSecret(): string {
    $env = loadEnv();
    return $env["JWT_SECRET"] ?? "dev-secret-change-me";
}

function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode(string $data): string {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Genera un JWT HS256.
 *
 * @param array $claims  Dati da inserire nel payload (es. sub, ruolo, email, classe)
 * @param int $ttl       Secondi di validità (default 1h)
 */
function jwtEncode(array $claims, int $ttl = 3600): string {
    $header  = ['alg' => 'HS256', 'typ' => 'JWT'];
    $now     = time();
    $payload = array_merge($claims, ['iat' => $now, 'exp' => $now + $ttl]);

    $h = base64UrlEncode(json_encode($header));
    $p = base64UrlEncode(json_encode($payload));
    $s = base64UrlEncode(hash_hmac('sha256', "$h.$p", jwtSecret(), true));
    return "$h.$p.$s";
}

/**
 * Decodifica e verifica un JWT.
 * Restituisce il payload se valido, altrimenti lancia eccezione.
 */
function jwtDecode(string $token): array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        throw new RuntimeException("Token JWT malformato");
    }
    [$h, $p, $s] = $parts;

    $expectedSig = base64UrlEncode(hash_hmac('sha256', "$h.$p", jwtSecret(), true));
    if (!hash_equals($expectedSig, $s)) {
        throw new RuntimeException("Firma JWT non valida");
    }
    $payload = json_decode(base64UrlDecode($p), true);
    if (!is_array($payload)) {
        throw new RuntimeException("Payload JWT illeggibile");
    }
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        throw new RuntimeException("Token scaduto");
    }
    return $payload;
}

/**
 * Estrae e valida il JWT dall'header Authorization.
 * Imposta status 401 e termina la richiesta se non valido.
 *
 * @param array|null $ruoliAmmessi Lista di ruoli ammessi (es. ['professore'])
 * @return array Payload del JWT
 */
function richiediAuth(?array $ruoliAmmessi = null): array {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (!preg_match('/Bearer\s+(.+)/i', $authHeader, $m)) {
        http_response_code(401);
        echo json_encode(['message' => 'Token mancante']);
        exit;
    }
    try {
        $payload = jwtDecode(trim($m[1]));
    } catch (Throwable $e) {
        http_response_code(401);
        echo json_encode(['message' => 'Token non valido', 'error' => $e->getMessage()]);
        exit;
    }
    if ($ruoliAmmessi !== null && !in_array($payload['ruolo'] ?? '', $ruoliAmmessi, true)) {
        http_response_code(403);
        echo json_encode(['message' => 'Accesso negato per il tuo ruolo']);
        exit;
    }
    return $payload;
}
