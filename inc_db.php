<?php
// inc_db.php - Centralized Database Connection
$host = getenv('DB_HOST') ?: 'dpg-dbr5q0t6e07s73ak86pg-a';
$db   = getenv('DB_NAME') ?: 'azkejora2';
$user = getenv('DB_USER') ?: 'azkejora2_user';
$pass = getenv('DB_PASS') ?: 'iaWV8wGhAnXWEY1JycGWvVDrOeRjfRAt';
$port = getenv('DB_PORT') ?: '5432';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>
