<?php 

require_once __DIR__ . '/../Models/Utenti.php';

class StudentiController {

    public function getAll($classe) {
        $model = new Utenti();

        $result = $model->getStudenti($classe);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
        exit;
    }
}


?>