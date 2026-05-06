<?php

function loadEnv(): array {
    static $env = null;
    if ($env !== null) {
        return $env;
    }
    $path = __DIR__ . '/../../.env';
    if (!file_exists($path)) {
        throw new RuntimeException("File .env non trovato in $path");
    }
    $parsed = parse_ini_file($path);
    if ($parsed === false) {
        throw new RuntimeException("Impossibile leggere il file .env");
    }
    $env = $parsed;
    return $env;
}

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $env = loadEnv();
    $host    = $env["PGHOST"];
    $dbname  = $env["PGDATABASE"];
    $port    = $env["PGPORT"];
    $user    = $env["PGUSER"];
    $pass    = $env["PGPASSWORD"];
    $sslmode = $env["PGSSLMODE"] ?? "require";

    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    return $pdo;
}
