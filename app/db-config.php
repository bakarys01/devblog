<?php
$host = 'localhost';
$database = 'blog_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    echo "<div style='background:#ffebee;color:#c62828;padding:15px;border-radius:4px;margin:20px;'>";
    echo "<strong>Database Connection Error:</strong><br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Please ensure MariaDB service is running and database exists.";
    echo "</div>";
    exit;
}
?>