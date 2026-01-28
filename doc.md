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