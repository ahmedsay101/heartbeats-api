<?php 
require_once("../controllers/config.php");
require_once("Session.php");
require_once("User.php");

class SongException extends Exception {}

class Song {
	private $con, $id, $name, $artistId, $albumId, $genreId, $duration, $url, $imgUrl, $plays;

    public function __construct($id) {
        $this->con = DB::connect();
        $this->id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
    }

    public function findById() {
        $songId = $this->id;
        $query = $this->con->prepare("SELECT 
                                        songs.id, 
                                        songs.song_name, 
                                        songs.artist_id, 
                                        artists.artist_name, 
                                        artists.artist_img_url, 
                                        songs.album_id,
                                        albums.album_name, 
                                        albums.album_img_url,
                                        songs.genre_id,
                                        genres.genre_name,
                                        songs.song_img_url,
                                        songs.song_url,
                                        songs.song_plays
                                        FROM songs
                                        INNER JOIN artists ON songs.artist_id = artists.id
                                        INNER JOIN albums ON songs.album_id = albums.id
                                        INNER JOIN genres ON songs.genre_id = genres.id
                                        WHERE songs.id = :id");
        $query->bindParam(":id", $songId);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new SongException("Song Not Found ".$songId, 404);
            exit;
        }

        $sqlData = $query->fetch(PDO::FETCH_ASSOC);

        $this->songData = array();
        $this->songData["id"] = $this->id;
        $this->songData["name"] = $sqlData["song_name"];
        $this->songData["artistId"] = $sqlData["artist_id"];
        $this->songData["artistName"] = $sqlData["artist_name"];
        $this->songData["artistImg"] = self::fullPath($sqlData["artist_img_url"]);
        $this->songData["albumId"] = $sqlData["album_id"];
        $this->songData["albumName"] = $sqlData["album_name"];
        $this->songData["albumImg"] = self::fullPath($sqlData["album_img_url"]);
        $this->songData["genreId"] = $sqlData["genre_id"];
        $this->songData["genreName"] = $sqlData["genre_name"];
        $this->songData["imgUrl"] = self::fullPath($sqlData["song_img_url"]);
        $this->songData["url"] = self::fullPath($sqlData["song_url"]);
        $this->songData["plays"] = $sqlData["song_plays"];
        $this->songData["isLiked"] = false;

        try {
            $currSess = new Session();
            $loggedInUser = new User($currSess->getUserId());
            if(in_array($this->id, $loggedInUser->getLikesIds())) {
                $this->songData["isLiked"] = true;
            }
        }
        catch(AuthException $err) {
            $this->songData["isLiked"] = false;
        }

        return $this->songData;
    }


    public static function findByKeyword($txt) {
        $con = DB::connect();
        
        $keyword = filter_var($txt, FILTER_SANITIZE_STRING);
        $query = $con->prepare("SELECT songs.id, 
                                        songs.song_name, 
                                        songs.artist_id, 
                                        artists.artist_name, 
                                        songs.album_id, 
                                        songs.song_img_url, 
                                        songs.song_url, 
                                        songs.song_plays 
                                        FROM songs
                                        INNER JOIN artists ON songs.artist_id = artists.id
                                        INNER JOIN albums ON songs.album_id = albums.id
                                        WHERE songs.song_name LIKE CONCAT('%', :kw, '%')");
        $query->bindParam(":kw", $keyword);
        $query->execute();

        $searchResults = array();
        while($sqlData = $query->fetch(PDO::FETCH_ASSOC)) {
            $songData = array();
            $songData["type"] = "Song";
            $songData["id"] = $sqlData["id"];
            $songData["name"] = $sqlData["song_name"];
            $songData["artistId"] = $sqlData["artist_id"];
            $songData["artistName"] = $sqlData["artist_name"];
            $songData["albumId"] = $sqlData["album_id"];
            $songData["imgUrl"] = self::fullPath($sqlData["song_img_url"]);
            $songData["url"] = self::fullPath($sqlData["song_url"]);
            $songData["plays"] = $sqlData["song_plays"];
            $songData["isLiked"] = false;
            try {
                $currSess = new Session();
                $loggedInUser = new User($currSess->getUserId());
                if(in_array($sqlData["id"], $loggedInUser->getLikesIds())) {
                    $songData["isLiked"] = true;
                }
            }
            catch(AuthException) {
                    $songData["isLiked"] = false;
            }
            $searchResults[] = $songData;
        }
        return $searchResults;
    }

    private static function fullPath($url) {
        $httpOrHttps = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https":"http");
        $host = $_SERVER["HTTP_HOST"];
        return $path = $httpOrHttps."://".$host.$url;
    }

    public static function generateRandomPlaylist() {
        $con = DB::connect();
        $query = $con->prepare("SELECT id FROM songs ORDER BY RAND() LIMIT 20");
        $query->execute();

        $randomPlaylist = $query->fetchAll(PDO::FETCH_COLUMN, 0);

        $lastPlayed = array();
        $lastPlayed["songId"] = $randomPlaylist[0];
        $lastPlayed["playlist"] = $randomPlaylist;

        return $lastPlayed;
    }
    
    public static function create($songData) {
        $con = DB::connect();

        if(!isset($songData->name) || !isset($songData->genreId) || !isset($songData->imgUrl) || !isset($songData->url)) {
            throw new SongException("Invalid Input", 400);
            exit;
        }
        if(!isset($songData->artistId)) {
            if(!isset($songData->albumId)) {
                $songData->artistId = 0;
                $songData->albumId = 0;
            }
            else {
                throw new SongException("Invalid Input", 400);
                exit;
            }
        }
        else if(!isset($songData->albumId)) {
            if(!isset($songData->artistId)) {
                $songData->artistId = 0;
                $songData->albumId = 0;
            }
            else {
                throw new SongException("Invalid Input", 400);
                exit;
            }
        }

        $query = $con->prepare("INSERT INTO songs (`song_name`, `artist_id`, `album_id`, `genre_id`, `song_url`, `song_img_url`, `song_plays`)
        VALUES (:name, :artist, :album, :genre, :url, :imgUrl, :plays)");

        if($query->execute([
            'name' => $songData->name,
            'artist' => $songData->artistId,
            'album' => $songData->albumId,
            'genre' => $songData->genreId,
            'url' => $songData->url,
            'imgUrl' => $songData->imgUrl,
            'plays' => 0
        ])) {
            return $songData;
        }
    }

    public function update($updateData) {
        $song = $this->findById();
        $willChange = array();

        foreach ($updateData as $key => $value) {
            $song[$key] = $value;
        }

        if(isset($updateData->name)) {
            $willChange["song_name"] = $updateData->name;
        }
        if(isset($updateData->artistId)) {
            $willChange["artist_id"] = $updateData->artistId;
        }
        if(isset($updateData->albumId)) {
            $willChange["album_id"] = $updateData->albumId;
        }
        if(isset($updateData->genreId)) {
            $willChange["genre_id"] = $updateData->genreId;
        }
        if(isset($updateData->imgUrl)) {
            $willChange["song_img_url"] = $updateData->imgUrl;
        }
        if(isset($updateData->songUrl)) {
            $willChange["song_url"] = $updateData->songUrl;
        }
        if(isset($updateData->plays)) {
            $willChange["song_plays"] = $updateData->plays;
        }
        
        if(empty($willChange)) {
            throw new SongException("Invalid Input", 400);
            exit;
        }

        $queryParams = "";

        foreach ($willChange as $key => $value) {
            $queryParams .= $key." = :".$key.", ";
        }

        $queryParams = rtrim($queryParams, ", ");

        $queryString = "UPDATE songs SET ".$queryParams." WHERE id = :id";

        $updateQuery = $this->con->prepare($queryString);
        $updateQuery->bindParam(":id", $this->id);
        foreach ($willChange as $key => &$value) {
            $updateQuery->bindParam(":".$key, $value, PDO::PARAM_STR);
        }

        if($updateQuery->execute()) {
            return $song;
        }
    }

    public function delete() {
        $query = $this->con->prepare("SELECT * FROM songs WHERE id = :id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new SongException("Song Not Found", 404);
            exit;
        }

        $deleteQuery = $this->con->prepare("DELETE FROM songs WHERE id = :id");
        $deleteQuery->bindParam(":id", $this->id);
        $deleteQuery->execute();
    }

    public static function upload($file) {
        $dir = '/heartbeats/assets/songs/';
        $fileName = basename($file["name"]); 
        $filePath = $_SERVER['DOCUMENT_ROOT'].$dir.basename($file["name"]); 
        $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if($file["size"] > 52428800) {
            throw new SongException("Too large file provided", 400);
        }

        if($fileType != "mp3") {
            throw new SongException("Only mp3 are allowed", 400);
        }

        if(!move_uploaded_file($file["tmp_name"], $filePath)) {
            throw new SongException("Something went wrong while uploading the song, Please try again", 500);
        }

        $httpOrHttps = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https":"http");
        $host = $_SERVER["HTTP_HOST"];
        $url = $dir.$fileName;
        return $songUrl = $httpOrHttps."://".$host.$url;
    }
}
?>