<?php 
require_once("../controllers/config.php");
require_once("Session.php");
require_once("User.php");

class UploadsException extends Exception {}

class Uploads {
    public function __construct($userId) {
        $this->con = DB::connect();
        $this->userId = filter_var($userId, FILTER_SANITIZE_NUMBER_INT);
    }

    public function getAll() {
        $uploadsArray = array();
        foreach($this->getIds() as $id) {
            $uploadsArray[] = $this->findById($id);
        }

        return $uploadsArray;
    }

    public function getIds() {
        $query = $this->con->prepare("SELECT id FROM uploads WHERE user_id = :id ORDER BY uploaded_at DESC");
        $query->bindParam(":id", $this->userId);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new UploadsException ("You don't have uploaded songs", 204);
            exit;
        }
        return $idsArray = $query->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function findById($id) {
        $query = $this->con->prepare("SELECT * FROM uploads WHERE id = :id AND user_id=:uid");
        $query->bindParam(":id", $id);
        $query->bindParam(":uid", $this->userId);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new UploadsException("Song Not Found " . $id . "", 404);
            exit;
        }

        $sqlData = $query->fetch(PDO::FETCH_ASSOC);

        $songData = array();
        $songData["id"] = $sqlData["id"];
        $songData["name"] = $sqlData["name"];
        $songData["url"] = $sqlData["url"];
        $songData["playlist"] = $this->getIds();

        $uploadDate = strtotime($sqlData["uploaded_at"]);
        $day = date("d", $uploadDate);
        $month = date("F", $uploadDate);
        $year = date("Y", $uploadDate);
        $formattedDate = $month." ".$day.", ".$year;

        $songData["date"] = $formattedDate;

        return $songData;
    }

    private static function fullPath($url) {
        $httpOrHttps = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https":"http");
        $host = $_SERVER["HTTP_HOST"];
        return $path = $httpOrHttps."://".$host.$url;
    }
    
    public function create($data) {
        $query = $this->con->prepare("INSERT INTO uploads (name, url, path, user_id, uploaded_at)
        VALUES (:name, :url, :path, :id, :at)");

        $date = date('Y/m/d h:i:s a', time());
        $query->bindParam(":name", $data["name"]);
        $query->bindParam(":url", $data["url"]);
        $query->bindParam(":path", $data["path"]);
        $query->bindParam(":id", $this->userId);
        $query->bindParam(":at", $date);

        $query->execute();

        return $this->findById($this->con->lastInsertId());
    }

    public function delete($id) {
        $query = $this->con->prepare("SELECT path FROM uploads WHERE id = :id AND user_id=:uid");
        $query->bindParam(":id", $id);
        $query->bindParam(":uid", $this->userId);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new UploadException("Song Not Found", 404);
            exit;
        }

        $path = $query->fetchColumn();
        $this->deleteSong($path);

        $deleteQuery = $this->con->prepare("DELETE FROM uploads WHERE id = :id AND user_id=:uid");
        $deleteQuery->bindParam(":id", $id);
        $deleteQuery->bindParam(":uid", $this->userId);
        $deleteQuery->execute();

        $deleteFromPlays = $this->con->prepare("DELETE FROM plays WHERE song_id=:id AND user_id=:uid AND from_uploads='1'");
        $deleteFromPlays->bindParam(":id", $id);
        $deleteFromPlays->bindParam(":uid", $this->userId);
        return $deleteFromPlays->execute();
    }

    public function deleteSong($path) {
        unlink($path);
    }

    public function upload($file) {
        $dir = '/heartbeats/assets/uploads/';
        $fileName = basename($file["name"]); 
        $filePath = $_SERVER['DOCUMENT_ROOT'].$dir.basename($file["name"]); 
        $clearName = pathinfo($filePath, PATHINFO_FILENAME);
        $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if($file["size"] > 52428800) {
            throw new UploadsException("Too large file provided", 400);
        }

        if($fileType != "mp3") {
            throw new UploadsException("Only mp3 files are allowed", 400);
        }

        if(!move_uploaded_file($file["tmp_name"], $filePath)) {
            throw new UploadsException("Something went wrong while uploading the song, Please try again", 500);
        }

        $httpOrHttps = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https":"http");
        $host = $_SERVER["HTTP_HOST"];
        $url = $dir.$fileName;
        $songUrl = $httpOrHttps."://".$host.$url;

        return $this->create(array(
            "name" => $clearName,
            "url" => $songUrl,
            "path" => $filePath
        ));
    }
}
?>