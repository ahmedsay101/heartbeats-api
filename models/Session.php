<?php 
require_once("../controllers/config.php");

class AuthException extends Exception {}
class Session {
    private $con, $id, $accessToken, $userId;

    public function __construct() {
        $this->con = DB::connect(); 

        if(!isset($_SERVER["HTTP_AUTHORIZATION"]) || strlen($_SERVER["HTTP_AUTHORIZATION"]) < 1) {
            throw new AuthException("Please Login or Register", 401);
            exit;        
        }

        $accessToken = $_SERVER["HTTP_AUTHORIZATION"];

        $query = $this->con->prepare("SELECT * FROM `sessions` WHERE access_token = :ac");
        $query->bindParam(":ac", $accessToken);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new AuthException("Invalid Credentials", 401);
            exit;
        }
        $sqlData = $query->fetch(PDO::FETCH_ASSOC);

        $this->id = $sqlData["id"];
        $this->accessToken = $accessToken;
        $this->userId = $sqlData["user_id"];

        if(strtotime($sqlData["expiry_date"]) < time()) {
            $this->delete();
            throw new AuthException("Session Expired, Please Login Again", 401);
            exit;
        }
    }
    public function getSessId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->userId;
    }

    public static function create($userId) {
        $con = DB::connect();

        if(self::hasSession($userId)) {
            self::clearSessions($userId);
        }
        $sessionData = array();
        $accessToken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)));
        $expiresIn = date('Y/m/d h:i:s', time() + 86400);

        $query = $con->prepare("INSERT INTO sessions (`user_id`, `access_token`, `expiry_date`) VALUES (:id, :ac, :ex)");

        if($query->execute(['id' => $userId, 'ac' => $accessToken, 'ex' => $expiresIn])) {

            $sessionData["sessId"] = $con->lastInsertId();
            $sessionData["userId"] = $userId;
            $sessionData["accessToken"] = $accessToken;
            $sessionData["expiresIn"] = $expiresIn;

            return $sessionData;
        }
    }

    private static function hasSession($userId) {
        $con = DB::connect();
        $query = $con->prepare("SELECT * FROM `sessions` WHERE user_id = :id");
        $query->bindParam(":id", $userId);
        $query->execute();

        if($query->rowCount() === 0) {
            return false;
        }
        else {
            return true;
        }
    }
    private static function clearSessions($userId) {
        $con = DB::connect();
        $query = $con->prepare("DELETE FROM `sessions` WHERE user_id = :id");
        $query->bindParam(":id", $userId);
        return $query->execute();
    }

    public function delete() {
        $query = $this->con->prepare("DELETE FROM `sessions` WHERE id =:id AND access_token = :ac AND user_id =:uid");
        $query->bindParam(":id", $this->id);
        $query->bindParam(":ac", $this->accessToken);
        $query->bindParam(":uid", $this->userId);
        return $query->execute();
    }

}
?>