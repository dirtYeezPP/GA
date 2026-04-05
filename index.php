<?php

require_once 'router.php';
require_once 'src/Response.php';
require_once 'src/connect.php';
require_once 'src/funHelper.php';
global $pdo;

session_start();

// MAKE IT COOL LATER
//TODO fix design and such in css.
//TODO fix media query for bigger screens, work in mobile first from now on.

$navItems = [
    ['id' => 'home', 'text' => 'Home', 'url' => PATH_PREFIX],
    ['id' => 'cars', 'text' => 'Cats', 'url' => PATH_PREFIX."cats"],
    ['id' => 'contact', 'text' => 'Contact', 'url' => PATH_PREFIX."cats/contact"],
];

$isLoggedIn = isset($_SESSION['id']);
$userName = $isLoggedIn ? $_SESSION['name'] : null; //if logged in is true --> username, otherwise --> null
$userId = $isLoggedIn ? $_SESSION['id'] : null;

require __DIR__ . "/vendor/autoload.php";
$renderer = new \Phug\Renderer([
    'paths' => [__DIR__ . '/views'],
    'expressionLanguage' => 'php'
]);
global $renderer;
$renderer->share('navItems', $navItems);
$renderer->share(['isLoggedIn' => $isLoggedIn, 'userName' => $userName, 'userId' => $userId, 'pathPrefix' => PATH_PREFIX, 'errors' => ERRORS]);

//HOME ROUTE
get("/", function () use ($renderer) {
    echo $renderer->renderFile('/homepage.pug', ['currentPage' => 'home']);
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

    } else {
        redirect("errors/ERR_INCORRECT_DATA");
    }
});

//LOGOUT
get("/auth/logout", function () use ($renderer) {
    loginRequired();
    $_SESSION = [];

    session_destroy();

    redirect("cats");
});

get("/profile", function () use ($renderer, $pdo, $userId) {
    loginRequired();

    $stmt = $pdo->prepare("SELECT * FROM cattos WHERE postedById = :postedById");
    $stmt->execute(['postedById' => $userId]);

    $userCats = [];
    while($cat = $stmt->fetch(PDO::FETCH_ASSOC)){
        $userCats[] = [
            'id' => $cat['id'],
            'name' => $cat['name'],
            'breed' => $cat['breed'],
            'img' => PATH_PREFIX.$cat['img']
        ];
    }

    echo $renderer->renderFile('/profile.pug', ['cats' => $userCats]);
});


// NEW
delete("/deleteProfile", function () use ($renderer, $pdo) {
    loginRequired();
    parse_str(file_get_contents("php://input"), $_DELETE);
    $uId = $_SESSION['id'];

    $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $uId]);

    $_SESSION = [];
    session_destroy();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'Loco'=> PATH_PREFIX.'cats'
    ]);

    //HA INTE REDIRECT FÖR DU FÅR VÄRSTA LOOPEN BRUV
});

patch("/profile", function() use($pdo){
    loginRequired();
    parse_str(file_get_contents('php://input'), $_PATCH);
    $uId = $_SESSION['id'];
    $givenPassword = $_PATCH['password'] ?? '';

    $stmt = $pdo->prepare("SELECT hashedPassword FROM users WHERE id=:id");
    $stmt->execute(['id' => $uId]);
    $user = $stmt->fetch();

    if(!$user || !password_verify($givenPassword, $user['hashedPassword'])){
        sendErrorPath('ERR_INCORRECT_DATA');
        return;
    }

    $allowedFields = ['name', 'email'];
    $updates = [];
    $params = [];

    foreach($allowedFields as $field){
        if(!empty($_PATCH[$field])){
            $updates[] = "$field = :$field";
            $params[$field] = $_PATCH[$field];
        }
    }

    if(!empty($updates)){
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";

        $params['id'] = $uId;
        $stmt = $pdo->prepare($sql);

        if(!$stmt->execute($params)){
            $refreshStmt = $pdo->prepare("SELECT name, email FROM users WHERE id=:id");
            $refreshStmt->execute(['id' => $uId]);
            $updatedUser = $refreshStmt->fetch(PDO::FETCH_ASSOC);

            $_SESSION['name'] = $updatedUser['name'];
            $_SESSION['email'] = $updatedUser['email'];
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'Loco'=> PATH_PREFIX.'cats'
    ]);
});

// SHOW ALL POSTS
get("/cats", function() use ($renderer, $pdo) {

    $stmt = $pdo->query("SELECT id, name, breed, img, postedById FROM cattos");
    $cats = [];
    while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cats[] = [
            'id' => $cat['id'],
            'name' => $cat['name'],
            'breed' => $cat['breed'],
            'img' => PATH_PREFIX.$cat['img'],
            'postedById' => $cat['postedById']
        ];
    };

    echo $renderer->renderFile('/cats.pug', [
         'cats'=>$cats,
         'currentPage' => 'cars'
    ]);
    //var_dump("cats", $cats);
});

//CREATE ROUTE
get("/cats/create", function () use ($renderer){

    loginRequired();

    echo $renderer->renderFile('/create.pug', ['currentPage' => 'createCar']);
});

post("/cats", function () use ($pdo, $userId){

    loginRequired();

    if(!isset($_FILES['img']) || $_FILES['img']['error'] != UPLOAD_ERR_OK) { //error fältet har inge, om allt är okej yes
        sendErrorPath('ERR_UPLOAD_FAIL');
        return;
    }

    $allowedExes = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    $fileName = $_FILES['img']['name'];
    $fileEx = strtolower(pathinfo($fileName, PATHINFO_EXTENSION)); //make it lowercase

    if(!in_array($fileEx, $allowedExes)) {
        sendErrorPath('ERR_INVALID_DATA');
        return;
    }

    $uniqueFileName = "posts/".uniqid('cat_').".".$fileEx;

    if(!move_uploaded_file($_FILES['img']['tmp_name'], (__DIR__."/".$uniqueFileName))) {
        sendErrorPath('ERR_UPLOAD_FAIL');
        return;
    }

    $requested = [
        "name"=>$_POST['name'],
        "breed" => $_POST['breed'],
        "img" => $uniqueFileName,
        "postedById" => $userId
    ];
    $sql = "INSERT INTO cattos (name, breed, img, postedById) VALUES ( :name, :breed, :img, :postedById)";
    $pdo->prepare($sql)->execute($requested);

    redirect("cats");
});


// ONE PRODUCT VIEW
get('/cats/$id', function($id) use ($renderer, $pdo, $userId){

    $stmt = $pdo->prepare("SELECT * FROM cattos WHERE id=:id");
    $stmt->execute(['id' => $id]);
    $catPost = $stmt->fetch(PDO::FETCH_ASSOC);

    //var_dump($catPost);

    if(!$catPost){
        sendErrorPath('ERR_NOT_FOUND');
        return;
    }

    $isOwner = false;
    if($catPost['postedById'] == $userId){
        $isOwner = true;
    }

    echo $renderer->renderFile('/cat.pug', ['catPost' => $catPost, 'isOwner' => $isOwner, 'userId' => $userId]);

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


// UPDATE ROUTES
patch("/cats", function () use($pdo, $userId) {
    loginRequired();
    parse_str(file_get_contents('php://input'), $_PATCH);
    $request = ["id" => $_PATCH['id'], "name" => $_PATCH['name'], "breed" => $_PATCH['breed']];

    $sqlPramValues = array_filter($request, function ($value) {
        return !empty($value);
    });

    if(count($sqlPramValues) > 1 && isset($sqlPramValues['id'])) {
        $sql = "UPDATES cattos SET ";
        $setClauses = []; // clause is name = "luffy" can be called columnsToChange

        foreach($sqlPramValues as $field => $value) {
            if($field=='id') continue;
            $setClauses[] = "$field = :$field";
        }

        $sql .= implode(', ', $setClauses);
        $sql .= " WHERE id = :id AND postedById = :postedById";

        $sqlPramValues['postedById'] = $userId;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($sqlPramValues);

        if($stmt->rowCount() === 0){
            http_response_code(403);
            echo "Error: The audacity you motherfucker! you dont have the purrmission for this!";
            return;
        }
    }

    header("Loco: /cats"); //GÖR PROLLY INGENTING!!!!
    echo "sauces";
});

post("/cats/image", function() use($pdo, $userId){
    loginRequired();
    $id = $_POST['id'] ?? null;
    $carFile = $_FILES['img'] ?? null;

    if(!$id || !$carFile || $carFile['error'] !== UPLOAD_ERR_OK) {
        sendErrorPath('ERR_MISSING_DATA');
        return;
    }

    $ext = strtolower(pathinfo($carFile['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
        sendErrorPath('ERR_INVALID_DATA');
        return;
    }

    $stmt = $pdo->prepare("SELECT img FROM cattos WHERE id=? AND postedById = ?");
    $stmt->execute([$id, $userId]);
    $oldCarImg = $stmt->fetchColumn();

    if($oldCarImg){
        sendErrorPath('ERR_MISSING_DATA');
        return;
    }

    $newImgPath = "posts/".uniqid('cat_').".".$ext;
    if(!move_uploaded_file($carFile['tmp_name'], __DIR__."/".$newImgPath)) {
        sendErrorPath('ERR_UPLOAD_FAIL');
        return;
    }

    $pdo->prepare("UPDATE cattos SET img = :img WHERE id = :id")->execute([$newImgPath, $id]);
    if(file_exists(__DIR__."/".$oldCarImg)){
        unlink(__DIR__."/".$oldCarImg);
    }

    echo "picture uploaded saucely";

});

get('/errors/$errorCode', function($errorCode) use($renderer){

    echo $renderer->renderFile('/errors.pug', ['errorCode' => $errorCode]);

});
