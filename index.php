<?php

require("router.php");
require("src/Response.php");
require_once 'src/connect.php';
require_once 'src/funHelper.php';
global $pdo;

session_start();

//SOMEWHERE IN THE MIDDLE OF FUNCTIONALITY AND COOLNESS
//TODO fix profile view and profile deletion
//TODO make connections to and fro databases (userID) on posts for AAA. + Admin user...

// MAKE IT COOL LATER
//TODO change update route into either a popup or an existent form on "single-product" view.
//TODO update maybe could replace the cat picture.. just ideas.
//TODO fix design and such in css.
//TODO fix media query for bigger screens, work in mobile first from now on.

$navItems = [
    ['id' => 'home', 'text' => 'Home', 'url' => '/GA/'],
    ['id' => 'cars', 'text' => 'Cats', 'url' => '/GA/cats'],
    ['id' => 'contact', 'text' => 'Contact', 'url' => '/GA/cats/contact'],
    //['id' => 'createCar', 'text' => 'Create', 'url' => '/GA/cats/create']
];

$isLoggedIn = isset($_SESSION['id']);
$userName = $isLoggedIn ? $_SESSION['name'] : null; //if logged in is true --> username, otherwise --> null

require __DIR__ . "/vendor/autoload.php";
$renderer = new \Phug\Renderer([
    'paths' => [__DIR__ . '/views'],
    'expressionLanguage' => 'php'
]);
global $renderer;
$renderer->share('navItems', $navItems);
$renderer->share(['isLoggedIn' => $isLoggedIn, 'userName' => $userName]);

//HOME ROUTE
get("/", function () use ($renderer) {
    echo $renderer->renderFile('/main.pug', ['currentPage' => 'home']);
});

get("/cats/contact", function () use ($renderer) {
    echo $renderer->renderFile('/contact.pug', ['currentPage' => 'contact']);
});

get("/auth/register", function () use ($renderer) {
    echo $renderer->renderFile('/register.pug');
});

post("/auth/register", function () use ($pdo){

    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $requested = [
        "username"=>$_POST['username'],
        "email" => $_POST['email'],
        "hashedPassword" => $hashedPassword
    ];

    $checksql = "SELECT * FROM users WHERE email = :email";
    $checkStmt = $pdo->prepare($checksql);
    $checkStmt->execute(['email' => $requested['email']]);

    $emailExists = $checkStmt->fetchColumn()>0;

    if($emailExists){
        echo "ts email is used vro..";
        exit;
    }

    $sql = "INSERT INTO users (name, email, hashedPassword) VALUES ( :username, :email, :hashedPassword)";
    $pdo->prepare($sql)->execute($requested);

    redirect("auth/login");

});

//LOGN ROUTE
get("/auth/login", function () use ($renderer) {
    echo $renderer->renderFile('/login.pug');
});

post("/auth/login", function() use ($pdo) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user && password_verify($password, $user['hashedPassword'])){
        $_SESSION['id'] = $user['id'];
        $_SESSION['name'] = $user['name'];

        redirect("cats");
        exit;
    } else {
        echo "invalid email or password. Please try again or register.";
    }
});

//LOGOUT
get("/auth/logout", function () use ($renderer) {
    loginRequired();
    $_SESSION = [];

    session_destroy();

    redirect("cats");
});

get("/profile", function () use ($renderer) {
    loginRequired();
    echo $renderer->renderFile('/profile.pug');
});


// NEW
delete("/deleteProfile", function () use ($renderer, $pdo) {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $uId = $_DELETE['id'];

    $stmt = $pdo->query("SELECT id, name, email, hashedPassword FROM users WHERE id = :id");
    $stmt->execute(['id' => $uId]);
    $userDelete = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uId]);

    //HA INTE REDIRECT FÖR DU FÅR VÄRSTA LOOPEN BRUV
});
//
//patch("/auth/updateAccount", function () use ($renderer) {
//
//});

// SHOW ALL POSTS
get("/cats", function() use ($renderer, $pdo) {

    $stmt = $pdo->query("SELECT id, name, breed, img FROM cattos");
    $cats = [];
    while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cats[] = [
            'id' => $cat['id'],
            'name' => $cat['name'],
            'breed' => $cat['breed'],
            'img' => "/GA/".$cat['img']
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
get('/GA/cat/:id', function ($id) use ($renderer) {
    echo $renderer->renderFile('/cat.pug', ['id' => $id]);
});

get('/api/cats/:id', function($id) use ($renderer, $pdo) {
    $stmt = $pdo->query("SELECT id, name, breed, img FROM cattos WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $cats = $stmt->fetch(PDO::FETCH_ASSOC);
});

//CREATE ROUTE
get("/cats/create", function () use ($renderer){

    loginRequired();

    echo $renderer->renderFile('/create.pug', ['currentPage' => 'createCar']);
});

post("/cats", function () use ($pdo){

    if(!isset($_FILES['img']) || $_FILES['img']['error'] != UPLOAD_ERR_OK) { //error fältet har inge, om allt är okej yes
        die("file no upload yes");
    }

    $allowedExes = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    $fileName = $_FILES['img']['name'];
    $fileEx = strtolower(pathinfo($fileName, PATHINFO_EXTENSION)); //make it lowercase

    if(!in_array($fileEx, $allowedExes)) {
        die("invalid file extension, its not allowed you bum");
    }

    $uniqueFileName = "posts/".uniqid('cat_').".".$fileEx;

    if(!move_uploaded_file($_FILES['img']['tmp_name'], (__DIR__."/".$uniqueFileName))) {
        die("failed uploading file :((");
    }

    $requested = [
        "name"=>$_POST['name'],
        "breed" => $_POST['breed'],
        "img" => $uniqueFileName
    ];
    $sql = "INSERT INTO cattos (name, breed, img) VALUES ( :name, :breed, :img)";
    $pdo->prepare($sql)->execute($requested);

    redirect("cats");
});


// DELETE THING
delete("/cats", function () use ($pdo) {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $catId = $_DELETE['id'];

    $stmt = $pdo->query("SELECT img FROM cattos WHERE id = :id");
    $stmt->execute(['id' => $catId]);
    $img = $stmt->fetchColumn();

    unlink(__DIR__."/".$img);
    $pdo->prepare("DELETE FROM cattos WHERE id=?")->execute([$catId]);

    //HA INTE REDIRECT FÖR DU FÅR VÄRSTA LOOPEN BRUV

});

// UPDATE ROUTE
get("/cats/update", function () use ($renderer){

    loginRequired();

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

    redirect("cats");
});
