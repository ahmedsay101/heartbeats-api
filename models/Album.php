<?php 
require_once("Song.php");


class AlbumException extends Exception {}

class Album {
	private $con, $id, $name, $artistId, $imgPath, $songIds, $year;

    public function __construct( $id) {
        $this->con = DB::connect();
        $this->id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
    }

    public function findById() {
        $query = $this->con->prepare("SELECT albums.id, 
                                    albums.album_name,
                                    albums.artist_id, 
                                    albums.album_img_url,
                                    albums.year,
                                    albums.plays,
                                    artists.artist_name 
                                    FROM albums 
                                    INNER JOIN artists ON albums.artist_id = artists.id
                                    WHERE albums.id = :id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new AlbumException("Album Not Found", 404);
            exit;
        }

        $sqlData = $query->fetch(PDO::FETCH_ASSOC);

        $this->albumData = array();
        $this->albumData["id"] = $this->id;
        $this->albumData["name"] = $sqlData["album_name"];
        $this->albumData["artistId"] = $sqlData["artist_id"];
        $this->albumData["artistName"] = $sqlData["artist_name"];
        $this->albumData["imgUrl"] = self::fullPath($sqlData["album_img_url"]);
        $this->albumData["songIds"] = $this->getSongIds();
        $this->albumData["year"] = $sqlData["year"];
        $this->albumData["plays"] = $sqlData["plays"];

        return $this->albumData;
    }

    private static function fullPath($url) {
        $httpOrHttps = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https":"http");
        $host = $_SERVER["HTTP_HOST"];
        return $path = "https://cors-everywhere-me.herokuapp.com/".$httpOrHttps."://".$host.$url;
    }

    public static function getRandom() {
        $con = DB::connect();
        $query = $con->prepare("SELECT albums.id, albums.album_name, albums.artist_id, artists.artist_name, albums.album_img_url, albums.year
                                FROM albums 
                                INNER JOIN artists ON albums.artist_id = artists.id
                                ORDER BY RAND() LIMIT 5");
        $query->execute();

        $albums = array();
        while($sqlData = $query->fetch(PDO::FETCH_ASSOC)) {
            $albumData = array();
            $albumData["id"] = $sqlData["id"];
            $albumData["name"] = $sqlData["album_name"];
            $albumData["artistId"] = $sqlData["artist_id"];
            $albumData["artistName"] = $sqlData["artist_name"];
            $albumData["imgUrl"] = self::fullPath($sqlData["album_img_url"]);
            $albumData["songIds"] = self::songIds($sqlData["id"]);
            $albumData["year"] = $sqlData["year"];
            $albums[] = $albumData;
        }
        return $albums;
    }

    public static function create($albumData) {
        $con = DB::connect();
        $songIds = "";

        if(!isset($albumData->name) || !isset($albumData->artistId) || !isset($albumData->year)) {
            throw new PlaylistException("Invalid Input", 400);
            exit;
        }
        if(!isset($albumData->imgUrl)) {
            $albumData->imgUrl = "heartbeats/assets/images/default-album.jpg";
        }
        if(isset($albumData->songIds)) {
            $songIds = implode(",", $albumData->songIds);
        }
        else {
            $albumData->songIds = $songIds;
        }

        $query = $con->prepare("INSERT INTO albums (`album_name`, `artist_id`, `album_img_url`, `year`)
        VALUES (:name, :artistId, :imgUrl, :year)");

        if($query->execute([
            'name' => $albumData->name,
            'artistId' => $albumData->artistId,
            'imgUrl' => $albumData->imgUrl,
            'year' => $albumData->year
        ])) {
            return $albumData;
        }
    }

    public function update($updateData) {
        $album = $this->findById();
        $willChange = array();

        foreach ($updateData as $key => $value) {
            $album[$key] = $value;
        }

        if(isset($updateData->name)) {
            $willChange["album_name"] = $updateData->name;
        }
        if(isset($updateData->userId)) {
            $willChange["artist_id"] = $updateData->artistId;
        }
        if(isset($updateData->imgUrl)) {
            $willChange["album_img_url"] = $updateData->imgUrl;
        }
        if(isset($updateData->songIds)) {
            $willChange["album_song_ids"] = implode(",", $updateData->songIds);
        }
        if(isset($updateData->date)) {
            $willChange["year"] = $updateData->year;
        }
        
        if(empty($willChange)) {
            throw new AlbumException("Invalid Input", 400);
            return;
        }

        $queryParams = "";

        foreach ($willChange as $key => $value) {
            $queryParams .= $key." = :".$key.", ";
        }

        $queryParams = rtrim($queryParams, ", ");

        $queryString = "UPDATE albums SET ".$queryParams." WHERE id = :id";

        $updateQuery = $this->con->prepare($queryString);
        $updateQuery->bindParam(":id", $this->id);
        foreach ($willChange as $key => &$value) {
            $updateQuery->bindParam(":".$key, $value, PDO::PARAM_STR);
        }

        if($updateQuery->execute()) {
            return $album;
        }
    }

    public function delete() {
        $query = $this->con->prepare("SELECT * FROM albums WHERE id = :id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new AlbumException("Album Not Found", 404);
            exit;
        }

        $deleteQuery = $this->con->prepare("DELETE FROM albums WHERE id = :id");
        $deleteQuery->bindParam(":id", $this->id);
        $deleteQuery->execute();
    }

    private static function songIds($id) {
        $con = DB::connect();
        $query = $con->prepare("SELECT id FROM songs WHERE album_id = :id");
        $query->bindParam(":id", $id);
        $query->execute();

        return $albumSongsIds = $query->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getSongIds() {
        $query = $this->con->prepare("SELECT id FROM songs WHERE album_id = :id");
        $query->bindParam(":id", $this->id);
        $query->execute();

        if($query->rowCount() === 0) {
            throw new AlbumException("This Album Has No Songs", 404);
            exit;
        }

        return $albumSongsIds = $query->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getSongs() {
        $albumSongs = array();

        foreach($this->getSongIds() as $id) {
            try {
                $song = new Song($id);
                $albumSongs[] = $song->findById();
            }
            catch(SongException $err) {
                $response = new Res(false, $err->getCode(), $err->getMessage());  
            }
        }

        return $albumSongs;
    }

    public function incPlays() {
        $query = $this->con->prepare("UPDATE albums SET plays = plays + 1 WHERE id = :id");
        $query->bindParam(":id", $this->id);
        return $query->execute();
    }
}

?>