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
*(för använding av php-variabler inom pug, se 2.1c INTAGNA VARIABLER I PHUG)*

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
*För information om 'loginRequired se,, användaren måste vara inloggad för tillgång till detta* <br>
*För information om 'sendErrorPath', se, denna beter sig olika eftersom POST data i detta fall inte skickas mha js* <br>
*För information om 'redirect' se 1.2 RELATERADE FILER*
Rendering av formen genom vilken en post skapas sker genom en GET-route (dvs '/cats/create'). Om ingen bild laddas upp sker en omdirigering till '/errors' *(se )* där felet står både i URL:en och på webbsidan.
Hanteringen av filuppladdning har två kontroller för säkerhet som definieras av '\$allowedExes' och '\$allowedMimes'.
* '\$allowedExes' utgör en lista av tillåtna fil-extensions (exempelvis .jpg). 
* '\$allowedMimes' utgör en lista av tillåtna MIMES (MultiPurpose Internet Mail Extensions), vilket kollar file content. 

Den uppladdade filens mime och extension kontrolleras innan vidare hantering. Efter godkännande anges ett unikt namn, genom bl.a. funktionen 'uniqid'. 
När filer laddas upp är de temporärt gömt lagrad och behöver flyttas till 'posts' mappen, i detta fall, för att sparas. Sista if-satsen kontrollerar 
om omflyttningen av filen misslyckats och resulterar då i en omdirigering till error page. 
 
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
När första matchningen av ID:et hittas, hämtas bild-kolumnen (dvs bild vägen för den specifika katten) för att hantera radering. 
SQL-strängen innehåller clause 'id = :id' där ':id' fungerar som en platshållare och är beroende på namnet av värdet som skickas in. 
Raden för bilder inom tabellen lagrar en väg (path) till uppladdad bild (dvs exempelvis '/posts/*bildnamn*'), eftersom bilder lagras i mappen 'posts'. 
Unlink används därför för borttagning av bilden från 'posts' för att minska onödig platsupptagning.

#### 1.1d PATCH / UPDATE ROUTE 
Uppdatering av posts sker separat gällande text (PATCH) och bildhantering (POST). 
###### PATCH 
För att ändra namn eller ras (befinnande text-information) på katten/posten används metoden PATCH.
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
Likt DELETE behöver PATCH först definieras för att möjliggöra servern intag av inskickad data ifrån klient-sidan.
'\$request' listan innehåller den parseade datan från \$_PATCH förfrågan av klient-sidan (dvs body från javascript).
För att tillåta användaren att lämna form-fält tomma lagrar '\$sqlPramValues' endast ifylld data från '\$request'.
Genom IF-satsen kontrolleras att ett ID är angivet och minst ett fält är ifyllt för en ändring. Foreach loopen 
bygger i sin tur dynamiskt upp SQL strängen med clauses tagna utifrån '\$sqlPramValues'. 
Implode funktionen gör om listan till en sträng med clauses (*en clause ser ut som följande: 'age = :age'*) och tillsätter komma emellan satserna.
Är bara ett fält angivet får satsen inget komma efter sig. 
För säkerhet måste både postens id OCH id:et av användaren som lagt upp bilden stämma överens med användaren som begärt uppläggs ändringen.
Sista if-satsen kontrollerar att variabeln '\$stmt' har 0 rader, vilket skulle innebära att ingen information/fält var givet och resulterar i en redirect till error page. 


'\$field' definierar namnet 
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
(*Anledningen till att uppdateringen av en post sker via två separata routes finns i*) 
Liksom i 'create' routen kontrolleras att filen finns och att den inte är av karaktär med otillåten extension eller MIME, 
(*se 1.1b POST*). <br> 
Om filen blivit godkänd av alla kontroller går processen vidare till try/catch statementen.
Först hämtas redan befinnande bild från databasen på den valda kattens/postens plats, efter vilket dess existens kontrolleras. 
Liksom i 'create' routen tilldelas ett unikt namn till filen och uppladdningen av bilden verifieras. Nästa steg är att lagra den nya datan i databasen, 
vilket görs med hjälp av SQL-strängen. I detta fall innehåller den 'prepared statements' (dvs 'id = ?') som tjänar funktionen av platshållare, skillanden mellan 
denna typ av clause och 'id = :id' (*se 1.1c DELETE*) är att '?' förlitar sig på ordningen av datan och inte namnet. 
Därefter sänds den uppdaterade datan till 'cattos' tabellen och den gamla bilden raderas från 'posts' mappen. 
Om allt gått bra får vi status 'sauce' (en medveten omformulering av 'success') och ett meddelande skickas till webbsidan (genom json, js).
Catch-blocket finns för att hantera plötsliga fel (som hade kraschat sidan och lämnat oss utan renderade vyer exempelvis) genom att omdirigera användaren vid sådan uppkommelse.

### 1.1e SESSIONS, LOGIN, & PROFILE 
###### SESSIONS
Sessions sätts igång på servern innan någon vy hinner renderas/en route körs genom följande:
````php
session_start();
````
*(detta skrivs på toppen av kodfilen, i detta fall efter de flesta 'require' raderna)* <br>
För att lagra users och aktivera sessions/cookies på klient-sidan skrivs följande kod: 
 
````php
$isLoggedIn = isset($_SESSION['id']);
$userName = $isLoggedIn ? $_SESSION['name'] : null; //if logged in is true --> username, otherwise --> null
$userId = $isLoggedIn ? $_SESSION['id'] : null;
````
'\$isLoggedIn' variabeln kollar efter ett session ID med hjälp av 'isset'. 
'\$userName' och '\$userId' definieras med hjälp av befintligt sessiond ID (genom '\$isLoggedIn' variabeln)
för vilket en ```````` används (en kort if-sats som säger '\$userName är \$_SESSION\['name'] om \$isLoggedIn är sant, annars är värdet null). 

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
Registrering sker via metoden POST eftersom varje användare sparas i databasens tabell 'users', som innehåller 
id, namn, email och hashed lösenord (*för mer information se 1.2a DATABAS*) <br> 
Lösenordet som användaren skriver in genomgår en hash med 'password_hash' funktionen, 'PASSWORD_DEFAULT' använder 
bcrypt algoritmen och är default sen PHP 5.5.0. Denna metod kräver att lösenordet lagras med mycket plats eftersom 
konstantens värde varierar över tid (PHP uppdateras etc). <br>
Asterix (*) inom SQL-strängen talar om att all data ska hämtas från tabellen 'users', efter vilket angiven email kontrolleras
och resulterar i omdirigering till error-page om ett konto med samma email redan finns. 
Annars skapas en ny user och dess information lagras i tabellen 'users'. 
För en lite bättre användar-upplevelse skickas användaren direkt till 'auth/login' efter registrering. 

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
Eftersom användare kan ha samma namn sker login genom email och lösenord-inskrivning. 
Först hämtas all data från tabellen 'users' där email motsvarar mejladressen inskriven i fältet, hämtat med POST superglobalen. 
'\$user' definieras genom befintlig data på mejladressens plats (dvs id, namn, email...). Därefter jämförs/verifieras det inskrivna lösenordet 
med '\$hashedPassword' inom 'user' tabellen (mejladressens plats) med hjälp av funktionen 'password_verify'. Om de stämmer överens 
blir användaren inom en egen session. 
Går allt åt skogen blir användaren omdirigerad till error-sidan med felet 'ERR_INVALID_DATA'. Genom att inte tala om exakt
vad felet beror på, såsom 'ERR_INCORRECT_PASSWORD' förebyggs att någon hackar sig in i kontot genom att gissa fram det rätta lösenordet.

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
Väljer användaren att logga ut måste den skapade sessionen för den specifika användaren att avslutas, 
vilket görs genom 'session_destroy' funktionen.

###### PROFILE MANAGEMENT 
Detta projekt innehåller en vy för egen profil samt möjlighet att både radera sitt konto eller uppdatera sin information. 
Funktionerna inom profil-management bygger på samma principer som uppläggen av katterna och koden skiljer sig därför inte mycket ifrån dem.

````php
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
````

### 1.2 RELATERADE FILER
#### 1.2a FUNHELPER.PHP
'funHelper.php' innehåller funktioner eller konstanter som används inom 'index.php'. 
##### PATH KONSTANTER OCH REDIRECT 
````php
const PATH_PREFIX = "/GA/";
const BASE_URL = "http://localhost".PATH_PREFIX;

function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}
````
Konstanten 'PATH_PREFIX' används eftersom '/GA/' alltid kommer vara i URL:en innan annat såsom '/cats' skrivs. 
Konstanten 'BASE_URL' används för enkelhetens skull, denna definierar starten av varje route för att kunna användas i redirects. 

Inom 'index.php' skickas variabeln 'PATH_PREFIX' genom följande:
````php
$renderer->share('navItems', $navItems);
$renderer->share(['isLoggedIn' => $isLoggedIn, 'userName' => $userName, 'userId' => $userId, 'pathPrefix' => PATH_PREFIX, 'errors' => ERRORS]);
````
'\$renderer->share' delar med sig av de inskrivna variablerna för att möjliggöra dess användning inom pug-filerna (*se 2.1c INTAGNA VARIABLER I PHUG*). 

##### LOGINREQUIRED 
````php
function loginRequired() {
    if(!isset($_SESSION["id"])){
        redirect("auth/login");
        exit;
    }
}
````
Ovanstående kod skrivs för principen DRY inom 'index.php'.
Funktionen ansvarar för en omdirigering av användaren till 'auth/login' route:n om de inte är inloggade. 
Exempelvis används detta inom 'create' eftersom användaren kanske testar skriva in create routen:s path i URL:en,
i detta fall kommer användaren hamna på 'auth/login'. 

##### ERROR HANDLING
The ubiquitous 'error-page' () med renderade errors har följande route-kod: 
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
    'ERR_FORBIDDEN'=>['status'=>403, 'message'=>'ERR: The audacity you motherfucked.. ts is furbidden for u.'],
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
I detta fall har en konstant array definierats med olika fel som kan uppstå. 
Felen kan ha samma statuskod och därför har eleven valt att definiera varje fel med strängar som 'ERR_INVALID_DATA' (dessa är '\$errorCode').
Varje sådan error innehåller motsvarande status kod och ett meddelande som sedan visas upp på webbsidan (*se 2.1C INTAGNA VARIABLER I PHUG*). <br>
Funktionen 'sendErrorPath' har skapats för att dels följa principen DRY (dont repeat yourself) men även för enkelhetens skull.
Funktionen tar in '\$errorCode' och kan utifrån det definiera vad response koden är ('http_response_code) genom 'status' fältet av '\$errorCode's plats (ex 'ERR_MISSING_DATA). 
Efter vilket den ger ut 'errorPath' som motsvarar routen för 'error-page':n. 

#### 1.2b DATABAS 
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
'\$db' anger var databasen ska befinna sig (i detta fall valdes mappen 'databas'). 
'\$dsn' variabeln (Data Source Name) ansvarar för PDO (PHP Data Objects) vilken 'motor' som behövs för denna databas, 
här blir '\$db' vägledaren för 'motorn' för att möjliggöra koppling till databasen. <br>
Varje tabell skapas endast en gång ('CREATE TABLE IF NOT EXISTS), varefter kolumner läggs till (id, name, breed). 
Varje kolumn eller fält specifierar vilken typ datan ska komma i. 'PRIMARY KEY' säkerställer unika värden och kan inte innehålla värden 'null'. 
Varje tabell kan bara ha ett fält av denna karaktär. 
'AUTO INCREMENT' betyder att för varje ny entry kommer id-värdet att adderas med 1. 
'VARCHAR' betyder 'variable-length string' och används när värdet varierar eller är icke determinerat. Värdet 255 beror på att
ett hashed lösenord typiskt sätt behöver mer än 60 bytes av utrymme. <br> 

*Ändringar i tabellerna sker via konsollen 'database.sql' eftersom tabellerna skapas bara en gång och redan är 
befintliga, kommer ett tillägg av fält just här resultera i ingenting.*

## 2. FRONTEND 
### 2.1 PHUG
PHUG (pug för php) är en PUG template engine skriven med och gjord för PHP. 

#### 2.1a MAIN FIL - LAYOUT 
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
Pug skrivs genom indentation likt python för att visa tillhörighet. 
'Include' låter innehåll från andra filer att infogas, i detta fall ska 'style.css', 'routeFunctions.js' och '_navbar.pug' vara infogade/befintliga varje gång. 
Inom 'main' och 'footer' elementen ingår 'block', informationen inom dessa byts ut beroende på kontext och var användaren befinner sig (ex '/contact' eller '/create').

#### 2.1b UTBYTE AV INFORMATION INOM BLOCK 
För varje route användaren befinner sig i, uppvisas olika innehåll: 
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
För att innehållet i de olika blocken ska bytas ut, måste filen vara en extension till 'layout.pug'. 
Eftersom olika routes har rendering av olika pug-filer, kommer 'layout.pug' alltid att skickas med, tillsammans med 
filen som renderas inom routen.

##### 2.1c INTAGNA VARIABLER I PHUG
Eftersom PHP deklarerades som expression-language inom 'index.php' (*se 1.1a GET*) skrivs variabler, concatenation och annat, enligt php språket (PHUG är även designat för just PHP). 
Inom get-routen för alla katter/posts, skickade servern med '\$cats' arrayen vid renderingen av sidan, detta möjliggör följande skrivsätt:
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
Varje variabel (såsom '\$cat') skrivs med ett '$', alltså standarden inom PHP för variabler. 
För att '\$cat\['name]' ska visas upp på sidan får inget mellanslag finnas mellan h4 och variabeln. 
Däremot efterson h5 taggen innehåller texten 'Breed:' används skrivsättet '\#{\$cat\['breed]}'.

###### ERROR HANDLING 
Ännu ett exempel finns inom 'errors.pug' då varje error Kod innehåller både en status och ett meddelande som skicas med genom '\$renderer->share' (*se 1.2a FUNHELPER.PHP*).
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

### 2.2 JAVASCRIPT
För att möjliggöra kommunikation mellan klient-och-server vid användning av metoder som 'PATCH' och 'DELETE' användes Javascript.
#### 2.2a DELETE
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
ID skickas med genom funktionen inom 'onclick' på knappen 'DELETE' (*se 2.1C INTAGNA VARIABLER I PHUG*). 
Först verifieras att ett ID finns, efter vilket konstanten 'data' definieras av formateringen av ID:et (utifrån URLSearchParams, skickas ej som JSON). 
Sedan används fetch API:n för att skicka DELETE metoden och tala med servern, await innebär att koden väntar tills ett svar är angivet. 
Om allt är godkänt och 'response.ok' är 'true' letas kattens id upp för att raderas från sidan.

#### 2.2b UPDATE MED PATCH OCH POST 
Fetch för uppdateringen av posts bygger på samma principer som ovanstående sektionens kod, där skillnaden är att 
konstanten data får tillägg av information genom 'append' och skickar sedan vidare allt i form av en body till servern.
Vad gäller bildhantering ligger skillnaden i utbytet av URLSearchParams mot FormData. 
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

###### 'HANDLERESERROR' FUNCTION 
````js
async function handleResError(response){
    const json = await response.json();
    window.location = json.errorPath;
}
````
Denna funktion skapades för principen DRY. 
Funktionen tar emot ett svar utifrån servern och får in en definierad väg (path) som används till omdirigering. 
