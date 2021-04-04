<?php
require_once("config.php");
require_once("../models/Router.php");
require_once("../models/Res.php");
require_once("../models/Song.php");
require_once("../models/Artist.php");

$router = new Router();

$router->get("v1/search/<keyword>", function($urlParams) {
    $keyword = str_replace("%20", " ", $urlParams[1]);
    try {
        $searchResult = array();
        foreach(Song::findByKeyword($keyword) as $song) {
            $searchResult[] = $song;
        }
        foreach(Artist::findByKeyword($keyword) as $artist) {
            $searchResult[] = $artist;
        }
        if(empty($searchResult)) {
            $response = new Res(false, 404, "No Search Results Found For ".$keyword."");
        }
        $response = new Res(true, 200, "", $searchResult);
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                

    }

});



?>