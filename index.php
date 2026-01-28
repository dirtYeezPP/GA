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

post("/save", function(){
    Res::json($_POST); 
}); 

get("/cats", function(){
    $catBreed =  $_GET['breed'] ?? " ";
    echo "cats like this: $catBreed , are pretty peak, right?"; //query string 
});
