<?php
$host = "eu2.ultra-h.com";
$username = "server_13239";  
$password = "3vmlnreiwu";      
$database = "server_13239_jawa";

try {
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
