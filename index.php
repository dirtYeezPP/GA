<?php

require("router.php");
require("src/Response.php");
require("src/data.php");
require_once 'src/connect.php';
global $pdo;

get("/", function () {
    echo "you're gay";
});

// CONTACT PAGE 
get("/contact", 'views/contact.html');

// SHOW ALL PRODUCTS 
get("/cats", function () {
    Res::debug(data::getData("cats"));
});

// SPECIFIED ID QUERY 
get('/cat/$id', function ($id) {
    echo "car with id: $id";
});

//CREATE ROUTE  
get("/cats/create", 'views/create.html');

post("/cats", function () use ($pdo){
    $requested = [
        "breed" => $_POST['catBreed'],
        "name" => $_POST['catName']
    ];
    $sql = "INSERT INTO cattos (name, breed) VALUES ( :name, :breed)";
    $stmt= $pdo->prepare($sql);
    $stmt->execute($requested);

    header("Location: http://localhost/GA/cats");
});

// DELETE ROUTE
get("/cats/delete", 'views/delete.html');

delete("/cats", function () use ($pdo) {
    parse_str(file_get_contents("php://input"), $_DELETE); //get ID from the request 

    $catId = $_DELETE['id'];

    $pdo->prepare("DELETE FROM cattos WHERE id=?")->execute([$catId]);

    header("Loco: http://localhost/GA/cats");
});

// UPDATE ROUTE
get("/cats/update", 'views/update.html');

patch("/cats", function () use($pdo) {
    parse_str(file_get_contents('php://input'), $_PATCH);
    $request = ["id" => $_PATCH['id'] ?? "no_id", "catName" => $_PATCH['name'], "catBreed" => $_PATCH['breed']];

    if(empty($request['catName'])) {

    }

    $sql = "UPDATE cattos SET name=:catName, breed=:catBreed WHERE id=:id";
    $pdo->prepare($sql)->execute($request);

    header("Loco: http://localhost/GA/cats");
});
