<?php
$server_name = "localhost";
$user_name = "root";
$password = "";
$database_name = "hscap";

$conn = mysqli_connect($server_name, $user_name, $password, $database_name);

if($conn->connect_error) {
    die("Connection Error: " . $conn->connect_error());
}
?>