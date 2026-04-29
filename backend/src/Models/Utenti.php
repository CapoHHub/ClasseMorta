<?php 

require_once __DIR__ . '/../helpers/db.php';



class Utenti {


    public function getStudenti($classe) {
        $conn = getPDO();

        $stmt = $conn->prepare(
            "SELECT * FROM studenti WHERE classe = ?"
        );

        $stmt->execute([$classe]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
}

?>