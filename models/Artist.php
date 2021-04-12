<?php 
require_once("../controllers/config.php");
require_once("Song.php");
require_once("Album.php");


class ArtistException extends Exception {}

class Artist {
	private $con, $id, $name, $imgPath, $ablumIds, $plays;

    public function __construct($id) {
        $this->con = DB::connect();
        $this->id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function getAll() {
        $con = DB::connect();
        $query = $con->prepare("SELECT * FROM artists ORDER BY RAND()");
        $query->execute();

        $artistsData = array();
        while($sqlData = $query->fetch(PDO::FETCH_ASSOC)) {
            $artistData = array();
            $artistData["id"] = $sqlData["id"];
            $artistData["name"] = $sqlData["artist_name"];
            $artistData["imgUrl"] = self::fullPath($sqlData["artist_img_url"]);
            $artistData["plays"] = $sqlData["artist_plays"];
            $artistsData[] = $artistData;    
        }
        return $artistsData;
    }

    public function findById() {
        $query = $this->con->prepare("SELECT * FROM artists WHERE id = :id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new ArtistException("Artist Not Found", 404);
            exit;
        }

        $sqlData = $query->fetch(PDO::FETCH_ASSOC);

        $this->artistData = array();
        $this->artistData["id"] = $this->id;
        $this->artistData["name"] = $sqlData["artist_name"];
        $this->artistData["imgUrl"] = self::fullPath($sqlData["artist_img_url"]);
        $this->artistData["plays"] = $sqlData["artist_plays"];

        return $this->artistData;
    }


    public static function findByKeyword($txt) {
        $con = DB::connect();

        $keyword = filter_var($txt, FILTER_SANITIZE_STRING);
        $query = $con->prepare("SELECT * FROM artists WHERE artist_name LIKE CONCAT('%', :kw, '%')");
        $query->bindParam(":kw", $keyword);
        $query->execute();

        $searchResults = array();
        while($sqlData = $query->fetch(PDO::FETCH_ASSOC)) {
            $artistData = array();
            $artistData["type"] = "Artist";
            $artistData["id"] = $sqlData["id"];
            $artistData["name"] = $sqlData["artist_name"];
            $artistData["imgUrl"] = self::fullPath($sqlData["artist_img_url"]);
            $artistData["plays"] = $sqlData["artist_plays"];
            $searchResults[] = $artistData;
        }
        return $searchResults;
    }

    private static function fullPath($url) {
        $httpOrHttps = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https":"http");
        $host = $_SERVER["HTTP_HOST"];
        return $path = $httpOrHttps."://".$host.$url;
    }
    
    public static function create($artistData) {
        $con = DB::connect();
        
        if(!isset($artistData->name) || !isset($artistData->imgUrl)) {
            throw new ArtistException("Invalid Input", 400);
            exit;
        }
        
        $query = $con->prepare("INSERT INTO artists (`artist_name`, `artist_img_url`)
        VALUES (:name, :imgUrl)");

        if($query->execute([
            'name' => $artistData->name,
            'imgUrl' => $artistData->imgUrl,
        ])) {
            return $artistData;
        }
    }

    public function update($updateData) {
        $artist = $this->findById();
        $willChange = array();

        foreach ($updateData as $key => $value) {
            $artist[$key] = $value;
        }

        if(isset($updateData->name)) {
            $willChange["artist_name"] = $updateData->name;
        }
        if(isset($updateData->imgUrl)) {
            $willChange["artist_img_url"] = $updateData->imgUrl;
        }
        if(isset($updateData->album_ids)) {
            $willChange["album_ids"] = $updateData->albumIds;
        }
        if(isset($updateData->plays)) {
            $willChange["artist_plays"] = $updateData->plays;
        }
        
        if(empty($willChange)) {
            throw new ArtistException("Invalid Input", 400);
            return;
        }

        $queryParams = "";

        foreach ($willChange as $key => $value) {
            $queryParams .= $key." = :".$key.", ";
        }

        $queryParams = rtrim($queryParams, ", ");

        $queryString = "UPDATE artists SET ".$queryParams." WHERE id = :id";

        $updateQuery = $this->con->prepare($queryString);
        $updateQuery->bindParam(":id", $this->id);
        foreach ($willChange as $key => &$value) {
            $updateQuery->bindParam(":".$key, $value, PDO::PARAM_STR);
        }

        if($updateQuery->execute()) {
            return $artist;
        }
    }
    public function delete() {
        $query = $this->con->prepare("SELECT * FROM artists WHERE id = :id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new ArtistException("Artist Not Found", 404);
            exit;
        }

        $deleteQuery = $this->con->prepare("DELETE FROM artists WHERE id = :id");
        $deleteQuery->bindParam(":id", $this->id);
        $deleteQuery->execute();
    }

    public function getSongIds() {
        $query = $this->con->prepare("SELECT id FROM songs WHERE artist_id = :id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new ArtistException("This Artist Has No Songs", 404);
            exit;
        }

        return $artistSongIds = $query->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getSongs() {
        $artistSongs = array();

        foreach($this->getSongIds() as $id) {
            try {
                $song = new Song($id);
                $artistSongs[] = $song->findById();
            }
            catch(SongException $err) {
                $response = new Res(false, $err->getCode(), $err->getMessage());  
            }
        }

        return $artistSongs;
    }

    public function getAlbums() {
        $query = $this->con->prepare("SELECT id FROM albums WHERE artist_id = :id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new ArtistException("This Artist has no albums", 404);
            exit;
        }

        $artistAlbums = array();

        $albumSongsIds = $query->fetchAll(PDO::FETCH_COLUMN, 0);

        foreach($albumSongsIds as $id) {
            $album = new Album($id);
            $artistAlbums[] = $album->findById();
        }
        
        return $artistAlbums;
    }

    public function incPlays() {
        $query = $this->con->prepare("UPDATE artists SET artist_plays = artist_plays + 1 WHERE id = :id");
        $query->bindParam(":id", $this->id);
        return $query->execute();
    }
}
?>