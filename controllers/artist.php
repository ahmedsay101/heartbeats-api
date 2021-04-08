<?php
require_once("config.php");
require_once("../models/Router.php");
require_once("../models/Res.php");
require_once("../models/Artist.php");

$router = new Router();

$router->get("v1/artists/<id>", function($urlParams) {
    $artistId = $urlParams[1];
    try {
        $artist = new Artist($artistId);
        $findArtist = $artist->findById();
        $response = new Res(true, 200, '', $findArtist);
    }
    catch(ArtistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->patch("v1/artists/<id>", function($urlParams, $updateData) {
    if(Router::authenticate()->user->getRole() != "admin") $response = new Res(false, 401, "Unauthorized");

    $artistId = $urlParams[1];
    try {
        $artist = new Artist($artistId);
        $updateArtist = $artist->update($updateData);
        $response = new Res(true, 201, 'Artist Updated Successfully', $updateArtist);
    }
    catch(ArtistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->delete("v1/artists/<id>", function($urlParams) {
    if(Router::authenticate()->user->getRole() != "admin") $response = new Res(false, 401, "Unauthorized");

    $artistId = $urlParams[1];
    try {
        $artist = new Artist($artistId);
        $deleteArtist = $artist->delete();
        $response = new Res(true, 200, 'Artist Has Been Deleted Successfully');
    }
    catch(ArtistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->get("v1/artists/<id>/songs", function($urlParams) {
    $artistId = $urlParams[1];
    try {
        $artist = new Artist($artistId);
        $getArtistSongs = $artist->getSongs();
        $response = new Res(true, 200, '', $getArtistSongs);
    }
    catch(ArtistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->get("v1/artists/<id>/albums", function($urlParams) {
    $artistId = $urlParams[1];
    try {
        $artist = new Artist($artistId);
        $getArtistAlbums = $artist->getAlbums();
        $response = new Res(true, 200, '', $getArtistAlbums);
    }
    catch(ArtistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->post("v1/artists", function($urlParams, $postData) {
    if(Router::authenticate()->user->getRole() != "admin") $response = new Res(false, 401, "Unauthorized");

    try {
        $createArtist = Artist::create($postData);
        $response = new Res(true, 201, 'A new artist has been created successfully', $createArtist);
    }
    catch(ArtistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->post("v1/artists/plays", function($urlParams, $postData) {
    try {
        $artist = new Artist($postData->artistId);
        if($artist->incPlays()) {
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

$router->get("v1/artists", function($urlParams) {
    try {
        $getArtists = Artist::getAll();
        $response = new Res(true, 200, '', $getArtists);
    }
    catch(ArtistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->upload("v1/songs/images", function($urlParams) {
    try {
        if(array_key_exists('artist_img', $_FILES)){
            if(array_key_exists('error', $_FILES['artist_img'])) {
                if(Router::authenticate()->user->getRole() != "admin") $response = new Res(false, 401, "Unauthorized");
                $image = new Image($_FILES['artist_img'], '/heartbeats/assets/images/artists/');
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
