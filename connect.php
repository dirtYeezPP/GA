<?php

$db = './databas/catties.db';

$dsn = "sqlite:$db";

try { 
    $pdo = new \PDO($dsn); 
    //echo "gay successfully";
} catch (\PDOException $e) {
    echo "". $e->getMessage() ."";
}
