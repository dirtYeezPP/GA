<?php

require("router.php");
require("src/Response.php");
require("src/data.php");
require_once 'src/connect.php';

get("/", function () {
    echo "you're gay";
});

// CONTACT PAGE 
get("/contact", 'views/contact.html');

// SHOW ALL PRODUCTS 
get("/cats", function () {
    Res::debug(data::getData("cats"));
});

// SPECIFIED ID QUERY 
get('/cat/$id', function ($id) {
    echo "car with id: $id";
});

//CREATE ROUTE  
get("/cats/create", 'views/create.html');

post("/cats", function () {
    $requested = [
        "id" => uniqid(true),
        "catBreed" => $_POST['catBreed'],
        "catName" => $_POST['catName']
    ];

    $cats = data::getData("cats");
    $cats[] = $requested;
    data::saveData("cats", $cats);

    header("Location: http://localhost/GA/cats");
});

// DELETE ROUTE
get("/cats/delete", 'views/delete.html');

delete("/cats", function () {
    parse_str(file_get_contents("php://input"), $_DELETE); //get ID from the request 

    $catId = $_DELETE['id'];
    $cats = data::getData("cats");

    $filteredCats = array_filter($cats, function ($c) use ($catId) { //use gör att vi kan använda cat som om den är global fr
        return $c['id'] != $catId;
    });
    $filteredCats = array_values($filteredCats);

    data::saveData("cats", $filteredCats);
    header("Loco: http://localhost/GA/cats");
});

// UPDATE ROUTE
get("/cats/update", 'views/update.html');

patch("/cats", function () {
    parse_str(file_get_contents('php://input'), $_PATCH);
    $request = ["id" => $_PATCH['id'] ?? "no_id", "catName" => $_PATCH['name'], "catBreed" => $_PATCH['breed']];

    $cats = data::getData("cats");

    foreach ($cats as &$c) {
        if ($request['id'] == $c['id']) {
            $c['catName'] = $request['catName'] ?: $c['catName']; //ternary operator
            $c['catBreed'] = $request['catBreed'] ?: $c['catBreed'];
        }
    }

    data::saveData("cats", $cats);
    header("Loco: http://localhost/GA/cats");
});
