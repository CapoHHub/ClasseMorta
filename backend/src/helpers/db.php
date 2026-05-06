<?php

/**
 * Carica la configurazione da .env (locale) e/o dalle env vars del processo
 * (Railway, Heroku, ecc.). Le env vars del processo hanno priorità solo se
 * il valore corrispondente nel .env è assente.
 */
function loadEnv(): array {
    static $env = null;
    if ($env !== null) {
        return $env;
    }

    $env = [];
    $path = __DIR__ . '/../../.env';
    if (file_exists($path)) {
        $parsed = parse_ini_file($path);
        if (is_array($parsed)) {
            $env = $parsed;
        }
    }

    $keys = [
        'PGHOST','PGDATABASE','PGUSER','PGPASSWORD','PGPORT','PGSSLMODE',
        'PGCHANNELBINDING','MONGO_URI','MONGO_DB','JWT_SECRET',
    ];
    foreach ($keys as $k) {
        if (!isset($env[$k]) || $env[$k] === '') {
            $val = getenv($k);
            if ($val === false || $val === '') {
                $val = $_ENV[$k] ?? '';
            }
            if ($val !== '') {
                $env[$k] = $val;
            }
        }
    }
    return $env;
}

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $env = loadEnv();
    foreach (['PGHOST','PGDATABASE','PGPORT','PGUSER','PGPASSWORD'] as $req) {
        if (empty($env[$req])) {
            throw new RuntimeException("Variabile $req non configurata");
        }
    }
    $host    = $env['PGHOST'];
    $dbname  = $env['PGDATABASE'];
    $port    = $env['PGPORT'];
    $user    = $env['PGUSER'];
    $pass    = $env['PGPASSWORD'];
    $sslmode = $env['PGSSLMODE'] ?? 'require';

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
