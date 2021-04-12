<?php 
require_once("../controllers/config.php");
require_once("Song.php");

class PlaylistException extends Exception {}

class Playlist {
	private $con, $id;

    public function __construct($id) {
        $this->con = DB::connect();
        $this->id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
    }

    public function getUserId() {
        $query = $this->con->prepare("SELECT user_id FROM playlists WHERE id = :id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        return $query->fetchColumn();
    }

    public function findById() {
        $userId = $this->getUserId();
        $query = $this->con->prepare("SELECT * FROM playlists
                                        INNER JOIN users ON users.id = :uid
                                        WHERE playlists.id = :id");
        $query->bindParam(":id", $this->id);
        $query->bindParam(":uid", $userId);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new PlaylistException("Playlist Not Found", 404);
            exit;
        }

        $sqlData = $query->fetch(PDO::FETCH_ASSOC);

        $this->playlistData = array();
        $this->playlistData["id"] = $this->id;
        $this->playlistData["name"] = $sqlData["playlist_name"];
        $this->playlistData["userId"] = $userId;
        $this->playlistData["userName"] = $sqlData["first_name"] . " " . $sqlData["last_name"];
        $this->playlistData["songIds"] = $sqlData['playlist_song_ids'] == null ? null : explode(",", $sqlData["playlist_song_ids"]);
        $this->playlistData["imgUrl"] = $this->getPlaylistImg($this->playlistData["songIds"]);
        $this->playlistData["numOfSongs"] = count(explode(",", $sqlData["playlist_song_ids"]));

        $creationDate = strtotime($sqlData["creation_date"]);
        $day = date("d", $creationDate);
        $month = date("F", $creationDate);
        $year = date("Y", $creationDate);
        $formattedDate = $month." ".$day.", ".$year;

        $this->playlistData["date"] = $formattedDate;

        return $this->playlistData;
    }
    
    public static function getPublicPlaylists() {
        $con = DB::connect();
        $query = $con->prepare("SELECT * FROM public_playlists");
        $query->execute();

        $playlists = array();

        while($sqlData = $query->fetch(PDO::FETCH_ASSOC)) {
            $playlistData = array();
            $playlistData["id"] = $sqlData["id"];
            $playlistData["name"] = $sqlData["name"];
            $playlistData["imgUrl"] = $sqlData["img_url"];
            $playlistData["songIds"] = explode(",", $sqlData["song_ids"]);
            $playlistData["numOfSongs"] = count($playlistData["songIds"]);
            $songs = array();
            foreach($playlistData["songIds"] as $id) {
                $songData = array();
                $song = new Song($id);
                $songData = $song->findById();
                $songData["imgColor"] = self::getSongImgColor($id);
                $songs[] = $songData;
            }
            $playlistData["songs"] = $songs;
            $playlists[] = $playlistData;
        }
        return $playlists;
    }

    public function getPlaylistImg($songs) {
        if($songs === null) {
            return self::fullPath('/heartbeats/assets/images/playlist.svg');
        }
        else {
            $song = new Song($songs[0]);
            return $song->findById()["imgUrl"];
        }
    }

    private static function getSongImgColor($id) {
        $con = DB::connect();
        $query = $con->prepare("SELECT color FROM song_img_color WHERE song_id = :id");
        $query->bindParam(":id", $id);
        $query->execute();

        return $color = $query->fetchColumn(); 
    }

    public static function create($userId, $playlistData) {
        $con = DB::connect();

        if(!isset($playlistData->name)) {
            throw new PlaylistException("Invalid Input", 400);
            exit;
        }
        if(!isset($playlistData->imgUrl)) {
            $playlistData->imgUrl = self::fullPath("heartbeats/assets/images/default-playlist.jpg");
        }
        if(isset($playlistData->songIds)) {
            foreach($playlistData->songIds as $id) {
                $query = $con->prepare("SELECT id FROM songs WHERE id = :id");
                $query->bindParam(":id", $id);
                $query->execute();

                if($query->rowCount() === 0) {
                    throw new PlaylistException("Trying to add a song that doesn't exist", 400);
                    exit;
                }
            }
            $songIds = implode(",", $playlistData->songIds);
        }
        else {
            $playlistData->songIds = array();
        }

        $query = $con->prepare("INSERT INTO playlists (`playlist_name`, `playlist_img_url`, `user_id`, `creation_date`)
        VALUES (:name, :imgUrl, :userId, :date)");

        $query->execute([
            'name' => $playlistData->name,
            'imgUrl' => $playlistData->imgUrl,
            'userId' => $userId,
            'date' => date('Y/m/d h:i:s a', time())
        ]);

        return (new self($con->lastInsertId()))->findById();
    }

    private static function fullPath($url) {
        $httpOrHttps = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https":"http");
        $host = $_SERVER["HTTP_HOST"];
        return $path = $httpOrHttps."://".$host.$url;
    }

    public function update($updateData) {
        $playlist = $this->findById();
        $willChange = array();

        foreach ($updateData as $key => $value) {
            $playlist[$key] = $value;
        }

        if(isset($updateData->name)) {
            $willChange["playlist_name"] = $updateData->name;
        }
        if(isset($updateData->artistId)) {
            $willChange["playlist_img_url"] = $updateData->imgUrl;
        }
        if(isset($updateData->userId)) {
            $willChange["user_id"] = $updateData->userId;
        }
        if(isset($updateData->songIds)) {
            foreach($updateData->songIds as $id) {
                $query = $this->con->prepare("SELECT song_name FROM songs WHERE id = :id");
                $query->bindParam(":id", $id);
                $query->execute();

                if($query->rowCount() === 0) {
                    throw new PlaylistException("Trying to add a song that doesn't exist", 400);
                    exit;
                }
            }
            $willChange["playlist_song_ids"] = implode(",", $updateData->songIds);
        }
        if(isset($updateData->date)) {
            $willChange["creation_date"] = $updateData->date;
        }
        
        if(empty($willChange)) {
            throw new PlaylistException("Invalid Input", 400);
            return;
        }

        $queryParams = "";

        foreach ($willChange as $key => $value) {
            $queryParams .= $key." = :".$key.", ";
        }

        $queryParams = rtrim($queryParams, ", ");

        $queryString = "UPDATE playlists SET ".$queryParams." WHERE id = :id";

        $updateQuery = $this->con->prepare($queryString);
        $updateQuery->bindParam(":id", $this->id);
        foreach ($willChange as $key => &$value) {
            $updateQuery->bindParam(":".$key, $value, PDO::PARAM_STR);
        }
        $updateQuery->execute();

        return $this->findById();
    }

    public function delete() {
        $query = $this->con->prepare("SELECT playlist_name FROM playlists WHERE id = :id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new PlaylistException("Playlist Not Found", 404);
            exit;
        }

        $deleteQuery = $this->con->prepare("DELETE FROM playlists WHERE id = :id");
        $deleteQuery->bindParam(":id", $this->id);
        $deleteQuery->execute();
    }

    public function getSongIds() {
        $query = $this->con->prepare("SELECT playlist_song_ids FROM playlists WHERE id = :id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new PlaylistException("Playlist Not Found", 404);
            exit;
        }
        
        $ids = $query->fetchColumn();

        return $playlistSongIds = $ids == null ? false : explode(",", $ids);
    }

    public function getSongs() {
        $songIds = $this->getSongIds();
        if(!$songIds) {
            return array("songs" => null);     
        }
        $playlistSongs = array();
        
        foreach($songIds as $id) {
            try {
                $song = new Song($id);
                $playlistSongs[] = $song->findById();
            }
            catch(SongException $err) {
                $response = new Res(false, $err->getCode(), $err->getMessage());  
            }
        }

        return array("songs" => $playlistSongs);
    }
}

?>