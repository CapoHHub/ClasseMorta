<?php

require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../Models/Utenti.php';

/**
 * Gestione login e identità.
 *
 * NOTA DIDATTICA: il login è VOLUTAMENTE vulnerabile a SQL injection
 * (vedi Utenti::loginVulnerabile).
 */
class AuthController {

    public function login(): void {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload) || empty($payload['email']) || !isset($payload['password'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Email e password sono obbligatori']);
            return;
        }

        $email    = (string) $payload['email'];
        $password = (string) $payload['password'];
        $ruolo    = strtolower((string) ($payload['ruolo'] ?? 'studente'));

        $tabella = $ruolo === 'professore' ? 'professori' : 'studenti';

        $model = new Utenti();
        // ⚠️ Vulnerabile a SQL injection (didattico)
        $user = $model->loginVulnerabile($tabella, $email, $password);

        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Credenziali non valide']);
            return;
        }

        $claims = [
            'sub'     => (int) $user['id'],
            'email'   => $user['email'],
            'nome'    => $user['nome'],
            'cognome' => $user['cognome'],
            'ruolo'   => $ruolo === 'professore' ? 'professore' : 'studente',
        ];
        if (isset($user['classe'])) {
            $claims['classe'] = $user['classe'];
        }

        $token = jwtEncode($claims, 3600);
        echo json_encode([
            'message'    => 'Login effettuato',
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'utente'     => $claims,
        ]);
    }

    public function me(): void {
        $payload = richiediAuth();
        echo json_encode(['utente' => $payload]);
    }
}
