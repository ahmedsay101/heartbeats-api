<?php 
require_once("../controllers/config.php");
require_once("Song.php");
require_once("Artist.php");
require_once("Playlist.php");
require_once("Session.php");

class UserException extends Exception {}

class User {
    private $con, $accessToken, $id;

    public function __construct($id) {
        $this->con = DB::connect();
        $this->id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function register($data) {
        $con = DB::connect();
        $resData = array();

        if(!isset($data->firstName) || !isset($data->lastName) || !isset($data->email) || !isset($data->password)) {
            throw new UserException("Please fill all required inputs", 400);
            exit;
        }

        if(!isset($data->imgPath)) {
            $data->imgPath = "/heartbeats/assets/images/users/user-default.png"; 
        }

        $data->firstName = self::validateFirstName($data->firstName);
        $data->lastName = self::validateLastName($data->lastName);
        $data->email = self::validateEmail($con, $data->email);
        $data->hashedPassword = self::validatePassword($data->password);

        $query = $con->prepare("INSERT INTO users (`first_name`, `last_name`, `email`, `password`, `user_img`, `join_date`, `role`)
        VALUES (:fn, :ln, :em, :pw, :img, :date, :role)");

        if($query->execute([
            'fn' => $data->firstName,
            'ln' => $data->lastName,
            'em' => $data->email,
            'pw' => $data->hashedPassword,
            'img' => $data->imgPath,
            'date' => date('Y/m/d h:i:s', time()),
            'role' => "client"
        ])) {
            $resData["user"] = array(
                "id" => $con->lastInsertId(),
                "firstName" => $data->firstName,
                "lastName" => $data->lastName,
                "email" => $data->email,
                "imgPath" => $data->imgPath
            );
            $resData["session"] = Session::create($con->lastInsertId());
            return $resData;
        }
        else {
            throw new UserException("Something went wrong while creating your account, Please try again later", 500);
            exit; 
        }
    }

    public static function login($data) {
        $con = DB::connect();

        if(!isset($data->email) || !isset($data->password)) {
            throw new UserException("Please fill all required inputs", 400);
            exit;
        }

        $data->email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
        $data->password = filter_var($data->password, FILTER_SANITIZE_STRING);

        $query = $con->prepare("SELECT * FROM users WHERE email=:em");
        $query->bindParam("em", $data->email);
        $query->execute();

        $sqlData = $query->fetch(PDO::FETCH_ASSOC);

        if($query->rowCount() !== 1) {
            throw new UserException("Wrong Email Or Password", 401);
            exit;
        }

        if(!password_verify($data->password, $sqlData["password"])) {
            throw new UserException("Wrong Email Or Password", 401);
            exit;
        }

        $userData = array(
            "id" => $sqlData["id"],
            "firstName" => $sqlData["first_name"],
            "lastName" => $sqlData["last_name"],
            "email" => $sqlData["email"],
            "imgUrl" => $sqlData["user_img"],
        );

        return array(
            "session" => Session::create($sqlData["id"]),
            "user" => $userData
        );       
    }

    private function verifyPassword($pw) {
        $query = $this->con->prepare("SELECT password FROM users WHERE id=:id");
        $query->bindParam("id", $this->id);
        $query->execute();

        if(password_verify($pw, $query->fetchColumn())) {
            return true;
        }
        else {
            return false;
        }
    }

    public function getData() {
        $query = $this->con->prepare("SELECT * FROM users WHERE id=:id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new UserException("User Not Found", 404);
        }

        $this->data = $query->fetch(PDO::FETCH_ASSOC);
        $this->mainData["id"] = $this->data["id"];
        $this->id = $this->data["id"];

        $this->mainData["firstName"] = $this->data["first_name"];
        $this->firstName = $this->data["first_name"];

        $this->mainData["lastName"] = $this->data["last_name"];
        $this->lastName = $this->data["last_name"];

        $this->mainData["email"] = $this->data["email"];
        $this->email = $this->data["email"];

        $this->mainData["imgUrl"] = self::fullPath($this->data["user_img"]);
        $this->imgPath = $this->data["user_img"];

        $dateRegistered = strtotime($this->data["join_date"]);
        $day = date("d", $dateRegistered);
        $month = date("F", $dateRegistered);
        $year = date("Y", $dateRegistered);
        $formattedDate = $month." ".$day.", ".$year;

        $this->mainData["joinDate"] = $formattedDate;

        return $this->mainData;
    }

    public function getLastPlayed() {
        $query = $this->con->prepare("SELECT last_plays, last_playlist, playing_uploads FROM users WHERE id=:id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new UserException("User Not Found", 404);
        }

        $sqlData = $query->fetch(PDO::FETCH_ASSOC);
        $lastPlayed = array();
        $lastPlayed["songs"] = array();
        $lastPlayed["playlist"] = explode(",", $sqlData["last_playlist"]);
        $lastPlayed["uploads"] = ($sqlData["playing_uploads"] == 0 ? false : true);

        foreach(explode(",", $sqlData["last_plays"]) as $songId) {
            $song = new Song($songId);
            array_push($lastPlayed["songs"], $song->findById());
        }

        return $lastPlayed;
    }
    public function getFollowedArtists() {
        $query = $this->con->prepare("SELECT artist_id FROM artist_followers WHERE user_id = :id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new UserException("User doesn't follow any artists", 404);
        }

        $sqlData = $query->fetchAll(PDO::FETCH_COLUMN);
        $artists = array();

        foreach($sqlData as $artist) {
            $artistInstance = new Artist($artist);
            $artists[] = $artistInstance->findById();
        }

        return $artists;
    }
    private static function fullPath($url) {
        $httpOrHttps = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https":"http");
        $host = $_SERVER["HTTP_HOST"];
        return $path = $httpOrHttps."://".$host.$url;
    }

    public function getRole() {
        $query = $this->con->prepare("SELECT role FROM users WHERE id=:id");
        $query->bindParam(":id", $this->id);
        $query->execute();
        return $query->fetchColumn();
    }

    public function update($updateData) {
        $userData = $this->getData();
        $willChange = array();

        if(isset($updateData->firstName) && $updateData->firstName !== $userData['firstName']) {
            $willChange["first_name"] = self::validateFirstName($updateData->firstName);
        }
        if(isset($updateData->lastName) && $updateData->lastName !== $userData['lastName']) {
            $willChange["last_name"] = self::validateFirstName($updateData->lastName);
        }
        if(isset($updateData->email) && $updateData->email !== $userData['email']) {
            $willChange["email"] = self::validateEmail($this->con, $updateData->email);
        }
        if(isset($updateData->newPassword)) {
            if(!isset($updateData->oldPassword)) {
                throw new UserException("You have to provide your old password", 400);
                exit;
            }
            if(!$this->verifyPassword($updateData->oldPassword)) {
                throw new UserException("Wrong Password Provided", 401);
                exit;
            }

            $willChange["password"] = self::validatePassword($updateData->newPassword);
        }
        if(isset($updateData->imgPath)) {
            $willChange["user_img"] = $updateData->imgPath;
        }
        if(isset($updateData->lastPlays)) {
            $willChange["last_plays"] = $updateData->lastPlays;
        }
        if(isset($updateData->lastPlaylist)) {
            $willChange["lastPlaylist"] = implode(",", $updateData->lastPlaylist);
        }

        if(empty($willChange)) {
            throw new UserException("Nothing Changed", 400);
            exit;
        }

        $queryParams = "";

        foreach ($willChange as $key => $value) {
            $queryParams .= $key." = :".$key.", ";
        }

        $queryParams = rtrim($queryParams, ", ");

        $queryString = "UPDATE users SET ".$queryParams." WHERE id = :id";

        $updateQuery = $this->con->prepare($queryString);
        $updateQuery->bindParam(":id", $this->id);
        foreach ($willChange as $key => &$value) {
            $updateQuery->bindParam(":".$key, $value, PDO::PARAM_STR);
        }

        if($updateQuery->execute()) {
            return $this->getData();
        }
    }

    public function delete() {
        $query = $this->con->prepare("DELETE FROM users WHERE user_id = :id");
        $query->bindParam(":id", $this->id);
        if($query->execute()) {
            $query = $this->con->prepare("DELETE FROM sessions WHERE user_id = :id");
            $query->bindParam(":id", $this->id);
            return $query->execute();
            /*$query = $this->con->prepare("DELETE FROM likes WHERE user_id = :id");
            $query->bindParam(":id", $this->id);
            if($query->execute()) {
                $query = $this->con->prepare("DELETE FROM playlists WHERE user_id = :id");
                $query->bindParam(":id", $this->id);
            }
            if($query->execute()) {
                $query = $this->con->prepare("DELETE FROM users WHERE user_id = :id");
                $query->bindParam(":id", $this->id);
                return $query->execute();
            }*/
        }
        
    }

    public function getPlaylists() {
        $query = $this->con->prepare("SELECT id FROM playlists WHERE user_id = :id ORDER BY creation_date DESC");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new UserException("This User Has No Playlists", 404);
            exit;
        }

        $playlistIds = $query->fetchAll(PDO::FETCH_COLUMN);

        $this->playlists = array();

        foreach($playlistIds as $id) {
            $playlist = new Playlist($id);
            $this->playlists[] = $playlist->findById();
        }

        return $this->playlists;
    }

    public function getLikesIds() {
        $query = $this->con->prepare("SELECT song_id FROM likes WHERE user_id = :id ORDER BY date DESC");
        $query->bindParam(":id", $this->id);
        $query->execute();
        return $songIds = $query->fetchAll(PDO::FETCH_COLUMN);
    }
    public function getLikes() {
        $this->likes = array();

        foreach($this->getLikesIds() as $id) {
            $song = new Song($id);
            $this->likes[] = $song->findById();
        }

        return $this->likes;
    }

    public function like($id) {
        $query = $this->con->prepare("INSERT INTO likes (user_id, song_id) VALUES (:uid, :sid)");
        $query->bindParam(":uid", $this->id);
        $query->bindParam(":sid", $id);
        return $query->execute();
    }

    public function removeLike($id) {
        $query = $this->con->prepare("DELETE FROM likes WHERE user_id = :uid AND song_id = :sid");
        $query->bindParam(":uid", $this->id);
        $query->bindParam(":sid", $id);
        return $query->execute();
    }

    private static function validatefirstName($fn) {
        $firstName = filter_var($fn, FILTER_SANITIZE_STRING);
        if(strlen($fn) > 25 || strlen($fn) < 2) {
            throw new UserException("First name must be between 2 and 25 character", 400);
            exit;
        }
        return ucfirst(strtolower($firstName));
    }

    private static function validateLastName($ln) {
        $lastName = filter_var($ln, FILTER_SANITIZE_STRING);
        if(strlen($ln) > 25 || strlen($ln) < 2) {
            throw new UserException("Last name must be between 2 and 25 character", 400);
            exit;
        }
        return ucfirst(strtolower($lastName));
    }

    private static function validateEmail($con, $em) {
        $em = filter_var($em, FILTER_SANITIZE_EMAIL);

        if(!filter_var($em, FILTER_VALIDATE_EMAIL)) {
            throw new UserException("Please Enter A Valid Email Adress", 400);
            exit;
        }

        $query = $con->prepare("SELECT email FROM users WHERE email=:em");
        $query->bindParam(":em", $em);
        $query->execute();
    
        if($query->rowCount() != 0) {
            throw new UserException("This Email is already exists", 400);
            exit;
        } 
        return $em;
    }

    private static function validatePassword($pw) {    
        $pw = filter_var($pw, FILTER_SANITIZE_STRING);

        if(strlen($pw) > 30 || strlen($pw) < 5) {
            throw new UserException("Your password must be between 5 and 30 characters", 400);
            exit;
        }

        return password_hash($pw, PASSWORD_DEFAULT);
    }   
}    
?>