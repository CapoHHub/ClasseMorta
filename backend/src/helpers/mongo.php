<?php

require_once __DIR__ . '/db.php';

function getMongoManager(): MongoDB\Driver\Manager {
    static $manager = null;
    if ($manager !== null) {
        return $manager;
    }
    $env = loadEnv();
    $uri = $env["MONGO_URI"] ?? null;
    if (!$uri) {
        throw new RuntimeException("MONGO_URI non configurato in .env");
    }
    $manager = new MongoDB\Driver\Manager($uri);
    return $manager;
}

function getMongoDbName(): string {
    $env = loadEnv();
    return $env["MONGO_DB"] ?? "classemorta";
}

function mongoNamespace(string $collection): string {
    return getMongoDbName() . '.' . $collection;
}

/**
 * Esegue una query (find) su una collection.
 *
 * @param string $collection
 * @param array|object $filter
 * @param array $options
 * @return array
 */
function mongoFind(string $collection, $filter = [], array $options = []): array {
    $query = new MongoDB\Driver\Query($filter, $options);
    $cursor = getMongoManager()->executeQuery(mongoNamespace($collection), $query);
    $results = [];
    foreach ($cursor as $doc) {
        $results[] = mongoDocToArray($doc);
    }
    return $results;
}

function mongoInsertOne(string $collection, array $document): string {
    $bulk = new MongoDB\Driver\BulkWrite();
    $id = $bulk->insert($document);
    getMongoManager()->executeBulkWrite(mongoNamespace($collection), $bulk);
    return (string) $id;
}

function mongoDeleteOne(string $collection, array $filter): int {
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->delete($filter, ['limit' => 1]);
    $result = getMongoManager()->executeBulkWrite(mongoNamespace($collection), $bulk);
    return $result->getDeletedCount();
}

/**
 * Converte un BSONDocument in array PHP serializzabile JSON.
 */
function mongoDocToArray($doc): array {
    $arr = json_decode(json_encode($doc), true);
    if (isset($arr['_id']['$oid'])) {
        $arr['_id'] = $arr['_id']['$oid'];
    }
    return $arr;
}
