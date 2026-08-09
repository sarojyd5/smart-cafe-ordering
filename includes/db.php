<?php

date_default_timezone_set('Asia/Kathmandu');

$host = "localhost";
$user = "root";
$password = "";
$database = "smart_cafe";

$conn = mysqli_connect(
    $host,
    $user,
    $password,
    $database
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}