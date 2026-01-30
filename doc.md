# Documentation / Loggbok 

## Gjort 
```  
```
## Ska göras 

## borttaget 
``` php 
get("/colors", function(){
    Res::debug(data::getData("colors")); 
}); 

get('/colors/$color', function($color){
    //en associative array 
    $color = [
        "id"=>uniqid(true), //genererar unikt id 
        "color"=>$color,
        "goes with"=>"unknown"
    ]; 

    $colors = data::getData("colors"); //hämta gammal data 
    array_push($colors, $color);   //först array sen vad vi pushar 
    data::saveData("colors", $colors); 

    //redirect 
    header("Location: http://localhost/GA/colors");  //absolute route  
});
```
taget från Fredrics video --> funktioner som save och get data i "data.php". 

#### query grej 
``` php
get("/cats", function(){
    $catBreed =  $_GET['breed'] ?? " ";
    echo "cats like this: $catBreed , are pretty peak, right?"; //query string  
}); 
``` 
query string --> i URL ger ut resultatet med echo som använder utgivna variabeln. 

#### post och formulär kopplat 
``` php 
post("/save", function(){
    Res::json($_POST); 
}); 
``` 
vi har ett formulär i html filen som då har action "./save" med method post. 
vi tar emot post grejen och använder oss utav res (klass, funktion?) från response.php. 
detta kan vi tack vare "require("filnamn")" i början av hela kodskiten yes. 

#### filter av fredric 
``` php

$arr = [12, 14, 15, 16];

$lT12 = array_filter($arr, function($n){

return $n>12;

});


var_dump($lT12);
```
filter yes 