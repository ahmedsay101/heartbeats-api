<?php
require_once("config.php");
require_once("../models/Router.php");
require_once("../models/Res.php");
require_once("../models/Playlist.php");

$router = new Router();

$router->get("v1/playlists/<id>", function($urlParams) {
    $playlistId = $urlParams[1];
    try {
        $playlist = new Playlist($playlistId);
        $getPlaylist = $playlist->findById();
        if($playlist->getUserId() != Router::authenticate()->session->getUserId()) $response = new Res(false, 401, "Unauthorized");
        $response = new Res(true, 200, '', $getPlaylist);
    }
    catch(PlaylistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->patch("v1/playlists/<id>", function($urlParams, $updateData) {
    $playlistId = $urlParams[1];
    try {
        $playlist = new Playlist($playlistId);
        if($playlist->getUserId() != Router::authenticate()->session->getUserId()) $response = new Res(false, 401, "Unauthorized");
        $updatePlaylist = $playlist->update($updateData);
        $response = new Res(true, 200, 'Playlist Updated', $updateData);
    }
    catch(PlaylistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->delete("v1/playlists/<id>", function($urlParams) {
    $playlistId = $urlParams[1];
    try {
        $playlist = new Playlist($playlistId);
        if($playlist->getUserId() != Router::authenticate()->session->getUserId()) $response = new Res(false, 401, "Unauthorized");
        $playlist->delete();
        $response = new Res(true, 200, 'Playlist Has Been Deleted Successfully');
    }
    catch(PlaylistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->post("v1/playlists", function($urlParams, $postData) {
    try {
        $createPlaylist = Playlist::create(Router::authenticate()->session->getUserId(), $postData);
        $response = new Res(true, 201, 'Playlist Created Successfully',  $createPlaylist);
    }
    catch(PlaylistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->get("v1/playlists", function($urlParams) {
    try {
        $getPlaylists = Playlist::getPublicPlaylists();
        $response = new Res(true, 200, "",  $getPlaylists);
    }
    catch(PlaylistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->get("v1/playlists/<id>/songs", function($urlParams) {
    $playlistId = $urlParams[1];
    try {
        $playlist = new Playlist($playlistId);
        $getPlaylistSongs = $playlist->getSongs();
        if($playlist->getUserId() != Router::authenticate()->session->getUserId()) $response = new Res(false, 401, "Unauthorized");
        $response = new Res(true, 200, '', $getPlaylistSongs);
    }
    catch(PlaylistException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->upload("v1/songs/images", function($urlParams) {
    try {
        if(array_key_exists('playlist_img', $_FILES)){
            if(array_key_exists('error', $_FILES['playlist_img'])) {
                if(Router::authenticate()->user->getRole() != "admin") $response = new Res(false, 401, "Unauthorized");
                $image = new Image($_FILES['playlist_img'], '/heartbeats/assets/images/playlists/');
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