<?php


$host = '127.0.0.1';
$port = 4306;
$dbusername = 'root';
$dbpassword = 'root';
$dbname = 'users';

$conn = mysqli_connect($host, $dbusername, $dbpassword, $dbname, $port);

if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}
?>