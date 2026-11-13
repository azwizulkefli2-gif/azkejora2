<?php
// We pull database details from environment variables (securely used on Render)
$host = getenv('DB_HOST') ?: 'dpg-dbr5q0t6e07s73ak86pg-a';
$db   = getenv('DB_NAME') ?: 'azkejora2';
$user = getenv('DB_USER') ?: 'azkejora2_user';
$pass = getenv('DB_PASS') ?: 'iaWV8wGhAnXWEY1JycGWvVDrOeRjfRAt';
$port = getenv('DB_PORT') ?: '5432';

//$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    // Create the Database Connector using PDO
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "<h1>Success! Connected to MySQL Database smoothly 🚀</h1>";
} catch (\PDOException $e) {
    echo "<h1>Database Connection Failed: </h1>" . $e->getMessage();
}
?>
