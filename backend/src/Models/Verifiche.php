<?php

require_once __DIR__ . '/../helpers/db.php';

class Verifiche {

    public function listByClasse(string $classe): array {
        $conn = getPDO();
        $stmt = $conn->prepare(
            "SELECT v.id, v.titolo, v.descrizione, v.classe, v.data_creazione,
                    p.nome AS prof_nome, p.cognome AS prof_cognome,
                    m.nome AS materia
             FROM verifiche v
             JOIN professori p ON p.id = v.professore_id
             JOIN materie m ON m.id = v.materia_id
             WHERE v.classe = ?
             ORDER BY v.data_creazione DESC"
        );
        $stmt->execute([$classe]);
        return $stmt->fetchAll();
    }

    public function listByProfessore(int $professoreId): array {
        $conn = getPDO();
        $stmt = $conn->prepare(
            "SELECT v.id, v.titolo, v.descrizione, v.classe, v.data_creazione,
                    m.nome AS materia,
                    (SELECT COUNT(*) FROM domande d WHERE d.verifica_id = v.id) AS num_domande,
                    (SELECT COUNT(DISTINCT r.studente_id) FROM risposte r
                       JOIN domande d ON d.id = r.domanda_id WHERE d.verifica_id = v.id) AS num_consegne
             FROM verifiche v
             JOIN materie m ON m.id = v.materia_id
             WHERE v.professore_id = ?
             ORDER BY v.data_creazione DESC"
        );
        $stmt->execute([$professoreId]);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $conn = getPDO();
        $stmt = $conn->prepare(
            "SELECT v.*, m.nome AS materia,
                    p.nome AS prof_nome, p.cognome AS prof_cognome
             FROM verifiche v
             JOIN materie m ON m.id = v.materia_id
             JOIN professori p ON p.id = v.professore_id
             WHERE v.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getDomande(int $verificaId): array {
        $conn = getPDO();
        $stmt = $conn->prepare(
            "SELECT id, testo, ordine FROM domande
             WHERE verifica_id = ? ORDER BY ordine, id"
        );
        $stmt->execute([$verificaId]);
        return $stmt->fetchAll();
    }

    public function create(int $professoreId, int $materiaId, string $classe, string $titolo, ?string $descrizione, array $domande): int {
        $conn = getPDO();
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare(
                "INSERT INTO verifiche (professore_id, materia_id, classe, titolo, descrizione)
                 VALUES (?, ?, ?, ?, ?) RETURNING id"
            );
            $stmt->execute([$professoreId, $materiaId, $classe, $titolo, $descrizione]);
            $verificaId = (int) $stmt->fetchColumn();

            $stmtD = $conn->prepare(
                "INSERT INTO domande (verifica_id, testo, ordine) VALUES (?, ?, ?)"
            );
            foreach ($domande as $i => $testo) {
                $testo = trim((string) $testo);
                if ($testo === '') continue;
                $stmtD->execute([$verificaId, $testo, $i + 1]);
            }
            $conn->commit();
            return $verificaId;
        } catch (Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * Salva la risposta di uno studente a una domanda.
     * ATTENZIONE: il testo viene salvato così come arriva dal client e
     * lato professore verrà renderizzato con innerHTML => Stored XSS.
     */
    public function salvaRisposta(int $domandaId, int $studenteId, string $testo): void {
        $conn = getPDO();
        $stmt = $conn->prepare(
            "INSERT INTO risposte (domanda_id, studente_id, testo)
             VALUES (?, ?, ?)
             ON CONFLICT (domanda_id, studente_id)
             DO UPDATE SET testo = EXCLUDED.testo, data_consegna = CURRENT_TIMESTAMP"
        );
        $stmt->execute([$domandaId, $studenteId, $testo]);
    }

    public function getRisposteVerifica(int $verificaId): array {
        $conn = getPDO();
        $stmt = $conn->prepare(
            "SELECT r.id, r.testo, r.data_consegna,
                    d.id AS domanda_id, d.testo AS domanda_testo, d.ordine,
                    s.id AS studente_id, s.nome AS studente_nome, s.cognome AS studente_cognome
             FROM risposte r
             JOIN domande d ON d.id = r.domanda_id
             JOIN studenti s ON s.id = r.studente_id
             WHERE d.verifica_id = ?
             ORDER BY s.cognome, s.nome, d.ordine"
        );
        $stmt->execute([$verificaId]);
        return $stmt->fetchAll();
    }

    public function getRisposteStudente(int $verificaId, int $studenteId): array {
        $conn = getPDO();
        $stmt = $conn->prepare(
            "SELECT r.testo, d.id AS domanda_id
             FROM risposte r
             JOIN domande d ON d.id = r.domanda_id
             WHERE d.verifica_id = ? AND r.studente_id = ?"
        );
        $stmt->execute([$verificaId, $studenteId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['domanda_id']] = $row['testo'];
        }
        return $out;
    }
}
