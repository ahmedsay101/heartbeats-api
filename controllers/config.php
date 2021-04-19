<?php
require_once("../models/Res.php");

ob_start();
session_start();
date_default_timezone_set("Africa/Cairo");

class DB {
    public static $con;

    public static function connect() {
        self::$con = new PDO("mysql:dbname=heartbeats;host=localhost", "root", "");
        self::$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        return self::$con;
    } 
}

try {
    DB::connect();
}
catch (PDOException $e) {
    $response = new Res(false, 500, 'Something went wrong, Please try again later');
}

?>