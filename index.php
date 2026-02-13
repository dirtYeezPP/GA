<?php

require("router.php");
require("Response.php");
require("data.php");
require("Db.php");







get("/", function () {


Res::json(Db::getCats());


});

// CONTACT PAGE 
get("/contact", function () {
    echo "this is the contact page";
});

// SHOW ALL PRODUCTS 
get("/cats", function () {
    res::debug(data::getData("cats"));
});

// SPECIFIED ID QUERY 
get('/cat/$id', function ($id) {
    echo "car with id: $id";
});


//CREATE ROUTE  
get("/cats/create", function () {
    include('create.html');
});

post("/cats", function () {
    //Res::json($_POST); 

    $cat = [
        "id" => uniqid(true),
        "catBreed" => $_POST['catBreed'],
        "catName" => $_POST['catName']
    ];

    $cats = data::getData("cats");
    array_push($cats, $cat);
    data::saveData("cats", $cats);

    header("Location: http://localhost/GA/cats");
});

// DELETE ROUTE
get("/cats/delete", function () {
    include("delete.html");
});

delete("/cats", function () {

    parse_str(file_get_contents("php://input"), $_DELETE); //get ID from the request 

    $catId = $_DELETE['id'];

    $cats = data::getData("cats");

    $filtedCats = array_filter($cats, function ($c) use ($catId) { //use gör att vi kan använda cat som om den är global fr 
        return $c['id'] != $catId;
    });

    $filtedCats = array_values($filtedCats);

    data::saveData("cats", $filtedCats);
    header("Loco: http://localhost/GA/cats");
});



// UPDATE ROUTE
get("/cats/update", function () {
    include("update.html");
});

patch("/cats", function () {

    parse_str(file_get_contents('php://input'), $_PATCH);

    $cat = [
        "id" => $_PATCH['id'] ?? "no_id",
        "catName" => $_PATCH['name'],
        "catBreed" => $_PATCH['breed']
    ];

    $cats = data::getData("cats");

    $updateIndex = null;
    $oldCat = ""; // används aldrig.. 
    foreach ($cats as $index => $c) {
        if ($cat['id'] == $c['id']) {
            $updateIndex = $index;
            $oldCat = $c; 
            break;
        }
    }
    if ($updateIndex) {
        $cats[$updateIndex]['catName'] = $cat['catName'] ? $cat['catName'] : $c['catName']; // här ska det möjligtvis egentligen stå oldcat efter : 
        $cats[$updateIndex]['catBreed'] = $cat['catBreed'] ? $cat['catBreed'] : $c['catBreed']; //ternary operator 
    }

    data::saveData("cats", $cats);
    header("Loco: http://localhost/GA/cats");
});
