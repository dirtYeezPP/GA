<?php

require("router.php");
require("src/Response.php");
require_once 'src/connect.php';
global $pdo;

//include_once __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/vendor/autoload.php";
try {
    $renderer = new \Phug\Renderer([
        'paths' => [__DIR__ . '/views']
    ]);
} catch (\Phug\RendererException $e) {
    echo $e->getMessage();
}
global $renderer;


//HOME ROUTE
get("/", function () use ($renderer) {
    echo $renderer->renderFile('/main.pug');
});

get("/cats/contact", function () use ($renderer) {
    echo $renderer->renderFile('/contact.pug');
});

// SHOW ALL POSTS
get("/cats", function () use ($pdo) {
    $stmt = $pdo->query("SELECT id, name, breed, img FROM cattos");
    $cats = [];
    while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cats[] = [
            'id'=>$cat['id'],
            'name'=>$cat['name'],
            'breed'=>$cat['breed'],
            'img'=>$cat['img']
        ];
    }
    Res::debug($cats);
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
        "breed" => $_POST['catBreed'],
        "name" => $_POST['catName'],
        "catPic" => $_POST['catPic']
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
    $request = ["id" => $_PATCH['id'] ?? "no_id", "catName" => $_PATCH['name'], "catBreed" => $_PATCH['breed'], "catPic" => $_PATCH['catPic']];
    $sqlPramValues = [ "id"=>$request['id']];

    $existentName = !empty($request['catName']);
    $existentBreed  = !empty($request['catBreed']);
    $existentPic    = !empty($request['catPic']);
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
    if($existentBreed && $existentPic){
        $sql = $sql.", ";
    }
    if($existentPic) {
        $sql = $sql."img=:catPic ";
        $sqlPramValues["catPic"] = $request['catPic'];
    }
    $sql = $sql."WHERE id=:id";
    echo $sql;
    if($existentName || $existentBreed || $existentPic){
        $pdo->prepare($sql)->execute($sqlPramValues);
    }

    header("Loco: http://localhost/GA/cats");
});
