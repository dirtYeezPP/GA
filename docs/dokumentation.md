# KOD DOKUMENTATION FÖR GYMNASIEARBETE 
Polina Panina TE23IT

#### BACKGROUND 
Dokumentationen beskriver verktyg och funktioner använda i projektet "NekoToru" (Gymnasiearbete), byggt på: PHP (phprouter), PHUG (pug för php), Javascript, och SQLite (PDO).

## 1. BACKEND
### 1.1 ROUTES
Skapande av routes möjliggörs av phprouter. (?)
#### 1.1a GET 
En typisk GET route i 'index.php' ser ut som följande; 
````php
get("/auth/register", function () use ($renderer) {
    echo $renderer->renderFile('/register.pug');
});
````
'\$renderer' definieras utifrån composer (vendor mappen, som även innehåller PHUG). Variabeln använder sig av filerna i mappen 'views' och definierar php som språk för variabel- och funktion hantering inom pug-filerna, enligt nedanstående kod: 
````php
require __DIR__ . "/vendor/autoload.php";
$renderer = new \Phug\Renderer([
    'paths' => [__DIR__ . '/views'],
    'expressionLanguage' => 'php'
]);
````
*(för använding av php-variabler inom pug, se )*

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
*För information om PDO och dess funktion, se*

'\$stmt' variabeln definieras som en förfrågan (query) för inhämtning av data från SQL databasen. 
While-loopen hämtar varje entry (varje befinnande katt) inom tabellen "cattos", tills den når slutet av tabellen för att informationen sedan
ska lagras inom arrayen '\$cats'. <br>
*(för information om PATH_PREFIX, se )* <br>
Listan skickas vidare till filen 'cats.pug' för rendering av vy med information. <br>
*(För information om 'currentPage' se)* <br>
Varje route (av annan typ än get) som kräver interaktion med klient-sidan börjar med en GET-route av vy-rendering (till exempel POST). 

#### 1.1b POST 
````php
get("/cats/create", function () use ($renderer){
    loginRequired();
    echo $renderer->renderFile('/create.pug', ['currentPage' => 'createCar']);
});

post("/cats", function () use ($pdo, $userId){
    loginRequired();
    if(!isset($_FILES['img']) || $_FILES['img']['error'] != UPLOAD_ERR_OK) { 
        redirect("errors/ERR_UPLOAD_FAIL");
    }

    $allowedExes = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    $fileName = $_FILES['img']['name'];
    $fileEx = strtolower(pathinfo($fileName, PATHINFO_EXTENSION)); //make it lowercase

    if(!in_array($fileEx, $allowedExes)) {
        redirect("errors/ERR_INVALID_DATA");
    }

    $uniqueFileName = "posts/".uniqid('cat_').".".$fileEx;

    if(!move_uploaded_file($_FILES['img']['tmp_name'], (__DIR__."/".$uniqueFileName))) {
        redirect("errors/ERR_UPLOAD_FAIL");
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
*För information om 'loginRequired se,, användaren måste vara inloggad för tillgång till detta* <br>
*För information om 'sendErrorPath', se, denna beter sig olika eftersom POST data i detta fall inte skickas mha js* <br>
Rendering av formen genom vilken en post skapas sker genom en GET-route (dvs '/cats/create'). Om ingen bild laddas upp sker en omdirigering till '/errors' *(se )* där felet står både i URL:en och på webbsidan.
För säkerhet/skydd definieras en lista med 'tillåtna extensions' av filer (i detta fall skall det vara bilder). 
Den uppladdade filens extension kontrolleras genom '\$allowedExes' listan och efter godkännande får filen ett unikt namn genom bl.a. 'uniqid' funktionen. 
Den uppladdade filen är temporärt gömt lagrad och behöver flyttas till "posts" mappen, if-satsen med "move_uploaded_file" kollar därmed om omflyttningen misslyckas (vilket leder till ännu en omdirigering). 
'\$requested' variabeln tar in POST informationen inskriven och skickad av ett POST formulär på klient-sidan, efter vilket informationen lagras i 'cattos' databasen. <br>
*'\$\_POST är en superglobal (inbyggd variabel) som innehåller listor av variables intagna från HTTP POST metoden*

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
Radering av posts sker genom en 'DELETE'-knapp som skickar data genom användning av javascript, en separat GET för vy-rendering behövs alltså inte *(se)*. <br>
Eftersom '\$\_DELETE' inte är en inbyggd variabel (och inte är tillåten som metod i html forms), måste denna deklareras i förhand. 
Informationen (i form av en body, skickad ifrån javascript genom en fetch, *se*), analyseras (parseas) för att konverteras till PHP variabler. 
Servern tar emot ett ID som motsvarar en påklickad post (id:et syns i inspection mode (hidden form field) och även i 'request' delen inom 'Network' eftersom webbsidan körs på HTTP), som finns i 'cattos' tabellen. 
När första matchningen av ID:et hittas, hämtas hela kolumnen (dvs entryn för den specifika katten) för att hantera radering av bilden. 
Raden för bilder inom tabellen lagrar en väg (path) till uppladdad bild (dvs exempelvis '/posts/*bildnamn*'), eftersom bilder lagras i mappen 'posts'. 
Unlink används därför för borttagning av bilden från 'posts' för att minska onödig platsupptagning.

#### 1.1d PATCH / UPDATE ROUTE 
Uppdatering av posts sker separat gällande text och bildhantering. 
###### PATCH 

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
````
f
````php
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
sedan 
````php
$isLoggedIn = isset($_SESSION['id']);
$userName = $isLoggedIn ? $_SESSION['name'] : null; //if logged in is true --> username, otherwise --> null
$userId = $isLoggedIn ? $_SESSION['id'] : null;
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

### 1.2 RELATERADE FILER 
#### 1.2a DATABAS 
Databsen kopplas in genom användning av PDO, där även tabellerna skapas endast en gång (if not exists). 
````php
$db = './databas/database.sqlite';
$dsn = "sqlite:$db";

try { 
    $pdo = new \PDO($dsn);
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cattos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            breed TEXT,
            img TEXT
        );
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            email TEXT,
            hashedPassword VARCHAR(255)
        );
    ");
    //echo "gay successfully";
} catch (\PDOException $e) {
    echo "". $e->getMessage() ."";
}
````
Varje rad inom parantesen representerar kolumner inom tabellen. Alla ändringar på tabellerna eller inom databasen sker genom konsollen, därför är exempelvis "createdById" inte med. 
Databasen ligger i mappen "databas" (passande namn) där alla tabeller är synliga samt access till konsollen. 

#### 1.2b ERROR HANDLING 

#### 1.2c PDO 


## 2. FRONTEND 
### 2.1 PHUG
PHUG är PUG template engine:n för PHP. 
#### 2.1a MAIN FIL - LAYOUT 

#### 2.1b INCLUDES OCH EXTENSIONS

##### 2.1c INTAGNA VARIABLER I PHUG 

### 2.2 JAVASCRIPT 
#### 2.2a DELETE
#### 2.2b UPDATE MED PATCH OCH POST 
