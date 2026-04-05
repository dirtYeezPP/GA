# KOD DOKUMENTATION FÖR GYMNASIEARBETE 
Polina Panina TE23IT

## 1. BACKEND
### 1.1 ROUTES
#### 1.1a GET 
````php
get("/auth/register", function () use ($renderer) {
    echo $renderer->renderFile('/register.pug');
});
````
En typisk get-route inom detta projekt ser ut som ovanstående, där '$renderer' kommer ifrån 'phug' (engine, pug för/med php) vars kod ser ut som följande:
````php
require __DIR__ . "/vendor/autoload.php";
$renderer = new \Phug\Renderer([
    'paths' => [__DIR__ . '/views'],
    'expressionLanguage' => 'php'
]);
````
Detta behövs för att möjliggöra rendering av pug-filerna som ansvarar för alla vyer på webbsidan, såsom 'register.pug'. Phug har installerats genom composer på datorn och kommandotolken.

###### ROUTE FÖR UPPVISNING AV ALLA POSTS  
````php
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
});
````
Ovanstående route använder sig av PDO ('\$pdo') för att hämta in information lagrad inom en tabell i databasen (SQLite, cattos).
Vanligtvis används en asterix för att hämta "all data" från databasens tabell. Datan från tabellen (i form av en associative array) kommer att hämtas så länge det finns något att hämta, är tabellen tom kommer webbsidan inte visa några posts..
Senare används '$renderer' för att vyn "cats.pug" ska visas och en currentPage indikation skickas för att användas inom navigerings fältet. 
#### 1.1b POST 
````php
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
````
(För LoginRequired och sendErrorPath funktioner se *1.3 RELATERADE FILER*) <br>
Denna post route tar in information från en form på webbsidan (med method post), efter vilket servern får inskriven information i fälten.
Metoden POST används för att "Lägga upp" något på webbsidan, därför renderas först en vy genom GET där formen för post ligger. Den angivna vägen (eller path:en) matchar den i *1.1a VY FÖR ALLA POSTS* eftersom metoden är annorlunda och samma path kan då återanvändas. 
* För säkerhetsskull deklareras först "tillåtna" bild-extensions för uppladdning ($allowedExes).
* Kontrollen av extensions sker sedan i första if-satsen då uppladdat extension söks i $allowedExes listan.
* Ännu ett säkerhetstillägg är ett unikt genererat namn för varje post med uniquid. 
* $requested listan bygger på informationen ifrån formen och PDO används för att sätta in ett nytt värde i tabellen "cattos" tillsammans med ett userId (se *1.1e SESSIONS OCH LOGIN*).

Det är standard/vanligare att ha med query strings i eller meddelanden i form av "?title=Query_string&action=edit" eller där antingen ett felmeddelande eller success meddelande visas upp, däremot har eleven valt att inte inkludera det i detta projekt. 
post data blir parseat och intaget i POST superglobalen. 
#### 1.1c DELETE 
````php
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
````
Delete har ingen rendering av vy eftersom detta utgörs av en knapp. Till skillnad från POST metoden är informationen skickad genom javascript fetch (se *2.2 JAVASCRIPT i FRONTEND section*). 
DELETE superglobalen måste deklareras för att servern ska ta in informationen skickad från javascript i form av en body, detta sätts igång genom en knapp i "/cats" (eller "/cats/$id").
Första raden gör alltså följande:
* parse_str --> tar URL-encoded sträng och konverterar den till PHP variabler (delete array)
* php://input --> tar rå och oprocesserad data från HTTP requesten.
Sedan används $pdo återigen för att kunna ändra inom SQL databasen "cattos". Efter att rätt id hittats, hämtas annan data från posten. 
* unlink --> en funktion som tar bort en fil specifierad utifrån dens path, som tas ifrån databasen. 
* Sedan raderas posten genom PDO på rätt plats (id). 

#### 1.1d PATCH / UPDATE ROUTE 
````php
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
````

### 1.1e SESSIONS, LOGIN, & PROFILE 
###### SESSIONS
Sessions sätts igång på servern innan någon vy hinner att renderas genom följande:
````php
session_start();
````
###### REGISTER / LOGIN
````php
// OVANFÖR DETTA FINNS EN GET ROUTE SOM RENDERAR UT "REGISTER.PUG"

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
        sendErrorPath('ERR_INVALID_DATA');
        exit;
    }

    $sql = "INSERT INTO users (name, email, hashedPassword) VALUES ( :username, :email, :hashedPassword)";
    $pdo->prepare($sql)->execute($requested);

    redirect("auth/login");

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
````
###### PROFILE MANAGEMENT 

### 1.1f ERROR HANDLING
````php
get('/errors/$errorCode', function($errorCode) use($renderer){
    echo $renderer->renderFile('/errors.pug', ['errorCode' => $errorCode]);
});
````

### 1.2 DATABAS 

### 1.3 RELATERADE FILER 

### 1.4 RELEVANT 

## 2. FRONTEND 
### 2.1 PHUG 
##### 2.1a VY FÖR ALLA POSTS 

### 2.2 JAVASCRIPT 
