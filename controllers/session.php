<?php
require_once("config.php");
require_once("../models/Router.php");
require_once("../models/Res.php");
require_once("../models/User.php");
require_once("../models/Session.php");

$router = new Router();

$router->get("v1/sessions/<id>", function($urlParams) {
    $sessId = $urlParams[1];
    try {
        if(Router::authenticate()->session->getSessId() !== $sessId) {
            $response = new Res(false, 401, "UnAuthorized");
        }
        $response = new Res(true, 200, "Session exists", Router::authenticate()->session->getUserId());
    }
    catch(AuthException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');              
    }
});

$router->post("v1/sessions", function($urlParams, $postData) {
    try {
        $authenticate = User::login($postData);
        $response = new Res(true, 200, "You've logged in successfully", $authenticate);
    }
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');              
    }
});

$router->delete("v1/sessions/<id>", function($urlParams) {
    $givenSessId = $urlParams[1];
    try {
        $currentSession = Router::authenticate()->session;
        if($givenSessId != $currentSession->getSessId()) $response = new Res(false, 401, 'Unauthorized');  
        $currentSession->delete();        
        $response = new Res(true, 200, "You've Logged Out Successfully");
    }
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');              
    }
});

?>