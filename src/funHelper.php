<?php

const PATH_PREFIX = "/GA/";
const BASE_URL = "http://localhost".PATH_PREFIX;

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
const ERRORS = [
    'ERR_MISSING_DATA' => ['status'=>400, 'message'=>'ERR: The data is meowssing.'],
    'ERR_INVALID_DATA' => ['status'=>400, 'message'=>'ERR: The data is invalid.'],
    'ERR_LOCKED_OUT'=>['status'=>401, 'message'=>'ERR: Log in first dude..'],
    'ERR_INCORRECT_DATA'=>['status'=>401, 'message'=>'ERR: Incorrect data.'],
    'ERR_FORBIDDEN'=>['status'=>403, 'message'=>'ERR: The audacity you motherfucked.. ts is furbidden for u.'],
    'ERR_NOT_FOUND'=>['status'=>404, 'message'=>'ERR: The requested resource is meowssing.. gone..'],
    'ERR_UPLOAD_FAIL'=>['status'=>422, 'message'=>'ERR: the file upload failed, sorri bum']
];

function sendErrorPath($errorCode){

    http_response_code(ERRORS[$errorCode]['status']);
    echo json_encode([
        'errorPath' => PATH_PREFIX.'errors/'.$errorCode
    ]);

}