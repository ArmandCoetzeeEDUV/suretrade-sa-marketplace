<?php
$host = 'sql308.infinityfree.com';         
$db_name = 'if0_42227923_suretrade'; 
$username = 'if0_42227923';
$password = 'PAssWORD123';

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    die("Database connection failed: " . $exception->getMessage());
}
?>
