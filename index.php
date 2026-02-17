<?php

require("router.php");
require("src/Response.php");
require_once 'src/connect.php';
global $pdo;

//TODO add a register/login link with view to extension (form).
//TODO  create a database that stores username, email and password of users who log in
//TODO if user is logged in, register/login link shall not be present. Change view based on role.
//TODO "enable" sessions to check authorization and determine allowed actions based on user role.
//TODO have a separated user who is admin, to whom everything is accessible whilst others can only change what is posted by themselves (auth).

//include_once __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/vendor/autoload.php";

$renderer = new \Phug\Renderer([
    'paths' => [__DIR__ . '/views'],
    'expressionLanguage' => 'php'
]);
global $renderer;

//HOME ROUTE
get("/", function () use ($renderer) {
    echo $renderer->renderFile('/main.pug');
});

get("/cats/contact", function () use ($renderer) {
    echo $renderer->renderFile('/contact.pug');
});

// SHOW ALL POSTS
get("/cats", function() use ($renderer, $pdo) {

    $stmt = $pdo->query("SELECT id, name, breed, img FROM cattos");
    $cats = [];
    while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cats[] = [
            'id' => $cat['id'],
            'name' => $cat['name'],
            'breed' => $cat['breed'],
            'img' => $cat['img']
        ];
    };

    if (empty($cats)) {
        die("Database returned zero cats. The array is empty!");
    }

    echo $renderer->renderFile('/cats.pug', ['cats'=>$cats]);
    //var_dump("cats", $cats);
});

// SPECIFIED ID QUERY 
get('/cat/$id', function ($id) {
    echo "car with id: $id";
});


//CREATE ROUTE
get("/cats/create", function () use ($renderer){
    echo $renderer->renderFile('/create.pug');
});

post("/cats", function () use ($pdo){
    $requested = [
        "name"=>$_POST["name"],
        "breed" => $_POST['breed'],
        "img" => $_POST['img']
    ];
    $sql = "INSERT INTO cattos (name, breed, img) VALUES ( :name, :breed, :img)";
    $pdo->prepare($sql)->execute($requested);

    header("Location: http://localhost/GA/cats");
});



// DELETE ROUTE
get("/cats/delete", function () use ($renderer){
    echo $renderer->renderFile('/delete.pug');
});

delete("/cats", function () use ($pdo) {
    parse_str(file_get_contents("php://input"), $_DELETE); //get ID from the request 

    $catId = $_DELETE['id'];

    $pdo->prepare("DELETE FROM cattos WHERE id=?")->execute([$catId]);

    header("Loco: http://localhost/GA/cats");
});



// UPDATE ROUTE
get("/cats/update", function () use ($renderer){
    echo $renderer->renderFile('/update.pug');
});

patch("/cats", function () use($pdo) {
    parse_str(file_get_contents('php://input'), $_PATCH);
    $request = ["id" => $_PATCH['id'] ?? "no_id", "name" => $_PATCH['name'], "breed" => $_PATCH['breed'], "img" => $_PATCH['img']];
    $sqlPramValues = [ "id"=>$request['id'] ];

    $existentName = !empty($request['name']);
    $existentBreed  = !empty($request['breed']);
    $existentPic    = !empty($request['img']);

    $sql = /** @lang text */
        "UPDATE cattos SET ";

    if($existentName) {
        $sql = $sql."name=:name ";
        $sqlPramValues["name"] = $request['name'];
    }
    if($existentName && $existentBreed) {
        $sql = $sql.", ";
    }
    if($existentBreed) {
        $sql = $sql."breed=:breed ";
        $sqlPramValues["breed"] = $request['breed'];
    }
    if( ($existentName || $existentBreed) && $existentPic){
        $sql = $sql.", ";
    }
    if($existentPic) {
        $sql = $sql."img=:img ";
        $sqlPramValues["img"] = $request['img'];
    }
    $sql = $sql."WHERE id=:id";
    echo $sql;
    if($existentName || $existentBreed || $existentPic){
        $pdo->prepare($sql)->execute($sqlPramValues);
    }

    header("Loco: http://localhost/GA/cats");
});
