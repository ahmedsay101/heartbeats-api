<?php
require_once("config.php");
require_once("../models/Router.php");
require_once("../models/Res.php");
require_once("../models/Album.php");

$router = new Router();

$router->get("v1/albums/<id>", function($urlParams) {
    $albumId = $urlParams[1];
    try {
        $album = new Album($albumId);
        $findAlbum = $album->findById();
        $response = new Res(true, 200, '', $findAlbum);
    }
    catch(AlbumException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->patch("v1/albums/<id>", function($urlParams, $updateData) {
    if(Router::authenticate()->user->getRole() != "admin") $response = new Res(false, 401, "Unauthorized");
    
    $albumId = $urlParams[1];
    try {
        $album = new Album($albumId);
        $updateAlbum = $album->update($updateData);
        $response = new Res(true, 201, 'Album Updated Successfully', $updateAlbum);
    }
    catch(AlbumException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->delete("v1/albums/<id>", function($urlParams) {
    if(Router::authenticate()->user->getRole() != "admin") $response = new Res(false, 401, "Unauthorized");

    $albumId = $urlParams[1];
    try {
        $album = new Album($albumId);
        $deleteAlbum = $album->delete();
        $response = new Res(true, 200, 'Album Has Been Deleted Successfully');
    }
    catch(AlbumException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->get("v1/albums/<id>/songs", function($urlParams) {
    $albumId = $urlParams[1];
    try {
        $album = new Album($albumId);
        $getAlbumSongs = $album->getSongs();
        $response = new Res(true, 200, '', $getAlbumSongs);
    }
    catch(AlbumException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->post("v1/albums", function($urlParams, $postData) {
    if(Router::authenticate()->user->getRole() != "admin") $response = new Res(false, 401, "Unauthorized");
    try {
        $createAlbum = Album::create($postData);
        $response = new Res(true, 201, 'A new album has been created successfully', $createAlbum);
    }
    catch(AlbumException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->get("v1/albums", function($urlParams) {
    try {
        $getAlbums = Album::getRandom();
        $response = new Res(true, 200, '', $getAlbums);
    }
    catch(AlbumException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->post("v1/albums/plays", function($urlParams, $postData) {
    try {
        $album = new Album($postData->albumId);
        if($album->incPlays()) {
            $response = new Res(true, 201, '');
        }
    }
    catch(ArtistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->upload("v1/albums/images", function($urlParams) {
    try {
        if(array_key_exists('album_img', $_FILES)){
            if(array_key_exists('error', $_FILES['album_img'])) {
                if(Router::authenticate()->user->getRole() != "admin") $response = new Res(false, 401, "Unauthorized");
                $image = new Image($_FILES['album_img'], '/heartbeats/assets/images/albums/');
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