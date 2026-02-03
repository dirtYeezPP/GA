<?php

require("router.php");
require("Response.php"); 
require("data.php");  


get("/", function(){
    echo "This is meant to be the default check page"; 
}); 

// CONTACT PAGE 
get("/contact", function(){
    echo "this is the contact page"; 
}); 

// SHOW ALL PRODUCTS 
get("/cats", function(){
    res::debug(data::getData("cats")); 
}); 

// SPECIFIED ID QUERY 
get('/cat/$id', function($id){
    echo "car with id: $id";  
}); 


//CREATE 
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

// DELETE 
get("/delete", function(){
    include("delete.html"); 
}); 

post("/remove", function(){
    //$cat = [
        //"id"=>$_POST['id']
        //"catBreed"=>$_POST['catBreed'],
        //"catName"=>$_POST['catName']
    //]; 

    $catId = $_POST['id']; 

    $cats = data::getData("cats"); 
    
    $filtedCats = array_filter($cats, function($c) use($catId){ //use gör att vi kan använda cat såsom den är global fr 
        return $c['id'] != $catId; 
    });

    data::saveData("cats", $filtedCats);
    header("Location: http://localhost/GA/cats"); 
});
 


