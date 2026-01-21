<?php

require("router.php");
require("Response.php"); 



get("/", function(){
    echo "Hello Word.................";
});


get('/cars/$id', function($id){
    echo "car with id: $id";
});

get("/cats", function(){
    $catBreed =  $_GET['breed'];
    echo "cats like this: $catBreed , are pretty peak, right?";
});

get("/create", function(){
    include("form.html"); 
});


post("/save", function(){
    Res::json($_POST); 
}); 
