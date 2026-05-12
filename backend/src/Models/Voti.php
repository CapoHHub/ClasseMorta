<?php

require_once __DIR__ . '/../helpers/mongo.php';

/**
 * Modello per la collection MongoDB "voti".
 * Documento esempio:
 *   {
 *     studente_id: 1,
 *     professore_id: 1,
 *     materia: "Matematica",
 *     voto: 7.5,
 *     descrizione: "Verifica scritta capitolo 3",
 *     data: "2025-04-12T10:00:00Z"
 *   }
 */
class Voti {

    /**
     * VOLUTAMENTE VULNERABILE A NoSQL INJECTION.
     * Il filtro arriva direttamente dall'input dell'utente.
     * Esempio di payload didattico (POST JSON):
     *   { "filtro": { "studente_id": { "$ne": null } } }
     */
    public function findVulnerabile(array $filtroUtente): array {
        return mongoFind('voti', $filtroUtente, ['sort' => ['data' => -1]]);
    }

    public function findByStudente(int $studenteId): array {
        return mongoFind('voti', ['studente_id' => $studenteId], ['sort' => ['data' => -1]]);
    }

    public function findByStudenteEProfessore(int $studenteId, int $professoreId): array {
        return mongoFind('voti', [
            'studente_id'   => $studenteId,
            'professore_id' => $professoreId,
        ], ['sort' => ['data' => -1]]);
    }

    public function inserisci(array $voto): string {
        $voto['data'] = $voto['data'] ?? date('c');
        return mongoInsertOne('voti', $voto);
    }

    public function elimina(string $id): int {
        try {
            $oid = new MongoDB\BSON\ObjectId($id);
        } catch (Throwable $e) {
            return 0;
        }
        return mongoDeleteOne('voti', ['_id' => $oid]);
    }

    public function findById(string $id): ?array {
        try {
            $oid = new MongoDB\BSON\ObjectId($id);
        } catch (Throwable $e) {
            return null;
        }
        return mongoFindOne('voti', ['_id' => $oid]);
    }

    /**
     * Aggiorna i campi indicati di un voto esistente.
     *
     * @param string $id  ObjectId del documento
     * @param array  $set Campi consentiti già validati (voto, materia, descrizione, ...)
     * @return int 0 se non modificato, 1 se aggiornato
     */
    public function aggiorna(string $id, array $set): int {
        try {
            $oid = new MongoDB\BSON\ObjectId($id);
        } catch (Throwable $e) {
            return 0;
        }
        return mongoUpdateOne('voti', ['_id' => $oid], $set);
    }
}
