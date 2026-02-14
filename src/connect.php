<?php

$db = './databas/cattos.db';

$dsn = "sqlite:$db";

try { 
    $pdo = new \PDO($dsn);
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cattos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            breed TEXT
        );
    ");
    //echo "gay successfully";
} catch (\PDOException $e) {
    echo "". $e->getMessage() ."";
}
