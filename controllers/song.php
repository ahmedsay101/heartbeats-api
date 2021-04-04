<?php
require_once("config.php");
require_once("../models/Router.php");
require_once("../models/Res.php");
require_once("../models/Song.php");

$router = new Router();

$router->get("v1/songs/<id>", function($urlParams) {
    $songId = $urlParams[1];
    try {
        $song = new Song($songId);
        $findSong = $song->findById();
        $response = new Res(true, 200, '', $findSong);
    }
    catch(SongException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->patch("v1/songs/<id>", function($urlParams, $updateData) {
    if(Router::authenticate()->user->getRole() != "admin") $response = new Res(false, 401, "Unauthorized");

    $songId = $urlParams[1];
    try {
        $song = new Song($songId);
        $updateSong = $song->update($updateData);
        $response = new Res(true, 201, 'Song Updated Successfully', $updateSong);
    }
    catch(SongException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->delete("v1/songs/<id>", function($urlParams) {
    if(Router::authenticate()->user->getRole() != "admin") $response = new Res(false, 401, "Unauthorized");

    $songId = $urlParams[1];
    try {
        $song = new Song($songId);
        $deleteSong = $song->delete();
        $response = new Res(true, 200, 'Song Has Been Deleted Successfully');
    }
    catch(SongException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->get("v1/songs/random", function($urlParams) {
    try {
        $response = Song::generateRandomPlaylist();
        $response = new Res(true, 200, '', $response);
    }
    catch(SongException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->post("v1/songs", function($urlParams, $postData) {
    if(Router::authenticate()->user->getRole() != "admin") $response = new Res(false, 401, "Unauthorized");

    try {
        $createSong = Song::create($postData);
        $response = new Res(true, 201, 'A new song has been created successfully', $createSong);
    }
    catch(SongException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->upload("v1/songs/images", function($urlParams) {
    try {
        if(array_key_exists('song_img', $_FILES)){
            if(array_key_exists('error', $_FILES['song_img'])) {
                if(Router::authenticate()->user->getRole() != "admin") $response = new Res(false, 401, "Unauthorized");
                $image = new Image($_FILES['song_img'], '/heartbeats/assets/images/songs/');
                $imgUrl = $image->upload();
                $response = new Res(true, 201, 'Image Uploaded Successfuly', $imgUrl);
            }
         }   
    }
    catch(ImageException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
});



?>
