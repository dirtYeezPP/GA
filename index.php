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
    
    $filtedCats = array_filter($cats, function($c) use($catId){ //use gör att vi kan använda cat som om den är global fr 
        return $c['id'] != $catId; 
    });

    data::saveData("cats", $filtedCats);
    header("Location: http://localhost/GA/cats"); 
});
 

get("/update", function(){
    include("update.html"); 
}); 

post("/alter", function(){ //MAKE IT BETTER !!! EX THROUGH MAKING AN EXTRA FUNCTION INSTEAD THAT FINDS THE CAT 



    $cat = [
        "id"=>$_POST['id'] ?? "no_id", 
        "catName"=>$_POST['catName'], 
        "catBreed"=>$_POST['catBreed']
    ];

    $cats = data::getData("cats"); 

    $updateIndex = null; //pretty gay to use 2 variables for a check later 
    $oldCat ="";
    //make the control act on earlier, in order to check it without the unnecessary extra code here.. 
    foreach($cats as $index => $c)
        {
            if($cat['id'] == $c['id'])
                {
                     $updateIndex = $index;
                     $oldCat = $c;
                     break;
                }

        }
        if($updateIndex) 
            {
                $cats[$updateIndex]['catName'] = $cat['catName'] ? $cat['catName'] : $c['catName'];
                $cats[$updateIndex]['catBreed'] = $cat['catBreed'] ? $cat['catBreed'] : $c['catBreed']; //ternary operator 
            }

    data::saveData("cats", $cats);
    header("Location: http://localhost/GA/cats");

});
