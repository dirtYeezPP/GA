<?php

require("router.php");
require("Response.php"); 
require("data.php");  


get("/", function(){
    echo "This is meant to be the default check page"; 
}); 

get("/contact", function(){
    echo "this is the contact page"; 
}); 

get('/cat/$id', function($id){
    echo "car with id: $id";  
}); 

get("/cats", function(){
    Res::debug(data::getData("cats")); 
}); 

get('/cats/$catBreed/$catName', function($catBreed, $catName){
    //en associative array 
    $cat = [
        "id"=>uniqid(true), //genererar unikt id 
        "catBreed"=>$catBreed,
        "catName"=>$catName
    ]; 

    $cats = data::getData("cats"); //hämta gammal data 
    array_push($cats, $cat);   //först array sen vad vi pushar 
    data::saveData("cats", $cats); 

    //redirect 
    header("Location: http://localhost/GA/cats");  //absolute route  
});


