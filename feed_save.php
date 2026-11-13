<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user'])) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }

require_once 'inc_db.php';
require_once 'inc_feeds.php';

$userId = $_SESSION['user']['userid'] ?? '';
$id     = (int)($_POST['id'] ?? 0);
if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'Bad id']); exit; }

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) { echo json_encode(['ok'=>false,'error'=>'DB']); exit; }

$item = feed_find($pdo, $id);
if (!$item) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

if (feed_is_saved($pdo, $userId, $id)) {
    $s = $pdo->prepare("DELETE FROM saved_feeds WHERE user_id = ? AND feed_cache_id = ?");
    $s->execute([$userId, $id]);
    echo json_encode(['ok'=>true,'saved'=>false]);
} else {
    $s = $pdo->prepare("INSERT INTO saved_feeds (user_id, feed_type, title, url, feed_cache_id, image_url)
                        VALUES (?,?,?,?,?,?)
                        ON CONFLICT DO NOTHING");
    $s->execute([$userId, $item['feed_type'], $item['title'], $item['feed_url'], $id, $item['image_url']]);
    echo json_encode(['ok'=>true,'saved'=>true]);
}
