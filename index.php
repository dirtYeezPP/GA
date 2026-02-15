<?php

require("router.php");
require("src/Response.php");
require_once 'src/connect.php';
global $pdo;

get("/", function () {
    echo "you're gay";
});

// CONTACT PAGE 
get("/contact", 'views/contact.html');

// SHOW ALL PRODUCTS 
get("/cats", function () use ($pdo) {
    $stmt = $pdo->query("SELECT id, name, breed FROM cattos");
    $cats = [];
    while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cats[] = [
            'id'=>$cat['id'],
            'name'=>$cat['name'],
            'breed'=>$cat['breed']
        ];
    }
    Res::debug($cats);
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
    $pdo->prepare($sql)->execute($requested);

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
    $sqlPramValues = [ "id"=>$request['id']];

    $existentName = !empty($request['catName']);
    $existentBreed  = !empty($request['catBreed']);
    $sql = /** @lang text */
        "UPDATE cattos SET ";
    if($existentName) {
        $sql = $sql."name=:catName ";
        $sqlPramValues["catName"] = $request['catName'];
    }
    if($existentName && $existentBreed){
        $sql = $sql.", ";
    }
    if($existentBreed) {
        $sql = $sql."breed=:catBreed ";
        $sqlPramValues["catBreed"] = $request['catBreed'];
    }
    $sql = $sql."WHERE id=:id";
    echo $sql;
    if($existentName || $existentBreed){
        $pdo->prepare($sql)->execute($sqlPramValues);
    }

    header("Loco: http://localhost/GA/cats");
});
