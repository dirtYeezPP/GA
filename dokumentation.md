# KOD DOKUMENTATION FÖR GYMNASIEARBETE
Polina Panina TE23IT

#### BACKGROUND
Dokumentationen beskriver verktyg och funktioner använda i projektet "NekoToru" (Gymnasiearbete), byggt på: PHP (phprouter), PHUG (pug för php), Javascript, och SQLite (PDO).

## 1. BACKEND
### 1.1 ROUTES
Skapande av routes möjliggörs av phprouter.
#### 1.1a GET
En typisk GET route i 'index.php' ser ut som följande;
````php
get("/auth/register", function () use ($renderer) {
    echo $renderer->renderFile('/register.pug');
});
````
'\$renderer' definieras utifrån PHUG/composer (vendor mappen, som även innehåller PHUG). Vägar (paths) befinner sig inom mappen 'views' och definieras för enklare skrivning i senare syften.
PHP anges som 'expression language' vilket innebär att dess syntax används inom PHUG-filerna för intag av variabler m.m.
````php
require __DIR__ . "/vendor/autoload.php";
$renderer = new \Phug\Renderer([
    'paths' => [__DIR__ . '/views'],
    'expressionLanguage' => 'php'
]);
````
*(för använding av php-variabler inom pug, se 3.1c INTAGNA VARIABLER I PHUG)*

###### READ - ROUTE FÖR ALLA POSTS
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
*För information om PDO och dess funktion, se 1.2b DATABAS*

PDO möjliggör en förenklad och säker kommunikation mellan serversidan och databasen. Genom dess användning skickas en förfrågan (query) för inhämtning av data från tabellen 'cattos' inom SQL databasen.
Datan går sedan igenom en while-loop där informationen placeras/lagras inom en associativ lista ('$cats') tills tabellen når sitt slut.
(*Information om PATH_PREFIX hittar du i 1.2a FUNHELPER.PHP*) <br>
Genom '\$renderer' renderas vyn inom 'cats.pug' och listan '\$cats' skickas med för uppvisning av information (*Se 3.1c INTAGNA VARIABLER I PHUG*). <br>

* Varje route (av annan typ än get) som kräver interaktion med användaren/klientsidan genomgår först en vy-rendering av en phug fil, såsom POST (eftersom den innehåller ett formulär).

#### 1.1b POST
````php
get("/cats/create", function () use ($renderer){
    loginRequired();
    echo $renderer->renderFile('/create.pug', ['currentPage' => 'createCar']);
});

post("/cats", function () use ($pdo, $userId){
    loginRequired();
    if(!isset($_FILES['img']) || $_FILES['img']['error'] != UPLOAD_ERR_OK) { //error fältet har inge, om allt är okej yes
        redirect("errors/ERR_UPLOAD_FAIL");
    }

    $allowedExes = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    $fileName = $_FILES['img']['name'];
    $tmpName = $_FILES['img']['tmp_name'];
    $fileEx = strtolower(pathinfo($fileName, PATHINFO_EXTENSION)); //make it lowercase

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpName);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if(!in_array($mimeType, $allowedMimes)) {
        redirect("errors/ERR_INVALID_DATA");
    }

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
*Information om LoginRequired finns i 1.2a FUNHELPER.PHP --> användaren måste vara inloggad för tillgång till detta* <br>
*För information om 'sendErrorPath' och 'redirect' se 1.2a FUNHELPER.PHP sektionen.* <br>

Allra först renderas en vy av 'create.pug' för routen 'cats/create'.
Först kontrolleras att en användare än inloggad och att en fil faktiskt har lagts upp/bifogats.
Sedan definieras tillåtna filändelser (extensions) samt en lista med tillåtna MIME-typer (MultiPurpose Internet Mail Extensions --> *type/subtype*).
* Extensions utgör ändelsen på filnamnet, såsom '.jpeg'
* MIME-typen utgör filens 'identitet' och talar om det exakta formatet filen består av.

Efter godkännande av både filtyp och MIME-typ anges ett unikt namn, genom bl.a. funktionen 'uniqid'.
Vid uppladdning blir filer temporärt gömt lagrade, från vilket de behöver flyttas och sparas lokalt. Sista if-satsen kollar därför att omflyttningen till 'posts' mappen inte misslyckats.  
Därefter lagras den uppladdade informationen (hämtad med POST superglobalen) inom variabeln '\$requested'.
Genom PDO talar servern om för databasen att lägga till '\$requested' inom tabellen 'cattos', det blir som en förfrågan eller ett kommando inom SQL konsollen.
Först förbereds databasen att ta emot förfrågan genom 'prepare' (här är värdena inte angivna, bara strukturen och tillåtna handlingar för förfrågan är angiven), efter vilket värdena är skickade genom 'execute'.

*'\$\_POST är en superglobal (inbyggd variabel) som innehåller listor av variables intagna från HTTP POST metoden* <br>
*en clause (sats) ser ut på följande sätt: 'name = :name' eller 'name = ?', i varje fall tjänar innehållet efter likhetstecknet funktionen av en platshållare. Vid fall 1 ('name = :name) förlitar
sig satsen på inskickade värdets namn. Vid fall 2 ('name = ?') förlitar sig satsen på inkommande datans ordning.*

#### 1.1c DELETE
````php
delete("/cats", function () use ($pdo) {
    loginRequired();
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
*Radering sker via en delete-knapp på klient-sidan* <br>
'\$\_DELETE' är, till skillnad från GET och POST, inte en inbyggd variabel i PHP, därför måste den deklareras i förhand för att kunna hantera information likt inbyggda PHP variabler.
* 'file_get_contents' tar emot och läser av inkommande data från klientens request.
* 'parse_str' funktionen analyserar datan och lagrar resultatet i listan som, i detta fall, kallas '\$\_DELETE'.

Klientens förfrågan skickar med ett id som motsvarar en plats i databasens tabell 'cattos'.
Varje katt innehåller ett id, namn, en ras, en bild, och ett ägar-id. Bilden lagras inom en separat mapp och tabellen lagrar vägen till bilden.
Genom PDO hämtas 'img' delen från databasen på angiven plats (id) efter vilket 'unlink' funktionen används för att radera filen från mappen 'posts'.
Därefter förbereds SQL för att ta emot datan och efter 'execute' raderas katten från tabellen.

#### 1.1d PATCH / UPDATE ROUTE
Uppdatering av posts sker separat gällande text (PATCH) och bildhantering (POST).
###### PATCH
För att ändra kattens namn eller ras (befinnande text-information) används metoden PATCH.
````php
patch("/cats", function () use($pdo, $userId) {
    loginRequired();
    parse_str(file_get_contents('php://input'), $_PATCH);
    $request = ["id" => $_PATCH['id'], "name" => $_PATCH['name'], "breed" => $_PATCH['breed']];

    $sqlPramValues = array_filter($request, function ($value) {
        return !empty($value);
    });

    if(count($sqlPramValues) > 1 && isset($sqlPramValues['id'])) {
        $sql = "UPDATE cattos SET ";
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
            sendErrorPath('ERR_FORBIDDEN');
            return;
        }
    }

    header("Loco: /cats"); //GÖR PROLLY INGENTING!!!!
    echo "sauces";
});
````
*Likt DELETE är PATCH (och PUT) inte inbyggda PHP-variabler och kan inte hantera information likt GET och POST superglobalerna.* <br>
Inskickad data läses in och översätts för att placeras i listan '\$\_PATCH'. Samma data placeras sedan i listan '\$request'.
Tomma fält filtreras bort och inskrivna fält hamnar i listan '\$sqlPramValues'. Om den överstiger längden av 1 och ett id är givet påbörjas SQL-strängen och
en lista för satser (clauses) skapas.
Varje entry inom '\$sqlPramValues' (utom 'id') läggs sedan till i listan '\$setClauses' formulerad som en sats (clause). Efter vilket implode används för att separera satserna med komman.
Är bara en sats (clause) given kommer inget kommatecken sättas.
SQL strängen avslutas med att specifiera att både kattens id men även 'postedById' (dvs skaparen av posten) stämmer överens med inskickad information.
'\$sqlPramValues' får tillsatt '\$userId' (dvs id av användaren som för tillfället är inloggad) på plats 'postedById', för att användaren inte ska kunna ljuga om deras identitet.
Sedan förbereds och 'executeas' datan.
Slutligen kontrolleras, med hjälp av funktionen 'rowCount' hur många rader varit ändrade inom databsen på grund av den skickade förfrågan. Om talet motsvarar 0
innebär det att katten antingen inte finns, eller att användaren inte äger upplägget.

'\$field' definierar index
'\$value' definierar dess värde (no way)
###### POST
````php
post("/cats/image", function() use($pdo, $userId){
    loginRequired();
    $id = $_POST['id'] ?? null;
    $carFile = $_FILES['img'] ?? null;

    if(!$id || !$carFile || $carFile['error'] !== UPLOAD_ERR_OK) {
        sendErrorPath('ERR_MISSING_DATA');
        return;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $carFile['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if(!in_array($mimeType, $allowedMimes)) {
        sendErrorPath('ERR_INVALID_DATA');
        return;
    }

    $ext = strtolower(pathinfo($carFile['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
        sendErrorPath('ERR_INVALID_DATA');
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT img FROM cattos WHERE id = ? AND postedById = ?");
        $stmt->execute([$id, $userId]);
        $oldCarImg = $stmt->fetchColumn();

        if(!$oldCarImg){
            sendErrorPath('ERR_MISSING_DATA');
            return;
        }

        $newImgPath = "posts/".uniqid('cat_').".".$ext;
        if(!move_uploaded_file($carFile['tmp_name'], __DIR__."/".$newImgPath)) {
            sendErrorPath('ERR_UPLOAD_FAIL');
            return;
        }

        $updateStmt = $pdo->prepare("UPDATE cattos SET img = ? WHERE id = ? AND postedById = ?");
        $updateStmt->execute([$newImgPath, $id, $userId]);

        if(!empty($oldCarImg) && file_exists(__DIR__."/".$oldCarImg)) {
            unlink(__DIR__."/".$oldCarImg);
        }

        json_encode(['status'=>'sauce', 'message'=>'picture uploaded saucely']);

    } catch (PDOException $e) {
        sendErrorPath('ERR_UPLOAD_FAIL');
    }
    
});
````
(*Anledningen till att uppdateringen av en post sker via två separata routes finns i 2. JAVASCRIPT --> 2.2b UPDATE MED PATCH OCH POST*) <br>
Liksom i 'create' routen kontrolleras att den uppladdade filen är av godkänd filändelse och MIME-typ (*Se 1.1b POST*). <br>
Därefter förbereds SQL med förfrågans struktur och dess menad funktion. Eftersom både id och postedById måste stämma överens och har positional platshållare ('?)
måste både id och userId att skickas in med execution, i rätt ordning.
Den gamla bildens path hämtas och ifall den inte finns blir användaren omdirigerad till error-page.
Nya filen tilldelas ett unikt namn och en kontroll av omflyttning sker med 'move_uploaded_file'.
Därefter skickas uppdaterings förfrågan med kraven av image, id och userId att vara ifyllda.
Om den gamla bilden och dess path finns, används unlink för att radera filen från 'posts' mappen.
Om allt går bra skickas ett meddelande och en status till klientsidan.

Om ett oväntat fel uppstår kommer användaren omdirigeras till error-page med error 'ERR_UPLOAD_FAIL' eftersom denna route innehåller en try/catch statement.

### 1.1e SESSIONS, LOGIN, & PROFILE
###### SESSIONS
Sessions sätts igång på servern innan någon hinner blinka:
````php
session_start();
````
*(detta skrivs på toppen av kodfilen, i detta fall efter de flesta 'require' raderna)* <br>

````php
$isLoggedIn = isset($_SESSION['id']);
$userName = $isLoggedIn ? $_SESSION['name'] : null; //if logged in is true --> username, otherwise --> null
$userId = $isLoggedIn ? $_SESSION['id'] : null;
````
'\$isLoggedIn' variabeln kollar efter ett session ID med hjälp av 'isset'.
'\$userName' och '\$userId' definieras med hjälp av befintligt sessiond ID (genom '\$isLoggedIn' variabeln)
för vilket en villkors sträng (ternary operator eller shorthand if...else-sats) används.
(*username = det som är lagrat inom sessions, och om det inte finns är username = null*).

###### REGISTER
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
````
Besökare har möjligheten att registrera sig och därmed skapa ett konto vars information lagras inom tabellen 'users'.
Angivet lösenord blir, med hjälp av 'password_hash', krypterat. 'PASSWORD_DEFAULT' talar om att bcrypt algoritmen används och är default sen PHP 5.5.0.
Vid sådan process måste tabellen kunna lagra lösenord med mycket utrymme (dvs mer än 60bytes?).

En asterix (*) inom SQL strängen betyder 'ALLT', servern hämtar alltså all data från platsen där email motsvarar inskrivet email.
Om sådan mejladress redan finns omdirigeras användaren till error-page.
Om samma email inte använts blir användaren tillagd i tabellen 'users' inom SQL-databasen och omdirigeras direkt till 'login'.

###### LOGIN
````php
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
Genom superglobalen '\$\_POST' tar servern emot inskriven mejladress och lösenord.
Därefter hämtas all data från tabellen på platsen där email motsvarar inskriven email.
Om sådan användare är registrerad inom databasen och lösenordet stämmer överens (password_verify används för att jämföra/verifiera det inskrivna lösenordet
med befintligt lösenord inom tabellen), blir session ID till användarens ID och detsamma sker med username. (*detta är relevant inom 1.1e --> SESSIONS*).
Om lösenordet är fel eller användaren inte finns, omdirigeras de till error-page.

###### LOGOUT
````php
get("/auth/logout", function () use ($renderer) {
    loginRequired();
    $_SESSION = [];
    session_destroy();
    redirect("cats");
});
````
*För att kunna logga ut måste du faktiskt vara inloggad.*
Väljer användaren att logga ut måste den skapade sessionen för den specifika användaren att avslutas/förstöras,
vilket görs genom 'session_destroy' funktionen.

###### PROFILE MANAGEMENT
DELETE och PATCH bygger mestadels på samma principer som inom katt routes.
Skillnaden ligger i att användaren måste skriva in sitt lösenord för att kunna genomgå ändring av information inom sin profil samt
att en uppdatering av session information sker.
`````php
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
`````
(*utdraget visar inte all kod inom patch routen för profil*) <br>

## 2 RELATERADE FILER & FUNKTIONER
### 2.1 FUNHELPER.PHP
'funHelper.php' innehåller funktioner och konstanter som används inom 'index.php'.
##### 2.1a PATH CONSTANTS
````php
const PATH_PREFIX = "/GA/";
const BASE_URL = "http://localhost".PATH_PREFIX;
}
````
Konstanten 'PATH_PREFIX' definierar '/GA/' delen i URL:en.
Konstanten BASE_URL definierar början av varje routes URL och används mestadels för att följa principen DRY men även på grund av lathet.

##### 2.1b REDIRECT
````php
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}
````
Inom PHP skrivs en omdirigering ut genom 'header' samt http: osv... Därför skapades en funktion för att hantera omdirigering utan lika mycket ansträngning.
Genom 'header' manipuleras HTTP headers, så fort 'Location' är givet skickas användaren dit direkt.
Vid användning av sådant redirect stoppas inte resten av PHP skripten från att köras. Utan 'exit' hade servern utfört resten av filen i bakgrunden och
allting hade gått kaput!

##### 2.1c LOGINREQUIRED
````php
function loginRequired() {
    if(!isset($_SESSION["id"])){
        redirect("auth/login");
    }
}
````
Funktionen kollar att inget SESSION ID finns för att upptäcka om en användare är inloggad eller ej.
Ifall det inte finns och if-satsen resulterar i true (! betyder inte) används redirect för att omdirigera besökaren till login sidan.

##### 2.1d ERROR HANDLING
The ubiquitous 'error-page' med renderade errors har följande route-kod:
````php
get('/errors/$errorCode', function($errorCode) use($renderer){
    echo $renderer->renderFile('/errors.pug', ['errorCode' => $errorCode]);
});
````
Alla '\$errorCodes' får denna däremot från 'funHelper.php', där error-sektionen ser ut på följande sätt:
````php
const ERRORS = [
    'ERR_MISSING_DATA' => ['status'=>400, 'message'=>'ERR: The data is meowssing.'],
    'ERR_INVALID_DATA' => ['status'=>400, 'message'=>'ERR: The data is invalid.'],
    'ERR_LOCKED_OUT'=>['status'=>401, 'message'=>'ERR: Log in first dude..'],
    'ERR_INCORRECT_DATA'=>['status'=>401, 'message'=>'ERR: Incorrect data.'],
    'ERR_FORBIDDEN'=>['status'=>403, 'message'=>'ERR: The audacity you meowtherfucker.. ts is furbidden for u.'],
    'ERR_NOT_FOUND'=>['status'=>404, 'message'=>'ERR: The requested resource is meowssing.. gone..'],
    'ERR_UPLOAD_FAIL'=>['status'=>422, 'message'=>'ERR: the file upload failed, sorri bum']
];

function sendErrorPath($errorCode){
    http_response_code(ERRORS[$errorCode]['status']);
    echo json_encode([
        'errorPath' => PATH_PREFIX.'errors/'.$errorCode
    ]);
}
````
Konstanten 'ERRORS' innehåller olika entries som motsvarar möjligt uppkommande fel med statuskoder och meddelanden.
'\$errorCode' utgörs av strängen som definierar varje fel (dvs exempelvis 'ERR_MISSING_DATA').
Funktionen 'sendErrorPath' tar emot error koder, ser in i listan med alla fel, och finner matchande statuskod. 
'http_response_code' talar om för klientsidan (javascript) att ett fel har uppstått. 
Genom 'errorPath' definieras URL vägen som ska skickas, för att javascript ska ta emot det måste det först konverteras till en JSON sträng.

### 2.2 CONNECT.PHP
'connect.php' innehåller kopplingen till databasen samt skapandet av tabeller.
Databasen byggs på SQLite och PDO (PHP data objects, en lightweight consistent interface gjord för enklare access av databaser).
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
Variabeln '\$db' anger var databasen ska befinna sig (i detta fall mappen 'databas').
'\$dsn' variabeln står för Data Source Name och talar om för PDO (PHP data objects) vilken driver som behövs (databas språket),
samt var databasen befinner sig. 

När PDO kopplar till databasen används 'exec' för att köra ett block av rå SQL, i detta fall är det skapandet av tabeller. 

###### SQL 
* 'CREATE TABLE IF NOT EXISTS' innebär att tabellen skapas endast en gång. 
* 'PRIMARY KEY' innebär att fältet inte kan innehålla NULL värden och är unikt för varje entry (max 1 fält i varje tabell).
* 'AUTOINCREMENT' innebär att för varje ny entry kommer id-värdet att adderas med 1. 

Anledningen till att 'hashedPassword' fältet använder variable character (VARCHAR) beror på att värdet varierar/inte är determinerat.
Ett hashed lösenord behöver mer än 60 bytes och maximala längden sätts därför till 255.

*Ändringar i tabellerna sker via konsollen 'database.sql' eftersom tabellerna skapas bara en gång och redan är
befintliga, kommer ett tillägg av fält just här resultera i ingenting.*

## 3. FRONTEND 
### 3.1 PHUG
PHUG (pug för php) är en PUG template engine skriven med och gjord för PHP.
#### 3.1a LAYOUT
```` pug
doctype html
html(lang="en")
    head
        meta(charset="UTF-8")
        meta(name="viewport", content="width=device-width, initial-scale=1.0")
        title NekoToru
        link(rel='icon' type='image/png' href='/GA/images/favicon1.png')
        script
            include routeFunctions.js
        style
            include style.css
    body
        header
            nav
                include _navbar.pug
        main
            block content

        footer
            block feet
````
'Include' infogar innehåll från andra filer. 
Inom 'main' och 'footer' elementen ingår blocks/templates vars information byts ut i efterhand med hjälp av extensions.

När en vy renderas genom servern skickas 'layout.pug' alltid med eftersom de andra filerna beror på denna (de är extensions).
#### 3.1b TEMPLATES
````pug
extends ./layout.pug

block content
    h1 HELLO AND WELCOME TO THIS CAT PAGE
    |
    .homeLink(style="display:flex; align-items:center;gap:4px;")
        p here you see Lucinator, if you want to know more about the creator.. click
        a(href=$pathPrefix."cats/contact") contact!
    |
    .imgBoxMain(style="width:50%;height:90%; aspect-ratio:1/1;")
        img(src="./images/Lucinatorr.jpg", alt = "the cat is chilling" style="width:100%;height:100%;object-fit:cover;")
        
block feet
    h4 this is the bottom of the page of the cat page
````
Eftersom filen är en extension behöver den inte ha med 'doctype html', dess funktion är att byta ut information i blocken. 

##### 3.1c INTAGNA VARIABLER I PHUG
Inom pug-filerna används variabler från serversidan, detta möjliggörs av följande kod:
````php
global $renderer;
$renderer->share('navItems', $navItems);
$renderer->share(['isLoggedIn' => $isLoggedIn, 'userName' => $userName, 'userId' => $userId, 'pathPrefix' => PATH_PREFIX, 'errors' => ERRORS]);
````
Genom 'share' delas variabler från serversidan med klientsidan och därmed användas inom vyerna. 
Variablerna kan även skickas in med rendering av vy som sett i *1.1a GET*. 

###### ALL CATS VIEW
````pug
extends ./layout.pug

block content
    if count($cats) > 0
        each $cat in $cats
            .card(id="catCard-{$cat['id']}")
                h4(style="font-size:1.5rem;")= $cat['name']
                h5(style="font-size:1.2rem;") Breed: #{$cat['breed']}
                |
                .imgBox(style="height:20vw;width:auto;aspect-ratio:1/1;")
                    img(src=$cat['img'] style="width:100%;height:100%;object-fit:cover;")
                |
                a(href=$pathPrefix."cats/{$cat['id']}") Show Post
                |
                if $userId === $cat['postedById']
                    button(onclick="deleteCar({$cat['id']})", type="button") DELETE
    else
        p no cattos here
````
Enligt PHP syntaxen skrivs varje variabel med '\$' och concatenation sker via punkter. 
Inget mellanslag är angivet då h4 får sitt 'innehåll' eftersom likhetstecknet måste vara fast på taggen för att variabeln ska hämtas.
(Annars kommer '\$cat\['name']) skrivas ut som plaintext..) <br> 
Hashtag tecknet är Pugs syntax för 'string interpolation' som tillåter införandet av variabler från serversidan.

##### 3.1d ERROR PAGE
Ännu ett exempel finns inom 'errors.pug' då varje error Kod innehåller både en status och ett meddelande som skickas med genom '\$renderer->share' (*se 2.1 FUNHELPER.PHP*).
````pug
extends ./layout.pug

block content
    p= $errorCode
    each $error, $index in $errors
        if($index == $errorCode)
            p= $error['status']
            p= $error['message']
            a(href=$pathPrefix."cats") GO TO CATS
````
Om '\$index' matchar '\$errorCode' (dvs strängen som definierar felet), tar PHUG in dess status och meddelande från listan. 
Därefter finns en länk som navigerar tillbaka till 'cats' page. 

### 3.2 JAVASCRIPT
För att möjliggöra kommunikation mellan klient-och-server vid användning av metoder som 'PATCH' och 'DELETE' användes Javascript.
#### 3.2a DELETE
````js
async function deleteCar(id) {
    if (!id) return;
    const data = new URLSearchParams();
    data.append('id', id);

    try {
        const response = await fetch(`/GA/cats`, {
            method: "DELETE",
            body: data,
            headers: {"Content-type": "application/x-www-form-urlencoded"}
        });

        if (!response.ok) {
            await handleResError(response)
            return;
        }
        // Find card in the HTML
        const cardToRemove = document.getElementById(`catCard-${id}`);
        // Make it disappear
        if (cardToRemove) {
            cardToRemove.remove();
            console.log(`Cat ${id} has left the building.`);
        }
    } catch (error) {
        console.error("Network error:", error);
    }
}
````
Denna funktion ansvarar för radering av vald post/katt. 
ID skickas med genom funktionen inom 'onclick' på knappen 'DELETE'. 
Först verifieras att ett ID finns, efter vilket konstanten 'data' definieras av formateringen av ID:et (utifrån URLSearchParams, skickas ej som JSON). 
Sedan används fetch API:n för att skicka DELETE metoden och tala med servern, await innebär att koden väntar tills ett svar är angivet. 
Om allt är godkänt och 'response.ok' är 'true' letas kattens card upp för att tas bort från browserns vy. 

#### 3.2b UPDATE MED PATCH OCH POST 
Fetch av text baserad informaton skickar också med 'URLSearchParams', däremot hanteras bildens ändring på olikt sätt. 
````js
async function updateCarImage(){
    const id = document.querySelector("#id").value;
    const img = document.querySelector("#img");

    if(img.files.length === 0){
        alert("dude select an image first :(")
        return;
    }
    const data = new FormData();
    data.append('id', id);
    data.append('img', img.files[0]);

    const response = await fetch(`/GA/cats/image`, {method:"POST", body:data});
    if(!response.ok){
        await handleResError(response)
        return;
    }

    window.location.reload();
}
````
I detta fall skickas id genom ett gömt form-fält (det syns i inspection mode, däremot behöver användaren inte skriva in det). 
Uppdateringen eller sändning av text-data (såsom name, breed) sker via URLSearchParams eftersom det primärt hanterar bara text. 
URLSearchParams formaterar data som kontinuerliga text-strängar, dvs 'application/x-www-form-urlencoded'. 
FormData däremot, är gjort för hantering av 'multipart/form-data', detta gör att fältet för file upload inom form (HTML) måste specifieras som 'file'.

###### FUNKTIONEN handleResError
````js
async function handleResError(response){
    const json = await response.json();
    window.location = json.errorPath;
}
````
Denna funktion skapades för principen DRY. 
Funktionen tar emot ett svar utifrån servern och får in en definierad väg (path) som används till omdirigering. 
