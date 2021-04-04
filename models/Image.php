<?php
require_once("../controllers/config.php");

class ImageException extends Exception {}

class Image {
    private $con, $file, $fileType, $filePath, $dir, $url;

    public function __construct($file, $dir) {
        $this->con = DB::connect();
        $this->file = $file;
        $this->dir = $dir; 
        $this->fileName = basename($this->file["name"]); 
        $this->filePath = $_SERVER['DOCUMENT_ROOT'].$this->dir.basename($this->file["name"]); 
        $this->fileType = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));
        $this->newFileName = uniqid('', true).uniqid('', true).'.'.$this->fileType;
        $this->newPath = $_SERVER['DOCUMENT_ROOT'].$this->dir.$this->newFileName;
    }

    public function upload() {

        if(!getimagesize($this->file["tmp_name"])) {
            throw new ImageException("File provided was not an Image", 400);
        }

        if($this->file["size"] > 500000) {
            throw new ImageException("Too large file provided", 400);
        }

        if($this->fileType != "jpg" && $this->fileType != "png" && $this->fileType != "jpeg" && $this->fileType != "gif" ) {
            throw new ImageException("Only JPG, JPEG, PNG & GIF files are allowed", 400);
        }

        if(!move_uploaded_file($this->file["tmp_name"], $this->newPath)) {
            throw new ImageException("Something went wrong, Please try again", 500);
        }
        return $this->makeUrl();

    }

    private function makeUrl() {
        $httpOrHttps = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https":"http");
        $host = $_SERVER["HTTP_HOST"];
        $url = $this->dir.$this->newFileName;
        return $this->url = $httpOrHttps."://".$host.$url;
    }

}

?>