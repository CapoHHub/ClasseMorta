<?php

require_once __DIR__ . '/../helpers/db.php';

class Utenti {

    /**
     * VOLUTAMENTE VULNERABILE A SQL INJECTION.
     * Concatena email e password direttamente nella query.
     * Esempio di payload didattico:
     *   email:    ' OR '1'='1' --
     *   password: qualsiasi
     */
    public function loginVulnerabile(string $tabella, string $email, string $password): ?array {
        if (!in_array($tabella, ['professori', 'studenti'], true)) {
            return null;
        }
        $conn = getPDO();
        $sql = "SELECT * FROM $tabella WHERE email = '$email' AND password = '$password' LIMIT 1";
        $stmt = $conn->query($sql);
        $row = $stmt ? $stmt->fetch() : false;
        return $row ?: null;
    }

    public function getStudentiByClasse(string $classe): array {
        $conn = getPDO();
        $stmt = $conn->prepare(
            "SELECT id, nome, cognome, email, classe, data_creazione
             FROM studenti WHERE classe = ? ORDER BY cognome, nome"
        );
        $stmt->execute([$classe]);
        return $stmt->fetchAll();
    }

    public function getStudenteById(int $id): ?array {
        $conn = getPDO();
        $stmt = $conn->prepare(
            "SELECT id, nome, cognome, email, classe FROM studenti WHERE id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getClassiInsegnate(int $professoreId): array {
        $conn = getPDO();
        $stmt = $conn->prepare(
            "SELECT DISTINCT i.classe
             FROM insegnamenti i
             WHERE i.professore_id = ?
             ORDER BY i.classe"
        );
        $stmt->execute([$professoreId]);
        return array_column($stmt->fetchAll(), 'classe');
    }

    public function getMaterieInsegnate(int $professoreId, ?string $classe = null): array {
        $conn = getPDO();
        $sql = "SELECT m.id, m.nome, i.classe
                FROM insegnamenti i
                JOIN materie m ON m.id = i.materia_id
                WHERE i.professore_id = ?";
        $params = [$professoreId];
        if ($classe !== null) {
            $sql .= " AND i.classe = ?";
            $params[] = $classe;
        }
        $sql .= " ORDER BY i.classe, m.nome";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Verifica se il professore può vedere/gestire i dati relativi
     * a uno specifico studente (deve insegnargli almeno una materia).
     */
    public function professoreInsegnaAStudente(int $professoreId, int $studenteId): bool {
        $conn = getPDO();
        $stmt = $conn->prepare(
            "SELECT 1
             FROM studenti s
             JOIN insegnamenti i ON i.classe = s.classe
             WHERE s.id = ? AND i.professore_id = ?
             LIMIT 1"
        );
        $stmt->execute([$studenteId, $professoreId]);
        return (bool) $stmt->fetch();
    }
}
