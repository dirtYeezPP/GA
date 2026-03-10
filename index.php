<?php

require("router.php");
require("src/Response.php");
require_once 'src/connect.php';
global $pdo;


// MAKE IT WORK
//TODO fix login and sessions + password hashing

//SOMEWHERE IN THE MIDDLE OF FUNCTIONALITY AND COOLNESS
//TODO change link view based on route (Login/Register not present when logged in).
//TODO fix media query for bigger screens, work in mobile first from now on.
//TODO make connections to and fro databases (userID) on posts for AAA. + Admin user...

// MAKE IT COOL LATER
//TODO change update route into either a popup or an existent form on "single-product" view.
//TODO update maybe could replace the cat picture.. just ideas.
//TODO fix design and such in css.

$navItems = [
    ['id' => 'home', 'text' => 'Home', 'url' => '/GA/'],
    ['id' => 'cars', 'text' => 'Cats', 'url' => '/GA/cats'],
    ['id' => 'contact', 'text' => 'Contact', 'url' => '/GA/cats/contact'],
    ['id' => 'createCar', 'text' => 'Create', 'url' => '/GA/cats/create']
];
require __DIR__ . "/vendor/autoload.php";
$renderer = new \Phug\Renderer([
    'paths' => [__DIR__ . '/views'],
    'expressionLanguage' => 'php'
]);
global $renderer;
$renderer->share('navItems', $navItems);

//HOME ROUTE
get("/", function () use ($renderer) {
    echo $renderer->renderFile('/main.pug', ['currentPage' => 'home']);
});

get("/cats/contact", function () use ($renderer) {
    echo $renderer->renderFile('/contact.pug', ['currentPage' => 'contact']);
});

get("/auth/register", function () use ($renderer) {
    echo $renderer->renderFile('/register.pug', ['currentPage' => 'register/login']);
});

post("/users", function () use ($pdo){

    $plainPassword = $_POST["password"];
    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $requested = [
        "username"=>$_POST['username'],
        "email" => $_POST['email'],
        "hashedPassword" => $hashedPassword
    ];

    $sql = "INSERT INTO users (name, email, hashedPassword) VALUES ( :username, :email, :hashedPassword)";
    $pdo->prepare($sql)->execute($requested);

    header("Location: http://localhost/GA/cats");
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

    echo $renderer->renderFile('/cats.pug', [
         'cats'=>$cats,
         'currentPage' => 'cars'
    ]);
    //var_dump("cats", $cats);
});

// SPECIFIED ID QUERY 
get('/cat/$id', function ($id) {
    echo "car with id: $id";
});

//CREATE ROUTE
get("/cats/create", function () use ($renderer){
    echo $renderer->renderFile('/create.pug', ['currentPage' => 'createCar']);
});

post("/cats", function () use ($pdo){
    $imgPathForDB = "";

    if(isset($_FILES['img']) && $_FILES['img']['error'] == UPLOAD_ERR_OK) {
        $uniqueFileName = time()."_".$_FILES['img']['name'];
        $destOnServer = "posts/".$uniqueFileName;

        if(move_uploaded_file($_FILES['img']['tmp_name'], $destOnServer)) {
            $imgPathForDB = $destOnServer;
        }
    }

    $requested = [
        "name"=>$_POST['name'],
        "breed" => $_POST['breed'],
        "img" => $imgPathForDB
    ];
    $sql = "INSERT INTO cattos (name, breed, img) VALUES ( :name, :breed, :img)";
    $pdo->prepare($sql)->execute($requested);

    header("Location: http://localhost/GA/cats");
});


// DELETE THING
delete("/cats", function () use ($pdo) {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $catId = $_DELETE['id'];

    $pdo->prepare("DELETE FROM cattos WHERE id=?")->execute([$catId]);

    //header("Location: http://localhost/GA/cats");
});

// UPDATE ROUTE
get("/cats/update", function () use ($renderer){
    echo $renderer->renderFile('/update.pug');
});

patch("/cats", function () use($pdo) {
    parse_str(file_get_contents('php://input'), $_PATCH);
    $request = ["id" => $_PATCH['id'], "name" => $_PATCH['name'], "breed" => $_PATCH['breed'], "img" => $_PATCH['img']];

    $sqlPramValues = array_filter($request, function ($value) {
        return !empty($value);
    });

    $sql = /** @lang text */
        "UPDATE cattos SET ";

    $i = 0;
    foreach ($sqlPramValues as $key => $value) {
        $i += 1;
        if ($key=="id") {
            continue;
        }
        $sql = $sql."$key=:$key";
        if ($i < count($sqlPramValues)) {
            $sql = $sql.", ";
        }
    }

    $sql = $sql." WHERE id=:id";
    echo $sql;

    //var_dump($request);
    //var_dump($sqlPramValues);

    if(count($sqlPramValues)>1){
        $pdo->prepare($sql)->execute($sqlPramValues);
    }

    header("Loco: http://localhost/GA/cats");
});
