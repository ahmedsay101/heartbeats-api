<?php
require_once("config.php");
require_once("../models/Router.php");
require_once("../models/Res.php");
require_once("../models/User.php");
require_once("../models/Session.php");
require_once("../models/Image.php");
require_once("../models/Uploads.php");

$router = new Router();

$router->get("v1/users/<id>", function($urlParams) {
    $userId = $urlParams[1];
    try {
        $user = Router::authenticate($userId)->user;
        $getUserData = $user->getData();
        $response = new Res(true, 200, '', $getUserData);
    }  
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');         
    } 
});

$router->patch("v1/users/<id>", function($urlParams, $updateData) {
    $userId = $urlParams[1];
    try {
        $user = Router::authenticate($userId)->user;
        $updateUser = $user->update($updateData);
        $response = new Res(true, 200, 'User updated successfully', $updateUser);
    }  
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');         
    } 
});

$router->delete("v1/users/<id>", function($urlParams) {
    $userId = $urlParams[1];

    try {
        $user = Router::authenticate($userId)->user;
        $user->delete();
        $response = new Res(true, 200, 'Your account is gone');
    }  
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');         
    } 
});

$router->get("v1/users/<id>/uploads/<songId>", function($urlParams) {
    $userId = $urlParams[1];
    $songId = $urlParams[2];

    try {
        $user = Router::authenticate($userId)->user;
        $uploads = new Uploads($userId);
        $song = $uploads->findById($songId);
        $response = new Res(true, 200, '', $song);
    }  
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');         
    } 
});

$router->delete("v1/users/<id>/uploads/<songId>", function($urlParams) {
    $userId = $urlParams[1];
    $songId = $urlParams[2];

    try {
        $user = Router::authenticate($userId)->user;
        $uploads = new Uploads($userId);
        if($uploads->delete($songId)) {
            $response = new Res(true, 200, 'Your account is gone');
        }
        else {
            $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');         
        }
    }  
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');         
    } 
});

$router->get("v1/users/<id>/uploads", function($urlParams) {
    $userId = $urlParams[1];

    try {
        $user = Router::authenticate($userId)->user;
        $uploads = new Uploads($userId);
        $songs = $uploads->getAll();
        $response = new Res(true, 200, '', $songs);
    }
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(UploadsException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->delete("v1/users/<id>/uploads/temp/<tempId>", function($urlParams) {
    $userId = $urlParams[1];
    $tempId = $urlParams[2];

    try {
        $user = Router::authenticate($userId)->user;
        $uploads = new Uploads($userId);
        $songData = $uploads->deleteTemp($tempId);
        $response = new Res(true, 201, 'Temporary Upload Has Been Deleted Successfully');
    }
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->upload("v1/users/<id>/uploads", function($urlParams) {
    $userId = $urlParams[1];

    try {
        $user = Router::authenticate($userId)->user;
        $uploads = new Uploads($userId);
        $uploadUrl = $uploads->upload($_FILES["song"]);
        $response = new Res(true, 201, 'A new song has been uploaded successfully', $uploadUrl);
    }
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                
    }
});

$router->get("v1/users/<id>/plays", function($urlParams) {
    $userId = $urlParams[1];
    try {
        $user = Router::authenticate($userId)->user;
        $getLastPlayed = $user->getLastPlayed();
        $response = new Res(true, 200, '', $getLastPlayed);
    }  
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');         
    } 
});

$router->get("v1/users/<id>/artists", function($urlParams) {
    $userId = $urlParams[1];
    try {
        $user = Router::authenticate($userId)->user;
        $getFollowedArtists = $user->getFollowedArtists();
        $response = new Res(true, 200, '', $getFollowedArtists);
    }  
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');         
    } 
});

$router->get("v1/users/<id>/likes", function($urlParams) {
    $userId = $urlParams[1];
    try {
        $user = new User($userId);
        $getLikes = $user->getLikes();
        $response = new Res(true, 200, '', $getLikes);
    }  
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');         
    } 
});

$router->post("v1/users/<id>/likes", function($urlParams, $postData) {
    $userId = $urlParams[1];
    try {
        $user = Router::authenticate($userId)->user;
        if(in_array($postData->songId, $user->getLikesIds())) {
            $response = new Res(true, 200, 'You Already Liked This Song');         
        }
        $user->like($postData->songId);
        $response = new Res(true, 200, 'Song Liked'); 
    }  
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');         
    } 
});

$router->get("v1/users/<id>/likes/<songId>", function($urlParams) {
    $userId = $urlParams[1];
    $songId = $urlParams[2];

    try {
        $user = new User($userId);
        if(!in_array($songId, $user->getLikesIds())) {
            $response = new Res(false, 404, "Couldn't find this song in ".$user->getData()['firstName']." likes");  
        }
        $response = new Res(true, 200, $user->getData()['firstName'].' Likes the song');  
    }
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                        
    } 
});

$router->delete("v1/users/<id>/likes/<songId>", function($urlParams) {
    $userId = $urlParams[1];
    $songId = $urlParams[2];

    try {
        $user = Router::authenticate($userId)->user;
        if($user->removeLike($songId)) {
            $response = new Res(true, 200, 'Song Removed from your likes');         
        }
        else {
            $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                        
        }
    }
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                        
    } 
});

$router->get("v1/users/<id>/playlists", function($urlParams) {
    $userId = $urlParams[1];
    try {
        $user = Router::authenticate($userId)->user;
        $getPlaylists = $user->getPlaylists();
        $response = new Res(true, 200, '', $getPlaylists);         
    }

    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                               
    }  
});

$router->post("v1/users", function($urlParams, $jsonData) {
    try {
        $createUser = User::register($jsonData);
        $response = new Res(true, 201,'User Created Successfully', $createUser);        
    }

    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');                               
    }  
});

$router->upload("v1/users/<id>/images", function($urlParams) {
    $userId = $urlParams[1];
    try {
        if(array_key_exists('user_img', $_FILES)){
            if(array_key_exists('error', $_FILES['user_img'])) {
                $user = Router::authenticate($userId)->user;   
                $image = new Image($_FILES['user_img'], '/heartbeats/assets/images/users/');
                $userUpdateObj = new stdClass();
                $userUpdateObj->imgPath = $image->upload();
                $user->update($userUpdateObj);
                $response = new Res(true, 201, 'Image Uploaded Successfuly', $userUpdateObj->imgPath);
            }
         }   
    }
    catch(ImageException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(UserException $err) {
        $response = new Res(false, $err->getCode(), $err->getMessage());  
    }
    catch(PDOException $err) {
        $response = new Res(false, 500, 'Something Went Wrong, Please Try Again Later');  
    }
});

?>