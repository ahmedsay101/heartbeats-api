<?php
require_once("User.php");
require_once("Session.php");
require_once("Res.php");

class Router {
    private $endpoints = array(
        'v1/users/<id>' => array(
            'regex' => '#/v1/users/(\\d+)/?$#',
            'methods' => array('GET', 'PATCH', 'DELETE')
        ),
        'v1/users/<id>/plays' => array(
            'regex' => '#/v1/users/(\\d+)/plays/?$#',
            'methods' => array('GET', 'POST')
        ),
        'v1/users/<id>/likes' => array(
            'regex' => '#/v1/users/(\\d+)/likes/?$#',
            'methods' => array('GET', 'POST')
        ),
        'v1/users/<id>/likes/<songId>' => array(
            'regex' => '#/v1/users/(\\d+)/likes/(\\d+)/?$#',
            'methods' => array('GET', 'DELETE')
        ),
        'v1/users/<id>/playlists' => array(
            'regex' => '#/v1/users/(\\d+)/playlists/?$#',
            'methods' => array('GET')
        ),
        'v1/users/<id>/artists' => array(
            'regex' => '#/v1/users/(\\d+)/artists/?$#',
            'methods' => array('GET', 'POST')
        ),
        'v1/users/<id>/artists/<artistId>' => array(
            'regex' => '#/v1/users/(\\d+)/artists/(\\d+)/?$#',
            'methods' => array('DELETE')
        ),
        'v1/users' => array(
            'regex' => '#/v1/users/?$#',
            'methods' => array('POST')
        ),
        'v1/users/<id>/images' => array(
            'regex' => '#/v1/users/(\\d+)/images/?$#',
            'methods' => array('POST')
        ),
        'v1/users/<id>/uploads' => array(
            'regex' => '#/v1/users/(\\d+)/uploads/?$#',
            'methods' => array('POST', 'GET')
        ),
        'v1/users/<id>/uploads/<songId>' => array(
            'regex' => '#/v1/users/(\\d+)/uploads/(\\d+)/?$#',
            'methods' => array('GET', 'DELETE')
        ),
        'v1/sessions' => array(
            'regex' => '#/v1/sessions/?$#',
            'methods' => array('POST')
        ),
        'v1/sessions/<id>' => array(
            'regex' => '#/v1/sessions/(\\d+)/?$#',
            'methods' => array('GET', 'DELETE')
        ),
        'v1/search/<keyword>' => array(
            'regex' => '#/v1/search/(.*?)/?$#',
            'methods' => array('GET')
        ),
        'v1/songs/<id>' => array(
            'regex' => '#/v1/songs/(\\d+)/?$#',
            'methods' => array('GET', 'PATCH', 'DELETE')
        ),
        'v1/songs/random' => array(
            'regex' => '#/v1/songs/random/?$#',
            'methods' => array('GET')
        ),
        'v1/songs' => array(
            'regex' => '#/v1/songs/?$#',
            'methods' => array('POST')
        ),
        'v1/songs/upload' => array(
            'regex' => '#/v1/songs/upload/?$#',
            'methods' => array('POST')
        ),   
        'v1/playlists/<id>' => array(
            'regex' => '#/v1/playlists/(\\d+)/?$#',
            'methods' => array('GET', 'PATCH', 'DELETE')
        ),
        'v1/playlists/<id>/songs' => array(
            'regex' => '#/v1/playlists/(\\d+)/songs/?$#',
            'methods' => array('GET')
        ),
        'v1/playlists' => array(
            'regex' => '#/v1/playlists/?$#',
            'methods' => array('POST', 'GET')
        ),
        'v1/artists/<id>' => array(
            'regex' => '#/v1/artists/(\\d+)/?$#',
            'methods' => array('GET', 'PATCH', 'DELETE')
        ),
        'v1/artists/<id>/songs' => array(
            'regex' => '#/v1/artists/(\\d+)/songs/?$#',
            'methods' => array('GET')
        ),
        'v1/artists/<id>/albums' => array(
            'regex' => '#/v1/artists/(\\d+)/albums/?$#',
            'methods' => array('GET')
        ),
        'v1/artists/plays' => array(
            'regex' => '#/v1/artists/plays/?$#',
            'methods' => array('POST')
        ),
        'v1/artists' => array(
            'regex' => '#/v1/artists/?$#',
            'methods' => array('POST', 'GET')
        ),
        'v1/albums/<id>' => array(
            'regex' => '#/v1/albums/(\\d+)/?$#',
            'methods' => array('GET', 'PATCH', 'DELETE')
        ),
        'v1/albums/<id>/songs' => array(
            'regex' => '#/v1/albums/(\\d+)/songs/?$#',
            'methods' => array('GET')
        ),
        'v1/albums/plays' => array(
            'regex' => '#/v1/albums/plays/?$#',
            'methods' => array('POST')
        ),
        'v1/albums' => array(
            'regex' => '#/v1/albums/?$#',
            'methods' => array('POST', 'GET')
        )
        
    );
    private $currentRequest = null;

    public function __construct() {
        if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: DELETE, POST, GET, PATCH, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type');
            $response = new Res(true, 200);
        }
        foreach($this->endpoints as $key => $value) {
            if(preg_match($value['regex'], $_SERVER['REQUEST_URI'], $matches)) {
                if(!in_array($_SERVER['REQUEST_METHOD'], $value['methods'])) {
                    $response = new Res(false, 405, 'Request method not allowed');
                }
                $this->currentRequest = new stdClass();
                $this->currentRequest->endpoint = $key;
                $this->currentRequest->method = $_SERVER['REQUEST_METHOD'];
                $this->currentRequest->urlParams = $matches;
            }
        }
        if($this->currentRequest === null) $response = new Res(false, 404, "We couldn't find what you're looking for");
    }

    public static function authenticate($userId = null) {
        try {
            $currSess = new Session();
        }
        catch(AuthException $err) {
            $response = new Res(false, $err->getCode(), $err->getMessage());  
        }
        if($userId !== null) {
            if($userId != $currSess->getUserId()) {
                $response = new Res(false, 401, 'Unauthorized');          
            }
        }
        $output = new stdClass();
        $output->user = new User($currSess->getUserId());
        $output->session = $currSess;
        return $output;
    }

    public function get($url, $todo) {
        if($this->currentRequest->method !== "GET" || $url != $this->currentRequest->endpoint) {
            return;
        }
        $todo($this->currentRequest->urlParams);
    }

    public function patch($url, $todo) {
        if($this->currentRequest->method !== "PATCH" || $url != $this->currentRequest->endpoint) {
            return;
        }

        if($_SERVER["CONTENT_TYPE"] !== 'application/json') {
            $response = new Res(false, 400, 'CONTENT_TYPE needs to be application/json');          
        }

        $patchData = file_get_contents('php://input');

        if(!$jsonData = json_decode($patchData)) {
            $response = new Res(false, 400, 'Request body is not a valid json');          
        }

        $todo($this->currentRequest->urlParams, $jsonData);
    }
    
    public function post($url, $todo) {
        if($this->currentRequest->method !== "POST" || $url != $this->currentRequest->endpoint) {
            return;
        }

        if($_SERVER["CONTENT_TYPE"] !== 'application/json') {
            $response = new Res(false, 400, 'Invalid Content Type');         
        }
    
        $postData = file_get_contents('php://input');

        if(!$jsonData = json_decode($postData)) {
            $response = new Res(false, 400, 'Request body is not a valid json');         
        }

        $todo($this->currentRequest->urlParams, $jsonData);
    }

    public function delete($url, $todo) {
        if($this->currentRequest->method !== "DELETE" || $url != $this->currentRequest->endpoint) {
            return;
        }
        $todo($this->currentRequest->urlParams);
    }

    public function upload($url, $todo) {
        if($this->currentRequest->method !== "POST" || $url != $this->currentRequest->endpoint) {
            return;
        }
        if(!isset($_SERVER['CONTENT_TYPE']) || strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data; boundary=') === false) {
            $response = new Res(false, 400, 'Invalid Content Type');  
        }
        $todo($this->currentRequest->urlParams);
    }
}

?>