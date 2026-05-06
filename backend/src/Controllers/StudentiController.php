<?php

require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../Models/Utenti.php';

class StudentiController {

    public function getByClasse(string $classe): void {
        $auth = richiediAuth(['professore']);
        $model = new Utenti();

        // Il prof può vedere solo le classi in cui insegna.
        $classi = $model->getClassiInsegnate((int) $auth['sub']);
        if (!in_array($classe, $classi, true)) {
            http_response_code(403);
            echo json_encode(['message' => "Non insegni nella classe $classe"]);
            return;
        }

        $studenti = $model->getStudentiByClasse($classe);
        echo json_encode($studenti);
    }

    public function getById(int $id): void {
        $auth = richiediAuth(['professore']);
        $model = new Utenti();

        $studente = $model->getStudenteById($id);
        if (!$studente) {
            http_response_code(404);
            echo json_encode(['message' => 'Studente non trovato']);
            return;
        }
        if (!$model->professoreInsegnaAStudente((int) $auth['sub'], $id)) {
            http_response_code(403);
            echo json_encode(['message' => 'Non sei autorizzato a vedere questo studente']);
            return;
        }
        echo json_encode($studente);
    }

    public function classiProfessore(): void {
        $auth = richiediAuth(['professore']);
        $model = new Utenti();
        echo json_encode([
            'classi'  => $model->getClassiInsegnate((int) $auth['sub']),
            'materie' => $model->getMaterieInsegnate((int) $auth['sub']),
        ]);
    }
}
