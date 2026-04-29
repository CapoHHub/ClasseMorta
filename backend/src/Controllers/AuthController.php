<?php

// Controller responsabile dell'autenticazione utente.
class AuthController
{
    // Chiave segreta usata per firmare il JWT.
    private string $jwtSecret;
    // Archivio utenti (demo) indicizzato per email.
    private array $users;

    public function __construct()
    {
        // Legge la secret dal sistema; fallback per ambiente locale.
        $this->jwtSecret = $_ENV["JWT_SECRET"] ?? getenv("JWT_SECRET") ?: "dev-secret-change-me";
        // Prepara la lista utenti autorizzati.
        $this->users = $this->buildUsers();
    }

    public function login(): void
    {
        // Decodifica il body JSON della richiesta HTTP.
        $payload = json_decode(file_get_contents("php://input"), true);

        // Valida presenza dei campi obbligatori.
        if (!is_array($payload) || empty($payload["email"]) || empty($payload["password"])) {
            $this->respond(400, ["message" => "Email e password sono obbligatori"]);
            return;
        }

        // Normalizza email e legge password in chiaro inviata dal client.
        $email = strtolower(trim($payload["email"]));
        $password = (string) $payload["password"];

        // Se email non esiste restituisce errore di autenticazione.
        if (!isset($this->users[$email])) {
            $this->respond(401, ["message" => "Credenziali non valide"]);
            return;
        }

        $user = $this->users[$email];
        // Confronta password in chiaro con hash Argon2id salvato.
        if (!password_verify($password, $user["password_hash"])) {
            $this->respond(401, ["message" => "Credenziali non valide"]);
            return;
        }

        // Se le credenziali sono corrette genera il token JWT.
        $token = $this->createJwt([
            "sub" => $user["id"],
            "email" => $user["email"],
            "name" => $user["name"],
        ]);

        // Ritorna payload standard di login riuscito.
        $this->respond(200, [
            "message" => "Login effettuato",
            "token" => $token,
            "token_type" => "Bearer",
            "expires_in" => 3600,
        ]);
    }

    private function buildUsers(): array
    {
        // Credenziali demo configurabili da variabili d'ambiente.
        $defaultEmail = $_ENV["AUTH_EMAIL"] ?? getenv("AUTH_EMAIL") ?: "admin@example.com";
        $plainPassword = $_ENV["AUTH_PASSWORD"] ?? getenv("AUTH_PASSWORD") ?: "admin123";

        // In un caso reale qui leggeresti da database.
        return [
            strtolower($defaultEmail) => [
                "id" => 1,
                "name" => "Admin",
                "email" => $defaultEmail,
                // Hash Argon2id richiesto per la password.
                "password_hash" => password_hash($plainPassword, PASSWORD_ARGON2ID),
            ],
        ];
    }

    private function createJwt(array $claims): string
    {
        // Header standard JWT con algoritmo HMAC-SHA256.
        $header = ["alg" => "HS256", "typ" => "JWT"];
        // Timestamp corrente.
        $now = time();

        // Claims finali con emissione e scadenza a 1 ora.
        $payload = array_merge($claims, [
            "iat" => $now,
            "exp" => $now + 3600,
        ]);

        // Codifica URL-safe dei segmenti.
        $baseHeader = $this->base64UrlEncode(json_encode($header));
        $basePayload = $this->base64UrlEncode(json_encode($payload));
        // Firma crittografica del token.
        $signature = hash_hmac("sha256", $baseHeader . "." . $basePayload, $this->jwtSecret, true);

        // Restituisce JWT nel formato header.payload.signature.
        return $baseHeader . "." . $basePayload . "." . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $input): string
    {
        // Converte Base64 standard in Base64 URL-safe senza padding.
        return rtrim(strtr(base64_encode($input), "+/", "-_"), "=");
    }

    private function respond(int $statusCode, array $data): void
    {
        // Imposta status code HTTP e risponde in JSON.
        http_response_code($statusCode);
        echo json_encode($data);
    }
}
