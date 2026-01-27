<?php

require("router.php");
require("Response.php"); 
require("data.php");  

get("/", function(){
    echo "Hello Word.................";
});

get('/cars/$id', function($id){
    echo "car with id: $id";
});

get("/cats", function(){
    $catBreed =  $_GET['breed'] ?? " ";
    echo "cats like this: $catBreed , are pretty peak, right?"; //query string 
});

get("/create", function(){
    include("form.html"); 
});


/* post("/save", function(){
    Res::json($_POST); 
});  */

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
