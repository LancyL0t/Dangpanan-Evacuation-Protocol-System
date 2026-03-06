<?php
// Dangpanan/config/db.php
$host = 'localhost';
$db_name = 'dangpanan_db';
$username = 'root'; // Default for XAMPP
$password = '';     // Default for XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connected successfully"; // Uncomment for testing
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>