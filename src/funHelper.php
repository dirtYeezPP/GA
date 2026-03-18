<?php

const BASE_URL = 'http://localhost/GA/';

function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}

function loginRequired() {
    if(!isset($_SESSION["id"])){
        redirect("auth/login");
        exit;
    }
}
