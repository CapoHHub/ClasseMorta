<?php

require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../Models/Voti.php';
require_once __DIR__ . '/../Models/Utenti.php';

class VotiController {

    /**
     * GET /api/voti?studente_id=...
     *
     * VULNERABILE a NoSQL injection: $_GET può contenere array nidificati,
     * quindi un client malevolo può passare:
     *   /api/voti?studente_id[$ne]=null
     * e ottenere TUTTI i voti.
     *
     * Demo accettata sia per professori che per studenti
     * (lo studente normalmente vede solo i propri, ma con NoSQLi può vederli tutti).
     */
    public function lista(): void {
        $auth = richiediAuth();
        $model = new Voti();

        $filtro = [];
        if (isset($_GET['studente_id'])) {
            $raw = $_GET['studente_id'];
            // I documenti hanno studente_id come intero: per il caso d'uso
            // normale castiamo. Se invece arriva un array (payload NoSQLi tipo
            // studente_id[$ne]=null) lo lasciamo passare com'è: vulnerabilità
            // volontaria.
            $filtro['studente_id'] = is_scalar($raw) ? (int) $raw : $raw;
        }
        if (isset($_GET['materia']) && $_GET['materia'] !== '') {
            $filtro['materia'] = $_GET['materia'];
        }

        // Per gli studenti, in modalità non-injection, forziamo lo studente_id
        // al loro id. Ma se passano l'array, il merge non avviene (didattico).
        if (($auth['ruolo'] ?? '') === 'studente' && !isset($filtro['studente_id'])) {
            $filtro['studente_id'] = (int) $auth['sub'];
        }

        $voti = $model->findVulnerabile($filtro);
        echo json_encode($voti);
    }

    /**
     * POST /api/voti
     * Solo professori. Body JSON: { studente_id, materia, voto, descrizione }
     */
    public function crea(): void {
        $auth = richiediAuth(['professore']);
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) {
            http_response_code(400);
            echo json_encode(['message' => 'Body JSON non valido']);
            return;
        }
        $studenteId = isset($body['studente_id']) ? (int) $body['studente_id'] : 0;
        $materia    = trim((string) ($body['materia'] ?? ''));
        $voto       = isset($body['voto']) ? (float) $body['voto'] : null;
        $descr      = trim((string) ($body['descrizione'] ?? ''));

        if ($studenteId <= 0 || $materia === '' || $voto === null) {
            http_response_code(400);
            echo json_encode(['message' => 'studente_id, materia e voto sono obbligatori']);
            return;
        }

        $utenti = new Utenti();
        if (!$utenti->professoreInsegnaAStudente((int) $auth['sub'], $studenteId)) {
            http_response_code(403);
            echo json_encode(['message' => 'Non sei autorizzato a inserire voti per questo studente']);
            return;
        }

        $model = new Voti();
        $id = $model->inserisci([
            'studente_id'   => $studenteId,
            'professore_id' => (int) $auth['sub'],
            'materia'       => $materia,
            'voto'          => $voto,
            'descrizione'   => $descr,
            'data'          => date('c'),
        ]);
        http_response_code(201);
        echo json_encode(['message' => 'Voto inserito', '_id' => $id]);
    }

    /**
     * DELETE /api/voti/:id
     */
    public function elimina(string $id): void {
        $auth = richiediAuth(['professore']);
        $model = new Voti();
        $deleted = $model->elimina($id);
        if (!$deleted) {
            http_response_code(404);
            echo json_encode(['message' => 'Voto non trovato']);
            return;
        }
        echo json_encode(['message' => 'Voto eliminato']);
    }

    /**
     * GET /api/voti/me - voti dello studente loggato.
     */
    public function miei(): void {
        $auth = richiediAuth(['studente']);
        $model = new Voti();
        echo json_encode($model->findByStudente((int) $auth['sub']));
    }
}
