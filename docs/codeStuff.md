# CODE STUFF 
Building application with the use of PHP (phprouter) and database (SQLite / MySQL dunno yet). 
Database includes cats. 
Create - done --> shall be sharpened 
Read - done --> shall be sharpened 
Update - in progress 
Delete - in progress

#### HTML 
``` html
 <button onclick="submitCats()" type="button">update</button> 
```
inom form --> om button inte får type appliceras den en default behavior av submit. Type gör att den inte följer en default behavior och är sig själv. 

## AI BASED 

##### DELETE ROUTE STUFF 
``` php
    $filtedCats = array_filter($cats, function($c) use($catId){ 
        return $c['id'] != $catId; 
    });

    $filtedCats = array_values($filtedCats);  // text underneath explains THIS 
```
AI says (gemini) --> reset the array keys. Otherwise the JSON saves with "missing indices" and the array turns into an object. 

## YAN BASED 
``` js
        function submitCats(){
            console.log("HELLOOO");
            const id = document.querySelector("#id").value; 
            const name = document.querySelector("#name").value; 
            const breed = document.querySelector("#breed").value; 

            const data = new URLSearchParams();
            data.append('id', id);
            data.append('name', name);
            data.append('breed', breed);

            fetch(`/GA/cats`, {method:"PATCH", body:data, headers:{"Content-type":"application/x-www-form-urlencoded"}, redirect:"manual"}).then((response) => {
                const loco = response.headers.get("Loco");
                window.location.replace(loco);
                return response.json();
            }).then((response) => {
                console.log(response);
            });
        }
```
##### GEMINI FÖRKLARADE MYCKET AV DETTA 
vi tar in id, name och breed genom id av formulärets skrivfält. 
append fyller på själva innehållet som ska skickas iväg till servern, alltså det som usern skriver in i fälten, vi ger de även en liksom key 
fetch skickar en request till servern
skriver in vilken metod (då PATCH inte är supported by form action i html, bara get och post är möjliga). 
body blir till datan, dvs det vi tog in föregående. 
headers --> label av "paketet" som vi skickar, servern måste veta hur den ska läsa informationen som skickas. 
Servern får genom headers (innehållet) reda på att datan är formaterad som en HTML form. Annars vet servern inte hur man ska parsea datan. 

Redirect manual --> vi talar om att vi vill hantera redirecten inom koden själv, att den inte ska göra det automatiskt. 
window location replace loco --> skickar browsern till URL som är i loco. 
return response json --> takes body från serverns svar och gör om det till en javascript object för att möjliggöra läsning. 
windows location replace händer före nästa then --> så pagen hinner faktiskt inte reagera (vi bortkommenterade detta inom annan fil).

``` php
parse_str(file_get_contents("php://input"), $_DELETE); //get ID from the request 
```
Servern måste parsea den data vi skickar genom fetch samt för att kunna göra nedanstående. 
``` php
"catName" => $_PATCH['name'],
```

#### REDIRECT GREJ 
``` php
 header("Location: http://localhost/GA/cats"); //php 
```
kan replaceas med 
``` js
window.location.replace("/GA/cats"); //script i html 
```
då window.location sätter vilken webbsida du är på --> efter vi servern utfört delete eller update kommer detta ta hand om redirect istället. 


## FREDRIC BASED 

#### MySQL DATABASE ADD-ON 
``` php
<?php
class Db{
    private static function connect(){
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "cat_db";
        // Create connection
        $con = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($con->connect_error)  return ["error" => $con->connect_error];
        return $con;
        }

        public static function getCats(){
            // Koppla upp oss mot databas
            $con = self::connect();

            $q = $con->query("SELECT * FROM cats");

            $cats = [];
            while($row = $q->fetch_assoc()){
                array_push($cats, $row);
            }
            $con->close();
            return $cats;
        }
    }/* End class */
```
Funktionen är privat eftersom vi måste accessa databasen BARA inifrån DB filen. 
Nästa funktion är publik för att vi ska kunna använda den inom andra filer såsom idnex.php. 
self --> vi är inom klassen och behöver inte necessarily skriva klassens namn då. 
$q delen --> vi vill välja ALLT från vår databas cats. 
vi gör det till en tom array och gör att vi tar in objekt så länge det finns nya att ta in, annars kanske den visar bara en. 
De kommer inte visas i rätt format om vi inte skriver fetch_assoc (associative array). 

``` php
Res::json(Db::getCats());
```
detta gör vi i en route för att hämta vår data från MySQL databasen, samt gör om den till json fil för rätt format och utskrift. 

## OTHER BASED 

#### SQLite 
SQLite is serverless database that is self-contained --> the DB engine runs as a part of the app. 
MySQL requires a server to run (vilken vi kan med XAMPP men ändå), MySQL kräver en client och server arkitektur för att interagera över nätverket. 

##### SQLite connection 

1. mkdir database 
2. define variable db to store path to file in database dir
3. define dsn -> stores data src name of SQLite database file. 
4. try catch statement to catch error :D 
5. open connect in browser, check if your .db file has been created 
``` php
$db = './databas/cattos.db';

$dsn = "sqlite:$db";

try { 
    $pdo = new \PDO($dsn); 
    //echo "gay successfully";
} catch (\PDOException $e) {
    echo "". $e->getMessage() ."";
}
```
###### SQLite tables 

``` php

```
