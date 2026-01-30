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

get("/cats", function(){
    Res::debug(data::getData("cats")); 
}); 

get('/cat/$id', function($id){
    echo "car with id: $id";  
}); 

get("/create", function(){
    include('create.html'); 
});

post("/save", function(){
    //Res::json($_POST); 

    $cat = [
        "id"=>uniqid(true), 
        "catBreed"=>$_POST['catBreed'],
        "catName"=>$_POST['catName']
    ]; 

    $cats = data::getData("cats"); 
    array_push($cats, $cat); 
    data::saveData("cats", $cats); 

    header("Location: http://localhost/GA/cats");
}); 

get("/delete", function(){
    include("delete.html"); 
}); 

// FIXA ROUTE IDJIWUFEIOJFEO
post("/remove", function(){
    $cat = [
        "id"=>$_POST['id'],
        "catBreed"=>$_POST['catBreed'],
        "catName"=>$_POST['catName']
    ]; 

    $cats = data::getData("cats"); 

    $filtedCats = array_filter($cats, function($car){ //fixa 
        return $car!=$cat; //fixa  
    });

    data::saveData("cats", $filtedCats); 
    header("Location: http://localhost/GA/cats"); 

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


