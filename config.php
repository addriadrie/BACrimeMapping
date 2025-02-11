<?php
// config.php

$host = "localhost";
$dbname = "bantayalisto";
$username = "root";
$password = "";

try {
    $conn = mysqli_connect($host, $username, $password, $dbname);
    
    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    return $conn;
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
?>