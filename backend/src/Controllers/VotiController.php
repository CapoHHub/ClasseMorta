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
     * PUT /api/voti/:id
     *
     * Solo professori. Body JSON con i campi da aggiornare (almeno uno):
     *   { voto?: number, materia?: string, descrizione?: string }
     *
     * Il professore può aggiornare un voto solo se insegna alla classe
     * dello studente intestatario del voto.
     */
    public function aggiorna(string $id): void {
        $auth = richiediAuth(['professore']);
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) {
            http_response_code(400);
            echo json_encode(['message' => 'Body JSON non valido']);
            return;
        }

        $model = new Voti();
        $voto = $model->findById($id);
        if (!$voto) {
            http_response_code(404);
            echo json_encode(['message' => 'Voto non trovato']);
            return;
        }

        $utenti = new Utenti();
        $studenteId = (int) ($voto['studente_id'] ?? 0);
        if (!$utenti->professoreInsegnaAStudente((int) $auth['sub'], $studenteId)) {
            http_response_code(403);
            echo json_encode([
                'message' => 'Non sei autorizzato a modificare i voti di questo studente',
            ]);
            return;
        }

        $set = [];
        if (array_key_exists('voto', $body)) {
            if (!is_numeric($body['voto'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Campo "voto" non numerico']);
                return;
            }
            $v = (float) $body['voto'];
            if ($v < 1 || $v > 10) {
                http_response_code(400);
                echo json_encode(['message' => 'Il voto deve essere compreso tra 1 e 10']);
                return;
            }
            $set['voto'] = $v;
        }
        if (array_key_exists('materia', $body)) {
            $materia = trim((string) $body['materia']);
            if ($materia === '') {
                http_response_code(400);
                echo json_encode(['message' => 'Campo "materia" non valido']);
                return;
            }
            $set['materia'] = $materia;
        }
        if (array_key_exists('descrizione', $body)) {
            $set['descrizione'] = trim((string) $body['descrizione']);
        }

        if (empty($set)) {
            http_response_code(400);
            echo json_encode(['message' => 'Nessun campo da aggiornare']);
            return;
        }

        $set['modificato_il']    = date('c');
        $set['modificato_da_id'] = (int) $auth['sub'];

        $count = $model->aggiorna($id, $set);
        echo json_encode([
            'message'  => 'Voto aggiornato',
            'modified' => $count,
            'campi'    => array_keys($set),
            'voto'     => array_merge($voto, $set),
        ]);
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
