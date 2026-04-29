<?php

function getPDO () {

    // Inizializzata solo alla prima chiamata
    static $pdo = null;

    // Se la connessione non è stata creata
    if ($pdo === null) {
        // Credenziali DB
        $env = parse_ini_file(__DIR__ . '/../../.env');
        if ($env === false) {
            throw new RuntimeException("Impossibile leggere il file .env");
        }
        $host = $env["PGHOST"];
        $dbname = $env["PGDATABASE"];
        $port = $env["PGPORT"];
        $user = $env["PGUSER"];
        $pass = $env["PGPASSWORD"];
        $sslmode = $env["PGSSLMODE"] ?? "require";

        // Connessione DB
        $pdo = new PDO(
            "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    // Ritorno connessione DB
    return $pdo;
}
?>