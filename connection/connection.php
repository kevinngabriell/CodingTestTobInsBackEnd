<?php

//dev env
$hostname = "localhost";
$database = "TobIns";
$username = "root";
$password = "password";

//prod env
// $hostname = "localhost";
// $database = "TobIns";
// $username = "root";
// $password = "password";

$connect = mysqli_connect($hostname, $username, $password, $database);

if (!$connect) {
    die("Connection error" . mysqli_connect_error());
}

?>